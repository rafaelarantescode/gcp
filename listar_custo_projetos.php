<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

// Buscar solicitantes únicos
$query_solicitantes = "SELECT DISTINCT 
    u.id, u.nome 
    FROM custos_projeto cp
    JOIN usuarios u ON cp.usuario_id = u.id 
    WHERE cp.ativo = 1 
    ORDER BY u.nome";
$result_solicitantes = $conexao->query($query_solicitantes);

// Obtém informações do usuário logado
$usuario_id = $_SESSION['usuario_id'];
$perfil_usuario = $_SESSION['perfil'];

// Recuperar datas do filtro
$ano_atual = date('Y');
$mes_atual = date('n');

// Se veio novo filtro via POST, atualizar sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['custo_filter_month'] = $_POST['mes'];
    $_SESSION['custo_filter_year'] = $_POST['ano'];
} 

// Usar valores da sessão ou padrões
$mes_filtro = isset($_SESSION['custo_filter_month']) ? $_SESSION['custo_filter_month'] : $mes_atual;
$ano_filtro = isset($_SESSION['custo_filter_year']) ? $_SESSION['custo_filter_year'] : $ano_atual;

// Query base
$query = "SELECT 
    cp.id,
    cp.projeto_id AS projeto_id,
    cp.tipo_custo,
    p.id_evento as id_eventos,
    p.nome AS nome_projeto,
    u1.nome AS nome_usuario,
    u1.email AS email_usuario,
    u1.departamento AS departamento_usuario,
    cp.data_inicio,
    cp.data_fim,
    cp.horas_trabalhadas,
    cp.valor_hora,
    cp.valor_total,
    cp.tipo_origem,
    cp.justificativa,
    u2.nome AS nome_aprovador,
    u2.email AS email_aprovador,
    cp.status,
    cp.comentario_aprovador,
    cp.created_at,
    cp.updated_at,
    cp.ativo 
FROM custos_projeto cp
LEFT JOIN projetos p ON cp.projeto_id = p.id
LEFT JOIN usuarios u1 ON cp.usuario_id = u1.id
LEFT JOIN usuarios u2 ON cp.aprovador_id = u2.id
WHERE cp.ativo = 1 
AND MONTH(cp.data_inicio) = ?
AND YEAR(cp.data_inicio) = ? ";

// Aplica filtros baseados no perfil
switch ($perfil_usuario) {
    case 'Usuario':
        $query .= "AND cp.usuario_id = ? ";
        $params_types = "iii";
        $params = array($mes_filtro, $ano_filtro, $usuario_id);
        break;

    case 'AprovadorN1':
        $query_dept = "SELECT departamento FROM usuarios WHERE id = ?";
        $stmt_dept = $conexao->prepare($query_dept);
        $stmt_dept->bind_param("i", $usuario_id);
        $stmt_dept->execute();
        $departamento_usuario = $stmt_dept->get_result()->fetch_assoc()['departamento'];
        
        $query .= "AND (cp.usuario_id = ? OR (u1.departamento = ? AND u1.perfil = 'Usuario')) ";
        $params_types = "iiis";
        $params = array($mes_filtro, $ano_filtro, $usuario_id, $departamento_usuario);
        break;

    case 'AprovadorN2':
        $query_dept = "SELECT departamento FROM usuarios WHERE id = ?";
        $stmt_dept = $conexao->prepare($query_dept);
        $stmt_dept->bind_param("i", $usuario_id);
        $stmt_dept->execute();
        $departamento_usuario = $stmt_dept->get_result()->fetch_assoc()['departamento'];
        
        $query .= "AND (cp.usuario_id = ? OR (u1.departamento = ? AND u1.perfil IN ('Usuario', 'AprovadorN1'))) ";
        $params_types = "iiis";
        $params = array($mes_filtro, $ano_filtro, $usuario_id, $departamento_usuario);
        break;

    case 'Administrador':
        $params_types = "ii";
        $params = array($mes_filtro, $ano_filtro);
        break;

    default:
        $query .= "AND cp.usuario_id = ? ";
        $params_types = "iii";
        $params = array($mes_filtro, $ano_filtro, $usuario_id);
}

$query .= "ORDER BY cp.id DESC";

// Prepara e executa a query
$stmt = $conexao->prepare($query);
if (!$stmt) {
    die("Erro na preparação da consulta: " . $conexao->error);
}

