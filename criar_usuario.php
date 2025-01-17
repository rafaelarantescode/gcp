<?php
	require_once 'session.php';
	require_once 'db_connection.php';
	verificarLogin();
	verificarPerfil(['Administrador', 'AprovadorN2']);
	$conexao = conectar();
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		try {
			$email = $conexao->real_escape_string($_POST['email']);
			$nome = $conexao->real_escape_string($_POST['nome']);
			$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
			$status = $conexao->real_escape_string($_POST['status']);
			$perfil = $conexao->real_escape_string($_POST['perfil']);
			$valor_hora = $conexao->real_escape_string($_POST['valor_hora']);
			$departamento = $conexao->real_escape_string($_POST['departamento']);
			
			// Verifica se o email já existe
			$check_email = "SELECT id FROM usuarios WHERE email = ?";
			$stmt_check = $conexao->prepare($check_email);
			$stmt_check->bind_param("s", $email);
			$stmt_check->execute();
			$result = $stmt_check->get_result();
			
			if ($result->num_rows > 0) {
				// Email já existe
				$_SESSION['error_message'] = "Este email já está cadastrado no sistema.";
				header("Location: index.php?modulo=criar_usuario");
				exit();
			}
			
			// Se chegou aqui, email não existe, então insere
			$query = "INSERT INTO usuarios (nome, email, senha, status, perfil, valor_hora, departamento) VALUES (?, ?, ?, ?, ?, ?, ?)";
			$stmt = $conexao->prepare($query);
			
			if (!$stmt) {
				throw new Exception("Erro na preparação da query: " . $conexao->error);
			}
			
			$stmt->bind_param("sssssds", $nome, $email, $senha, $status, $perfil, $valor_hora, $departamento);
			
			if ($stmt->execute()) {
				$novo_usuario_id = $conexao->insert_id;
				registrarLog($_SESSION['usuario_id'], "Criou um novo usuário com ID $novo_usuario_id");
				$_SESSION['success_message'] = "Usuário criado com sucesso!";
				header("Location: index.php?modulo=listar_usuarios");
				exit();
			} else {
				throw new Exception("Erro ao executar a query: " . $stmt->error);
			}
			
		} catch (Exception $e) {
			$_SESSION['error_message'] = "Erro ao criar usuário: " . $e->getMessage();
			header("Location: index.php?modulo=criar_usuario");
			exit();
		}
	}
	$conexao->close();
?>
<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Criar Novo Usuário</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $erro; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="index.php?modulo=criar_usuario" method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                 <div class="col-md-6">
                        <label for="nome" class="form-label">Nome</label>
                        <div class="input-group">
                            <input type="nome" class="form-control" id="nome" name="nome" required>
                            <div class="invalid-feedback">Por favor, informe o nome do usuário.</div>
                        </div>
                    </div>
					
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Por favor, insira um email válido.</div>
                        </div>
                    </div>

                    <!-- Nova Senha -->
                    <div class="col-md-6">
                        <label for="nova_senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha" name="senha" 
                                minlength="6">
                            <button class="btn btn-outline-secondary toggle-password" 
                                    type="button" data-target="senha">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">A senha deve ter pelo menos 6 caracteres.</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Ativo">Ativo</option>
                            <option value="Inativo">Inativo</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="perfil" class="form-label">Perfil</label>
						<select class="form-select" id="perfil" name="perfil" required>
							<?php if ($_SESSION['perfil'] === 'Administrador'): ?>
								<option value="Administrador">Administrador</option>
							<?php endif; ?>
							<option value="AprovadorN2">Aprovador N2</option>
							<option value="AprovadorN1">Aprovador N1</option>
							<option value="Usuario">Usuário</option>
						</select>
                    </div>

                    <div class="col-md-4">
                        <label for="valor_hora" class="form-label">Valor Hora</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="valor_hora" name="valor_hora" required>
                            <div class="invalid-feedback">Por favor, insira um valor válido.</div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label for="departamento" class="form-label">Departamento</label>
                        <select class="form-select" id="departamento" name="departamento" required>
                            <option value="Administracao">Administração</option>
                            <option value="Comercial">Comercial</option>
                            <option value="Operacoes">Operações</option>
                            <option value="Diretoria">Diretoria</option>
                            <option value="Marketing">Marketing</option>
							<option value="Desenvolvimento">Desenvolvimento</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php?modulo=listar_usuarios" class="btn btn-outline-secondary">
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