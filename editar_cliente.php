<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

try {
    if (!isset($_GET['id'])) {
        throw new Exception("ID do cliente não especificado");
    }

    $id = intval($_GET['id']);
    $stmt = $conexao->prepare("SELECT * FROM clientes WHERE id = ? AND ativo = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result->num_rows) {
        throw new Exception("Cliente não encontrado");
    }
    
    $cliente = $result->fetch_assoc();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: index.php?modulo=listar_clientes");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $nome = trim($_POST['nome']);
        $contato = trim($_POST['contato']);
        $status = $_POST['status'];

        if (empty($nome)) {
            throw new Exception("Nome do cliente é obrigatório");
        }

        $query = "UPDATE clientes SET nome = ?, contato = ?, status = ? WHERE id = ?";
        $stmt = $conexao->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Erro na preparação da query: " . $conexao->error);
        }
        
        $stmt->bind_param("sssi", $nome, $contato, $status, $id);
        
        if ($stmt->execute()) {
            registrarLog($_SESSION['usuario_id'], "Atualizou cliente ID $id");
            $_SESSION['success_message'] = "Cliente atualizado com sucesso!";
            header("Location: index.php?modulo=listar_clientes");
            exit();
        } else {
            throw new Exception("Erro ao atualizar cliente: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: index.php?modulo=editar_cliente&id=" . $id);
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Editar Cliente</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="index.php?modulo=editar_cliente&id=<?php echo $id; ?>" method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                        <div class="invalid-feedback">Por favor, informe o nome do cliente.</div>
                    </div>

                    <div class="col-md-8">
                        <label for="contato" class="form-label">Contato</label>
                        <input type="text" class="form-control" id="contato" name="contato" 
                               value="<?php echo htmlspecialchars($cliente['contato']); ?>">
                        <div class="invalid-feedback">Por favor, informe o contato.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Ativo" <?php echo $cliente['status'] == 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="Inativo" <?php echo $cliente['status'] == 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione o status.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php?modulo=listar_clientes" class="btn btn-outline-secondary">
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