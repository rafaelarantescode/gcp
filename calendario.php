<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

// Configurar locale para português
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

$conexao = conectar();

// Recuperar mês e ano selecionados
$mes_atual = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$ano_atual = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$tipo_visualizacao = isset($_GET['tipo']) ? $_GET['tipo'] : 'atendimento';

// Determinar primeiro e último dia do mês
$primeiro_dia = "$ano_atual-$mes_atual-01";
$ultimo_dia = date('Y-m-t', strtotime($primeiro_dia));

// Query para buscar os projetos do mês
$campo_inicio = $tipo_visualizacao === 'atendimento' ? 'p.data_inicio' : 'p.data_inicio_evento';
$campo_fim = $tipo_visualizacao === 'atendimento' ? 'p.data_fim' : 'p.data_fim_evento';

$query = "SELECT 
    p.*,
    u1.nome as responsavel_nome,
    u2.nome as atendimento_nome,
    u3.nome as tecnico_nome,
    c1.nome as contratante_nome,
    TIME(p.data_inicio_evento) as hora_inicio_evento
    FROM projetos p
    LEFT JOIN usuarios u1 ON p.responsavel_comercial = u1.id
    LEFT JOIN usuarios u2 ON p.responsavel_atendimento = u2.id
    LEFT JOIN usuarios u3 ON p.responsavel_tecnico = u3.id
    LEFT JOIN clientes c1 ON p.contratante_id = c1.id
    WHERE p.ativo = 1
    AND (
        (MONTH($campo_inicio) = ? AND YEAR($campo_inicio) = ?)
        OR
        (MONTH($campo_fim) = ? AND YEAR($campo_fim) = ?)
        OR
        ($campo_inicio <= ? AND $campo_fim >= ?)
    )
    ORDER BY $campo_inicio ASC";

$stmt = $conexao->prepare($query);
$stmt->bind_param("iiiiss", 
    $mes_atual, $ano_atual,
    $mes_atual, $ano_atual,
    $ultimo_dia, $primeiro_dia
);
$stmt->execute();
$resultado = $stmt->get_result();

// Array para armazenar projetos por dia
$projetos_por_dia = array();
while ($projeto = $resultado->fetch_assoc()) {
    $data_inicio = $tipo_visualizacao === 'atendimento' ? 
        new DateTime($projeto['data_inicio']) : 
        new DateTime($projeto['data_inicio_evento']);
    
    $data_fim = $tipo_visualizacao === 'atendimento' ? 
        new DateTime($projeto['data_fim']) : 
        new DateTime($projeto['data_fim_evento']);
    
    $periodo = new DatePeriod(
        $data_inicio,
        new DateInterval('P1D'),
        $data_fim->modify('+1 day')
    );
    
    foreach ($periodo as $data) {
        if ($data->format('n') == $mes_atual && $data->format('Y') == $ano_atual) {
            $dia = $data->format('j');
            if (!isset($projetos_por_dia[$dia])) {
                $projetos_por_dia[$dia] = array();
            }
            $projetos_por_dia[$dia][] = $projeto;
        }
    }
}

// Total de Eventos em Atendimento
$query_em_atendimento = "SELECT COUNT(*) as total FROM projetos p 
    WHERE p.ativo = 1 
    AND ? BETWEEN DATE_FORMAT(p.data_inicio, '%Y-%m-01') AND LAST_DAY(p.data_fim)";
$stmt = $conexao->prepare($query_em_atendimento);
$data_atual = date('Y-m-d', mktime(0, 0, 0, $mes_atual, 1, $ano_atual));
$stmt->bind_param("s", $data_atual);
$stmt->execute();
$total_em_atendimento = $stmt->get_result()->fetch_assoc()['total'];

// Total de Eventos a entregar
$query_a_entregar = "SELECT COUNT(*) as total FROM projetos p 
    WHERE p.ativo = 1 
    AND ? BETWEEN DATE_FORMAT(p.data_inicio_evento, '%Y-%m-01') AND LAST_DAY(p.data_fim_evento)
    AND p.data_fim_evento >= CURDATE()";
$stmt = $conexao->prepare($query_a_entregar);
$stmt->bind_param("s", $data_atual);
$stmt->execute();
$total_a_entregar = $stmt->get_result()->fetch_assoc()['total'];

// Total de Eventos entregues
$query_entregues = "SELECT COUNT(*) as total FROM projetos p 
    WHERE p.ativo = 1 
    AND ? BETWEEN DATE_FORMAT(p.data_inicio_evento, '%Y-%m-01') AND LAST_DAY(p.data_fim_evento)
    AND p.data_fim_evento < CURDATE()";
