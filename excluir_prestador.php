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

    // Verificar se prestador existe e está ativo
    $query_check = "SELECT id FROM prestadores WHERE id = ? AND ativo = 1";
    $stmt_check = $conexao->prepare($query_check);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if (!$result->num_rows) {
        throw new Exception("Prestador não encontrado ou já excluído");
    }

    // Realizar exclusão lógica
    $query = "UPDATE prestadores SET ativo = 0 WHERE id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao excluir prestador");
    }

    registrarLog($_SESSION['usuario_id'], "Excluiu prestador ID {$id}");
    $_SESSION['success_message'] = "Prestador excluído com sucesso!";

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erro ao excluir prestador: " . $e->getMessage();
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conexao)) $conexao->close();
    header("Location: index.php?modulo=listar_prestadores");
    exit();
}
?>