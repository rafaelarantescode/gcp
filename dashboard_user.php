<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();
verificarPerfil(['Usuario']);

// Verificar se é usuário comum
if (!in_array($_SESSION['perfil'], ['Usuario'])) {
   header("Location: index.php?modulo=dashboard");
   exit();
}

$conexao = conectar();
$usuario_id = $_SESSION['usuario_id'];

try {
   // Recuperar datas do filtro
   $ano_atual = date('Y');
   $data_inicio = isset($_SESSION['dashboard_filter_start']) 
       ? $_SESSION['dashboard_filter_start'] 
       : $ano_atual . '-01-01';
   $data_fim = isset($_SESSION['dashboard_filter_end']) 
       ? $_SESSION['dashboard_filter_end'] 
       : $ano_atual . '-12-31';

   // Se veio novo filtro via POST, validar e atualizar
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       $nova_data_inicio = filter_input(INPUT_POST, 'data_inicio', FILTER_SANITIZE_STRING);
       $nova_data_fim = filter_input(INPUT_POST, 'data_fim', FILTER_SANITIZE_STRING);
       
       if (!$nova_data_inicio || !$nova_data_fim) {
           throw new Exception("Datas inválidas");
       }
       
       if (strtotime($nova_data_fim) <= strtotime($nova_data_inicio)) {
           throw new Exception("A data final deve ser maior que a data inicial");
       }
       
       $data_inicio = $nova_data_inicio;
       $data_fim = $nova_data_fim;
       $_SESSION['dashboard_filter_start'] = $data_inicio;
       $_SESSION['dashboard_filter_end'] = $data_fim;
   }

   // Resumo dos lançamentos do usuário
   $query_resumo = "SELECT 
       COUNT(*) as total_lancamentos,
       COUNT(CASE WHEN status = 'Pendente' THEN 1 END) as lancamentos_pendentes,
       COUNT(CASE WHEN status = 'Aprovado' THEN 1 END) as lancamentos_aprovados,
       COUNT(CASE WHEN status = 'Reprovado' THEN 1 END) as lancamentos_reprovados,
       SUM(CASE WHEN status = 'Aprovado' THEN horas_trabalhadas ELSE 0 END) as total_horas_aprovadas,
       COUNT(DISTINCT projeto_id) as total_projetos,
       COUNT(CASE WHEN MONTH(data_inicio) = MONTH(CURRENT_DATE) THEN 1 END) as lancamentos_mes_atual,
       SUM(CASE WHEN status = 'Aprovado' AND MONTH(data_inicio) = MONTH(CURRENT_DATE) 
           THEN horas_trabalhadas ELSE 0 END) as horas_mes_atual,
       COUNT(CASE WHEN tipo_origem = 'Previsto em Proposta' THEN 1 END) as total_previsto,
       COUNT(CASE WHEN tipo_origem = 'Venda Adicional' THEN 1 END) as total_adicional,
       COUNT(CASE WHEN tipo_origem = 'Custo Operacional' THEN 1 END) as total_operacional
   FROM custos_projeto
   WHERE usuario_id = ?
   AND data_inicio BETWEEN ? AND ?";

   $stmt = $conexao->prepare($query_resumo);
   $stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
   $stmt->execute();
   $info_resumo = $stmt->get_result()->fetch_assoc();

   // Últimos lançamentos
   $query_ultimos = "SELECT 
       cp.id,
       p.nome as projeto_nome,
       p.id_evento,
       cp.data_inicio,
       cp.data_fim,
       cp.horas_trabalhadas,
       cp.tipo_origem,
       cp.status,
       cp.comentario_aprovador,
       u.nome as aprovador_nome,
       cp.created_at
   FROM custos_projeto cp
   JOIN projetos p ON cp.projeto_id = p.id
   LEFT JOIN usuarios u ON cp.aprovador_id = u.id
   WHERE cp.usuario_id = ?
   AND cp.ativo = 1
   ORDER BY cp.created_at DESC
   LIMIT 10";

   $stmt = $conexao->prepare($query_ultimos);
   $stmt->bind_param("i", $usuario_id);
   $stmt->execute();
   $result_ultimos = $stmt->get_result();

} catch (Exception $e) {
   error_log("Erro no dashboard usuário: " . $e->getMessage());
   $_SESSION['error_message'] = "Erro ao carregar dados do dashboard";
   $info_resumo = ['error' => true];
}
?>