$stmt = $conexao->prepare($query_entregues);
$stmt->bind_param("s", $data_atual);
$stmt->execute();
$total_entregues = $stmt->get_result()->fetch_assoc()['total'];



// Logo após as configurações iniciais e antes do HTML
try {
    // Total de Eventos em Atendimento
    $query_em_atendimento = "SELECT COUNT(*) as total 
        FROM projetos p 
        WHERE p.ativo = 1 
        AND MONTH(p.data_inicio) = ? 
        AND YEAR(p.data_inicio) = ?
        AND p.data_fim >= CURDATE()";
    $stmt = $conexao->prepare($query_em_atendimento);
    $stmt->bind_param("ii", $mes_atual, $ano_atual);
    $stmt->execute();
    $total_em_atendimento = $stmt->get_result()->fetch_assoc()['total'];

    // Total de Eventos a entregar
    $query_a_entregar = "SELECT COUNT(*) as total 
        FROM projetos p 
        WHERE p.ativo = 1 
        AND MONTH(p.data_inicio_evento) = ? 
        AND YEAR(p.data_inicio_evento) = ?
        AND p.data_fim_evento >= CURDATE()";
    $stmt = $conexao->prepare($query_a_entregar);
    $stmt->bind_param("ii", $mes_atual, $ano_atual);
    $stmt->execute();
    $total_a_entregar = $stmt->get_result()->fetch_assoc()['total'];

    // Total de Eventos entregues
    $query_entregues = "SELECT COUNT(*) as total 
        FROM projetos p 
        WHERE p.ativo = 1 
        AND MONTH(p.data_inicio_evento) = ? 
        AND YEAR(p.data_inicio_evento) = ?
        AND p.data_fim_evento < CURDATE()";
    $stmt = $conexao->prepare($query_entregues);
    $stmt->bind_param("ii", $mes_atual, $ano_atual);
    $stmt->execute();
    $total_entregues = $stmt->get_result()->fetch_assoc()['total'];

    // Total de Dias sem evento para entregar
    $query_dias_sem_evento = "SELECT 
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM projetos 
                WHERE ativo = 1 
                AND MONTH(data_inicio_evento) = ? 
                AND YEAR(data_inicio_evento) = ?
                AND data_inicio_evento > CURDATE()
            ) THEN
                DATEDIFF(
                    (SELECT MIN(data_inicio_evento) 
                     FROM projetos 
                     WHERE ativo = 1
                     AND MONTH(data_inicio_evento) = ? 
                     AND YEAR(data_inicio_evento) = ?
                     AND data_inicio_evento > CURDATE()
                    ),
                    CURDATE()
                )
            ELSE 
                CASE 
                    WHEN ? = MONTH(CURDATE()) AND ? = YEAR(CURDATE()) THEN 
                        DATEDIFF(LAST_DAY(CURDATE()), CURDATE())
                    ELSE 0
                END
        END as dias_sem_evento";

    $stmt = $conexao->prepare($query_dias_sem_evento);
    $stmt->bind_param("iiiiii", 
        $mes_atual, $ano_atual,  // Para o EXISTS
        $mes_atual, $ano_atual,  // Para o MIN
        $mes_atual, $ano_atual   // Para o CASE do mês atual
    );
    $stmt->execute();
    $dias_sem_evento = $stmt->get_result()->fetch_assoc()['dias_sem_evento'];
    $dias_sem_evento = ($dias_sem_evento !== null && $dias_sem_evento > 0) ? $dias_sem_evento : 0;

} catch (Exception $e) {
    error_log("Erro ao calcular totalizadores: " . $e->getMessage());
    $total_em_atendimento = 0;
    $total_a_entregar = 0;
    $total_entregues = 0;
    $dias_sem_evento = 0;
}


?>

