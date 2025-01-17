<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();
verificarPerfil(['Administrador']);

$conexao = conectar();

// Recuperar datas do filtro
$ano_atual = date('Y');
$mes_atual = date('n');

// Se veio novo filtro via POST, atualizar sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['solicitantes_filter_month'] = $_POST['mes'];
    $_SESSION['solicitantes_filter_year'] = $_POST['ano'];
} 

// Usar valores da sessão ou padrões
$mes_filtro = isset($_SESSION['solicitantes_filter_month']) ? $_SESSION['solicitantes_filter_month'] : $mes_atual;
$ano_filtro = isset($_SESSION['solicitantes_filter_year']) ? $_SESSION['solicitantes_filter_year'] : $ano_atual;

// Query para buscar os totais por tipo de origem
$query_totais = "
    SELECT 
        cp.tipo_origem,
        SUM(cp.valor_total) as valor_total
    FROM custos_projeto cp
    WHERE cp.ativo = 1 
    AND cp.status = 'Aprovado'
    AND MONTH(cp.data_inicio) = ?
    AND YEAR(cp.data_inicio) = ?
    GROUP BY cp.tipo_origem";

$stmt_totais = $conexao->prepare($query_totais);
$stmt_totais->bind_param("ii", $mes_filtro, $ano_filtro);
$stmt_totais->execute();
$resultado_totais = $stmt_totais->get_result();

$totais = [
    'Previsto em Proposta' => 0,
    'Venda Adicional' => 0,
    'Custo Operacional' => 0
];

while ($total = $resultado_totais->fetch_assoc()) {
    $totais[$total['tipo_origem']] = $total['valor_total'];
}

// Query principal
$query = "
WITH custos_agrupados AS (
    SELECT 
        cp.usuario_id,
        COUNT(cp.id) as total_custos,
        SUM(CASE WHEN cp.tipo_custo = 'Horas' THEN cp.horas_trabalhadas ELSE 0 END) as total_horas,
        SUM(CASE WHEN cp.tipo_custo = 'Diaria' THEN 1 ELSE 0 END) as total_diarias,
        SUM(cp.valor_total) as valor_total,
        p.id as pagamento_id
    FROM custos_projeto cp
    JOIN usuarios u ON cp.usuario_id = u.id
    LEFT JOIN pagamentos p ON u.id = p.solicitante_id 
        AND p.mes = ? AND p.ano = ? AND p.ativo = 1
    LEFT JOIN pagamentos_custos pc ON p.id = pc.pagamento_id
    WHERE cp.ativo = 1 
    AND cp.status = 'Aprovado'
    AND (
        (p.id IS NULL AND MONTH(cp.data_inicio) = ? AND YEAR(cp.data_inicio) = ?)
        OR 
        (p.id IS NOT NULL AND cp.id = pc.custo_id)
    )
    GROUP BY cp.usuario_id, p.id
)
SELECT 
    u.id as solicitante_id,
    u.nome as solicitante_nome,
    u.departamento,
    ca.total_custos,
    ca.total_horas,
    ca.total_diarias,
    ca.valor_total,
    COALESCE(p.status, 'Pendente Geração') as status_pagamento,
    p.id as pagamento_id
FROM custos_agrupados ca
JOIN usuarios u ON ca.usuario_id = u.id
LEFT JOIN pagamentos p ON ca.pagamento_id = p.id
WHERE u.ativo = 1
ORDER BY u.departamento, u.nome;";

