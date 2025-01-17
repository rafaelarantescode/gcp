<?php
    session_start();
    date_default_timezone_set('America/Sao_Paulo');
    require_once 'db_connection.php';

    function verificarLogin() {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: login.php");
            exit();
        }
    }

    function verificarPerfil($perfisPermitidos) {
        if (!in_array($_SESSION['perfil'], $perfisPermitidos)) {
            $_SESSION['error_message'] = "Você não tem permissão para acessar esta área.";
            header("Location: index.php");
            exit();
        }
    }

    function verificarPrimeiroAcesso() {
        if (!isset($_GET['modulo']) || $_GET['modulo'] !== 'alterar_senha') {
            $conexao = conectar();
            $query = "SELECT primeiro_acesso FROM usuarios WHERE id = ?";
            $stmt = $conexao->prepare($query);
            $stmt->bind_param("i", $_SESSION['usuario_id']);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario = $resultado->fetch_assoc();

            if ($usuario['primeiro_acesso']) {
                header("Location: index.php?modulo=alterar_senha&primeiro_acesso=1");
                exit();
            }
            
            // Fechar conexões
            $stmt->close();
            $conexao->close();
        }
    }

    function isAdmin() {
        return !isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'Usuario';
    }
?>