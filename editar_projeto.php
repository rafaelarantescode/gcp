<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

// Buscar lista de clientes
$query_clientes = "SELECT id, nome FROM clientes WHERE status = 'Ativo' AND ativo = 1 ORDER BY nome";
$resultado_clientes = $conexao->query($query_clientes);

// Verificar se a consulta foi bem-sucedida
if (!$resultado_clientes) {
    die("Erro ao buscar clientes: " . $conexao->error);
}

// Armazenar os clientes em um array
$clientes = array();
while ($cliente = $resultado_clientes->fetch_assoc()) {
    $clientes[] = $cliente;
}

// Estados brasileiros
$estados = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 
    'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará', 
    'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 
    'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 
    'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais', 
    'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 
    'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 
    'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul', 
    'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 
    'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
];

function uploadArquivos($conexao, $projeto_id, $arquivos) {
    $upload_dir = 'uploads/projetos/' . $projeto_id . '/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['xls', 'xlsx', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'txt', 'csv'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    $uploaded_files = [];

    foreach ($arquivos['name'] as $key => $nome_arquivo) {
        if ($arquivos['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmp_name = $arquivos['tmp_name'][$key];
        $tamanho = $arquivos['size'][$key];
        $tipo = $arquivos['type'][$key];
        $ext = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_types)) {
            throw new Exception("Tipo de arquivo não permitido: $nome_arquivo");
        }

        if ($tamanho > $max_file_size) {
            throw new Exception("Arquivo muito grande: $nome_arquivo (máximo 5MB)");
        }

        $novo_nome = uniqid() . '.' . $ext;
        $caminho_destino = $upload_dir . $novo_nome;

        if (move_uploaded_file($tmp_name, $caminho_destino)) {
            $query = "INSERT INTO projeto_arquivos 
                      (projeto_id, nome_arquivo, caminho_arquivo, tipo_arquivo, tamanho_arquivo, usuario_id) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conexao->prepare($query);
            $stmt->bind_param("isssii", 
                $projeto_id, 
                $nome_arquivo, 
                $caminho_destino, 
                $tipo, 
                $tamanho, 
                $_SESSION['usuario_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao salvar informações do arquivo: " . $stmt->error);
            }

            $uploaded_files[] = $caminho_destino;
        }
    }

    return $uploaded_files;
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception("ID do projeto não especificado");
    }

    $id = intval($_GET['id']);

    // Buscar dados do projeto
    $query = "SELECT p.* FROM projetos p WHERE p.id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if (!$resultado->num_rows) {
        throw new Exception("Projeto não encontrado");
    }
    
    $projeto = $resultado->fetch_assoc();

    // Buscar lista de usuários
    $query_usuarios = "SELECT id, nome, email 
                      FROM usuarios 
                      WHERE status = 'Ativo' 
                      ORDER BY nome";
    $resultado_usuarios = $conexao->query($query_usuarios);
    
    if (!$resultado_usuarios) {
        throw new Exception("Erro ao buscar usuários: " . $conexao->error);
    }

    $usuarios = array();
    while ($usuario = $resultado_usuarios->fetch_assoc()) {
        $usuarios[] = $usuario;
    }

    // Buscar arquivos do projeto
    $query_arquivos = "SELECT * FROM projeto_arquivos 
                      WHERE projeto_id = ?
                      ORDER BY id DESC";
    $stmt = $conexao->prepare($query_arquivos);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $arquivos = $stmt->get_result();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            // Processar dados do POST...
            $nome = trim($_POST['nome']);
            $id_evento = trim($_POST['id_evento']);
            $descricao = trim($_POST['descricao']);
            $responsavel_comercial = intval($_POST['responsavel_comercial']);
            $responsavel_atendimento = !empty($_POST['responsavel_atendimento']) ? intval($_POST['responsavel_atendimento']) : null;
            $responsavel_tecnico = !empty($_POST['responsavel_tecnico']) ? intval($_POST['responsavel_tecnico']) : null;
            $valor_total = str_replace(['.', ','], ['', '.'], $_POST['valor_total']);
            $valor_total = floatval($valor_total);
            $data_inicio_atendimento = date('Y-m-d H:i:s', strtotime($_POST['data_inicio_atendimento']));
            $data_fim_atendimento = date('Y-m-d H:i:s', strtotime($_POST['data_fim_atendimento']));
            $data_inicio_evento = date('Y-m-d H:i:s', strtotime($_POST['data_inicio_evento']));
            $data_fim_evento = date('Y-m-d H:i:s', strtotime($_POST['data_fim_evento']));
            $local = trim($_POST['local']);
            $estado = trim($_POST['estado']);
            $cidade = trim($_POST['cidade']);
            $contratante_id = !empty($_POST['contratante_id']) ? intval($_POST['contratante_id']) : null;
            $cliente_final_id = !empty($_POST['cliente_final_id']) ? intval($_POST['cliente_final_id']) : null;

            // Validações...
            if (empty($nome)) {
                throw new Exception("Nome do projeto é obrigatório");
            }

            if ($responsavel_comercial <= 0) {
                throw new Exception("Responsável comercial deve ser selecionado");
            }

            if (strtotime($data_fim_atendimento) <= strtotime($data_inicio_atendimento)) {
                throw new Exception("A data final de atendimento deve ser maior que a data inicial");
            }

            if (strtotime($data_fim_evento) <= strtotime($data_inicio_evento)) {
                throw new Exception("A data final do evento deve ser maior que a data inicial");
            }

            $conexao->begin_transaction();

            // Atualizar projeto
            $query = "UPDATE projetos SET 
                nome = ?, id_evento = ?, descricao = ?, 
                responsavel_comercial = ?, responsavel_atendimento = ?, 
                responsavel_tecnico = ?, valor_total = ?,
                data_inicio = ?, data_fim = ?,
                data_inicio_evento = ?, data_fim_evento = ?,
                local = ?, estado = ?, cidade = ?, contratante_id = ?, cliente_final_id = ? 
                WHERE id = ?";

            $stmt = $conexao->prepare($query);
            $stmt->bind_param(
                "sssiiidsssssssiii",
                $nome,
                $id_evento,
                $descricao,
                $responsavel_comercial,
                $responsavel_atendimento,
                $responsavel_tecnico,
                $valor_total,
                $data_inicio_atendimento,
                $data_fim_atendimento,
                $data_inicio_evento,
                $data_fim_evento,
                $local,
                $estado,
                $cidade,
                $contratante_id,
                $cliente_final_id,
                $id
            );

            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar projeto: " . $stmt->error);
            }

            // Upload de novos arquivos
            if (!empty($_FILES['arquivos']['name'][0])) {
                $arquivos_upload = uploadArquivos($conexao, $id, $_FILES['arquivos']);
            }

            $conexao->commit();
            registrarLog($_SESSION['usuario_id'], "Atualizou o projeto ID $id");
            $_SESSION['success_message'] = "Projeto atualizado com sucesso!";
            header("Location: index.php?modulo=listar_projetos");
            exit();

        } catch (Exception $e) {
            if ($conexao->errno == 0) {
                $conexao->rollback();
            }
            $_SESSION['error_message'] = $e->getMessage();
            header("Location: index.php?modulo=editar_projeto&id=" . $id);
            exit();
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: index.php?modulo=listar_projetos");
    exit();
}
?>

