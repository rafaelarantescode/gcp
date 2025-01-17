<?php
	require_once 'session.php';
	require_once 'db_connection.php';
	verificarLogin();
	verificarPerfil(['Administrador', 'AprovadorN2']);
	$conexao = conectar();
	try {
		if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
			$id = intval($_GET['id']);
			$stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
			$stmt->bind_param("i", $id);
			$stmt->execute();
			$result = $stmt->get_result();
			
			if (!$result->num_rows) {
				throw new Exception("Usuário não encontrado");
			}
			
			$usuario = $result->fetch_assoc();
			
		} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
			try {
				$id = intval($_POST['id']);
				$nome = $conexao->real_escape_string($_POST['nome']);
				$email = $conexao->real_escape_string($_POST['email']);
				$status = $conexao->real_escape_string($_POST['status']);
				$perfil = $conexao->real_escape_string($_POST['perfil']);
				$valor_hora = $conexao->real_escape_string($_POST['valor_hora']);
				$departamento = $conexao->real_escape_string($_POST['departamento']);
                $nova_senha = trim($_POST['senha'] ?? '');

				// Verifica se o email já existe para outro usuário
				$check_email = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
				$stmt_check = $conexao->prepare($check_email);
				$stmt_check->bind_param("si", $email, $id);
				$stmt_check->execute();
				$result = $stmt_check->get_result();
				
				if ($result->num_rows > 0) {
					throw new Exception("Este email já está em uso por outro usuário");
				}
				
                // Prepara a query base
                $sql_base = "UPDATE usuarios SET nome = ?, email = ?, status = ?, 
                            perfil = ?, valor_hora = ?, departamento = ?";
                $params = array($nome, $email, $status, $perfil, $valor_hora, $departamento);
                $types = "ssssds";

                // Se uma nova senha foi fornecida, adiciona à query
                if (!empty($nova_senha)) {
                    if (strlen($nova_senha) < 6) {
                        throw new Exception("A senha deve ter pelo menos 6 caracteres");
                    }
                    $sql_base .= ", senha = ?";
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $params[] = $senha_hash;
                    $types .= "s";
                }

                $sql_base .= " WHERE id = ?";
                $params[] = $id;
                $types .= "i";

                $stmt = $conexao->prepare($sql_base);
                if (!$stmt) {
                    throw new Exception("Erro na preparação da query: " . $conexao->error);
                }

                // Faz o bind dos parâmetros dinamicamente
                $stmt->bind_param($types, ...$params);
				
				if ($stmt->execute()) {
					registrarLog($_SESSION['usuario_id'], "Atualizou o usuário com ID $id" . 
                        (!empty($nova_senha) ? " (senha alterada)" : ""));
					$_SESSION['success_message'] = "Usuário atualizado com sucesso!";
					header("Location: index.php?modulo=listar_usuarios");
					exit();
				} else {
					throw new Exception("Erro ao atualizar usuário: " . $stmt->error);
				}
				
			} catch (Exception $e) {
				$_SESSION['error_message'] = $e->getMessage();
				header("Location: index.php?modulo=editar_usuario&id=" . $id);
				exit();
			}
		}
	} catch (Exception $e) {
		$_SESSION['error_message'] = $e->getMessage();
		header("Location: index.php?modulo=listar_usuarios");
		exit();
	}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Editar Usuário</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="index.php?modulo=editar_usuario" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                
                <div class="row g-3">
                    <!-- Nome -->
					<div class="col-md-6">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        <div class="invalid-feedback">Por favor, informe o nome do usuário.</div>
                    </div>
					
                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            <div class="invalid-feedback">Por favor, insira um email válido.</div>
                        </div>
                    </div>

                    <!-- Nova Senha (Opcional) -->
                    <div class="col-md-6">
                        <label for="nova_senha" class="form-label">Nova Senha (opcional)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha" name="senha" 
                                minlength="6">
                            <button class="btn btn-outline-secondary toggle-password" 
                                    type="button" data-target="senha">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">A senha deve ter pelo menos 6 caracteres.</div>
                        </div>
                        <small class="form-text text-muted">
                            Deixe em branco para manter a senha atual
                        </small>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Ativo" <?php echo ($usuario['status'] == 'Ativo') ? 'selected' : ''; ?>>
                                Ativo
                            </option>
                            <option value="Inativo" <?php echo ($usuario['status'] == 'Inativo') ? 'selected' : ''; ?>>
                                Inativo
                            </option>
                        </select>
                    </div>

                    <!-- Perfil -->
                    <div class="col-md-4">
                        <label for="perfil" class="form-label">Perfil</label>
						<select class="form-select" id="perfil" name="perfil" required>
							<?php if ($_SESSION['perfil'] === 'Administrador'): ?>
								<option value="Administrador" <?php echo ($usuario['perfil'] == 'Administrador') ? 'selected' : ''; ?>>
									Administrador
								</option>
							<?php endif; ?>
							<option value="AprovadorN2" <?php echo ($usuario['perfil'] == 'AprovadorN2') ? 'selected' : ''; ?>>
								Aprovador N2
							</option>
							<option value="AprovadorN1" <?php echo ($usuario['perfil'] == 'AprovadorN1') ? 'selected' : ''; ?>>
								Aprovador N1
							</option>
							<option value="Usuario" <?php echo ($usuario['perfil'] == 'Usuario') ? 'selected' : ''; ?>>
								Usuário
							</option>
						</select>
                    </div>

                    <!-- Valor Hora -->
                    <div class="col-md-4">
                        <label for="valor_hora" class="form-label">Valor Hora</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="valor_hora" name="valor_hora" 
                                   value="<?php echo number_format($usuario['valor_hora'], 2, ',', '.'); ?>" required>
                            <div class="invalid-feedback">Por favor, insira um valor válido.</div>
                        </div>
                    </div>

                    <!-- Departamento -->
                    <div class="col-md-4">
                        <label for="departamento" class="form-label">Departamento</label>
                        <select class="form-select" id="departamento" name="departamento" required>
                            <option value="Administracao" <?php echo ($usuario['departamento'] == 'Administracao') ? 'selected' : ''; ?>>
                                Administração
                            </option>
                            <option value="Comercial" <?php echo ($usuario['departamento'] == 'Comercial') ? 'selected' : ''; ?>>
                                Comercial
                            </option>
                            <option value="Operacoes" <?php echo ($usuario['departamento'] == 'Operacoes') ? 'selected' : ''; ?>>
                                Operações
                            </option>
                            <option value="Diretoria" <?php echo ($usuario['departamento'] == 'Diretoria') ? 'selected' : ''; ?>>
                                Diretoria
                            </option>
                            <option value="Marketing" <?php echo ($usuario['departamento'] == 'Marketing') ? 'selected' : ''; ?>>
                                Marketing
                            </option>
							<option value="Desenvolvimento" <?php echo ($usuario['departamento'] == 'Desenvolvimento') ? 'selected' : ''; ?>>
                                Desenvolvimento
                            </option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php?modulo=listar_usuarios" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>