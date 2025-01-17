<?php
require_once 'session.php';
require_once 'db_connection.php';

verificarLogin();
verificarPerfil(['Administrador']);

// Configurações iniciais
date_default_timezone_set('America/Sao_Paulo');
set_time_limit(300); // 5 minutos
ini_set('memory_limit', '512M');

// Função para limpar nome do arquivo
function limparNomeArquivo($nome) {
    $nome = preg_replace("/[áàãâä]/u", "a", $nome);
    $nome = preg_replace("/[éèêë]/u", "e", $nome);
    $nome = preg_replace("/[íìîï]/u", "i", $nome);
    $nome = preg_replace("/[óòõôö]/u", "o", $nome);
    $nome = preg_replace("/[úùûü]/u", "u", $nome);
    $nome = preg_replace("/[ç]/u", "c", $nome);
    $nome = preg_replace("/[^a-zA-Z0-9]/", "", $nome);
    return strtolower($nome);
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y H:i', strtotime($data));
}

// Função para formatar valor monetário
function formatarMoeda($valor) {
    return number_format($valor, 2, ',', '.');
}

function formatarHorasTrabalhadasHHMM($data_inicio, $data_fim) {
    try {
        $inicio = new DateTime($data_inicio);
        $fim = new DateTime($data_fim);
        
        // Calcula a diferença
        $diff = $fim->diff($inicio);
        
        // Pega horas e minutos diretamente
        $horas = $diff->h;
        $minutos = $diff->i;
        
        // Formata como HH:MM usando : ao invés de .
        return sprintf("%d:%02d", $horas, $minutos);
        
    } catch (Exception $e) {
        error_log("Erro ao calcular horas: " . $e->getMessage());
        return "0:00";
    }
}

