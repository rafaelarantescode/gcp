<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();
verificarPerfil(['AprovadorN1', 'AprovadorN2']);

$conexao = conectar();
$usuario_id = $_SESSION['usuario_id'];
$perfil_usuario = $_SESSION['perfil'];
$departamento_usuario = $_SESSION['departamento'];

// Recuperar datas do filtro
$ano_atual = date('Y');
$data_inicio = isset($_SESSION['dashboard_filter_start']) 
    ? $_SESSION['dashboard_filter_start'] 
    : $ano_atual . '-01-01';
$data_fim = isset($_SESSION['dashboard_filter_end']) 
    ? $_SESSION['dashboard_filter_end'] 
    : $ano_atual . '-12-31';

// Se veio novo filtro via POST, atualizar sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $_SESSION['dashboard_filter_start'] = $data_inicio;
    $_SESSION['dashboard_filter_end'] = $data_fim;
}

// Definir condição de perfil baseada no tipo de aprovador
$perfil_condition = $perfil_usuario === 'AprovadorN1' 
    ? "AND u.perfil = 'Usuario'"  // N1 vê apenas usuários
    : "AND u.perfil IN ('Usuario', 'AprovadorN1')";  // N2 vê usuários e N1

// Query para Status dos Lançamentos
$query_status = "
    SELECT 
        COUNT(*) as total_lancamentos,
        SUM(CASE WHEN cp.status = 'Pendente' THEN 1 ELSE 0 END) as total_pendentes,
        SUM(CASE WHEN cp.status = 'Aprovado' THEN 1 ELSE 0 END) as total_aprovados,
        SUM(CASE WHEN cp.status = 'Reprovado' THEN 1 ELSE 0 END) as total_reprovados,
        SUM(CASE WHEN cp.status = 'Pendente' THEN cp.valor_total ELSE 0 END) as valor_pendentes,
        SUM(CASE WHEN cp.status = 'Aprovado' THEN cp.valor_total ELSE 0 END) as valor_aprovados,
        SUM(CASE WHEN cp.status = 'Reprovado' THEN cp.valor_total ELSE 0 END) as valor_reprovados
    FROM custos_projeto cp
    JOIN usuarios u ON cp.usuario_id = u.id
    WHERE cp.ativo = 1 
    AND (cp.usuario_id = ? OR (u.departamento = ? {$perfil_condition}))
    AND cp.data_inicio BETWEEN ? AND ?";

// Query para Status por Tipo de Origem
$query_origem = "
    SELECT 
        cp.tipo_origem,
        COUNT(*) as total,
        SUM(cp.valor_total) as valor_total
    FROM custos_projeto cp
    JOIN usuarios u ON cp.usuario_id = u.id
    WHERE cp.ativo = 1 
    AND (cp.usuario_id = ? OR (u.departamento = ? {$perfil_condition}))
    AND cp.data_inicio BETWEEN ? AND ?
    GROUP BY cp.tipo_origem";

// Query para Pendências de Aprovação
$query_pendencias = "
    SELECT 
        p.nome as projeto_nome,
        p.id_evento,
        u.nome as solicitante,
        cp.data_inicio,
        cp.data_fim,
        cp.tipo_custo,
        cp.valor_total,
        cp.created_at,
        cp.id as custo_id
    FROM custos_projeto cp
    JOIN projetos p ON cp.projeto_id = p.id
    JOIN usuarios u ON cp.usuario_id = u.id
    WHERE cp.ativo = 1 
    AND cp.status = 'Pendente'
    AND cp.aprovador_id = ?
    AND cp.data_inicio BETWEEN ? AND ?
    ORDER BY cp.created_at DESC";

// Query para TOP 5 Projetos
$query_top_projetos = "
    SELECT 
        p.nome as projeto_nome,
        p.id_evento,
        MIN(cp.data_inicio) as data_inicio,
        MAX(cp.data_fim) as data_fim,
        COUNT(DISTINCT cp.usuario_id) as total_envolvidos,
        SUM(cp.valor_total) as valor_total
    FROM custos_projeto cp
    JOIN projetos p ON cp.projeto_id = p.id
    JOIN usuarios u ON cp.usuario_id = u.id
    WHERE cp.ativo = 1 
    AND cp.status = 'Aprovado'
    AND (cp.usuario_id = ? OR (u.departamento = ? {$perfil_condition}))
    AND cp.data_inicio BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY valor_total DESC
    LIMIT 5";

// Executar queries
$stmt_status = $conexao->prepare($query_status);
$stmt_status->bind_param("isss", $usuario_id, $departamento_usuario, $data_inicio, $data_fim);
$stmt_status->execute();
$info_status = $stmt_status->get_result()->fetch_assoc();

$stmt_origem = $conexao->prepare($query_origem);
$stmt_origem->bind_param("isss", $usuario_id, $departamento_usuario, $data_inicio, $data_fim);
$stmt_origem->execute();
$result_origem = $stmt_origem->get_result();

$stmt_pendencias = $conexao->prepare($query_pendencias);
$stmt_pendencias->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
$stmt_pendencias->execute();
$result_pendencias = $stmt_pendencias->get_result();

