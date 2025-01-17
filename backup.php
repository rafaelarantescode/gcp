<?php
// Ativar exibição de todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

// Aumentar limite de memória e tempo de execução
ini_set('memory_limit', '512M');
set_time_limit(300);

require_once 'db_connection.php';
require_once 'mail_helper.php';

// Configurações básicas
$config = array(
    'backup_path' => dirname(__FILE__) . '/backups/',
    'date_format' => date('Y-m-d_H-i-s')
);

// Função para debug
function debug($message) {
    echo date('Y-m-d H:i:s') . " - " . $message . "\n";
    flush();
    ob_flush();
}

// Função para formatar bytes
function formatBytes($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Função para backup do banco de dados
function backupDatabase($config) {
    try {
        $output_file = $config['backup_path'] . 'db_' . $config['date_format'] . '.sql';
        debug("Iniciando backup do banco de dados para: " . basename($output_file));
        
        $conexao = conectar();
        if (!$conexao) {
            throw new Exception("Não foi possível conectar ao banco de dados");
        }
        
        $handle = fopen($output_file, 'w');
        if (!$handle) {
            throw new Exception("Não foi possível criar o arquivo de backup");
        }
        
        // Cabeçalho
        fwrite($handle, "-- Backup gerado em " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
        
        // Listar todas as tabelas
        $tables_result = $conexao->query("SHOW TABLES");
        while ($table_row = $tables_result->fetch_array()) {
            $table = $table_row[0];
            debug("Processando tabela: " . $table);
            
            // Estrutura
            $create_table = $conexao->query("SHOW CREATE TABLE `$table`");
            $row = $create_table->fetch_array();
            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
            fwrite($handle, $row[1] . ";\n\n");
            
            // Dados
            $rows = $conexao->query("SELECT * FROM `$table`");
            
            if ($rows->num_rows > 0) {
                while ($row = $rows->fetch_assoc()) {
                    $values = array_map(function($value) use ($conexao) {
                        if ($value === null) return 'NULL';
                        return "'" . $conexao->real_escape_string($value) . "'";
                    }, $row);
                    
                    fwrite($handle, "INSERT INTO `$table` SET " . 
                           implode(", ", array_map(function($k, $v) {
                               return "`$k`=$v";
                           }, array_keys($row), $values)) . ";\n");
                }
                fwrite($handle, "\n");
            }
        }
        
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
        $conexao->close();
        
        debug("Backup do banco de dados concluído: " . formatBytes(filesize($output_file)));
        return $output_file;
        
    } catch (Exception $e) {
        debug("Erro no backup do banco: " . $e->getMessage());
        if (isset($handle) && is_resource($handle)) fclose($handle);
        if (isset($conexao)) $conexao->close();
        return false;
    }
}

// Adicione esta nova função para backup dos arquivos
function backupFiles($config) {
    try {
        $output_file = $config['backup_path'] . 'files_' . $config['date_format'] . '.zip';
        debug("Iniciando backup dos arquivos para: " . basename($output_file));
        
        $zip = new ZipArchive();
        if ($zip->open($output_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Não foi possível criar o arquivo ZIP");
        }
        
        // Lista de arquivos/diretórios a serem excluídos do backup
        $exclude_list = array(
            'backups',
            'backup_errors.log',
            '.git',
            '.gitignore',
            'tmp',
            basename($output_file)
        );
        
        // Diretório raiz
        $rootPath = dirname(__FILE__);
        debug("Diretório raiz: " . $rootPath);
        
        // Recursivamente adicionar arquivos
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $count = 0;
        foreach ($files as $file) {
            // Pular diretórios . e ..
            if ($file->getBasename() === '.' || $file->getBasename() === '..') {
                continue;
            }
            
            // Verificar se o arquivo/diretório deve ser excluído
            $relativePath = substr($file->getPathname(), strlen($rootPath) + 1);
            $shouldExclude = false;
            foreach ($exclude_list as $exclude) {
                if (strpos($relativePath, $exclude) === 0) {
                    $shouldExclude = true;
                    break;
                }
            }
            
            if ($shouldExclude) {
                continue;
            }
            
            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), $relativePath);
                debug("Adicionado: " . $relativePath);
                $count++;
            }
        }
        
        $zip->close();
        
        debug("Total de arquivos incluídos no backup: " . $count);
        debug("Backup dos arquivos concluído: " . formatBytes(filesize($output_file)));
        
        return $output_file;
        
    } catch (Exception $e) {
        debug("Erro no backup dos arquivos: " . $e->getMessage());
        if (isset($zip) && $zip instanceof ZipArchive) {
            $zip->close();
        }
        if (file_exists($output_file)) {
            unlink($output_file);
        }
        return false;
    }
}

