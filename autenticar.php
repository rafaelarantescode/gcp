<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conexao = conectar();
        
        $email = $conexao->real_escape_string($_POST['email']);
        $senha = $_POST['senha'];

        $query = "SELECT id, nome, email, senha, perfil, departamento, primeiro_acesso 
                FROM usuarios 
                WHERE email = ? 
                AND status = 'Ativo' 
                AND ativo = 1";
        $stmt = $conexao->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            
            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['perfil'] = $usuario['perfil'];
                $_SESSION['departamento'] = $usuario['departamento'];
                
                // Verifica se é primeiro acesso
                if ($usuario['primeiro_acesso']) {
                    header("Location: index.php?modulo=alterar_senha&primeiro_acesso=1");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                throw new Exception("Senha incorreta");
            }
        } else {
            throw new Exception("Usuário não encontrado ou inativo");
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erro no login: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
}

header("Location: login.php");
exit();
?>