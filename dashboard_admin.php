<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();
verificarPerfil(['Administrador']);

$conexao = conectar();

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

// Query para Status Geral dos Lançamentos
$query_status = "
    SELECT 
        COUNT(*) as total_lancamentos,
        SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as total_pendentes,
        SUM(CASE WHEN status = 'Aprovado' THEN 1 ELSE 0 END) as total_aprovados,
        SUM(CASE WHEN status = 'Reprovado' THEN 1 ELSE 0 END) as total_reprovados,
        SUM(CASE WHEN status = 'Pendente' THEN valor_total ELSE 0 END) as valor_pendentes,
        SUM(CASE WHEN status = 'Aprovado' THEN valor_total ELSE 0 END) as valor_aprovados,
        SUM(CASE WHEN status = 'Reprovado' THEN valor_total ELSE 0 END) as valor_reprovados
    FROM custos_projeto 
    WHERE ativo = 1 
    AND data_inicio BETWEEN ? AND ?";

// Query para Status por Departamento
$query_departamentos = "
    SELECT 
        u.departamento,
        COUNT(*) as total_lancamentos,
        COUNT(DISTINCT cp.usuario_id) as total_usuarios,
        SUM(CASE WHEN cp.status = 'Aprovado' THEN cp.valor_total ELSE 0 END) as valor_aprovado,
        SUM(CASE WHEN cp.status = 'Pendente' THEN cp.valor_total ELSE 0 END) as valor_pendente,
        SUM(CASE WHEN cp.status = 'Reprovado' THEN cp.valor_total ELSE 0 END) as valor_reprovado
    FROM custos_projeto cp
    JOIN usuarios u ON cp.usuario_id = u.id
    WHERE cp.ativo = 1 
    AND cp.data_inicio BETWEEN ? AND ?
    GROUP BY u.departamento
    ORDER BY valor_aprovado DESC";

// Query para Status por Tipo de Origem
$query_origem = "
    SELECT 
        tipo_origem,
        COUNT(*) as total,
        COUNT(DISTINCT usuario_id) as total_usuarios,
        SUM(valor_total) as valor_total,
        COUNT(CASE WHEN status = 'Aprovado' THEN 1 END) as total_aprovados,
        SUM(CASE WHEN status = 'Aprovado' THEN valor_total ELSE 0 END) as valor_aprovado
    FROM custos_projeto
    WHERE ativo = 1 
    AND data_inicio BETWEEN ? AND ?
    GROUP BY tipo_origem
    ORDER BY valor_total DESC";

// Query para Pendências de Aprovação (Todos os Departamentos)
$query_pendencias = "
    SELECT 
        p.nome as projeto_nome,
        p.id_evento,
        u.nome as solicitante,
        u.departamento as departamento_solicitante,
        ua.nome as aprovador,
        cp.data_inicio,
        cp.data_fim,
        cp.tipo_custo,
        cp.tipo_origem,
        cp.valor_total,
        cp.created_at,
        cp.id as custo_id
    FROM custos_projeto cp
    JOIN projetos p ON cp.projeto_id = p.id
    JOIN usuarios u ON cp.usuario_id = u.id
    JOIN usuarios ua ON cp.aprovador_id = ua.id
    WHERE cp.ativo = 1 
    AND cp.status = 'Pendente'
    AND cp.data_inicio BETWEEN ? AND ?
    ORDER BY cp.created_at DESC
    LIMIT 10";

// Query para TOP 5 Projetos Geral
$query_top_projetos = "
    SELECT 
        p.nome as projeto_nome,
        p.id_evento,
        MIN(cp.data_inicio) as data_inicio,
        MAX(cp.data_fim) as data_fim,
        COUNT(DISTINCT cp.usuario_id) as total_envolvidos,
        COUNT(DISTINCT u.departamento) as total_departamentos,
        SUM(cp.valor_total) as valor_total,
        GROUP_CONCAT(DISTINCT u.departamento) as departamentos
    FROM custos_projeto cp
    JOIN projetos p ON cp.projeto_id = p.id
    JOIN usuarios u ON cp.usuario_id = u.id
    WHERE cp.ativo = 1 
    AND cp.status = 'Aprovado'
    AND cp.data_inicio BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY valor_total DESC
    LIMIT 5";

