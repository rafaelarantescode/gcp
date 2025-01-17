<?php
require_once 'session.php';
require_once 'db_connection.php';

function verificarPagamentoExistente($conexao, $solicitante_id, $mes, $ano) {
    $query = "SELECT id FROM pagamentos 
              WHERE solicitante_id = ? 
              AND mes = ? 
              AND ano = ? 
              AND ativo = 1";
    
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("iii", $solicitante_id, $mes, $ano);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

function aprovarPagamento($pagamento_id) {
    $conexao = conectar();
    
    try {
        $conexao->begin_transaction();

        // Verifica perfil e existência do pagamento
        if ($_SESSION['perfil'] !== 'Administrador') {
            throw new Exception("Apenas administradores podem aprovar pagamentos");
        }

        $query = "UPDATE pagamentos SET 
                  status = 'Aprovado',
                  data_aprovacao = NOW(),
                  aprovador_id = ?
                  WHERE id = ? AND status = 'Pendente'";
        
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("ii", $_SESSION['usuario_id'], $pagamento_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao aprovar pagamento");
        }

        $conexao->commit();
        registrarLog($_SESSION['usuario_id'], "Aprovou pagamento ID {$pagamento_id}");
        return true;

    } catch (Exception $e) {
        $conexao->rollback();
        throw $e;
    }
}

function marcarComoPago($pagamento_id) {
    $conexao = conectar();
    
    try {
        $conexao->begin_transaction();

        if ($_SESSION['perfil'] !== 'Administrador') {
            throw new Exception("Apenas administradores podem marcar como pago");
        }

        $query = "UPDATE pagamentos SET 
                  status = 'Pago',
                  data_pagamento = NOW()
                  WHERE id = ? AND status = 'Nota Fiscal'";
        
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $pagamento_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao marcar pagamento como pago");
        }

        $conexao->commit();
        registrarLog($_SESSION['usuario_id'], "Marcou pagamento ID {$pagamento_id} como pago");
        return true;

    } catch (Exception $e) {
        $conexao->rollback();
        throw $e;
    }
}

function verificarPermissaoPagamento($conexao, $pagamento_id = null, $tipo = 'visualizar') {
    $perfil = $_SESSION['perfil'];
    
    if ($perfil !== 'Administrador') {
        return false;
    }

    if ($pagamento_id) {
        $query = "SELECT status FROM pagamentos WHERE id = ? AND ativo = 1";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $pagamento_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            return false;
        }

        $pagamento = $resultado->fetch_assoc();

        // Verifica se pode editar baseado no status
        if ($tipo === 'editar' && $pagamento['status'] === 'Aprovado') {
            return false;
        }
    }

    return true;
}
?>