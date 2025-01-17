<?php
	require_once 'session.php';
	verificarLogin();
	verificarPrimeiroAcesso(); // Adicionar esta linha
	$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'dashboard';
	date_default_timezone_set('America/Sao_Paulo');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestão</title>
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
	<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
	<link href="css/style.css?v=<?php echo rand(1,100000); ?>" rel="stylesheet">

</head>
<body data-user-profile="<?php echo htmlspecialchars($_SESSION['perfil']); ?>">
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <h3>Sistema de Gestão</h3>
            </div>
				<ul class="list-unstyled components">
					<li <?php echo $modulo == 'dashboard' ? 'class="active"' : ''; ?>>
						<a href="index.php?modulo=dashboard">
							<i class="fas fa-home"></i> Dashboard
						</a>
					</li>
					<li <?php echo $modulo == 'calendario' ? 'class="active"' : ''; ?>>
						<a href="index.php?modulo=calendario">
							<i class="fas fa-calendar"></i> Calendário
						</a>
					</li>
					<?php if (in_array($_SESSION['perfil'], ['Administrador', 'AprovadorN2'])): ?>
						<li <?php echo $modulo == 'listar_usuarios' ? 'class="active"' : ''; ?>>
							<a href="index.php?modulo=listar_usuarios">
								<i class="fas fa-users"></i> Usuários
							</a>
						</li>
					<?php endif; ?>
					<li <?php echo $modulo == 'listar_projetos' ? 'class="active"' : ''; ?>>
						<a href="index.php?modulo=listar_projetos">
							<i class="fas fa-project-diagram"></i> Projetos
						</a>
					</li>
					<li <?php echo $modulo == 'listar_clientes' ? 'class="active"' : ''; ?>>
						<a href="index.php?modulo=listar_clientes">
							<i class="fas fa-building"></i> Clientes
						</a>
					</li>
					<li <?php echo $modulo == 'listar_prestadores' ? 'class="active"' : ''; ?>>
						<a href="index.php?modulo=listar_prestadores">
							<i class="fas fa-handshake"></i> Prestadores
						</a>
					</li>
					<li <?php echo $modulo == 'listar_custo_projetos' ? 'class="active"' : ''; ?>>
						<a href="index.php?modulo=listar_custo_projetos">
							<i class="fas fa-dollar-sign"></i> Custos de Projetos
						</a>
					</li>
					<li <?php echo $modulo == 'listar_pagamentos' ? 'class="active"' : ''; ?>>
						<a href="index.php?modulo=listar_pagamentos">
							<i class="fas fa-money-check-alt"></i> Pagamentos
						</a>
					</li>
				</ul>
        </nav>

        <!-- Conteúdo da Página -->
        <div id="content">
			<!-- Navbar Superior -->
			<nav class="top-navbar">
				<div class="d-flex align-items-center">
					<button type="button" class="navbar-toggler-desktop d-none d-md-block" id="sidebarCollapseDesktop">
        				<i class="fas fa-bars"></i>
    				</button>
					<button type="button" class="navbar-toggler" id="sidebarCollapse">
						<i class="fas fa-bars"></i>
					</button>
					<h4 class="mb-0 ml-3 d-none d-md-block"></h4>
				</div>
				<div class="d-flex align-items-center">
					<div class="dropdown">
						<button class="btn text-decoration-none dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
							<span class="mr-2">Bem-vindo, <?php echo $_SESSION['nome']; ?></span>
						</button>
						<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
							<li>
								<a class="dropdown-item" href="index.php?modulo=alterar_senha">
									<i class="fas fa-key me-2"></i>Alterar Senha
								</a>
							</li>
							<li><hr class="dropdown-divider"></li>
							<li>
								<a class="dropdown-item text-danger" href="logout.php">
									<i class="fas fa-sign-out-alt me-2"></i>Sair
								</a>
							</li>
						</ul>
					</div>
				</div>
			</nav>

            <!-- Conteúdo Dinâmico -->
            <!-- No seu index.php, logo após o início do content-wrapper -->
			<div class="container-fluid content-wrapper">
				<?php
					// Debug - remova depois
					echo "<!-- Debug: ";
					print_r($_SESSION);
					echo " -->";
					
					// Mensagens de alerta
					if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
						?>
						<div class="alert alert-danger alert-dismissible fade show" role="alert">
							<i class="fas fa-exclamation-triangle me-2"></i>
							<?php echo htmlspecialchars($_SESSION['error_message']); ?>
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
						<?php
						unset($_SESSION['error_message']);
					}

					if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
						?>
						<div class="alert alert-success alert-dismissible fade show" role="alert">
							<i class="fas fa-check-circle me-2"></i>
							<?php echo htmlspecialchars($_SESSION['success_message']); ?>
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
						<?php
						unset($_SESSION['success_message']);
					}
				
					// Carrega o módulo
					$arquivo = $modulo . ".php";
					if (file_exists($arquivo)) {
						require_once $arquivo;
					} else {
						echo '<div class="alert alert-danger">Módulo não encontrado!</div>';
					}
				?>
			</div>
        </div>
    </div>



<!-- jQuery primeiro -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap Bundle (inclui Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery Mask Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<!-- DataTables e suas extensões -->
 <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>


<!-- Seu script customizado por último -->
<script src="js/script.js?v=<?php echo rand(1,100000); ?>"></script>

</body>
</html>