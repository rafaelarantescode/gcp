<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

// Verifica se é primeiro acesso
$primeiro_acesso = isset($_GET['primeiro_acesso']) && $_GET['primeiro_acesso'] == 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        // Validar se as senhas novas coincidem
        if ($nova_senha !== $confirmar_senha) {
            throw new Exception("A nova senha e a confirmação não coincidem");
        }

        // Validar comprimento mínimo da senha
        if (strlen($nova_senha) < 6) {
            throw new Exception("A nova senha deve ter pelo menos 6 caracteres");
        }

        // Verificar senha atual
        $query = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();

        if (!password_verify($senha_atual, $usuario['senha'])) {
            throw new Exception("Senha atual incorreta");
        }

        // Atualizar senha e marcar que não é mais primeiro acesso
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $query_update = "UPDATE usuarios SET senha = ?, primeiro_acesso = 0 WHERE id = ?";
        $stmt = $conexao->prepare($query_update);
        $stmt->bind_param("si", $nova_senha_hash, $_SESSION['usuario_id']);

        if ($stmt->execute()) {
            registrarLog($_SESSION['usuario_id'], "Alterou sua senha");
            $_SESSION['success_message'] = "Senha alterada com sucesso!";
            header("Location: index.php");
            exit();
        } else {
            throw new Exception("Erro ao atualizar senha");
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erro ao alterar senha: " . $e->getMessage();
        header("Location: index.php?modulo=alterar_senha" . ($primeiro_acesso ? "&primeiro_acesso=1" : ""));
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Alterar Senha</h1>
            <?php if ($primeiro_acesso): ?>
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle me-2"></i>
                    Por favor, altere sua senha para continuar usando o sistema.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form action="index.php?modulo=alterar_senha<?php echo $primeiro_acesso ? '&primeiro_acesso=1' : ''; ?>" 
                          method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="senha_atual" 
                                       name="senha_atual" required>
                                <button class="btn btn-outline-secondary toggle-password" 
                                        type="button" data-target="senha_atual">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Por favor, insira sua senha atual.</div>
                        </div>

                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="nova_senha" 
                                       name="nova_senha" required minlength="6">
                                <button class="btn btn-outline-secondary toggle-password" 
                                        type="button" data-target="nova_senha">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">A senha deve ter pelo menos 6 caracteres.</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmar_senha" 
                                       name="confirmar_senha" required minlength="6">
                                <button class="btn btn-outline-secondary toggle-password" 
                                        type="button" data-target="confirmar_senha">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Por favor, confirme a nova senha.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <?php if (!$primeiro_acesso): ?>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>