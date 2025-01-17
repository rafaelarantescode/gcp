<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();
$query = "SELECT id, nome, contato, status FROM clientes WHERE ativo = 1";

$stmt = $conexao->prepare($query);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="h2">Clientes</h1>
        <a href="index.php?modulo=criar_cliente" class="btn btn-primary btn-novo">
            <i class="fas fa-plus"></i> Novo Cliente
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="tabelaClientes" class="datatable table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Contato</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cliente = $resultado->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo $cliente['id']; ?></td>
                            <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['contato']); ?></td>
                            <td>
                                <span class="badge <?php echo $cliente['status'] == 'Ativo' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($cliente['status']); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?modulo=editar_cliente&id=<?php echo $cliente['id']; ?>" 
                                       class="btn btn-light" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" 
                                       onclick="confirmarExclusao(<?php echo $cliente['id']; ?>, 'cliente')"
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