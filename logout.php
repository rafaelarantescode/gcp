<?php
session_start();
require_once 'db_connection.php';

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    registrarLog($usuario_id, 'Logout realizado');
} else {
    registrarLog(null, 'Tentativa de logout sem sessão ativa');
}

session_destroy();
header("Location: login.php");
exit();
?>