<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

try {
    if (!isset($_GET['id'])) {
        throw new Exception("ID do custo não especificado");
    }

    $id = intval($_GET['id']);
    
    // Buscar dados do custo do projeto com email do solicitante
    $query = "SELECT cp.*, u.nome as usuario_nome, u.email as usuario_email 
              FROM custos_projeto cp 
              LEFT JOIN usuarios u ON cp.usuario_id = u.id 
              WHERE cp.id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if (!$resultado->num_rows) {
        throw new Exception("Custo de projeto não encontrado");
    }
    
    $custo = $resultado->fetch_assoc();
    $custoAprovado = $custo['status'] === 'Aprovado';

    // Verificar se o usuário tem permissão para editar
    $podeAprovar = in_array($_SESSION['perfil'], ['Administrador', 'AprovadorN1', 'AprovadorN2']);
    if ($custo['usuario_id'] != $_SESSION['usuario_id'] && !$podeAprovar) {
        throw new Exception("Você não tem permissão para editar este custo");
    }

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
    $valor_hora_usuario = $custo['valor_hora']; // Usa o valor da hora do registro

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
            // Verificar se o custo está aprovado
            if ($custoAprovado) {
                throw new Exception("Não é possível editar um custo já aprovado");
            }

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

            $tipo_origem = $conexao->real_escape_string($_POST['tipo_origem']);
            $data_inicio_mysql = $data_inicio->format('Y-m-d H:i:s');
            $data_fim_mysql = $data_fim->format('Y-m-d H:i:s');

            // Se for aprovador, pode alterar o status e comentário
            if ($podeAprovar) {
                $status = $conexao->real_escape_string($_POST['status']);
                $comentario_aprovador = trim($conexao->real_escape_string($_POST['comentario_aprovador']));
                
                // Validar status
                if (!in_array($status, ['Pendente', 'Aprovado', 'Reprovado'])) {
                    throw new Exception("Status inválido");
                }
                
                // Se estiver alterando o status, exigir comentário
                if ($status != $custo['status'] && empty($comentario_aprovador)) {
                    throw new Exception("É necessário fornecer um comentário ao alterar o status");
                }

                $query = "UPDATE custos_projeto SET 
                         projeto_id = ?, tipo_custo = ?, data_inicio = ?, data_fim = ?, 
                         horas_trabalhadas = ?, valor_total = ?, tipo_origem = ?,
                         justificativa = ?, aprovador_id = ?, status = ?, 
                         comentario_aprovador = ? WHERE id = ?";

                $stmt = $conexao->prepare($query);
                if (!$stmt) {
                    throw new Exception("Erro na preparação da query: " . $conexao->error);
                }

                $stmt->bind_param("isssddsssssi", 
                    $projeto_id,
                    $tipo_custo, 
                    $data_inicio_mysql, 
                    $data_fim_mysql,
                    $horas_trabalhadas, 
                    $valor_total,
                    $tipo_origem, 
                    $justificativa, 
                    $aprovador_id, 
                    $status, 
                    $comentario_aprovador,
                    $id
                );
            } else {
                $query = "UPDATE custos_projeto SET 
                         projeto_id = ?, tipo_custo = ?, data_inicio = ?, data_fim = ?, 
                         horas_trabalhadas = ?, valor_total = ?, tipo_origem = ?,
                         justificativa = ?, aprovador_id = ? WHERE id = ?";
                         
                $stmt = $conexao->prepare($query);
                if (!$stmt) {
                    throw new Exception("Erro na preparação da query: " . $conexao->error);
                }

                $stmt->bind_param("isssddsssi", 
                    $projeto_id,
                    $tipo_custo, 
                    $data_inicio_mysql, 
                    $data_fim_mysql,
                    $horas_trabalhadas, 
                    $valor_total,
                    $tipo_origem, 
                    $justificativa, 
                    $aprovador_id, 
                    $id
                );
            }

            if ($stmt->execute()) {
                registrarLog($_SESSION['usuario_id'], "Atualizou custo de projeto ID $id");
                
                // Enviar e-mail
                require_once 'mail_helper.php';
                $dados_email = prepararDadosEmail($conexao, $id);
                if ($dados_email) {
                    enviarEmailCusto($dados_email);
                }
                
                $_SESSION['success_message'] = "Custo de projeto atualizado com sucesso!";
                header("Location: index.php?modulo=listar_custo_projetos");
                exit();
            } else {
                throw new Exception("Erro ao atualizar o custo: " . $stmt->error);
            }

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erro ao atualizar custo de projeto: " . $e->getMessage();
            header("Location: index.php?modulo=editar_custo_projeto&id=" . $id);
            exit();
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: index.php?modulo=listar_custo_projetos");
    exit();
}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Editar Custo de Projeto</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($custoAprovado): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Este custo já foi aprovado e não pode ser editado.
                </div>
            <?php endif; ?>

            <form action="index.php?modulo=editar_custo_projeto&id=<?php echo $id; ?>" method="post" class="needs-validation" novalidate>
                <input type="hidden" id="valor_hora_usuario" value="<?php echo $valor_hora_usuario; ?>">
                <input type="hidden" id="valor_total_hidden" name="valor_total" value="<?php echo $custo['valor_total']; ?>">
                
                <div class="row g-3">
                    <!-- Solicitante -->
                    <?php if(isAdmin()): ?>
                        <div class="col-md-12">
                            <div class="form-control-plaintext">
                                Solicitante: <?php echo htmlspecialchars($custo['usuario_nome']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Projeto -->
                    <div class="col-md-12">
                        <label for="projeto_busca" class="form-label">Projeto</label>
                        <input type="text" class="form-control" id="projeto_busca" 
                            placeholder="Buscar projeto" required 
                            value="<?php 
                                $query_projeto = "SELECT nome, id_evento FROM projetos WHERE id = ?";
                                $stmt_projeto = $conexao->prepare($query_projeto);
                                $stmt_projeto->bind_param("i", $custo['projeto_id']);
                                $stmt_projeto->execute();
                                $resultado_projeto = $stmt_projeto->get_result();
                                $projeto = $resultado_projeto->fetch_assoc();

                                echo htmlspecialchars(
                                    $projeto['nome'] . 
                                    " (ID Evento: {$projeto['id_evento']})"
                                ); 
                            ?>"
                            oninvalid="this.setCustomValidity('Por favor, selecione um projeto')"
                            oninput="this.setCustomValidity('')">
                        <input type="hidden" name="projeto_id" id="projeto_id" 
                            value="<?php echo $custo['projeto_id']; ?>" required>
                        <div class="invalid-feedback">Por favor, selecione um projeto.</div>
                    </div>

                                        <!-- Tipo de Custo -->
                                        <div class="col-md-6">
                        <label for="tipo_custo" class="form-label">Tipo de Custo</label>
                        <select class="form-select" id="tipo_custo" name="tipo_custo" 
                                required <?php echo $custoAprovado ? 'disabled' : ''; ?>>
                            <option value="Horas" <?php echo $custo['tipo_custo'] == 'Horas' ? 'selected' : ''; ?>>
                                Horas
                            </option>
                            <option value="Diaria" <?php echo $custo['tipo_custo'] == 'Diaria' ? 'selected' : ''; ?>>
                                Diária
                            </option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione o tipo de custo.</div>
                    </div>

                    <!-- Tipo de Origem -->
                    <div class="col-md-6">
                        <label for="tipo_origem" class="form-label">Tipo de Origem</label>
                        <select class="form-select" id="tipo_origem" name="tipo_origem" 
                                required <?php echo $custoAprovado ? 'disabled' : ''; ?>>
                            <option value="Previsto em Proposta" 
                                    <?php echo $custo['tipo_origem'] == 'Previsto em Proposta' ? 'selected' : ''; ?>>
                                Previsto em Proposta
                            </option>
                            <option value="Venda Adicional" 
                                    <?php echo $custo['tipo_origem'] == 'Venda Adicional' ? 'selected' : ''; ?>>
                                Venda Adicional
                            </option>
                            <option value="Custo Operacional" 
                                    <?php echo $custo['tipo_origem'] == 'Custo Operacional' ? 'selected' : ''; ?>>
                                Custo Operacional
                            </option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione o tipo de origem.</div>
                    </div>

                    <!-- Datas -->
                    <div class="col-md-6">
                        <label for="data_inicio" class="form-label">Data de Início</label>
                        <input type="datetime-local" class="form-control" id="data_inicio" 
                               name="data_inicio" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($custo['data_inicio'])); ?>" 
                               required <?php echo $custoAprovado ? 'disabled' : ''; ?>>
                        <div class="invalid-feedback">Por favor, selecione a data de início.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="data_fim" class="form-label">Data de Fim</label>
                        <input type="datetime-local" class="form-control" id="data_fim" 
                               name="data_fim" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($custo['data_fim'])); ?>" 
                               required <?php echo $custoAprovado ? 'disabled' : ''; ?>>
                        <div class="invalid-feedback">Por favor, selecione a data de fim.</div>
                    </div>

                    <!-- Valor da Diária -->
                    <div class="col-md-12" id="valor_diaria_container" style="display: <?php echo $custo['tipo_custo'] == 'Diaria' ? 'block' : 'none'; ?>;">
                        <label for="valor_diaria" class="form-label">Valor da Diária</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="valor_diaria" name="valor_diaria"
                                   value="<?php echo $custo['tipo_custo'] == 'Diaria' ? number_format($custo['valor_total'] / max(1, ceil((strtotime($custo['data_fim']) - strtotime($custo['data_inicio'])) / (60*60*24))), 2, ',', '.') : ''; ?>"
                                   data-mask="#.##0,00" data-mask-reverse="true"
                                   <?php echo $custoAprovado ? 'disabled' : ''; ?>>
                        </div>
                        <div class="invalid-feedback">Por favor, informe o valor da diária.</div>
                    </div>

                    <!-- Justificativa -->
                    <div class="col-md-12">
                        <label for="justificativa" class="form-label">Justificativa</label>
                        <textarea class="form-control" id="justificativa" name="justificativa" rows="3" required><?php echo htmlspecialchars($custo['justificativa']); ?></textarea>
                        <div class="invalid-feedback">Por favor, forneça uma justificativa.</div>
                    </div>

                    <!-- Aprovador -->
                    <div class="col-md-6">
                        <label for="aprovador_id" class="form-label">Aprovador</label>
                        <select class="form-select" id="aprovador_id" name="aprovador_id" 
                                required <?php echo $custoAprovado ? 'disabled' : ''; ?>>
                            <option value="">Selecione um aprovador...</option>
                            <?php while ($aprovador = $resultado_aprovadores->fetch_assoc()): ?>
                                <option value="<?php echo $aprovador['id']; ?>" 
                                        <?php echo $aprovador['id'] == $custo['aprovador_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($aprovador['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione um aprovador.</div>
                    </div>

                    <!-- Status e Comentário - Apenas para aprovadores -->
                    <?php if ($podeAprovar): ?>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required 
                                <?php echo $custoAprovado ? 'disabled' : ''; ?> 
                                data-status-atual="<?php echo htmlspecialchars($custo['status']); ?>">
                            <option value="Pendente" <?php echo $custo['status'] == 'Pendente' ? 'selected' : ''; ?>>
                                Pendente
                            </option>
                            <option value="Aprovado" <?php echo $custo['status'] == 'Aprovado' ? 'selected' : ''; ?>>
                                Aprovado
                            </option>
                            <option value="Reprovado" <?php echo $custo['status'] == 'Reprovado' ? 'selected' : ''; ?>>
                                Reprovado
                            </option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione o status.</div>
                    </div>
                    <div class="col-md-12">
                        <label for="comentario_aprovador" class="form-label">Comentário do Aprovador</label>
                        <textarea class="form-control" id="comentario_aprovador" name="comentario_aprovador" 
                                rows="3" <?php echo $custoAprovado ? 'disabled' : ''; ?>><?php echo htmlspecialchars($custo['comentario_aprovador']); ?></textarea>
                        <div class="invalid-feedback">Por favor, forneça um comentário.</div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <div class="form-control-plaintext">
                            <span class="badge bg-<?php echo $custo['status'] == 'Aprovado' ? 'success' : 
                                                        ($custo['status'] == 'Reprovado' ? 'danger' : 'warning'); ?>">
                                <?php echo $custo['status']; ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($custo['comentario_aprovador'])): ?>
                    <div class="col-md-12">
                        <label class="form-label">Comentário do Aprovador</label>
                        <div class="form-control-plaintext">
                            <?php echo nl2br(htmlspecialchars($custo['comentario_aprovador'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Preview dos cálculos -->
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Resumo do Cálculo</h6>
                                <div class="row">
                                    <!-- Seção de Horas -->
                                    <div class="col-md-4" id="horasSection" 
                                         style="display: <?php echo $custo['tipo_custo'] == 'Horas' ? 'block' : 'none'; ?>;">
                                        <p class="mb-1">Horas Trabalhadas:</p>
                                        <h5 id="preview_horas">
                                            <?php 
                                                // Converte o decimal para formato de hora
                                                $horas = floor($custo['horas_trabalhadas']);
                                                $minutos = round(($custo['horas_trabalhadas'] - $horas) * 60);
                                                echo sprintf('%d:%02d', $horas, $minutos) . 'h';
                                            ?>
                                        </h5>
                                    </div>
                                    
                                    <!-- Seção de Dias (para Diárias) -->
                                    <div class="col-md-4" id="diasSection" 
                                         style="display: <?php echo $custo['tipo_custo'] == 'Diaria' ? 'block' : 'none'; ?>;">
                                        <p class="mb-1">Quantidade de Dias:</p>
                                        <h5 id="preview_dias"><?php 
                                            $dias = ceil((strtotime($custo['data_fim']) - strtotime($custo['data_inicio'])) / (60*60*24));
                                            echo $dias; ?> dia(s)</h5>
                                    </div>
                                    
                                    <!-- Valor por Tipo -->
                                    <div class="col-md-4">
                                        <p class="mb-1">
                                            <span id="valorPorTipoLabel">
                                                <?php echo $custo['tipo_custo'] == 'Diaria' ? 'Valor da Diária' : 'Valor por Hora'; ?>:
                                            </span>
                                        </p>
                                        <h5 id="valorPorTipo">R$ <?php 
                                            if ($custo['tipo_custo'] == 'Diaria') {
                                                $valorDiaria = $custo['valor_total'] / max(1, $dias);
                                                echo number_format($valorDiaria, 2, ',', '.');
                                            } else {
                                                echo number_format($valor_hora_usuario, 2, ',', '.');
                                            }
                                        ?></h5>
                                    </div>
                                    
                                    <!-- Valor Total -->
                                    <div class="col-md-4">
                                        <p class="mb-1">Valor Total:</p>
                                        <h5 id="preview_valor">R$ <?php echo number_format($custo['valor_total'], 2, ',', '.'); ?></h5>
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
                    <?php if (!$custoAprovado): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Salvar Alterações
                        </button>
                    <?php endif; ?>
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