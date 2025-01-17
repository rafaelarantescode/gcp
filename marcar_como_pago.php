// marcar_como_pago.php
<?php
require_once 'controle_pagamento.php';

header('Content-Type: application/json');

try {
    $id = intval($_GET['id']);
    
    if (marcarComoPago($id)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Erro ao marcar pagamento como pago");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}