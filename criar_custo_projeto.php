<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

try {
    // Buscar projetos ativos
    $query_projetos = "SELECT id, nome, id_evento FROM projetos WHERE ativo = 1 ORDER BY nome";
    $resultado_projetos = $conexao->query($query_projetos);
    if (!$resultado_projetos) {
        throw new Exception("Erro ao buscar projetos: " . $conexao->error);
    }

    // Buscar informações do usuário logado
    $usuario_id = $_SESSION['usuario_id'];
    $query_usuario = "SELECT perfil, departamento, valor_hora FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($query_usuario);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $resultado_usuario = $stmt->get_result();
    $usuario = $resultado_usuario->fetch_assoc();

    $perfil_usuario = $usuario['perfil'];
    $departamento_usuario = $usuario['departamento'];
    $valor_hora_usuario = $usuario['valor_hora'];

    // Buscar aprovadores potenciais
    $query_aprovadores = "SELECT id, nome, email FROM usuarios 
                         WHERE status = 'Ativo' AND ativo = 1 
                         AND (perfil IN ('AprovadorN1', 'AprovadorN2')";
    
    if ($perfil_usuario != 'Administrador') {
        $query_aprovadores .= " AND (departamento = ? OR perfil IN ('AprovadorN2'))";
    }
    
    $query_aprovadores .= ") ORDER BY nome";
    
    $stmt = $conexao->prepare($query_aprovadores);
    if ($perfil_usuario != 'Administrador') {
        $stmt->bind_param("s", $departamento_usuario);
    }
    $stmt->execute();
    $resultado_aprovadores = $stmt->get_result();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            // Validações básicas
            $projeto_id = intval($_POST['projeto_id']);
            if ($projeto_id <= 0) {
                throw new Exception("Projeto inválido");
            }

            $tipo_custo = $conexao->real_escape_string($_POST['tipo_custo']);
            if (!in_array($tipo_custo, ['Horas', 'Diaria'])) {
                throw new Exception("Tipo de custo inválido");
            }

            $data_inicio = new DateTime($_POST['data_inicio']);
            $data_fim = new DateTime($_POST['data_fim']);

            if ($data_fim <= $data_inicio) {
                throw new Exception("A data final deve ser maior que a data inicial");
            }

            $tipo_origem = $conexao->real_escape_string($_POST['tipo_origem']);
            if (!in_array($tipo_origem, ['Previsto em Proposta', 'Venda Adicional', 'Custo Operacional'])) {
                throw new Exception("Tipo de origem inválido");
            }

            $justificativa = trim($_POST['justificativa']);


            if (empty($justificativa)) {
                throw new Exception("A justificativa é obrigatória");
            }

            $aprovador_id = intval($_POST['aprovador_id']);
            if ($aprovador_id <= 0) {
                throw new Exception("Aprovador inválido");
            }

            // Cálculos baseados no tipo de custo
            if ($tipo_custo === 'Horas') {
                // Calcula diferença em horas
                $diff = $data_inicio->diff($data_fim);
                $horas_trabalhadas = $diff->h + ($diff->days * 24) + ($diff->i / 60);
                $horas_trabalhadas = round($horas_trabalhadas, 2);
                $valor_total = round($horas_trabalhadas * $valor_hora_usuario, 2);
            } else {
                // Para diárias
                $diff = $data_inicio->diff($data_fim);
                $dias = $diff->days + 1; // Considera o dia inicial
                $horas_trabalhadas = 0; // Zero para diárias
                
                $valor_diaria = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_diaria']);
                $valor_diaria = floatval($valor_diaria);
                
                if ($valor_diaria <= 0) {
                    throw new Exception("Valor da diária inválido");
                }
                
                $valor_total = $valor_diaria * $dias;
            }

            $data_inicio_mysql = $data_inicio->format('Y-m-d H:i:s');
            $data_fim_mysql = $data_fim->format('Y-m-d H:i:s');

            $query = "INSERT INTO custos_projeto (
                projeto_id, usuario_id, tipo_custo, data_inicio, data_fim, 
                horas_trabalhadas, valor_hora, valor_total, tipo_origem, 
                justificativa, aprovador_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendente')";

            $stmt = $conexao->prepare($query);
            if (!$stmt) {
                throw new Exception("Erro na preparação da query: " . $conexao->error);
            }

            $stmt->bind_param("iisssdddssi", 
                $projeto_id, 
                $usuario_id,
                $tipo_custo,
                $data_inicio_mysql, 
                $data_fim_mysql, 
                $horas_trabalhadas, 
                $valor_hora_usuario, 
                $valor_total,
                $tipo_origem, 
                $justificativa, 
                $aprovador_id
            );

            if ($stmt->execute()) {
                $novo_custo_id = $conexao->insert_id;
                registrarLog($_SESSION['usuario_id'], "Criou custo de projeto ID $novo_custo_id");
                
                // Enviar e-mail
                require_once 'mail_helper.php';
                $dados_email = prepararDadosEmail($conexao, $novo_custo_id);
                if ($dados_email) {
                    enviarEmailCusto($dados_email);
                }
                
                $_SESSION['success_message'] = "Custo de projeto criado com sucesso!";
                header("Location: index.php?modulo=listar_custo_projetos");
                exit();
            } else {
                throw new Exception("Erro ao executar a query: " . $stmt->error);
            }

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erro ao criar custo: " . $e->getMessage();
            header("Location: index.php?modulo=criar_custo_projeto");
            exit();
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erro ao carregar dados: " . $e->getMessage();
    header("Location: index.php?modulo=listar_custo_projetos");
    exit();
}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Criar Custo de Projeto</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="index.php?modulo=criar_custo_projeto" method="post" class="needs-validation" novalidate>
                <input type="hidden" id="valor_hora_usuario" value="<?php echo $valor_hora_usuario; ?>">
                <input type="hidden" id="valor_total_hidden" name="valor_total" value="0">
                
                <div class="row g-3">
                    <!-- Projeto -->
                    <div class="col-md-12">
                        <label for="projeto_busca" class="form-label">Projeto</label>
                        <input type="text" class="form-control" id="projeto_busca" 
                            placeholder="Buscar projeto" required 
                            oninvalid="this.setCustomValidity('Por favor, selecione um projeto')"
                            oninput="this.setCustomValidity('')">
                        <input type="hidden" name="projeto_id" id="projeto_id" required>
                        <div class="invalid-feedback">Por favor, selecione um projeto.</div>
                    </div>

                    <!-- Tipo de Custo -->
                    <div class="col-md-6">
                        <label for="tipo_custo" class="form-label">Tipo de Custo</label>
                        <select class="form-select" id="tipo_custo" name="tipo_custo" required>
                            <option value="Horas">Horas</option>
                            <option value="Diaria">Diária</option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione o tipo de custo.</div>
                    </div>

                    <!-- Tipo de Origem -->
                    <div class="col-md-6">
                        <label for="tipo_origem" class="form-label">Tipo de Origem</label>
                        <select class="form-select" id="tipo_origem" name="tipo_origem" required>
                            <option value="">Selecione a origem...</option>
                            <option value="Previsto em Proposta">Previsto em Proposta</option>
                            <option value="Venda Adicional">Venda Adicional</option>
                            <option value="Custo Operacional">Custo Operacional</option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione o tipo de origem.</div>
                    </div>

                    <!-- Datas -->
                    <div class="col-md-6">
                        <label for="data_inicio" class="form-label">Data de Início</label>
                        <input type="datetime-local" class="form-control" id="data_inicio" 
                               name="data_inicio" required>
                        <div class="invalid-feedback">Por favor, selecione a data de início.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="data_fim" class="form-label">Data de Fim</label>
                        <input type="datetime-local" class="form-control" id="data_fim" 
                               name="data_fim" required>
                        <div class="invalid-feedback">Por favor, selecione a data de fim.</div>
                    </div>
                    
                    <!-- Valor da Diária -->
                    <div class="col-md-12" id="valor_diaria_container" style="display: none;">
                        <label for="valor_diaria" class="form-label">Valor da Diária</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="valor_diaria" name="valor_diaria"
                                   data-mask="#.##0,00" data-mask-reverse="true">
                        </div>
                        <div class="invalid-feedback">Por favor, informe o valor da diária.</div>
                    </div>

                    <!-- Justificativa -->
                    <div class="col-md-12">
                        <label for="justificativa" class="form-label">Justificativa</label>
                        <textarea class="form-control" id="justificativa" name="justificativa" 
                                  rows="3" required></textarea>
                        <div class="invalid-feedback">Por favor, forneça uma justificativa.</div>
                    </div>

                    <!-- Aprovador -->
                    <div class="col-md-12">
                        <label for="aprovador_id" class="form-label">Aprovador</label>
                        <select class="form-select" id="aprovador_id" name="aprovador_id" required>
                            <option value="">Selecione um aprovador...</option>
                            <?php while ($aprovador = $resultado_aprovadores->fetch_assoc()): ?>
                                <option value="<?php echo $aprovador['id']; ?>">
                                    <?php echo htmlspecialchars($aprovador['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione um aprovador.</div>
                    </div>

                    <!-- Preview dos cálculos -->
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Resumo do Cálculo</h6>
                                <div class="row">
                                    <!-- Seção de Horas -->
                                    <div class="col-md-4" id="horasSection">
                                        <p class="mb-1">Horas Trabalhadas:</p>
                                        <h5 id="preview_horas">0h</h5>
                                    </div>
                                    
                                    <!-- Seção de Dias (para Diárias) -->
                                    <div class="col-md-4" id="diasSection" style="display:none;">
                                        <p class="mb-1">Quantidade de Dias:</p>
                                        <h5 id="preview_dias">0 dia(s)</h5>
                                    </div>
                                    
                                    <!-- Valor por Tipo -->
                                    <div class="col-md-4">
                                        <p class="mb-1">
                                            <span id="valorPorTipoLabel">Valor por Hora</span>:
                                        </p>
                                        <h5 id="valorPorTipo">R$ <?php echo number_format($valor_hora_usuario, 2, ',', '.'); ?></h5>
                                    </div>
                                    
                                    <!-- Valor Total -->
                                    <div class="col-md-4">
                                        <p class="mb-1">Valor Total:</p>
                                        <h5 id="preview_valor">R$ 0,00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php?modulo=listar_custo_projetos" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery não carregado');
    } else {
        // Seu código de autocomplete aqui
        $(document).ready(function() {
            $('#projeto_busca').autocomplete({
                source: 'buscar_projetos.php',
                minLength: 2,
                select: function(event, ui) {
                    $('#projeto_id').val(ui.item.id);
                    $('#projeto_busca').val(ui.item.label);
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        // Se não selecionou um item da lista
                        $('#projeto_busca').val('');
                        $('#projeto_id').val('');
                        $('#projeto_busca')[0].setCustomValidity('Por favor, selecione um projeto da lista');
                        $('#projeto_busca')[0].reportValidity();
                    } else {
                        $('#projeto_busca')[0].setCustomValidity('');
                    }
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append("<div>" + item.label + "</div>")
                    .appendTo(ul);
            };
        });
    }
});



</script>