$stmt = $conexao->prepare($query);
$stmt->bind_param("iiii", $mes_filtro, $ano_filtro, $mes_filtro, $ano_filtro);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="h2">Controle de Pagamentos por Solicitante</h1>
        <form id="filterForm" class="d-flex gap-2" method="POST">
            <div class="input-group">
                <select class="form-select form-select-sm" id="mes" name="mes" style="width: 100px;">
                    <?php for($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $mes_filtro == $i ? 'selected' : ''; ?>>
                            <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select class="form-select form-select-sm" id="ano" name="ano" style="width: 100px;">
                    <?php for($i = 2024; $i <= $ano_atual; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $ano_filtro == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </form>
    </div>
    <!-- Cards de Totais -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-success">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-0">Previsto em Proposta</h6>
                            <h5 class="mb-0">R$ <?php echo number_format($totais['Previsto em Proposta'], 2, ',', '.'); ?></h5>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-file-invoice fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-warning">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-0">Venda Adicional</h6>
                            <h5 class="mb-0">R$ <?php echo number_format($totais['Venda Adicional'], 2, ',', '.'); ?></h5>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-plus-circle fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-info">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-0">Custo Operacional</h6>
                            <h5 class="mb-0">R$ <?php echo number_format($totais['Custo Operacional'], 2, ',', '.'); ?></h5>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-cogs fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Indicador do período -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-filter me-2"></i>
        Visualizando solicitantes com custos em <?php echo str_pad($mes_filtro, 2, '0', STR_PAD_LEFT) . "/" . $ano_filtro; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="tabelaSolicitantes" class="datatable table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Solicitante</th>
                        <th class="text-center">Custos</th>
                        <th class="text-center">Total Horas</th>
                        <th class="text-center">Total Diárias</th>
                        <th class="text-end">Valor Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['solicitante_nome']); ?></td>
                            <td class="text-center">
                                <span class="badge bg-info">
                                    <?php echo number_format($row['total_custos'], 0, ',', '.'); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php 
                                if ($row['total_horas'] > 0) {
                                    $horas = floor($row['total_horas']);
                                    $minutos = round(($row['total_horas'] - $horas) * 60);
                                    echo "<span class='badge bg-primary'>{$horas}:{$minutos}h</span>";
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                if ($row['total_diarias'] > 0) {
                                    echo "<span class='badge bg-secondary'>" . 
                                         number_format($row['total_diarias'], 0, ',', '.') . 
                                         " diária(s)</span>";
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>
                            <td class="text-end">
                                R$ <?php echo number_format($row['valor_total'], 2, ',', '.'); ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $statusClass = [
                                        'Pendente Geração' => 'bg-warning',
                                        'Pendente' => 'bg-warning',
                                        'Aprovado' => 'bg-primary',
                                        'Nota Fiscal' => 'bg-info',
                                        'Pago' => 'bg-success'
                                    ][$row['status_pagamento']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($row['status_pagamento']); ?>
                                </span>
                            </td>
                           <!-- Em listar_solicitantes_custos.php, na coluna de ações -->
                            <td class="actions">
                                <div class="btn-group btn-group-sm">
                                    <?php if (!isset($row['pagamento_id'])): ?>
                                        <!-- Criar Novo Pagamento -->
                                        <a href="index.php?modulo=criar_pagamento&solicitante_id=<?php echo $row['solicitante_id']; ?>&mes=<?php echo $mes_filtro; ?>&ano=<?php echo $ano_filtro; ?>" 
                                        class="btn btn-light" title="Criar Pagamento">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                    <?php else: ?>
                                        <!-- Editar Pagamento -->
                                        <a href="index.php?modulo=editar_pagamento&id=<?php echo $row['pagamento_id']; ?>" 
                                            class="btn btn-light" title="Editar Pagamento">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php if ($row['status_pagamento'] === 'Pendente'): ?>

                                            <!-- Aprovar Pagamento -->
                                            <a href="javascript:void(0)" 
                                            onclick="aprovarPagamento(<?php echo $row['pagamento_id']; ?>)"
                                            class="btn btn-light" title="Aprovar Pagamento">
                                                <i class="fas fa-check text-success"></i>
                                            </a>
                                        <?php elseif ($row['status_pagamento'] === 'Aprovado'): ?>
                                            <!-- Anexar Nota Fiscal -->
                                            <a href="index.php?modulo=editar_pagamento&id=<?php echo $row['pagamento_id']; ?>&acao=nota_fiscal" 
                                            class="btn btn-light" title="Anexar Nota Fiscal">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                        <?php elseif ($row['status_pagamento'] === 'Nota Fiscal'): ?>
                                            <!-- Marcar como Pago -->
                                            <a href="javascript:void(0)" 
                                            onclick="marcarComoPago(<?php echo $row['pagamento_id']; ?>)"
                                            class="btn btn-light" title="Marcar como Pago">
                                                <i class="fas fa-dollar-sign text-success"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação e atualização automática do filtro
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const mes = document.getElementById('mes').value;
            const ano = document.getElementById('ano').value;

            if (!mes || !ano) {
                e.preventDefault();
                alert('Por favor, selecione mês e ano para filtrar');
                return false;
            }
        });

        // Atualização automática ao mudar seleção
        document.getElementById('mes').addEventListener('change', function() {
            if (document.getElementById('ano').value) {
                filterForm.submit();
            }
        });

        document.getElementById('ano').addEventListener('change', function() {
            if (document.getElementById('mes').value) {
                filterForm.submit();
            }
        });
    }
});

function aprovarPagamento(id) {
    if (confirm('Confirma a aprovação deste pagamento?')) {
        fetch('aprovar_pagamento.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao aprovar pagamento');
                }
            })
            .catch(error => {
                alert('Erro ao processar solicitação');
                console.error(error);
            });
    }
}

function marcarComoPago(id) {
    if (confirm('Confirma marcar este pagamento como pago?')) {
        fetch('marcar_como_pago.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao marcar pagamento como pago');
                }
            })
            .catch(error => {
                alert('Erro ao processar solicitação');
                console.error(error);
            });
    }
}
</script>
<?php
$stmt->close();
$conexao->close();
?>