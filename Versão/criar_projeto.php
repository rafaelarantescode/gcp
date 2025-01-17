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

// Buscar lista de usuários para os campos de responsáveis
$query_usuarios = "SELECT id, nome, email 
                  FROM usuarios 
                  WHERE status = 'Ativo' AND ativo = 1 
                  ORDER BY nome";
$resultado_usuarios = $conexao->query($query_usuarios);

// Verificar se a consulta foi bem-sucedida
if (!$resultado_usuarios) {
    die("Erro ao buscar usuários: " . $conexao->error);
}

// Armazenar os usuários em um array
$usuarios = array();
while ($usuario = $resultado_usuarios->fetch_assoc()) {
    $usuarios[] = $usuario;
}

// Verificar se há usuários
if (empty($usuarios)) {
    $_SESSION['error_message'] = "Não existem usuários ativos para selecionar como responsáveis.";
}

function uploadArquivos($conexao, $projeto_id, $arquivos) {
    $upload_dir = 'uploads/projetos/' . $projeto_id . '/';
    
    // Cria diretório se não existir
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['xls', 'xlsx', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'txt', 'csv'];
    $max_file_size = 5 * 1024 * 1024; // 5MB

    $uploaded_files = [];

    foreach ($arquivos['name'] as $key => $nome_arquivo) {
        if ($arquivos['error'][$key] !== UPLOAD_ERR_OK) {
            continue; // Pula arquivos com erro de upload
        }

        $tmp_name = $arquivos['tmp_name'][$key];
        $tamanho = $arquivos['size'][$key];
        $tipo = $arquivos['type'][$key];
        
        $ext = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        
        // Validações
        if (!in_array($ext, $allowed_types)) {
            throw new Exception("Tipo de arquivo não permitido: $nome_arquivo");
        }

        if ($tamanho > $max_file_size) {
            throw new Exception("Arquivo muito grande: $nome_arquivo (máximo 5MB)");
        }

        // Nome único para o arquivo
        $novo_nome = uniqid() . '.' . $ext;
        $caminho_destino = $upload_dir . $novo_nome;

        if (move_uploaded_file($tmp_name, $caminho_destino)) {
            // Salvar no banco de dados
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Conversão e sanitização dos dados
        $nome = trim($_POST['nome']);
        $id_evento = trim($_POST['id_evento']);
        $contratante_id = !empty($_POST['contratante_id']) ? intval($_POST['contratante_id']) : null;
        $cliente_final_id = !empty($_POST['cliente_final_id']) ? intval($_POST['cliente_final_id']) : null;
        $descricao = trim($_POST['descricao']);
        
        // Conversão de responsáveis para inteiros
        $responsavel_comercial = intval($_POST['responsavel_comercial']);
        $responsavel_atendimento = intval($_POST['responsavel_atendimento']);
        $responsavel_tecnico = intval($_POST['responsavel_tecnico']);
        
        // Conversão de valor
        $valor_total = str_replace(['.', ','], ['', '.'], $_POST['valor_total']);
        $valor_total = floatval($valor_total);

        // Conversão de datas
        $data_inicio_atendimento = date('Y-m-d H:i:s', strtotime($_POST['data_inicio_atendimento']));
        $data_fim_atendimento = date('Y-m-d H:i:s', strtotime($_POST['data_fim_atendimento']));
        $data_inicio_evento = date('Y-m-d H:i:s', strtotime($_POST['data_inicio_evento']));
        $data_fim_evento = date('Y-m-d H:i:s', strtotime($_POST['data_fim_evento']));

        // Sanitização de campos restantes
        $local = trim($_POST['local']);
        $estado = trim($_POST['estado']);
        $cidade = trim($_POST['cidade']);

        // Validações
        if (empty($nome)) {
            throw new Exception("Nome do projeto é obrigatório");
        }

        $responsavel_atendimento = !empty($_POST['responsavel_atendimento']) ? intval($_POST['responsavel_atendimento']) : null;
        $responsavel_tecnico = !empty($_POST['responsavel_tecnico']) ? intval($_POST['responsavel_tecnico']) : null;

        if ($responsavel_comercial <= 0) {
            throw new Exception("Responsável comercial deve ser selecionado");
        }

        if (strtotime($data_fim_atendimento) <= strtotime($data_inicio_atendimento)) {
            throw new Exception("A data final de atendimento deve ser maior que a data inicial");
        }

        if (strtotime($data_fim_evento) <= strtotime($data_inicio_evento)) {
            throw new Exception("A data final do evento deve ser maior que a data inicial");
        }

        // Início da transação
        $conexao->begin_transaction();

        // Preparar a query
        $query = "INSERT INTO projetos (
            nome, id_evento, descricao, 
            responsavel_comercial, responsavel_atendimento, responsavel_tecnico, 
            valor_total, 
            data_inicio, data_fim, 
            data_inicio_evento, data_fim_evento,
            local, estado, cidade, contratante_id, cliente_final_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Verifique se todos os valores estão definidos
        $stmt = $conexao->prepare($query);
        $stmt->bind_param(
            "sssiiidsssssssii", 
            $nome,               // s
            $id_evento,          // s
            $descricao,          // s
            $responsavel_comercial,   // i
            $responsavel_atendimento, // i
            $responsavel_tecnico,     // i
            $valor_total,        // d (float)
            $data_inicio_atendimento,   // s
            $data_fim_atendimento,      // s
            $data_inicio_evento,        // s
            $data_fim_evento,           // s
            $local,              // s
            $estado,             // s
            $cidade,              // s
            $contratante_id,  // i
            $cliente_final_id // i
        );
    
        if (!$stmt->execute()) {
            // Log detalhado do erro
            error_log("Erro ao inserir projeto: " . $stmt->error);
            error_log("Dados: " . print_r([
                'nome' => $nome,
                'id_evento' => $id_evento,
                'descricao' => $descricao,
                'responsavel_comercial' => $responsavel_comercial,
                'responsavel_atendimento' => $responsavel_atendimento,
                'responsavel_tecnico' => $responsavel_tecnico,
                'valor_total' => $valor_total,
                'data_inicio_atendimento' => $data_inicio_atendimento,
                'data_fim_atendimento' => $data_fim_atendimento,
                'data_inicio_evento' => $data_inicio_evento,
                'data_fim_evento' => $data_fim_evento,
                'local' => $local,
                'estado' => $estado,
                'cidade' => $cidade,
                'contratante_id' => $contratante_id, 
                'cliente_final_id' => $cliente_final_id
            ], true));
            
            throw new Exception("Erro ao criar projeto: " . $stmt->error);
        }

        $novo_projeto_id = $conexao->insert_id;

        // Upload de arquivos
        if (!empty($_FILES['arquivos']['name'][0])) {
            $arquivos_upload = uploadArquivos($conexao, $novo_projeto_id, $_FILES['arquivos']);
        }

        $conexao->commit();
        registrarLog($_SESSION['usuario_id'], "Criou um novo projeto com ID $novo_projeto_id");
        $_SESSION['success_message'] = "Projeto criado com sucesso!";
        header("Location: index.php?modulo=listar_projetos");
        exit();

    } catch (Exception $e) {
        if ($conexao->errno) {
            $conexao->rollback();
        }
        $_SESSION['error_message'] = "Erro ao criar projeto: " . $e->getMessage();
        header("Location: index.php?modulo=criar_projeto");
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Criar Novo Projeto</h1>
        </div>
    </div>

    <div class="card">
    <div class="card-body p-4">
        <form action="index.php?modulo=criar_projeto" method="post" class="needs-validation" novalidate enctype="multipart/form-data">
            <div class="row gy-4 gx-3">
                <!-- Nome e ID do Evento -->
                <div class="col-md-8 mb-3">
                    <label for="nome" class="form-label">Nome do Projeto</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                    <div class="invalid-feedback">Por favor, insira o nome do projeto.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="id_evento" class="form-label">ID do Evento</label>
                    <input type="number" class="form-control" id="id_evento" name="id_evento">
                    <div class="invalid-feedback">Por favor, insira o ID do evento.</div>
                </div>

                <!-- Clientes -->
                <div class="col-md-6 mb-3">
                    <label for="contratante_id" class="form-label">Contratante</label>
                    <select class="form-select" id="contratante_id" name="contratante_id">
                        <option value="">Selecione um contratante...</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>">
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cliente_final_id" class="form-label">Cliente Final</label>
                    <select class="form-select" id="cliente_final_id" name="cliente_final_id">
                        <option value="">Selecione o cliente final...</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>">
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Descrição -->
                <div class="col-md-12 mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                    <div class="invalid-feedback">Por favor, insira a descrição do projeto.</div>
                </div>

                <!-- Responsáveis -->
                <div class="col-12 p-3 border rounded mb-3">
                    <div class="row gy-3">
                        <div class="col-md-4">
                            <label for="responsavel_comercial" class="form-label">Responsável Comercial</label>
                            <select class="form-select" id="responsavel_comercial" name="responsavel_comercial" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['nome']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Por favor, selecione o responsável comercial.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="responsavel_atendimento" class="form-label">Responsável Atendimento</label>
                            <select class="form-select" id="responsavel_atendimento" name="responsavel_atendimento">
                                <option value="">Selecione...</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['nome']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Por favor, selecione o responsável de atendimento.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="responsavel_tecnico" class="form-label">Responsável Técnico</label>
                            <select class="form-select" id="responsavel_tecnico" name="responsavel_tecnico">
                                <option value="">Selecione...</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['nome']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Por favor, selecione o responsável técnico.</div>
                        </div>
                    </div>
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
                <!-- Localização -->
                <div class="col-md-4 mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado" required>
                        <option value="">Selecione o Estado</option>
                        <?php foreach($estados as $sigla => $nome): ?>
                            <option value="<?php echo $sigla; ?>"><?php echo $nome; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="cidade" required>
                </div>

                <!-- Valor e Upload -->
                <div class="col-md-4 mb-3">
                    <label for="valor_total" class="form-label">Valor Total</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" id="valor_total" name="valor_total" required>
                        <div class="invalid-feedback">Por favor, insira o valor total.</div>
                    </div>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="arquivos" class="form-label">Anexar Arquivos</label>
                    <input type="file" class="form-control" id="arquivos" name="arquivos[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                    <small class="form-text text-muted">
                        Tipos permitidos: XLS, XLSX, PPT, PPTX, PDF, DOC, DOCX, JPG, PNG, TXT e CSV (máximo 5MB por arquivo)
                    </small>
                </div>

                <!-- Local -->
                <div class="col-md-12 mb-3">
                    <label for="local" class="form-label">Local</label>
                    <input type="text" class="form-control" id="local" name="local">
                    <div class="invalid-feedback">Por favor, insira o local do projeto.</div>
                </div>
            </div>

            <!-- Botões -->
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