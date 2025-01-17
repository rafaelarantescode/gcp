<?php
require_once 'session.php';
require_once 'db_connection.php';
require_once 'controle_pagamento.php';
verificarLogin();
verificarPerfil(['Administrador']);

$conexao = conectar();

// Recuperar parâmetros
$solicitante_id = isset($_GET['solicitante_id']) ? intval($_GET['solicitante_id']) : 0;
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');

// Buscar informações do solicitante
$query_solicitante = "SELECT nome FROM usuarios WHERE id = ?";
$stmt = $conexao->prepare($query_solicitante);
$stmt->bind_param("i", $solicitante_id);
$stmt->execute();
$solicitante = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $ordem_pagamento = trim($_POST['ordem_pagamento']);
        $custos_selecionados = isset($_POST['custos']) ? $_POST['custos'] : [];

        // Validações
        if (empty($ordem_pagamento)) {
            throw new Exception("Ordem de pagamento é obrigatória");
        }
        if (empty($custos_selecionados)) {
            throw new Exception("Selecione pelo menos um custo");
        }

        // Verificar se já existe pagamento
        if (verificarPagamentoExistente($conexao, $solicitante_id, $mes, $ano)) {
            throw new Exception("Já existe um pagamento para este solicitante neste período");
        }

        // Calcular valor total
        $placeholders = str_repeat('?,', count($custos_selecionados) - 1) . '?';
        $query_valor = "SELECT SUM(valor_total) as total FROM custos_projeto 
                       WHERE id IN ($placeholders) AND status = 'Aprovado'";
        $params = $custos_selecionados;
        $stmt = $conexao->prepare($query_valor);
        $stmt->bind_param(str_repeat('i', count($custos_selecionados)), ...$params);
        $stmt->execute();
        $valor_total = $stmt->get_result()->fetch_assoc()['total'];

        $conexao->begin_transaction();

        // Criar pagamento
        $query = "INSERT INTO pagamentos (
            solicitante_id, mes, ano, valor_total, ordem_pagamento, status
        ) VALUES (?, ?, ?, ?, ?, 'Pendente')";

        $stmt = $conexao->prepare($query);
        $stmt->bind_param("iiids", 
            $solicitante_id, $mes, $ano, $valor_total, $ordem_pagamento
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao criar pagamento");
        }

        $pagamento_id = $conexao->insert_id;

        // Vincular custos
        $query_custos = "INSERT INTO pagamentos_custos (pagamento_id, custo_id) VALUES (?, ?)";
        $stmt = $conexao->prepare($query_custos);
        foreach ($custos_selecionados as $custo_id) {
            $stmt->bind_param("ii", $pagamento_id, $custo_id);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao vincular custos");
            }
        }

        $conexao->commit();
        registrarLog($_SESSION['usuario_id'], "Criou pagamento ID {$pagamento_id}");
        $_SESSION['success_message'] = "Pagamento criado com sucesso!";
        header("Location: index.php?modulo=listar_pagamentos");
        exit();

    } catch (Exception $e) {
        if (isset($conexao) && $conexao->connect_errno == 0) {
            $conexao->rollback();
        }
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: index.php?modulo=criar_pagamento&solicitante_id=" . $solicitante_id . 
               "&mes=" . $mes . "&ano=" . $ano);
        exit();
    }
}

// Buscar custos disponíveis
$query_custos = "SELECT cp.*, p.nome as projeto_nome
                 FROM custos_projeto cp
                 JOIN projetos p ON cp.projeto_id = p.id
                 WHERE cp.usuario_id = ? 
                 AND cp.status = 'Aprovado'
                 AND MONTH(cp.data_inicio) = ?
                 AND YEAR(cp.data_inicio) = ?
                 AND cp.id NOT IN (SELECT custo_id FROM pagamentos_custos)
                 AND cp.ativo = 1
                 ORDER BY cp.data_inicio ASC";

