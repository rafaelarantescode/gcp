<?php
	require_once 'session.php';
	require_once 'db_connection.php';
	verificarLogin();
	verificarPerfil(['Administrador', 'AprovadorN2']);
	$conexao = conectar();
	$query = "SELECT id, nome, email, status, perfil, valor_hora, departamento FROM usuarios WHERE ativo=1";
	$stmt = $conexao->prepare($query);
	if (!$stmt) {
		die("Erro na preparação da consulta: " . $conexao->error);
	}

	$stmt->execute();
	$resultado = $stmt->get_result();
	if (!$resultado) {
		die("Erro na consulta: " . $conexao->error);
	}
?>
<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="h2">Usuários</h1>
        <a href="index.php?modulo=criar_usuario" class="btn btn-primary btn-novo">
            <i class="fas fa-plus"></i> Novo Usuário
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="tabelaUsuarios" class="datatable table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
						<th>Nome</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Perfil</th>
                        <th>Departamento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($usuario = $resultado->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
							<td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $usuario['status'] == 'Ativo' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($usuario['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($usuario['perfil']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['departamento']); ?></td>
							<td class="actions">
								<div class="btn-group btn-group-sm" role="group">
									<a href="index.php?modulo=editar_usuario&id=<?php echo $usuario['id']; ?>" 
									   class="btn btn-light" title="Editar">
										<i class="fas fa-edit"></i>
									</a>
									<a href="javascript:void(0)" 
									   onclick="confirmarExclusao(<?php echo $usuario['id']; ?>, 'usuário')"
									   class="btn btn-light" title="Excluir">
										<i class="fas fa-trash text-danger"></i>
									</a>
								</div>
							</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php 
	$stmt->close();
	$conexao->close(); 
?>