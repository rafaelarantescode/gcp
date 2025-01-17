<?php
    session_start();
    require_once 'db_connection.php';

    try {
        if (!isset($_GET['token'])) {
            throw new Exception("Token não fornecido");
        }

        $conexao = conectar();
        $token = $conexao->real_escape_string($_GET['token']);

        // Verificar validade do token
        $query = "SELECT r.usuario_id, r.expiracao, u.nome 
                FROM recuperacao_senha r
                JOIN usuarios u ON r.usuario_id = u.id
                WHERE r.token = ? AND r.usado = 0
                AND r.expiracao > NOW()";
                
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {
            throw new Exception("Token inválido ou expirado");
        }

        $dados = $resultado->fetch_assoc();
        $usuario_id = $dados['usuario_id'];
        $nome_usuario = $dados['nome'];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nova_senha = $_POST['senha'];
            $confirmar_senha = $_POST['confirmar_senha'];

            if ($nova_senha !== $confirmar_senha) {
                throw new Exception("As senhas não coincidem");
            }

            if (strlen($nova_senha) < 6) {
                throw new Exception("A senha deve ter pelo menos 6 caracteres");
            }

            // Atualizar senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $query_update = "UPDATE usuarios SET senha = ?, primeiro_acesso = 0 WHERE id = ?";
            $stmt = $conexao->prepare($query_update);
            $stmt->bind_param("si", $senha_hash, $usuario_id);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar senha");
            }

            // Marcar token como usado
            $query_token = "UPDATE recuperacao_senha SET usado = 1 WHERE token = ?";
            $stmt = $conexao->prepare($query_token);
            $stmt->bind_param("s", $token);
            $stmt->execute();

            registrarLog($usuario_id, "Senha redefinida via recuperação");
            $_SESSION['success_message'] = "Senha alterada com sucesso!";
            header("Location: login.php");
            exit();
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: login.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema de Gestão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css?v=<?php echo rand(1,100000); ?>" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-header">
            <h1>Redefinir Senha</h1>
        </div>

        <div class="login-body">
            <form action="redefinir_senha.php?token=<?php echo htmlspecialchars($token); ?>" 
                  method="post" class="needs-validation" novalidate>
                  
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Olá <?php echo htmlspecialchars($nome_usuario); ?>, defina sua nova senha abaixo.
                </div>

                <div class="mb-3">
                    <label for="senha">Nova Senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="senha" name="senha" 
                                placeholder="Nova senha" required minlength="6">
                        
                        <button class="btn btn-outline-secondary toggle-password" 
                                type="button" data-target="senha">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">
                        A senha deve ter pelo menos 6 caracteres.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirmar_senha" 
                                name="confirmar_senha" placeholder="Confirmar senha" required>
                        
                        <button class="btn btn-outline-secondary toggle-password" 
                                type="button" data-target="confirmar_senha">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">
                        Por favor, confirme sua senha.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-save me-2"></i>Salvar Nova Senha
                </button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js?v=<?php echo rand(1,100000); ?>"></script>
</body>
</html>