// Adicione esta nova função para criar o backup final
function createFinalBackup($db_file, $files_file, $config) {
    try {
        $output_file = $config['backup_path'] . 'backup_' . $config['date_format'] . '.zip';
        debug("Criando backup final: " . basename($output_file));
        
        $zip = new ZipArchive();
        if ($zip->open($output_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Não foi possível criar o arquivo ZIP final");
        }
        
        // Adicionar arquivo do banco de dados
        $zip->addFile($db_file, 'database/' . basename($db_file));
        debug("Arquivo do banco de dados adicionado");
        
        // Adicionar arquivo de arquivos do sistema
        $zip->addFile($files_file, 'files/' . basename($files_file));
        debug("Arquivo de arquivos do sistema adicionado");
        
        $zip->close();
        
        debug("Backup final criado: " . formatBytes(filesize($output_file)));
        return $output_file;
        
    } catch (Exception $e) {
        debug("Erro ao criar backup final: " . $e->getMessage());
        if (isset($zip) && $zip instanceof ZipArchive) {
            $zip->close();
        }
        if (file_exists($output_file)) {
            unlink($output_file);
        }
        return false;
    }
}

// Função para enviar email usando o padrão do sistema
function sendBackupEmail($backup_file) {
    try {
        if (!file_exists($backup_file)) {
            throw new Exception("Arquivo de backup não encontrado");
        }
        
        debug("Preparando envio do email...");
        
        $destinatario = 'rafael@inteegra.com.br';
        $assunto = "Backup do Sistema - " . date('d/m/Y H:i');
        
        // Template do email no padrão do sistema
        $mensagem = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
                <h2 style='color: #333; margin: 0;'>Backup do Sistema</h2>
            </div>
            
            <div style='margin-bottom: 20px;'>
                <p><strong>Data:</strong> " . date('d/m/Y H:i:s') . "</p>
                <p><strong>Arquivo:</strong> " . basename($backup_file) . "</p>
                <p><strong>Tamanho:</strong> " . formatBytes(filesize($backup_file)) . "</p>
            </div>
            
            <div style='color: #666; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                <p>Este é um email automático do sistema de backup.</p>
            </div>
        </div>";
        
        // Headers do email
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: Sistema GCP <eventos@inteegra.com.br>',
            'X-Mailer: PHP/' . phpversion()
        );
        
        // Enviar email
        if (mail($destinatario, $assunto, $mensagem, implode("\r\n", $headers))) {
            debug("Email enviado com sucesso para: " . $destinatario);
            return true;
        } else {
            throw new Exception("Erro ao enviar email");
        }
        
    } catch (Exception $e) {
        debug("Erro ao enviar email: " . $e->getMessage());
        return false;
    }
}

// Modifique o bloco de execução principal
try {
    debug("Iniciando processo de backup...");
    
    // Verificar/criar diretório de backup
    if (!file_exists($config['backup_path'])) {
        debug("Criando diretório de backup...");
        if (!mkdir($config['backup_path'], 0755, true)) {
            throw new Exception("Não foi possível criar o diretório de backup");
        }
    }
    
    // Backup do banco de dados
    $db_file = backupDatabase($config);
    if (!$db_file) {
        throw new Exception("Falha no backup do banco de dados");
    }
    
    // Backup dos arquivos
    $files_file = backupFiles($config);
    if (!$files_file) {
        throw new Exception("Falha no backup dos arquivos");
    }
    
    // Criar backup final
    $final_file = createFinalBackup($db_file, $files_file, $config);
    if (!$final_file) {
        throw new Exception("Falha ao criar backup final");
    }
    
    // Enviar email
    if (!sendBackupEmail($final_file)) {
        debug("Aviso: Não foi possível enviar o email, mas o backup foi criado com sucesso");
    }
    
    // Limpar arquivos temporários
    if (file_exists($db_file)) unlink($db_file);
    if (file_exists($files_file)) unlink($files_file);
    
    debug("Processo de backup concluído com sucesso!");
    debug("Arquivo final: " . basename($final_file) . " (" . formatBytes(filesize($final_file)) . ")");
    
    // Registrar no log do sistema
    registrarLog(0, "Backup completo realizado: " . basename($final_file));
    
} catch (Exception $e) {
    debug("ERRO: " . $e->getMessage());
    registrarLog(0, "Erro no backup: " . $e->getMessage());
    
    // Limpar arquivos em caso de erro
    if (isset($db_file) && file_exists($db_file)) unlink($db_file);
    if (isset($files_file) && file_exists($files_file)) unlink($files_file);
    if (isset($final_file) && file_exists($final_file)) unlink($final_file);
}
?>