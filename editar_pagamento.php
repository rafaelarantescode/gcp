<?php
require_once 'session.php';
require_once 'db_connection.php';
require_once 'controle_pagamento.php';
verificarLogin();

$conexao = conectar();
$perfil_usuario = $_SESSION['perfil'];

try {
    if (!isset($_GET['id'])) {
        throw new Exception("ID do pagamento não especificado");
    }

    $id = intval($_GET['id']);

    // Buscar dados do pagamento
    $query = "SELECT p.*, 
              u.nome as solicitante_nome
              FROM pagamentos p
              LEFT JOIN usuarios u ON p.solicitante_id = u.id
              WHERE p.id = ? AND p.ativo = 1";

    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if (!$resultado->num_rows) {
        throw new Exception("Pagamento não encontrado");
    }

    $pagamento = $resultado->fetch_assoc();

    // Buscar todos os custos disponíveis
    $query_custos = "SELECT cp.*, p.nome as projeto_nome, p.id_evento, 
                     CASE WHEN pc.pagamento_id IS NOT NULL THEN 1 ELSE 0 END as selecionado
                     FROM custos_projeto cp
                     JOIN projetos p ON cp.projeto_id = p.id
                     LEFT JOIN pagamentos_custos pc ON cp.id = pc.custo_id AND pc.pagamento_id = ?
                     WHERE cp.usuario_id = ? 
                     AND cp.status = 'Aprovado'
                     AND cp.ativo = 1
                     AND MONTH(cp.data_inicio) = ?
                     AND YEAR(cp.data_inicio) = ?
                     ORDER BY cp.data_inicio ASC";

    $stmt = $conexao->prepare($query_custos);
    $stmt->bind_param("iiii", $id, $pagamento['solicitante_id'], $pagamento['mes'], $pagamento['ano']);
    $stmt->execute();
    $custos_disponiveis = $stmt->get_result();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conexao->begin_transaction();

        if ($pagamento['status'] === 'Pendente') {
            // Atualizar custos vinculados
            $custos_selecionados = isset($_POST['custos']) ? $_POST['custos'] : [];
            
            if (!empty($custos_selecionados)) {
                // Calcular novo valor total
                $placeholders = str_repeat('?,', count($custos_selecionados) - 1) . '?';
                $query_valor = "SELECT SUM(valor_total) as total FROM custos_projeto WHERE id IN ($placeholders)";
                $stmt = $conexao->prepare($query_valor);
                $stmt->bind_param(str_repeat('i', count($custos_selecionados)), ...$custos_selecionados);
                $stmt->execute();
                $novo_valor_total = $stmt->get_result()->fetch_assoc()['total'];

                // Atualizar pagamento
                $ordem_pagamento = trim($_POST['ordem_pagamento']);
                $query = "UPDATE pagamentos SET 
                        valor_total = ?,
                        ordem_pagamento = ? 
                        WHERE id = ?";
                $stmt = $conexao->prepare($query);
                $stmt->bind_param("dsi", $novo_valor_total, $ordem_pagamento, $id);
                $stmt->execute();
            } else {
                $query = "UPDATE pagamentos SET 
                         valor_total = ?,
                         ordem_pagamento = ?,
                         updated_at = NOW()
                         WHERE id = ?";
                $stmt = $conexao->prepare($query);
                $stmt->bind_param("dsi", $novo_valor_total, $ordem_pagamento, $id);
                $stmt->execute();
            }

            // Remover vínculos existentes
            $query = "DELETE FROM pagamentos_custos WHERE pagamento_id = ?";
            $stmt = $conexao->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Inserir novos vínculos
            if (!empty($custos_selecionados)) {
                $query = "INSERT INTO pagamentos_custos (pagamento_id, custo_id) VALUES (?, ?)";
                $stmt = $conexao->prepare($query);
                foreach ($custos_selecionados as $custo_id) {
                    $stmt->bind_param("ii", $id, $custo_id);
                    $stmt->execute();
                }
            }
        } else if ($pagamento['status'] === 'Aprovado') {
            if (empty($_FILES['nota_fiscal']['tmp_name'])) {
                throw new Exception("A nota fiscal é obrigatória");
            }

            // Upload da nota fiscal
            $upload_dir = 'uploads/notas_fiscais/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['nota_fiscal']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                throw new Exception("Tipo de arquivo não permitido");
            }

            $nota_fiscal = uniqid() . '_' . time() . '.' . $ext;
            $arquivo = $upload_dir . $nota_fiscal;

            if (!move_uploaded_file($_FILES['nota_fiscal']['tmp_name'], $arquivo)) {
                throw new Exception("Erro ao fazer upload da nota fiscal");
            }

            $query = "UPDATE pagamentos SET 
                    status = 'Nota Fiscal',
                    nota_fiscal = ?,
                    data_nf = NOW()
                    WHERE id = ?";
                    
            $stmt = $conexao->prepare($query);
            $stmt->bind_param("si", $nota_fiscal, $id);
            $stmt->execute();
        }

        $conexao->commit();
        $_SESSION['success_message'] = "Pagamento atualizado com sucesso!";
        header("Location: index.php?modulo=listar_pagamentos");
        exit();

    }

} catch (Exception $e) {
    if (isset($conexao) && $conexao->errno == 0) {
        $conexao->rollback();
    }
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: index.php?modulo=listar_pagamentos");
    exit();
}
?>