<div class="container-fluid">
<div class="container-fluid">
    <!-- Cards de Totalizadores -->
    <div class="row mb-4">
        <!-- Card Eventos em Atendimento -->
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary mb-0">Em Atendimento</h6>
                            <h5 class="mb-0"><?php echo number_format($total_em_atendimento, 0, ',', '.'); ?></h5>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-calendar-check fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Eventos a Entregar -->
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-0">A Entregar</h6>
                            <h5 class="mb-0"><?php echo number_format($total_a_entregar, 0, ',', '.'); ?></h5>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-clock fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Eventos Entregues -->
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-0">Entregues</h6>
                            <h5 class="mb-0"><?php echo number_format($total_entregues, 0, ',', '.'); ?></h5>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Dias sem Evento -->
        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-0">Dias sem Evento</h6>
                            <h5 class="mb-0"><?php echo number_format($dias_sem_evento, 0, ',', '.'); ?> dias</h5>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-hourglass-half fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Restante do código existente -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h2">Calendário de Projetos</h1>
            </div>
            <div class="col-auto">
                <form id="filterForm" class="row g-3 align-items-center" method="GET">
                    <input type="hidden" name="modulo" value="calendario">
                    
                    <div class="col-auto">
                        <select class="form-select form-select-sm" name="tipo" style="width: 200px;" onchange="this.form.submit()">
                            <option value="atendimento" <?php echo $tipo_visualizacao == 'atendimento' ? 'selected' : ''; ?>>
                                Por Atendimento
                            </option>
                            <option value="evento" <?php echo $tipo_visualizacao == 'evento' ? 'selected' : ''; ?>>
                                Por Evento
                            </option>
                        </select>
                    </div>
                    
                    <div class="col-auto">
                        <select class="form-select form-select-sm" name="mes" style="width: 150px;" onchange="this.form.submit()">
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $mes_atual == $i ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(strftime('%B', mktime(0, 0, 0, $i, 1))); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-auto">
                        <select class="form-select form-select-sm" name="ano" style="width: 100px;" onchange="this.form.submit()">
                            <?php for($i = $ano_atual - 1; $i <= $ano_atual + 1; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $ano_atual == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php 
            $dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mes_atual, $ano_atual);
            for ($dia = 1; $dia <= $dias_no_mes; $dia++):
                if (!isset($projetos_por_dia[$dia])) continue;
            ?>
                <div class="border-bottom" data-dia="<?php echo $dia; ?>">
                    <div class="bg-light p-2">
                        <h5 class="mb-0">
                            <?php echo $dia . ' de ' . ucfirst(strftime('%B', mktime(0, 0, 0, $mes_atual, 1))); ?>
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Horário Início/Fim</th>
                                    <th>ID Evento</th>
                                    <th>Projeto</th>
                                    <th>Atendimento</th>
                                    <th>Técnico</th>
                                    <th>Contratante</th>
                                    <th>Local</th>
                                    <th>Cidade/Estado</th>
                                    <th>Período</th>
                                    <th width="50">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projetos_por_dia[$dia] as $projeto): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $hora_inicio = $tipo_visualizacao === 'atendimento' ? 
                                                date('H:i', strtotime($projeto['data_inicio'])) : 
                                                date('H:i', strtotime($projeto['data_inicio_evento']));
                                            
                                            $hora_fim = $tipo_visualizacao === 'atendimento' ? 
                                                date('H:i', strtotime($projeto['data_fim'])) : 
                                                date('H:i', strtotime($projeto['data_fim_evento']));
                                            
                                            echo "{$hora_inicio} ás {$hora_fim}";
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($projeto['id_evento']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($projeto['nome']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($projeto['atendimento_nome'] ?? ''); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($projeto['tecnico_nome'] ?? ''); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($projeto['contratante_nome']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($projeto['local']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($projeto['cidade'] . '/' . $projeto['estado']); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $data_inicio = $tipo_visualizacao === 'atendimento' ? 
                                                $projeto['data_inicio'] : $projeto['data_inicio_evento'];
                                            $data_fim = $tipo_visualizacao === 'atendimento' ? 
                                                $projeto['data_fim'] : $projeto['data_fim_evento'];
                                            
                                            echo date('d/m/Y', strtotime($data_inicio)) . ' até<br>' . 
                                                 date('d/m/Y', strtotime($data_fim));
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="index.php?modulo=editar_projeto&id=<?php echo $projeto['id']; ?>" 
                                                   class="btn btn-light" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endfor; ?>

            <?php if (empty($projetos_por_dia)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                    <p>Nenhum projeto encontrado para este período.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Se estiver no mês atual, rola até o dia atual
    if (<?php echo $mes_atual; ?> === <?php echo date('n'); ?> && 
        <?php echo $ano_atual; ?> === <?php echo date('Y'); ?>) {
        
        const diaAtual = <?php echo date('j'); ?>;
        const elementoDiaAtual = document.querySelector(`[data-dia="${diaAtual}"]`);
        
        if (elementoDiaAtual) {
            elementoDiaAtual.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
});
</script>

<?php
$stmt->close();
$conexao->close();
?>