<!-- HTML do Dashboard -->
<div class="container-fluid">
   <div class="page-header mb-4">
       <div class="row align-items-center">
           <div class="col">
               <h1 class="h2">Meu Dashboard</h1>
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
       <div class="col-xl-12">
           <div class="card">
               <div class="card-header py-3">
                   <h6 class="m-0 font-weight-bold text-primary">Status dos Meus Lançamentos</h6>
               </div>
               <div class="card-body">
                   <div class="row align-items-center">
                       <div class="col-md-4 text-center mb-3">
                           <div class="h4 mb-0 text-success">
                               <?php echo number_format($info_resumo['lancamentos_aprovados'], 0, ',', '.'); ?>
                           </div>
                           <small class="text-muted">Aprovados</small>
                           <div class="progress mt-2" style="height: 4px;">
                               <div class="progress-bar bg-success" style="width: <?php 
                                   echo $info_resumo['total_lancamentos'] > 0 
                                       ? ($info_resumo['lancamentos_aprovados'] / $info_resumo['total_lancamentos'] * 100) 
                                       : 0; 
                               ?>%"></div>
                           </div>
                       </div>
                       <div class="col-md-4 text-center mb-3">
                           <div class="h4 mb-0 text-warning">
                               <?php echo number_format($info_resumo['lancamentos_pendentes'], 0, ',', '.'); ?>
                           </div>
                           <small class="text-muted">Pendentes</small>
                           <div class="progress mt-2" style="height: 4px;">
                               <div class="progress-bar bg-warning" style="width: <?php 
                                   echo $info_resumo['total_lancamentos'] > 0 
                                       ? ($info_resumo['lancamentos_pendentes'] / $info_resumo['total_lancamentos'] * 100) 
                                       : 0; 
                               ?>%"></div>
                           </div>
                       </div>
                       <div class="col-md-4 text-center mb-3">
                           <div class="h4 mb-0 text-danger">
                               <?php echo number_format($info_resumo['lancamentos_reprovados'], 0, ',', '.'); ?>
                           </div>
                           <small class="text-muted">Reprovados</small>
                           <div class="progress mt-2" style="height: 4px;">
                               <div class="progress-bar bg-danger" style="width: <?php 
                                   echo $info_resumo['total_lancamentos'] > 0 
                                       ? ($info_resumo['lancamentos_reprovados'] / $info_resumo['total_lancamentos'] * 100) 
                                       : 0; 
                               ?>%"></div>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   </div>

   <!-- Últimos Lançamentos -->
   <div class="row">
       <div class="col-12">
           <div class="card">
               <div class="card-header py-3 d-flex justify-content-between align-items-center">
                   <h6 class="m-0 font-weight-bold text-primary">Meus Últimos Lançamentos</h6>
                   <a href="index.php?modulo=criar_custo_projeto" class="btn btn-sm btn-primary">
                       <i class="fas fa-plus me-1"></i>Novo Lançamento
                   </a>
               </div>
               <div class="card-body">
                   <div class="table-responsive">
                       <table class="table table-hover">
                           <thead>
                               <tr>
                                   <th>Projeto</th>
                                   <th>Período</th>
                                   <th>Tipo</th>
                                   <th>Status</th>
                                   <th>Aprovador</th>
                                   <th>Data Cadastro</th>
                                   <th></th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php while ($lancamento = $result_ultimos->fetch_assoc()): ?>
                               <tr>
                                   <td>
                                       <?php echo htmlspecialchars($lancamento['projeto_nome']); ?>
                                       <small class="text-muted d-block">
                                           ID: <?php echo htmlspecialchars($lancamento['id_evento']); ?>
                                       </small>
                                   </td>
                                   <td>
                                       <?php 
                                       echo date('d/m/Y', strtotime($lancamento['data_inicio'])) . ' - ' . 
                                            date('d/m/Y', strtotime($lancamento['data_fim']));
                                       ?>
                                   </td>
                                   <td><?php echo $lancamento['tipo_origem']; ?></td>
                                   <td>
                                       <span class="badge bg-<?php 
                                           echo $lancamento['status'] == 'Aprovado' ? 'success' : 
                                               ($lancamento['status'] == 'Pendente' ? 'warning' : 'danger'); 
                                       ?>">
                                           <?php echo $lancamento['status']; ?>
                                       </span>
                                   </td>
                                   <td>
                                       <?php 
                                       echo $lancamento['status'] != 'Pendente' 
                                           ? htmlspecialchars($lancamento['aprovador_nome'])
                                           : '<span class="text-muted">Aguardando</span>';

                                       if ($lancamento['status'] == 'Reprovado' && !empty($lancamento['comentario_aprovador'])) {
                                           echo '<i class="fas fa-info-circle ms-1" data-bs-toggle="tooltip" 
                                                   title="' . htmlspecialchars($lancamento['comentario_aprovador']) . '"></i>';
                                       }
                                       ?>
                                   </td>
                                   <td>
                                       <?php echo date('d/m/Y H:i', strtotime($lancamento['created_at'])); ?>
                                   </td>
                                   <td>
                                       <?php if ($lancamento['status'] == 'Pendente'): ?>
                                       <a href="index.php?modulo=editar_custo_projeto&id=<?php echo $lancamento['id']; ?>" 
                                          class="btn btn-sm btn-outline-primary">
                                           Editar
                                       </a>
                                       <?php endif; ?>
                                   </td>
                               </tr>
                               <?php endwhile; ?>
                               <?php if ($result_ultimos->num_rows === 0): ?>
                               <tr>
                                   <td colspan="8" class="text-center text-muted py-4">
                                       Nenhum lançamento encontrado no período selecionado
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

<?php
// Fechar conexões
if(isset($stmt)) $stmt->close();
if(isset($conexao)) $conexao->close();
?>