<!-- HTML -->
<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Editar Pagamento</h1>
            <p class="text-muted mb-0">
                Solicitante: <?php echo htmlspecialchars($pagamento['solicitante_nome']); ?> - 
                Período: <?php echo str_pad($pagamento['mes'], 2, '0', STR_PAD_LEFT) . '/' . $pagamento['ano']; ?>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?php if ($pagamento['status'] !== 'Pago'): ?>
                    <div class="mb-3">
                        <label for="ordem_pagamento" class="form-label">Ordem de Pagamento</label>
                        <input type="text" class="form-control" id="ordem_pagamento" 
                               name="ordem_pagamento" 
                               value="<?php echo htmlspecialchars($pagamento['ordem_pagamento']); ?>" 
                               required>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="50px">
                                        <input type="checkbox" class="form-check-input" id="check-all">
                                    </th>
                                    <th>Projeto</th>
                                    <th>ID Evento</th>
                                    <th>Período</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                while ($custo = $custos_disponiveis->fetch_assoc()): 
                                    $total += $custo['selecionado'] ? $custo['valor_total'] : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input" 
                                                   name="custos[]" 
                                                   value="<?php echo $custo['id']; ?>" 
                                                   <?php echo $custo['selecionado'] ? 'checked' : ''; ?>
                                                   data-valor="<?php echo $custo['valor_total']; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($custo['projeto_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($custo['id_evento']); ?></td>
                                        <td>
                                            <?php 
                                            echo date('d/m/Y', strtotime($custo['data_inicio'])) . ' até ' . 
                                                 date('d/m/Y', strtotime($custo['data_fim'])); 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $custo['tipo_custo']; ?></span>
                                            <small class="text-muted d-block">
                                                <?php echo $custo['tipo_origem']; ?>
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            R$ <?php echo number_format($custo['valor_total'], 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end">Total:</td>
                                    <td class="text-end fw-bold" id="total-valor">
                                        R$ <?php echo number_format($total, 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($pagamento['status'] === 'Aprovado'): ?>
                    <div class="mb-3">
                        <label for="nota_fiscal" class="form-label">Nota Fiscal</label>
                        <input type="file" class="form-control" id="nota_fiscal" 
                               name="nota_fiscal" required>
                        <div class="form-text">Formatos aceitos: PDF, JPG, JPEG, PNG</div>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?modulo=listar_pagamentos" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('check-all');
    const custoChecks = document.querySelectorAll('input[name="custos[]"]');
    const totalValor = document.getElementById('total-valor');
    
    function atualizarTotal() {
        let total = 0;
        custoChecks.forEach(check => {
            if (check.checked) {
                total += parseFloat(check.dataset.valor);
            }
        });
        totalValor.textContent = `R$ ${total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            custoChecks.forEach(check => {
                check.checked = this.checked;
                atualizarTotal();
            });
        });
    }

    custoChecks.forEach(check => {
        check.addEventListener('change', atualizarTotal);
    });
});
</script>