<!-- HTML do formulário permanece o mesmo... -->
<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Editar Projeto</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="index.php?modulo=editar_projeto&id=<?php echo $id; ?>" method="post" 
                  class="needs-validation" novalidate enctype="multipart/form-data">
                <div class="row g-3">
                    <!-- Nome e ID do Evento -->
                    <div class="col-md-8">
                        <label for="nome" class="form-label">Nome do Projeto</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($projeto['nome']); ?>" required>
                        <div class="invalid-feedback">Por favor, insira o nome do projeto.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="id_evento" class="form-label">ID do Evento</label>
                        <input type="number" class="form-control" id="id_evento" name="id_evento" 
                               value="<?php echo htmlspecialchars($projeto['id_evento']); ?>">
                        <div class="invalid-feedback">Por favor, insira o ID do evento.</div>
                    </div>

                    <!-- Clientes -->
                    <div class="col-md-6">
                        <label for="contratante_id" class="form-label">Contratante</label>
                        <select class="form-select" id="contratante_id" name="contratante_id">
                            <option value="">Selecione um contratante...</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>"
                                        <?php echo ($cliente['id'] == $projeto['contratante_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="cliente_final_id" class="form-label">Cliente Final</label>
                        <select class="form-select" id="cliente_final_id" name="cliente_final_id">
                            <option value="">Selecione o cliente final...</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>"
                                        <?php echo ($cliente['id'] == $projeto['cliente_final_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Descrição -->
                    <div class="col-md-12">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" 
                                  rows="3" required><?php echo htmlspecialchars($projeto['descricao']); ?></textarea>
                        <div class="invalid-feedback">Por favor, insira a descrição do projeto.</div>
                    </div>

                    <!-- Responsáveis -->
                    <div class="col-md-4">
                        <label for="responsavel_comercial" class="form-label">Responsável Comercial</label>
                        <select class="form-select" id="responsavel_comercial" name="responsavel_comercial" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>" 
                                        <?php echo ($usuario['id'] == $projeto['responsavel_comercial']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione o responsável comercial.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="responsavel_atendimento" class="form-label">Responsável Atendimento</label>
                        <select class="form-select" id="responsavel_atendimento" name="responsavel_atendimento">
                            <option value="">Selecione...</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>" 
                                        <?php echo ($usuario['id'] == $projeto['responsavel_atendimento']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="responsavel_tecnico" class="form-label">Responsável Técnico</label>
                        <select class="form-select" id="responsavel_tecnico" name="responsavel_tecnico">
                            <option value="">Selecione...</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>" 
                                        <?php echo ($usuario['id'] == $projeto['responsavel_tecnico']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Datas  -->
                <div class="col-12 p-3 border rounded mb-3">
                    <div class="row align-items-end">
                        <!-- Datas de Atendimento -->
                        <div class="col-md-3">
                            <label for="data_inicio_atendimento" class="form-label">Data de Início do Atendimento</label>
                            <input type="datetime-local" class="form-control" id="data_inicio_atendimento" 
                                name="data_inicio_atendimento" 
                                value="<?php echo date('Y-m-d\TH:i', strtotime($projeto['data_inicio'])); ?>" 
                                required>
                        </div>

                        <div class="col-md-3">
                            <label for="data_fim_atendimento" class="form-label">Data de Fim do Atendimento</label>
                            <input type="datetime-local" class="form-control" id="data_fim_atendimento" 
                                name="data_fim_atendimento" 
                                value="<?php echo date('Y-m-d\TH:i', strtotime($projeto['data_fim'])); ?>" 
                                required>
                        </div>

                        <!-- Botão para copiar datas -->
                        <div class="col-md-1"> <!-- Reduzido para col-md-1 -->
                        <label for="data_fim_atendimento" class="form-label">Copiar</label>
                            <button type="button" class="btn btn-secondary" id="copiarDatas">
                                <i class="fas fa-copy me-1"></i>
                            </button>
                        </div>

                        <!-- Datas do Evento -->
                        <div class="col-md-3">
                            <label for="data_inicio_evento" class="form-label">Data de Início do Evento</label>
                            <input type="datetime-local" class="form-control" id="data_inicio_evento" 
                                name="data_inicio_evento" 
                                value="<?php echo date('Y-m-d\TH:i', strtotime($projeto['data_inicio_evento'])); ?>" 
                                required>
                        </div>

                        <div class="col-md-2"> <!-- Ajustado para col-md-2 -->
                            <label for="data_fim_evento" class="form-label">Data de Fim do Evento</label>
                            <input type="datetime-local" class="form-control" id="data_fim_evento" 
                                name="data_fim_evento" 
                                value="<?php echo date('Y-m-d\TH:i', strtotime($projeto['data_fim_evento'])); ?>" 
                                required>
                        </div>
                    </div>
                </div>
                    <!-- Estado e Cidade -->
                    <div class="col-md-4">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Selecione o Estado</option>
                            <?php foreach($estados as $sigla => $nome): ?>
                                <option value="<?php echo $sigla; ?>" 
                                        <?php echo ($projeto['estado'] == $sigla) ? 'selected' : ''; ?>>
                                    <?php echo $nome; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade" 
                               value="<?php echo htmlspecialchars($projeto['cidade']); ?>" required>
                    </div>

                    <!-- Valor -->
                    <div class="col-md-4">
                        <label for="valor_total" class="form-label">Valor Total</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="valor_total" name="valor_total" 
                                   value="<?php echo number_format($projeto['valor_total'], 2, ',', '.'); ?>" required>
                            <div class="invalid-feedback">Por favor, insira o valor total.</div>
                        </div>
                    </div>

                    <!-- Local -->
                    <div class="col-md-8">
                        <label for="local" class="form-label">Local</label>
                        <input type="text" class="form-control" id="local" name="local" 
                               value="<?php echo htmlspecialchars($projeto['local']); ?>" required>
                        <div class="invalid-feedback">Por favor, insira o local do projeto.</div>
                    </div>

                    <!-- Arquivos Existentes -->
                    <?php if ($arquivos && $arquivos->num_rows > 0): ?>
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Arquivos Anexados</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Tipo</th>
                                                <th>Tamanho</th>
                                                <th>Data Upload</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($arquivo = $arquivos->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($arquivo['nome_arquivo']); ?></td>
                                                <td><?php echo htmlspecialchars($arquivo['tipo_arquivo']); ?></td>
                                                <td><?php echo number_format($arquivo['tamanho_arquivo'] / 1024, 2) . ' KB'; ?></td>
                                                <td><?php echo date('d/m/Y H:i'); ?></td>
                                                <td>
                                                    <a href="<?php echo $arquivo['caminho_arquivo']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       target="_blank">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Upload de Novos Arquivos -->
                    <div class="col-md-12">
                        <label for="arquivos" class="form-label">Anexar Novos Arquivos</label>
                        <input type="file" class="form-control" id="arquivos" name="arquivos[]" multiple 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                        <small class="form-text text-muted">
                            Tipos permitidos: XLS, XLSX, PPT, PPTX, PDF, DOC, DOCX, JPG, PNG, TXT e CSV (máximo 5MB por arquivo)
                        </small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php?modulo=listar_projetos" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para copiar datas do atendimento para o evento
    const btnCopiarDatas = document.getElementById('copiarDatas');

        // Máscara para campo de valor
        $('#valor_total').mask('#.##0,00', {
        reverse: true
    });
    
    if (btnCopiarDatas) {
        btnCopiarDatas.addEventListener('click', function() {
            const dataInicioAtendimento = document.getElementById('data_inicio_atendimento').value;
            const dataFimAtendimento = document.getElementById('data_fim_atendimento').value;
            
            if (!dataInicioAtendimento || !dataFimAtendimento) {
                alert('Por favor, preencha as datas de atendimento primeiro.');
                return;
            }

            // Copiar os valores
            document.getElementById('data_inicio_evento').value = dataInicioAtendimento;
            document.getElementById('data_fim_evento').value = dataFimAtendimento;

            // Feedback visual
            btnCopiarDatas.innerHTML = '<i class="fas fa-check me-1"></i>';
            btnCopiarDatas.classList.remove('btn-secondary');
            btnCopiarDatas.classList.add('btn-success');

            // Restaurar o botão após 2 segundos
            setTimeout(() => {
                btnCopiarDatas.innerHTML = '<i class="fas fa-copy me-1"></i>';
                btnCopiarDatas.classList.remove('btn-success');
                btnCopiarDatas.classList.add('btn-secondary');
            }, 2000);
        });
    }
    
    // Validação de datas
    const dataInicioAtendimento = document.getElementById('data_inicio_atendimento');
    const dataFimAtendimento = document.getElementById('data_fim_atendimento');
    const dataInicioEvento = document.getElementById('data_inicio_evento');
    const dataFimEvento = document.getElementById('data_fim_evento');

    function validarDatas(inicio, fim) {
        if (inicio.value && fim.value) {
            if (new Date(fim.value) < new Date(inicio.value)) {
                alert('A data final não pode ser menor que a data inicial');
                fim.value = inicio.value;
            }
        }
    }

    // Adicionar validação para os pares de datas
    if (dataFimAtendimento) {
        dataFimAtendimento.addEventListener('change', () => 
            validarDatas(dataInicioAtendimento, dataFimAtendimento));
    }

    if (dataFimEvento) {
        dataFimEvento.addEventListener('change', () => 
            validarDatas(dataInicioEvento, dataFimEvento));
    }
});
</script>