// Faz o bind dos parâmetros
$stmt->bind_param($params_types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="h2">Custos de Projetos</h1>
        <div class="d-flex gap-2">
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
            <?php if ($_SESSION['perfil'] === 'Administrador'): ?>
                <a href="extrator.php" class="btn btn-primary">
                    <i class="fas fa-file-excel me-1"></i>
                    Extrator
                </a>
            <?php endif; ?>
            <a href="index.php?modulo=criar_custo_projeto" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Custo
            </a>

        </div>
    </div>

    <!-- Indicador visual do filtro ativo -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-filter me-2"></i>
        <?php
        echo "Visualizando custos de " . str_pad($mes_filtro, 2, '0', STR_PAD_LEFT) . "/" . $ano_filtro;
        switch ($perfil_usuario) {
            case 'Usuario':
                echo " - Seus lançamentos";
                break;
            case 'AprovadorN1':
                echo " - Seus lançamentos e dos usuários do departamento " . htmlspecialchars($departamento_usuario);
                break;
            case 'AprovadorN2':
                echo " - Seus lançamentos e da equipe do departamento " . htmlspecialchars($departamento_usuario);
                break;
            case 'Administrador':
                echo " - Todos os lançamentos";
                break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Tabela -->
    <div class="card">
        <div class="card-body">
            <table id="tabelaCustos" class="datatable table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Projeto</th>
                        <th>ID Evento</th>
                        <?php if(isAdmin()): ?><th>Solicitante</th><?php endif; ?>
                        <th>Período</th>
                        <th>Alocação</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aprovador</th>
                        <th>Tipo de Origem</th>
                        <th>Data Cadastro</th>
                        <th>Última Atualização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($custo = $resultado->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($custo['id']); ?></td>
                            <td><?php echo htmlspecialchars($custo['nome_projeto']); ?></td>
                            <td><?php echo htmlspecialchars($custo['id_eventos']); ?></td>
                            <?php if(isAdmin()): ?>
                                <td><?php echo htmlspecialchars($custo['nome_usuario']); ?></td>
                            <?php endif; ?>
                            
                            <td data-order="<?php echo strtotime($custo['data_inicio']); ?>">
                                <?php 
                                    echo date('d/m/Y', strtotime($custo['data_inicio'])) . ' até <br>' . 
                                         date('d/m/Y', strtotime($custo['data_fim']));
                                ?>
                            </td>
                            
                            <td>
                                <?php 
                                if ($custo['tipo_custo'] === 'Horas') {
                                    echo '<div class="d-flex align-items-center">';
                                    echo '<span class="badge bg-primary me-2">'.number_format($custo['horas_trabalhadas'], 1, ':', ':').'h</span>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="d-flex align-items-center">';
                                    echo '<span class="badge bg-info me-2">Diária</span>';
                                    echo '</div>';
                                }
                                ?>
                            </td>

                            <td data-order="<?php echo $custo['valor_total']; ?>">
                                R$ <?php echo number_format($custo['valor_total'], 2, ',', '.'); ?>
                            </td>
                            
                            <td>
                                <?php
                                    $statusClass = [
                                        'Pendente' => 'bg-warning',
                                        'Aprovado' => 'bg-success',
                                        'Reprovado' => 'bg-danger'
                                    ][$custo['status']];
                                ?>
                                <span class="badge badge-status <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($custo['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($custo['nome_aprovador']); ?></td>
                            <td><?php echo htmlspecialchars($custo['tipo_origem']); ?></td>
                            <td data-order="<?php echo strtotime($custo['created_at']); ?>">
                                <?php echo date('d/m/Y H:i', strtotime($custo['created_at'])); ?>
                            </td>
                            <td data-order="<?php echo strtotime($custo['updated_at']); ?>">
                                <?php echo date('d/m/Y H:i', strtotime($custo['updated_at'])); ?>
                            </td>
                            <td class="actions">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="index.php?modulo=editar_custo_projeto&id=<?php echo $custo['id']; ?>" 
                                       class="btn btn-light" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" 
                                       onclick="confirmarExclusao(<?php echo $custo['id']; ?>, 'custo_projeto')"
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
    // Validação de datas do filtro
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
</script>

<?php 
$stmt->close();
$conexao->close(); 
?>