$stmt = $conexao->prepare($query_custos);
$stmt->bind_param("iii", $solicitante_id, $mes, $ano);
$stmt->execute();
$custos = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Criar Pagamento</h1>
            <p class="text-muted mb-0">
                Solicitante: <strong><?php echo htmlspecialchars($solicitante['nome']); ?></strong>
            </p>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Criando pagamento para <strong><?php echo htmlspecialchars($solicitante['nome']); ?></strong> 
        referente a <?php echo str_pad($mes, 2, '0', STR_PAD_LEFT) . '/' . $ano; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="index.php?modulo=criar_pagamento&solicitante_id=<?php echo $solicitante_id; ?>&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>" 
                  method="post" class="needs-validation" novalidate>

                <div class="mb-3">
                    <label for="ordem_pagamento" class="form-label">Ordem de Pagamento</label>
                    <input type="text" class="form-control" id="ordem_pagamento" name="ordem_pagamento" required>
                    <div class="invalid-feedback">Por favor, informe a ordem de pagamento.</div>
                </div>

                <div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th width="50px">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="check-all">
                    </div>
                </th>
                <th>Projeto</th>
                <th>Período</th>
                <th>Tipo</th>
                <th>Justificativa</th>
                <th>Comentário Aprovador</th>
                <th class="text-end">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total = 0;
            while ($custo = $custos->fetch_assoc()): 
                $total += $custo['valor_total'];
            ?>
                <tr>
                    <td>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="custos[]" 
                                   value="<?php echo $custo['id']; ?>">
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($custo['projeto_nome']); ?></td>
                    <td>
                        <?php 
                        echo date('d/m/Y', strtotime($custo['data_inicio'])) . ' até ' . 
                             date('d/m/Y', strtotime($custo['data_fim'])); 
                        ?>
                    </td>
                    <td>
                        <span class="badge bg-info"><?php echo $custo['tipo_custo']; ?></span>
                        <small class="text-muted d-block"><?php echo $custo['tipo_origem']; ?></small>
                    </td>
                    <td>
                        <?php if (strlen($custo['justificativa']) > 50): ?>
                            <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($custo['justificativa']); ?>">
                                <?php echo htmlspecialchars(substr($custo['justificativa'], 0, 50)) . '...'; ?>
                            </span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($custo['justificativa']); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($custo['comentario_aprovador'])): ?>
                            <?php if (strlen($custo['comentario_aprovador']) > 50): ?>
                                <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($custo['comentario_aprovador']); ?>">
                                    <?php echo htmlspecialchars(substr($custo['comentario_aprovador'], 0, 50)) . '...'; ?>
                                </span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($custo['comentario_aprovador']); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end" data-valor="<?php echo $custo['valor_total']; ?>">
                        R$ <?php echo number_format($custo['valor_total'], 2, ',', '.'); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr class="table-light">
                <td colspan="6" class="text-end">Total Disponível:</td>
                <td class="text-end fw-bold">
                    R$ <?php echo number_format($total, 2, ',', '.'); ?>
                </td>
            </tr>
            <tr class="table-light">
                <td colspan="6" class="text-end">Total Selecionado:</td>
                <td class="text-end fw-bold">
                    <span id="total-selecionado">R$ 0,00</span>
                </td>
            </tr>
        </tfoot>
    </table>
         </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?modulo=listar_pagamentos" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Criar Pagamento
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
    const totalElement = document.getElementById('total-selecionado');

    function atualizarTotal() {
        let total = 0;
        custoChecks.forEach(check => {
            if (check.checked) {
                const valor = parseFloat(check.closest('tr').querySelector('[data-valor]').dataset.valor);
                total += valor;
            }
        });

        totalElement.textContent = total.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    // Handler para checkbox principal
    checkAll?.addEventListener('change', function() {
        custoChecks.forEach(check => check.checked = this.checked);
        atualizarTotal();
    });

    // Handler para checkboxes individuais
    custoChecks.forEach(check => {
        check.addEventListener('change', function() {
            const todosMarcados = Array.from(custoChecks).every(c => c.checked);
            const algunsMarcados = Array.from(custoChecks).some(c => c.checked);
            
            if (checkAll) {
                checkAll.checked = todosMarcados;
                checkAll.indeterminate = algunsMarcados && !todosMarcados;
            }
            
            atualizarTotal();
        });
    });
});
</script>