<?php
	require_once 'session.php';
	require_once 'db_connection.php';
	verificarLogin();

	try {
		if (!isset($_GET['id'])) {
			throw new Exception("ID não especificado");
		}

		$id = intval($_GET['id']);
		$conexao = conectar();

		// Verificar se custo existe, está ativo e seu status
		$query_check = "SELECT id, status, usuario_id FROM custos_projeto 
					   WHERE id = ? AND ativo = 1";
		$stmt_check = $conexao->prepare($query_check);
		$stmt_check->bind_param("i", $id);
		$stmt_check->execute();
		$result = $stmt_check->get_result();
		$custo = $result->fetch_assoc();

		if (!$custo) {
			throw new Exception("Custo não encontrado ou já excluído");
		}

		// Verificar se está aprovado
		if ($custo['status'] == 'Aprovado') {
			throw new Exception("Não é possível excluir um custo já aprovado");
		}

		// Verificar se o usuário tem permissão (criador do custo ou admin)
		if ($custo['usuario_id'] != $_SESSION['usuario_id'] && 
			$_SESSION['perfil'] != 'Administrador') {
			throw new Exception("Você não tem permissão para excluir este custo");
		}

		// Realizar exclusão lógica
		$query = "UPDATE custos_projeto SET ativo = 0 WHERE id = ?";
		$stmt = $conexao->prepare($query);
		$stmt->bind_param("i", $id);
		
		if (!$stmt->execute()) {
			throw new Exception("Erro ao excluir custo");
		}

		registrarLog($_SESSION['usuario_id'], "Excluiu custo de projeto ID {$id}");
		$_SESSION['success_message'] = "Custo excluído com sucesso!";

	} catch (Exception $e) {
		$_SESSION['error_message'] = "Erro ao excluir custo: " . $e->getMessage();
	} finally {
		if (isset($stmt)) $stmt->close();
		if (isset($conexao)) $conexao->close();
		header("Location: index.php?modulo=listar_custo_projetos");
		exit();
	}
?>