<?php
	session_start();
	if(isset($_SESSION['usuario_id'])) {
		header("Location: index.php");
		exit();
	}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestão</title>
    
    <!-- CSS Base -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css?v=<?php echo rand(1,100000); ?>" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-header">
            <h1>Sistema de Gestão</h1>
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

            <form action="autenticar.php" method="post" class="needs-validation" novalidate>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="nome@exemplo.com" required>
                    <label for="email">Email</label>
                    <div class="invalid-feedback">
                        Por favor, insira um email válido.
                    </div>
                </div>

                <div class="form-floating mb-3 position-relative">
                    <input type="password" class="form-control" id="senha" name="senha" 
                           placeholder="Senha" required>
                    <label for="senha">Senha</label>
                    <span class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="invalid-feedback">
                        Por favor, insira sua senha.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                </button>
                <div class="text-center mt-3">
                    <a href="recuperar_senha.php" class="text-decoration-none">
                        <i class="fas fa-lock me-1"></i>Esqueci minha senha
                    </a>
                </div>
            </form>
        </div>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js?v=<?php echo rand(1,100000); ?>"></script>

</body>
</html>