// Função para gerar arquivo XLSX
function gerarArquivoExcel($solicitante, $custos, $caminho_arquivo) {
    ob_start();
    
    echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
          <head>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Custos</x:Name>
                            <x:WorksheetOptions>
                                <x:Print>
                                    <x:ValidPrinterInfo/>
                                </x:Print>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                td, th { padding: 5px; }
                th { background-color: #ccc; font-weight: bold; }
                .header { background-color: #333; color: #fff; font-size: 14pt; }
                .money { mso-number-format:\"#,##0.00\"; }
                .date { mso-number-format:\"dd/mm/yyyy hh:mm\"; }
            </style>
          </head>
          <body>";
          
    echo "<table border='1'>";
    echo "<tr><th colspan='17' class='header'>";
    echo "Custos - " . htmlspecialchars($solicitante['nome_solicitante']);
    echo "</th></tr>";
    
    // Cabeçalhos
    echo "<tr>";
    $headers = array(
        'ID', 'Projeto', 'ID Evento', 'Data Início', 'Data Fim',
        'Tipo Custo', 'Horas Trabalhadas', 'Valor Hora', 'Valor Total',
        'Tipo Origem', 'Justificativa', 'Status', 'Departamento',
        'Aprovador', 'Email Aprovador', 'Data Criação', 'Última Atualização'
    );
    foreach ($headers as $header) {
        echo "<th>" . htmlspecialchars($header) . "</th>";
    }
    echo "</tr>";
    
    $total_horas = 0;
    $total_minutos = 0;
    $total_valor = 0;
    
    // Dados
    while ($custo = $custos->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $custo['id'] . "</td>";
        echo "<td>" . htmlspecialchars($custo['projeto_nome']) . "</td>";
        echo "<td>" . htmlspecialchars($custo['id_evento']) . "</td>";
        echo "<td class='date'>" . formatarData($custo['data_inicio']) . "</td>";
        echo "<td class='date'>" . formatarData($custo['data_fim']) . "</td>";
        echo "<td>" . htmlspecialchars($custo['tipo_custo']) . "</td>";
        echo "<td>" . ($custo['tipo_custo'] == 'Horas' ? formatarHorasTrabalhadasHHMM($custo['data_inicio'], $custo['data_fim']) : number_format($custo['horas_trabalhadas'], 2, ',', '.')) . "</td>";
        echo "<td class='money'>" . formatarMoeda($custo['valor_hora']) . "</td>";
        echo "<td class='money'>" . formatarMoeda($custo['valor_total']) . "</td>";
        echo "<td>" . htmlspecialchars($custo['tipo_origem']) . "</td>";
        echo "<td>" . htmlspecialchars($custo['justificativa']) . "</td>";
        echo "<td>" . htmlspecialchars($custo['status']) . "</td>";
        echo "<td>" . htmlspecialchars($custo['solicitante_departamento']) . "</td>";
        echo "<td>" . htmlspecialchars($custo['aprovador_nome']) . "</td>";
        echo "<td>" . htmlspecialchars($custo['aprovador_email']) . "</td>";
        echo "<td class='date'>" . formatarData($custo['created_at']) . "</td>";
        echo "<td class='date'>" . formatarData($custo['updated_at']) . "</td>";
        echo "</tr>";
        
        
        // Se for do tipo Horas, calcula e soma ao total
        if ($custo['tipo_custo'] == 'Horas') {
            $inicio = new DateTime($custo['data_inicio']);
            $fim = new DateTime($custo['data_fim']);
            $diff = $fim->diff($inicio);
            $total_horas += $diff->h;
            $total_minutos += $diff->i;
        }
        
        // Soma o valor total
        $total_valor += $custo['valor_total'];
        
        
        
    }
    
    $total_horas += floor($total_minutos / 60);
    $total_minutos = $total_minutos % 60;
    
    echo "<tr style='font-weight: bold; background-color: #f0f0f0;'>";
    echo "<td colspan='6' style='text-align: right;'>Total:</td>";
    echo "<td>" . sprintf("%d:%02d", $total_horas, $total_minutos) . "</td>";
    echo "<td></td>";
    echo "<td class='money'>" . formatarMoeda($total_valor) . "</td>";
    echo "<td colspan='8'></td>";
    echo "</tr>";
    
    
    echo "</table>";
    echo "</body></html>";
    
    $conteudo = ob_get_clean();
    file_put_contents($caminho_arquivo, $conteudo);
}

try {
    $conexao = conectar();
    
    // Criar diretório base
    $base_dir = 'relatorios/dez_2024';
    if (!file_exists($base_dir)) {
        mkdir($base_dir, 0777, true);
    }
    
    // Buscar todos os solicitantes que têm custos aprovados em dez/2024
    $query_solicitantes = "
        SELECT DISTINCT 
            u.id as usuario_id,
            u.nome as nome_solicitante
        FROM custos_projeto cp
        JOIN usuarios u ON cp.usuario_id = u.id
        WHERE cp.status = 'Aprovado'
        AND cp.ativo = 1
        AND MONTH(cp.data_inicio) = 12
        AND YEAR(cp.data_inicio) = 2024
        ORDER BY u.nome";
    
    $resultado_solicitantes = $conexao->query($query_solicitantes);
    
    if ($resultado_solicitantes->num_rows === 0) {
        throw new Exception("Nenhum custo aprovado encontrado para Dez/2024");
    }
    
    // Query base para buscar os custos
    $query_custos = "
        SELECT 
            cp.*,
            p.nome as projeto_nome,
            p.id_evento,
            us.nome as solicitante_nome,
            us.email as solicitante_email,
            us.departamento as solicitante_departamento,
            ua.nome as aprovador_nome,
            ua.email as aprovador_email
        FROM custos_projeto cp
        JOIN projetos p ON cp.projeto_id = p.id
        JOIN usuarios us ON cp.usuario_id = us.id
        JOIN usuarios ua ON cp.aprovador_id = ua.id
        WHERE cp.status = 'Aprovado'
        AND cp.ativo = 1
        AND MONTH(cp.data_inicio) = 12
        AND YEAR(cp.data_inicio) = 2024
        AND cp.usuario_id = ?
        ORDER BY cp.data_inicio ASC";
    
    $stmt = $conexao->prepare($query_custos);
    
    $arquivos_gerados = array();
    
    // Processar cada solicitante
    while ($solicitante = $resultado_solicitantes->fetch_assoc()) {
        // Preparar nome do arquivo
        $nome_arquivo = limparNomeArquivo($solicitante['nome_solicitante']) . '_dez24.xls';
        $caminho_arquivo = $base_dir . '/' . $nome_arquivo;
        
        // Buscar custos do solicitante
        $stmt->bind_param("i", $solicitante['usuario_id']);
        $stmt->execute();
        $resultado_custos = $stmt->get_result();
        
        // Gerar arquivo Excel
        gerarArquivoExcel($solicitante, $resultado_custos, $caminho_arquivo);
        
        $arquivos_gerados[] = $caminho_arquivo;
    }
    
    // Criar arquivo ZIP
    $zip_nome = 'custos_dez2024.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zip_nome, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($arquivos_gerados as $arquivo) {
            $zip->addFile($arquivo, basename($arquivo));
        }
        $zip->close();
        
        // Forçar download do ZIP
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_nome . '"');
        header('Content-Length: ' . filesize($zip_nome));
        header('Cache-Control: max-age=0');
        
        readfile($zip_nome);
        
        // Limpar arquivos
        foreach ($arquivos_gerados as $arquivo) {
            unlink($arquivo);
        }
        rmdir($base_dir);
        unlink($zip_nome);
        
    } else {
        throw new Exception("Erro ao criar arquivo ZIP");
    }
    
} catch (Exception $e) {
    error_log("Erro ao gerar relatório: " . $e->getMessage());
    $_SESSION['error_message'] = "Erro ao gerar relatório: " . $e->getMessage();
    header("Location: index.php");
    exit();
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conexao)) $conexao->close();
}