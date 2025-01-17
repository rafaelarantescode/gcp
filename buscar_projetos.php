<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();
$termo = $conexao->real_escape_string($_GET['term']);

$query = "SELECT id, nome, id_evento 
          FROM projetos 
          WHERE ativo = 1 
          AND (nome LIKE ? OR id_evento LIKE ?)
          ORDER BY nome
          LIMIT 20";

$stmt = $conexao->prepare($query);
$termo_like = "%{$termo}%";
$stmt->bind_param("ss", $termo_like, $termo_like);
$stmt->execute();
$resultado = $stmt->get_result();

$projetos = [];
while ($projeto = $resultado->fetch_assoc()) {
    $projetos[] = [
        'id' => $projeto['id'],
        'label' => "{$projeto['nome']} (ID Evento: {$projeto['id_evento']})",
        'value' => $projeto['nome']
    ];
}

echo json_encode($projetos);