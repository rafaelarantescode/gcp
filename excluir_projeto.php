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

		// Verificar se há custos vinculados e ativos
		$query_check_custos = "SELECT COUNT(*) as total FROM custos_projeto 
							  WHERE projeto_id = ? AND ativo = 1";
		$stmt_check = $conexao->prepare($query_check_custos);
		$stmt_check->bind_param("i", $id);
		$stmt_check->execute();
		$result = $stmt_check->get_result();
		$row = $result->fetch_assoc();

		if ($row['total'] > 0) {
			throw new Exception("Não é possível excluir o projeto pois existem custos vinculados");
		}

		// Verificar se projeto existe e está ativo
		$query_check = "SELECT id FROM projetos WHERE id = ? AND ativo = 1";
		$stmt_check = $conexao->prepare($query_check);
		$stmt_check->bind_param("i", $id);
		$stmt_check->execute();
		$result = $stmt_check->get_result();

		if (!$result->num_rows) {
			throw new Exception("Projeto não encontrado ou já excluído");
		}

		// Realizar exclusão lógica
		$query = "UPDATE projetos SET ativo = 0 WHERE id = ?";
		$stmt = $conexao->prepare($query);
		$stmt->bind_param("i", $id);
		
		if (!$stmt->execute()) {
			throw new Exception("Erro ao excluir projeto");
		}

		registrarLog($_SESSION['usuario_id'], "Excluiu projeto ID {$id}");
		$_SESSION['success_message'] = "Projeto excluído com sucesso!";

	} catch (Exception $e) {
		$_SESSION['error_message'] = "Erro ao excluir projeto: " . $e->getMessage();
	} finally {
		if (isset($stmt)) $stmt->close();
		if (isset($conexao)) $conexao->close();
		header("Location: index.php?modulo=listar_projetos");
		exit();
	}
?>