// Query para métricas de aprovação por perfil
$query_metricas_perfil = "
    SELECT 
        u.perfil,
        COUNT(DISTINCT cp.usuario_id) as total_usuarios,
        COUNT(*) as total_lancamentos,
        SUM(cp.valor_total) as valor_total,
        AVG(CASE WHEN cp.status = 'Aprovado' THEN 1 ELSE 0 END) * 100 as taxa_aprovacao,
        AVG(TIMESTAMPDIFF(HOUR, cp.created_at, 
            CASE WHEN cp.status != 'Pendente' 
                THEN cp.updated_at 
                ELSE NOW() 
            END)) as tempo_medio_aprovacao
    FROM custos_projeto cp
    JOIN usuarios u ON cp.usuario_id = u.id
    WHERE cp.ativo = 1 
    AND cp.data_inicio BETWEEN ? AND ?
    GROUP BY u.perfil";

// Executar queries
$stmt_status = $conexao->prepare($query_status);
$stmt_status->bind_param("ss", $data_inicio, $data_fim);
$stmt_status->execute();
$info_status = $stmt_status->get_result()->fetch_assoc();

$stmt_departamentos = $conexao->prepare($query_departamentos);
$stmt_departamentos->bind_param("ss", $data_inicio, $data_fim);
$stmt_departamentos->execute();
$result_departamentos = $stmt_departamentos->get_result();

$stmt_origem = $conexao->prepare($query_origem);
$stmt_origem->bind_param("ss", $data_inicio, $data_fim);
$stmt_origem->execute();
$result_origem = $stmt_origem->get_result();

$stmt_pendencias = $conexao->prepare($query_pendencias);
$stmt_pendencias->bind_param("ss", $data_inicio, $data_fim);
$stmt_pendencias->execute();
$result_pendencias = $stmt_pendencias->get_result();

$stmt_top_projetos = $conexao->prepare($query_top_projetos);
$stmt_top_projetos->bind_param("ss", $data_inicio, $data_fim);
$stmt_top_projetos->execute();
$result_top_projetos = $stmt_top_projetos->get_result();

