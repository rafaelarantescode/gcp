<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $nome = trim($_POST['nome']);
        $contato = trim($_POST['contato']);
        $status = $_POST['status'];

        if (empty($nome)) {
            throw new Exception("Nome do cliente é obrigatório");
        }

        $query = "INSERT INTO clientes (nome, contato, status) VALUES (?, ?, ?)";
        $stmt = $conexao->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Erro na preparação da query: " . $conexao->error);
        }
        
        $stmt->bind_param("sss", $nome, $contato, $status);
        
        if ($stmt->execute()) {
            $novo_cliente_id = $conexao->insert_id;
            registrarLog($_SESSION['usuario_id'], "Criou cliente ID $novo_cliente_id");
            $_SESSION['success_message'] = "Cliente criado com sucesso!";
            header("Location: index.php?modulo=listar_clientes");
            exit();
        } else {
            throw new Exception("Erro ao criar cliente: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: index.php?modulo=criar_cliente");
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Criar Novo Cliente</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="index.php?modulo=criar_cliente" method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                        <div class="invalid-feedback">Por favor, informe o nome do cliente.</div>
                    </div>

                    <div class="col-md-8">
                        <label for="contato" class="form-label">Contato</label>
                        <input type="text" class="form-control" id="contato" name="contato">
                        <div class="invalid-feedback">Por favor, informe o contato.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Ativo">Ativo</option>
                            <option value="Inativo">Inativo</option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione o status.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php?modulo=listar_clientes" class="btn btn-outline-secondary">
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