$stmt_top_projetos = $conexao->prepare($query_top_projetos);
$stmt_top_projetos->bind_param("isss", $usuario_id, $departamento_usuario, $data_inicio, $data_fim);
$stmt_top_projetos->execute();
$result_top_projetos = $stmt_top_projetos->get_result();
?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h2">Dashboard de Aprovação</h1>
            </div>
            <div class="col-auto">
                <form id="filterForm" class="row g-3 align-items-center" method="POST">
                    <div class="col-auto">
                        <label class="col-form-label">Período:</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" class="form-control form-control-sm" 
                               id="data_inicio" name="data_inicio" 
                               value="<?php echo $data_inicio; ?>">
                    </div>
                    <div class="col-auto">
                        <label class="col-form-label">até</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" class="form-control form-control-sm" 
                               id="data_fim" name="data_fim" 
                               value="<?php echo $data_fim; ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Status dos Lançamentos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status dos Lançamentos da Equipe</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Aprovados -->
                        <div class="col-md-4">
                            <div class="card border-left-success">
                                <div class="card-body">
                                    <h6 class="card-title text-success">Aprovados</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <h4 class="mb-0"><?php echo number_format($info_status['total_aprovados'], 0, ',', '.'); ?></h4>
                                            <small class="text-muted">Quantidade</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h4 class="mb-0">R$ <?php echo number_format($info_status['valor_aprovados'], 2, ',', '.'); ?></h4>
                                            <small class="text-muted">Valor Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pendentes -->
                        <div class="col-md-4">
                            <div class="card border-left-warning">
                                <div class="card-body">
                                    <h6 class="card-title text-warning">Pendentes</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <h4 class="mb-0"><?php echo number_format($info_status['total_pendentes'], 0, ',', '.'); ?></h4>
                                            <small class="text-muted">Quantidade</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h4 class="mb-0">R$ <?php echo number_format($info_status['valor_pendentes'], 2, ',', '.'); ?></h4>
                                            <small class="text-muted">Valor Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reprovados -->
                        <div class="col-md-4">
                            <div class="card border-left-danger">
                                <div class="card-body">
                                    <h6 class="card-title text-danger">Reprovados</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <h4 class="mb-0"><?php echo number_format($info_status['total_reprovados'], 0, ',', '.'); ?></h4>
                                            <small class="text-muted">Quantidade</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h4 class="mb-0">R$ <?php echo number_format($info_status['valor_reprovados'], 2, ',', '.'); ?></h4>
                                            <small class="text-muted">Valor Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status por Tipo de Origem -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status por Tipo de Origem</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php while ($origem = $result_origem->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card border-left-primary">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo $origem['tipo_origem']; ?></h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <h4 class="mb-0"><?php echo number_format($origem['total'], 0, ',', '.'); ?></h4>
                                            <small class="text-muted">Quantidade</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h4 class="mb-0">R$ <?php echo number_format($origem['valor_total'], 2, ',', '.'); ?></h4>
                                            <small class="text-muted">Valor Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pendências de Aprovação -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pendências de Aprovação</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Projeto</th>
                                    <th>ID Evento</th>
                                    <th>Solicitante</th>
                                    <th>Período</th>
                                    <th>Tipo de Custo</th>
                                    <th class="text-end">Valor Total</th>
                                    <th>Data Solicitação</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pendencia = $result_pendencias->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pendencia['projeto_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($pendencia['id_evento']); ?></td>
                                    <td><?php echo htmlspecialchars($pendencia['solicitante']); ?></td>
                                    <td>
                                        <?php 
                                        echo date('d/m/Y', strtotime($pendencia['data_inicio'])) . ' até<br>' . 
                                             date('d/m/Y', strtotime($pendencia['data_fim']));
                                        ?>
                                    </td>
                                    <td><?php echo $pendencia['tipo_custo']; ?></td>
                                    <td class="text-end">
                                        R$ <?php echo number_format($pendencia['valor_total'], 2, ',', '.'); ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pendencia['created_at'])); ?></td>
                                    <td>
                                        <a href="index.php?modulo=editar_custo_projeto&id=<?php echo $pendencia['custo_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                           <i class="fas fa-edit me-1"></i>Avaliar
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($result_pendencias->num_rows === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Não há lançamentos pendentes de aprovação
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TOP 5 Projetos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">TOP 5 Projetos com Maior Custo</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Projeto</th>
                                    <th>ID Evento</th>
                                    <th>Período</th>
                                    <th class="text-center">Envolvidos</th>
                                    <th class="text-end">Valor Total</th>
                                    <th class="text-end">% do Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_geral = array_sum(array_column($result_top_projetos->fetch_all(MYSQLI_ASSOC), 'valor_total'));
                                $result_top_projetos->data_seek(0);
                                
                                while ($projeto = $result_top_projetos->fetch_assoc()): 
                                    $percentual = $total_geral > 0 ? ($projeto['valor_total'] / $total_geral) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($projeto['projeto_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($projeto['id_evento']); ?></td>
                                    <td>
                                        <?php 
                                        echo date('d/m/Y', strtotime($projeto['data_inicio'])) . ' até<br>' . 
                                             date('d/m/Y', strtotime($projeto['data_fim']));
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?php echo number_format($projeto['total_envolvidos'], 0, ',', '.'); ?>
                                            usuário(s)
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        R$ <?php echo number_format($projeto['valor_total'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo number_format($percentual, 1, ',', '.'); ?>%
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($result_top_projetos->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        Nenhum projeto encontrado no período
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializa tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Validação de datas do filtro
    $('#filterForm').on('submit', function(e) {
        const dataInicio = new Date($('#data_inicio').val());
        const dataFim = new Date($('#data_fim').val());

        if (dataFim < dataInicio) {
            e.preventDefault();
            alert('A data final deve ser maior ou igual à data inicial');
            return false;
        }
    });

    // Atualização automática ao mudar datas
    $('#data_inicio, #data_fim').on('change', function() {
        const dataInicio = new Date($('#data_inicio').val());
        const dataFim = new Date($('#data_fim').val());

        if (dataInicio && dataFim && dataFim >= dataInicio) {
            $('#filterForm').submit();
        }
    });
});
</script>