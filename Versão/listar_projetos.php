<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

// Recuperar datas do filtro
$ano_atual = date('Y');
$mes_atual = date('n');

// Recuperar filtros da sessão ou POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['projeto_filter_month'] = $_POST['mes'];
    $_SESSION['projeto_filter_year'] = $_POST['ano'];
    $_SESSION['projeto_filter_tipo'] = $_POST['tipo'];
} 

// Usar valores da sessão ou padrões
$mes_filtro = isset($_SESSION['projeto_filter_month']) ? $_SESSION['projeto_filter_month'] : $mes_atual;
$ano_filtro = isset($_SESSION['projeto_filter_year']) ? $_SESSION['projeto_filter_year'] : $ano_atual;
$tipo_filtro = isset($_SESSION['projeto_filter_tipo']) ? $_SESSION['projeto_filter_tipo'] : 'todos';

// Query base com filtros
$query = "SELECT p.id, p.nome, p.id_evento, p.descricao, p.valor_total, 
                 p.data_inicio, p.data_fim, p.local, p.ativo,
                 u1.nome AS responsavel_comercial, 
                 u2.nome AS responsavel_atendimento, 
                 u3.nome AS responsavel_tecnico 
          FROM projetos p 
          LEFT JOIN usuarios u1 ON p.responsavel_comercial = u1.id 
          LEFT JOIN usuarios u2 ON p.responsavel_atendimento = u2.id 
          LEFT JOIN usuarios u3 ON p.responsavel_tecnico = u3.id
          WHERE p.ativo = 1
          AND (
              (YEAR(p.data_inicio) = ? AND MONTH(p.data_inicio) = ?)
              OR 
              (YEAR(p.data_fim) = ? AND MONTH(p.data_fim) = ?)
              OR 
              (
                  p.data_inicio < LAST_DAY(?) + INTERVAL 1 DAY
                  AND 
                  p.data_fim >= ?
              )
          )";

// Adicionar filtro de tipo
if ($tipo_filtro === 'eventos') {
    $query .= " AND (p.id_evento > 0)";
} elseif ($tipo_filtro === 'projetos') {
    $query .= " AND (p.id_evento IS NULL OR p.id_evento = 0)";
}

$query .= " ORDER BY p.data_inicio DESC";

// Preparar datas para o filtro
$data_inicio_mes = sprintf('%04d-%02d-01', $ano_filtro, $mes_filtro);
$data_fim_mes = date('Y-m-t', strtotime($data_inicio_mes));

$stmt = $conexao->prepare($query);
if (!$stmt) {
    die("Erro na preparação da consulta: " . $conexao->error);
}

// Bind dos parâmetros
$stmt->bind_param("iiiiss", 
    $ano_filtro,
    $mes_filtro,
    $ano_filtro,
    $mes_filtro,
    $data_inicio_mes,
    $data_inicio_mes
);

$stmt->execute();
$resultado = $stmt->get_result();
?>

<div class="container-fluid">
    <!-- Cabeçalho -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Projetos e Eventos</h1>
        <div class="d-flex gap-2">
            <form id="filterForm" class="d-flex gap-2" method="POST">
                <div class="input-group input-group-sm">
                    <!-- Filtro de Tipo -->
                    <select class="form-select" id="tipo" name="tipo" style="width: 120px;">
                        <option value="todos" <?php echo $tipo_filtro == 'todos' ? 'selected' : ''; ?>>
                            Todos
                        </option>
                        <option value="eventos" <?php echo $tipo_filtro == 'eventos' ? 'selected' : ''; ?>>
                            Eventos
                        </option>
                        <option value="projetos" <?php echo $tipo_filtro == 'projetos' ? 'selected' : ''; ?>>
                            Projetos
                        </option>
                    </select>
                    <!-- Filtro de Mês -->
                    <select class="form-select" id="mes" name="mes" style="width: 80px;">
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $mes_filtro == $i ? 'selected' : ''; ?>>
                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <!-- Filtro de Ano -->
                    <select class="form-select" id="ano" name="ano" style="width: 100px;">
                        <?php for($i = 2024; $i <= $ano_atual + 1; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $ano_filtro == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </form>
            <a href="index.php?modulo=criar_projeto" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Novo Projeto
            </a>
        </div>
    </div>

    <!-- Indicador do filtro ativo -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-filter me-2"></i>
        Visualizando <?php 
            echo $tipo_filtro == 'todos' ? 'todos os projetos' : 
                ($tipo_filtro == 'eventos' ? 'eventos' : 'projetos');
        ?> de <?php echo str_pad($mes_filtro, 2, '0', STR_PAD_LEFT) . "/" . $ano_filtro; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Tabela -->
    <div class="card">
        <div class="card-body">
            <table id="tabelaProjetos" class="datatable table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome do Projeto</th>
                        <th>ID Evento</th>
                        <th>Valor Total</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Resp. Comercial</th>
                        <th>Resp. Atendimento</th>
                        <th>Resp. Técnico</th>
                        <th width="90">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($projeto = $resultado->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($projeto['id']); ?></td>
                            <td><?php echo htmlspecialchars($projeto['nome']); ?></td>
                            <td><?php echo $projeto['id_evento'] ? htmlspecialchars($projeto['id_evento']) : '-'; ?></td>
                            <td data-order="<?php echo $projeto['valor_total']; ?>" class="text-end">
                                R$ <?php echo number_format($projeto['valor_total'], 2, ',', '.'); ?>
                            </td>
                            <td data-order="<?php echo strtotime($projeto['data_inicio']); ?>" class="text-center">
                                <?php echo date('d/m/Y', strtotime($projeto['data_inicio'])); ?>
                            </td>
                            <td data-order="<?php echo strtotime($projeto['data_fim']); ?>" class="text-center">
                                <?php echo date('d/m/Y', strtotime($projeto['data_fim'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($projeto['responsavel_comercial']); ?></td>
                            <td><?php echo htmlspecialchars($projeto['responsavel_atendimento'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($projeto['responsavel_tecnico'] ?? '-'); ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?modulo=editar_projeto&id=<?php echo $projeto['id']; ?>" 
                                       class="btn btn-light" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" 
                                       onclick="confirmarExclusao(<?php echo $projeto['id']; ?>, 'projeto')"
                                       class="btn btn-light" title="Excluir">
                                        <i class="fas fa-trash text-danger"></i>
                                    </a>
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
    // Validação e atualização automática dos filtros
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        // Atualização automática ao mudar qualquer filtro
        ['tipo', 'mes', 'ano'].forEach(function(fieldId) {
            document.getElementById(fieldId).addEventListener('change', function() {
                filterForm.submit();
            });
        });

        // Validação antes do submit
        filterForm.addEventListener('submit', function(e) {
            const mes = document.getElementById('mes').value;
            const ano = document.getElementById('ano').value;

            if (!mes || !ano) {
                e.preventDefault();
                alert('Por favor, selecione mês e ano para filtrar');
                return false;
            }
        });
    }

});
</script>

<?php 
$stmt->close();
$conexao->close(); 
?>