$stmt_metricas = $conexao->prepare($query_metricas_perfil);
$stmt_metricas->bind_param("ss", $data_inicio, $data_fim);
$stmt_metricas->execute();
$result_metricas = $stmt_metricas->get_result();
?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h2">Dashboard Administrativo</h1>
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

    <!-- Status Geral -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status Geral</h5>
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
                                            <h4 class="mb-0">
                                                <?php echo number_format($info_status['total_aprovados'], 0, ',', '.'); ?>
                                            </h4>
                                            <small class="text-muted">Quantidade</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h4 class="mb-0">
                                                R$ <?php echo number_format($info_status['valor_aprovados'], 2, ',', '.'); ?>
                                            </h4>
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
                                            <h4 class="mb-0">
                                                <?php echo number_format($info_status['total_pendentes'], 0, ',', '.'); ?>
                                            </h4>
                                            <small class="text-muted">Quantidade</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h4 class="mb-0">
                                                R$ <?php echo number_format($info_status['valor_pendentes'], 2, ',', '.'); ?>
                                            </h4>
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
                                            <h4 class="mb-0">
                                                <?php echo number_format($info_status['total_reprovados'], 0, ',', '.'); ?>
                                            </h4>
                                            <small class="text-muted">Quantidade</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h4 class="mb-0">
                                                R$ <?php echo number_format($info_status['valor_reprovados'], 2, ',', '.'); ?>
                                            </h4>
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

    <!-- Status por Departamento -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status por Departamento</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Departamento</th>
                                    <th class="text-center">Usuários</th>
                                    <th class="text-center">Lançamentos</th>
                                    <th class="text-end">Valor Aprovado</th>
                                    <th class="text-end">Valor Pendente</th>
                                    <th class="text-end">Valor Reprovado</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_valor = 0;
                                while ($dept = $result_departamentos->fetch_assoc()):
                                    $total_valor += $dept['valor_aprovado'] + $dept['valor_pendente'] + $dept['valor_reprovado'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dept['departamento']); ?></td>
                                    <td class="text-center">
                                        <?php echo number_format($dept['total_usuarios'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo number_format($dept['total_lancamentos'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="text-end">
                                        R$ <?php echo number_format($dept['valor_aprovado'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="text-end">
                                        R$ <?php echo number_format($dept['valor_pendente'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="text-end">

                                    R$ <?php echo number_format($dept['valor_reprovado'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="text-end text-primary fw-bold">
                                        R$ <?php echo number_format($dept['valor_aprovado'] + $dept['valor_pendente'] + $dept['valor_reprovado'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="3">Total Geral</td>
                                    <td class="text-end">R$ <?php echo number_format($total_valor, 2, ',', '.'); ?></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tbody>
                        </table>
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
                        <?php 
                        $cores = [
                            'Previsto em Proposta' => 'success',
                            'Venda Adicional' => 'warning',
                            'Custo Operacional' => 'danger'
                        ];
                        while ($origem = $result_origem->fetch_assoc()): 
                            $cor = $cores[$origem['tipo_origem']] ?? 'primary';
                        ?>
                        <div class="col-md-4">
                            <div class="card border-left-<?php echo $cor; ?>">
                                <div class="card-body">
                                    <h6 class="card-title text-<?php echo $cor; ?>"><?php echo $origem['tipo_origem']; ?></h6>
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <h4 class="mb-0"><?php echo number_format($origem['total'], 0, ',', '.'); ?></h4>
                                            <small class="text-muted">Total de Lançamentos</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h4 class="mb-0"><?php echo number_format($origem['total_usuarios'], 0, ',', '.'); ?></h4>
                                            <small class="text-muted">Usuários Únicos</small>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="mb-0">
                                                R$ <?php echo number_format($origem['valor_aprovado'], 2, ',', '.'); ?>
                                            </h5>
                                            <small class="text-success">Valor Aprovado</small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h5 class="mb-0">
                                                R$ <?php echo number_format($origem['valor_total'], 2, ',', '.'); ?>
                                            </h5>
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

    <!-- Métricas por Perfil -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Métricas por Perfil</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Perfil</th>
                                    <th class="text-center">Usuários</th>
                                    <th class="text-center">Lançamentos</th>
                                    <th class="text-end">Valor Total</th>
                                    <th class="text-center">Taxa de Aprovação</th>
                                    <th class="text-center">Tempo Médio Aprovação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($metrica = $result_metricas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($metrica['perfil']); ?></td>
                                    <td class="text-center"><?php echo number_format($metrica['total_usuarios'], 0, ',', '.'); ?></td>
                                    <td class="text-center"><?php echo number_format($metrica['total_lancamentos'], 0, ',', '.'); ?></td>
                                    <td class="text-end">R$ <?php echo number_format($metrica['valor_total'], 2, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $metrica['taxa_aprovacao']; ?>%"
                                                 aria-valuenow="<?php echo $metrica['taxa_aprovacao']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo number_format($metrica['taxa_aprovacao'], 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $horas = round($metrica['tempo_medio_aprovacao'], 1);
                                        echo $horas > 24 
                                            ? number_format($horas / 24, 1) . ' dias' 
                                            : $horas . ' horas';
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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
                    <a href="index.php?modulo=listar_custo_projetos&filter=pendentes" class="btn btn-sm btn-primary">
                        Ver Todas
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Projeto</th>
                                    <th>ID Evento</th>
                                    <th>Solicitante</th>
                                    <th>Departamento</th>
                                    <th>Aprovador</th>
                                    <th>Período</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Valor</th>
                                    <th>Aguardando</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pendencia = $result_pendencias->fetch_assoc()): 
                                    $tempo_espera = (strtotime('now') - strtotime($pendencia['created_at'])) / 3600; // horas
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pendencia['projeto_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($pendencia['id_evento']); ?></td>
                                    <td><?php echo htmlspecialchars($pendencia['solicitante']); ?></td>
                                    <td><?php echo htmlspecialchars($pendencia['departamento_solicitante']); ?></td>
                                    <td><?php echo htmlspecialchars($pendencia['aprovador']); ?></td>
                                    <td>
                                        <?php 
                                        echo date('d/m/Y', strtotime($pendencia['data_inicio'])) . ' até<br>' . 
                                             date('d/m/Y', strtotime($pendencia['data_fim']));
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $pendencia['tipo_custo']; ?>
                                        </span>
                                        <small class="d-block text-muted">
                                            <?php echo $pendencia['tipo_origem']; ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        R$ <?php echo number_format($pendencia['valor_total'], 2, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $tempo_espera > 48 ? 'danger' : ($tempo_espera > 24 ? 'warning' : 'success'); ?>">
                                            <?php 
                                            echo $tempo_espera > 48 
                                                ? number_format($tempo_espera/24, 1) . ' dias' 
                                                : number_format($tempo_espera, 1) . ' horas';
                                            ?>
                                        </span>
                                    </td>
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
                                    <td colspan="10" class="text-center py-4">
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
                    <h5 class="card-title mb-0">TOP 5 Projetos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Projeto</th>
                                    <th>ID Evento</th>
                                    <th>Período</th>
                                    <th>Departamentos</th>
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
                                    <td>
                                        <?php 
                                        $departamentos = explode(',', $projeto['departamentos']);
                                        foreach ($departamentos as $dep) {
                                            echo "<span class='badge bg-secondary me-1'>" . htmlspecialchars($dep) . "</span>";
                                        }
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