<?php
	function conectar() {
		date_default_timezone_set('America/Sao_Paulo');
		$servidor = "localhost";
		$usuario = "inteegra_gcp";
		$senha = "=@1bvW*c.m]j";
		$banco = "inteegra_gcp";

		$conexao = new mysqli($servidor, $usuario, $senha, $banco);

		if ($conexao->connect_error) {
			die("Erro na conexão: " . $conexao->connect_error);
		}

		$conexao->set_charset("utf8");
		return $conexao;
	}

	function registrarLog($usuario_id, $acao) {
		date_default_timezone_set('America/Sao_Paulo');
		$conexao = conectar();
		
		// Verificar se o usuário existe
		$check_user = $conexao->prepare("SELECT id FROM usuarios WHERE id = ?");
		$check_user->bind_param("i", $usuario_id);
		$check_user->execute();
		$result = $check_user->get_result();
		
		if ($result->num_rows == 0) {
			// Se o usuário não existir, registre o log sem um usuário associado
			$stmt = $conexao->prepare("INSERT INTO logs (acao, data_hora) VALUES (?, NOW())");
			$stmt->bind_param("s", $acao);
		} else {
			// Se o usuário existir, registre o log normalmente
			$stmt = $conexao->prepare("INSERT INTO logs (usuario_id, acao, data_hora) VALUES (?, ?, NOW())");
			$stmt->bind_param("is", $usuario_id, $acao);
		}
		
		if (!$stmt->execute()) {
			error_log("Erro ao registrar log: " . $stmt->error);
		}
		
		$stmt->close();
		$conexao->close();
	}

	function hash_senha($senha) {
		return password_hash($senha, PASSWORD_DEFAULT);
	}

	function verificar_senha($senha, $hash) {
		return password_verify($senha, $hash);
	}
?>