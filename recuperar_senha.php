<?php
session_start();
require_once 'db_connection.php';
require_once 'mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conexao = conectar();
        $email = $conexao->real_escape_string($_POST['email']);

        // Verificar se o email existe
        $query = "SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            throw new Exception("Email não encontrado");
        }

        $usuario = $resultado->fetch_assoc();
        
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        $expiracao = date('Y-m-d H:i:s', strtotime('+12 hour'));
        
        // Salvar token no banco
        $query_token = "INSERT INTO recuperacao_senha (usuario_id, token, expiracao) 
                       VALUES (?, ?, ?)";
        $stmt = $conexao->prepare($query_token);
        $stmt->bind_param("iss", $usuario['id'], $token, $expiracao);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao gerar token de recuperação");
        }

        // Enviar email
        $link = "http://" . $_SERVER['HTTP_HOST'] . 
                dirname($_SERVER['PHP_SELF']) . 
                "/redefinir_senha.php?token=" . $token;

        $assunto = "Recuperação de Senha - Sistema GCP";
        
        $mensagem = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
                <h2 style='color: #333; margin: 0;'>Recuperação de Senha</h2>
            </div>
            
            <div style='margin-bottom: 20px;'>
                <p>Olá {$usuario['nome']},</p>
                <p>Recebemos uma solicitação para redefinir sua senha.</p>
                <p>Para continuar o processo, clique no link abaixo:</p>
                <p><a href='{$link}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;'>Redefinir Senha</a></p>
                <p>Este link é válido por 1 hora.</p>
                <p>Se você não solicitou esta recuperação, ignore este email.</p>
            </div>
            
            <div style='color: #666; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                <p>Este é um email automático, não responda.</p>
            </div>
        </div>";

        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: Sistema GCP <eventos@inteegra.com.br>',
            'X-Mailer: PHP/' . phpversion()
        );

        if (!mail($email, $assunto, $mensagem, implode("\r\n", $headers))) {
            throw new Exception("Erro ao enviar email");
        }

        $_SESSION['success_message'] = "Email de recuperação enviado com sucesso!";
        header("Location: login.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: recuperar_senha.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Sistema de Gestão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css?v=<?php echo rand(1,100000); ?>" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-header">
            <h1>Recuperar Senha</h1>
        </div>

        <div class="login-body">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form action="recuperar_senha.php" method="post" class="needs-validation" novalidate>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="nome@exemplo.com" required>
                    <label for="email">Email</label>
                    <div class="invalid-feedback">
                        Por favor, insira um email válido.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Link de Recuperação
                </button>

                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Voltar para Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js?v=<?php echo rand(1,100000); ?>"></script>
</body>
</html>