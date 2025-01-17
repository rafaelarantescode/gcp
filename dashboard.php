<?php
	require_once 'session.php';
	require_once 'db_connection.php';
	verificarLogin();

	// Definir qual dashboard deve ser carregado baseado no perfil
	switch($_SESSION['perfil']) {
		case 'Administrador':
			require_once 'dashboard_admin.php';
			break;
		case 'AprovadorN1':
				require_once 'dashboard_n1.php';
				break;
		case 'AprovadorN2':
				require_once 'dashboard_n2.php';
				break;
		case 'Usuario':
				require_once 'dashboard_user.php';
				break;
		default:
			// Caso aconteça algum erro com o perfil
			$_SESSION['error_message'] = "Perfil não identificado. Por favor, faça login novamente.";
			header("Location: logout.php");
			exit();
	}
?>