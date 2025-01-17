<?php
    require_once 'session.php';
    require_once 'db_connection.php';
    verificarLogin();
    verificarPerfil(['Administrador', 'AprovadorN2']);
    try {
        if (!isset($_GET['id'])) {
            throw new Exception("ID não especificado");
        }

        $id = intval($_GET['id']);
        $conexao = conectar();

        // Não permitir excluir o próprio usuário
        if ($id == $_SESSION['usuario_id']) {
            throw new Exception("Não é possível excluir o próprio usuário");
        }
        
        // Verificar permissões para AprovadorN2
        if ($_SESSION['perfil'] === 'AprovadorN2') {
            $query_check_perfil = "SELECT perfil FROM usuarios WHERE id = ? AND ativo = 1";
            $stmt_check_perfil = $conexao->prepare($query_check_perfil);
            $stmt_check_perfil->bind_param("i", $id);
            $stmt_check_perfil->execute();
            $result_perfil = $stmt_check_perfil->get_result();
            $usuario_perfil = $result_perfil->fetch_assoc()['perfil'];

            if ($usuario_perfil === 'Administrador') {
                throw new Exception("Você não tem permissão para excluir um administrador");
            }
        }

        // Verificar se usuário existe e está ativo
        $query_check = "SELECT id FROM usuarios WHERE id = ? AND ativo = 1";
        $stmt_check = $conexao->prepare($query_check);
        if (!$stmt_check) {
            throw new Exception("Erro ao preparar consulta: " . $conexao->error);
        }

        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if (!$result->num_rows) {
            throw new Exception("Usuário não encontrado ou já excluído");
        }

        // Realizar exclusão lógica
        $query = "UPDATE usuarios SET ativo = 0 WHERE id = ?";
        $stmt = $conexao->prepare($query);
        if (!$stmt) {
            throw new Exception("Erro ao preparar exclusão");
        }

        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao excluir usuário");
        }

        registrarLog($_SESSION['usuario_id'], "Excluiu usuário ID {$id}");
        $_SESSION['success_message'] = "Usuário excluído com sucesso!";

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erro ao excluir usuário: " . $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($conexao)) $conexao->close();
        header("Location: index.php?modulo=listar_usuarios");
        exit();
    }
?>