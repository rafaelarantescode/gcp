<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();
$query = "SELECT * FROM prestadores WHERE ativo = 1";

$stmt = $conexao->prepare($query);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="h2">Prestadores</h1>
        <a href="index.php?modulo=criar_prestador" class="btn btn-primary btn-novo">
            <i class="fas fa-plus"></i> Novo Prestador
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="tabelaPrestadores" class="datatable table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>CPF</th>
                        <th>Celular</th>
                        <th>Cidade/Estado</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($prestador = $resultado->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo $prestador['id']; ?></td>
                            <td><?php echo htmlspecialchars($prestador['nome']); ?></td>
                            <td><?php echo htmlspecialchars($prestador['email']); ?></td>
                            <td><?php echo \substr_replace($prestador['cpf'], '***', 3, 6); ?></td>
                            <td><?php echo htmlspecialchars($prestador['celular']); ?></td>
                            <td><?php echo htmlspecialchars($prestador['cidade'] . ' - ' . $prestador['estado']); ?></td>
                            <td>
                                <span class="badge <?php echo $prestador['blacklist'] ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $prestador['blacklist'] ? 'Blacklist' : 'Ativo'; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?modulo=editar_prestador&id=<?php echo $prestador['id']; ?>" 
                                       class="btn btn-light" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" 
                                       onclick="confirmarExclusao(<?php echo $prestador['id']; ?>, 'prestador')"
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