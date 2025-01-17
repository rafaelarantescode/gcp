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

    // Verificar se cliente existe e está ativo
    $query_check = "SELECT id FROM clientes WHERE id = ? AND ativo = 1";
    $stmt_check = $conexao->prepare($query_check);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if (!$result->num_rows) {
        throw new Exception("Cliente não encontrado ou já excluído");
    }

    // Realizar exclusão lógica
    $query = "UPDATE clientes SET ativo = 0 WHERE id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao excluir cliente");
    }

    registrarLog($_SESSION['usuario_id'], "Excluiu cliente ID {$id}");
    $_SESSION['success_message'] = "Cliente excluído com sucesso!";

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erro ao excluir cliente: " . $e->getMessage();
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conexao)) $conexao->close();
    header("Location: index.php?modulo=listar_clientes");
    exit();
}
?>