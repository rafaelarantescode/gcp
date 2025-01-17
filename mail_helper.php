<?php
    function enviarEmailCusto($dados) {
        // Validar dados obrigatórios
        $campos_obrigatorios = [
            'id', 'solicitante_nome', 'solicitante_email', 'aprovador_nome', 
            'aprovador_email', 'projeto_nome', 'created_at', 'updated_at',
            'tipo_custo', 'tipo_origem', 'valor_total', 'justificativa', 'status'
        ];

        foreach ($campos_obrigatorios as $campo) {
            if (!isset($dados[$campo])) {
                error_log("Campo obrigatório não fornecido: $campo", 3, 'logs/email_custo_erro.log');
                return false;
            }
        }

        // URL base do sistema 
        $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $link_custo = $base_url . "/index.php?modulo=editar_custo_projeto&id=" . $dados['id'];

        // Template do e-mail
        $template_email = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .info-item { margin-bottom: 10px; }
                .label { font-weight: bold; color: #666; }
                .value { color: #333; }
                .status { padding: 5px 10px; border-radius: 3px; font-weight: bold; }
                .status-pendente { background-color: #ffeeba; color: #856404; }
                .status-aprovado { background-color: #d4edda; color: #155724; }
                .status-reprovado { background-color: #f8d7da; color: #721c24; }
                .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; 
                        text-decoration: none; border-radius: 5px; margin-top: 20px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Atualização de Custo de Projeto</h2>
                    <p>Este é um e-mail automático sobre a atualização de um custo de projeto.</p>
                </div>

                <div class='info-item'>
                    <span class='label'>Solicitante:</span>
                    <span class='value'>{$dados['solicitante_nome']}</span>
                </div>

                <div class='info-item'>
                    <span class='label'>Projeto:</span>
                    <span class='value'>{$dados['projeto_nome']}</span>
                </div>

                <div class='info-item'>
                    <span class='label'>Data de Criação:</span>
                    <span class='value'>" . date('d/m/Y H:i', strtotime($dados['created_at'])) . "</span>
                </div>

                <div class='info-item'>
                    <span class='label'>Última Atualização:</span>
                    <span class='value'>" . date('d/m/Y H:i', strtotime($dados['updated_at'])) . "</span>
                </div>

                <div class='info-item'>
                    <span class='label'>Tipo de Custo:</span>
                    <span class='value'>{$dados['tipo_custo']}</span>
                </div>

                <div class='info-item'>
                    <span class='label'>Tipo de Origem:</span>
                    <span class='value'>{$dados['tipo_origem']}</span>
                </div>

                <div class='info-item'>
                    <span class='label'>Valor Total:</span>
                    <span class='value'>R$ " . number_format($dados['valor_total'], 2, ',', '.') . "</span>
                </div>

                <div class='info-item'>
                    <span class='label'>Status:</span>
                    <span class='status status-" . strtolower($dados['status']) . "'>{$dados['status']}</span>
                </div>

                <div class='info-item'>
                    <span class='label'>Justificativa:</span>
                    <p class='value'>" . nl2br(htmlspecialchars($dados['justificativa'])) . "</p>
                </div>

                <a href='{$link_custo}' class='button'>Visualizar Custo</a>

                <div class='footer'>
                    <p>Este é um e-mail automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>";

        // Headers do e-mail
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: Sistema GCP <eventos@inteegra.com.br>',
            'X-Mailer: PHP/' . phpversion()
        ];

        // Enviar para o solicitante
        $assunto_solicitante = "Custo de Projeto - {$dados['status']}";
        $resultado_solicitante = @mail($dados['solicitante_email'], $assunto_solicitante, $template_email, implode("\r\n", $headers));

        // Enviar para o aprovador
        $assunto_aprovador = "Novo Custo de Projeto para Aprovação - {$dados['projeto_nome']}";
        $resultado_aprovador = @mail($dados['aprovador_email'], $assunto_aprovador, $template_email, implode("\r\n", $headers));

        // Logar resultados
        if (!$resultado_solicitante) {
            error_log("Falha ao enviar email para solicitante: {$dados['solicitante_email']}", 3, 'logs/email_custo_erro.log');
        }
        
        if (!$resultado_aprovador) {
            error_log("Falha ao enviar email para aprovador: {$dados['aprovador_email']}", 3, 'logs/email_custo_erro.log');
        }

        return $resultado_solicitante && $resultado_aprovador;
    }

    // Função auxiliar para preparar os dados do e-mail
    function prepararDadosEmail($conexao, $custo_id) {
        $query = "SELECT 
            cp.*,
            p.nome as projeto_nome,
            us.nome as solicitante_nome,
            us.email as solicitante_email,
            ua.nome as aprovador_nome,
            ua.email as aprovador_email
        FROM custos_projeto cp
        JOIN projetos p ON cp.projeto_id = p.id
        JOIN usuarios us ON cp.usuario_id = us.id
        JOIN usuarios ua ON cp.aprovador_id = ua.id
        WHERE cp.id = ?";

        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $custo_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $dados = $resultado->fetch_assoc();

        if (!$dados) {
            return null;
        }

        return $dados;
    }

    function prepararDadosEmailPagamento($conexao, $pagamento_id) {
        $query = "SELECT 
            p.*,
            us.nome as solicitante_nome,
            us.email as solicitante_email,
            ua.nome as aprovador_nome,
            ua.email as aprovador_email,
            COUNT(pc.custo_id) as total_custos
        FROM pagamentos p
        JOIN usuarios us ON p.solicitante_id = us.id
        JOIN usuarios ua ON p.aprovador_id = ua.id
        LEFT JOIN pagamentos_custos pc ON p.id = pc.pagamento_id
        WHERE p.id = ?
        GROUP BY p.id";
    
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $pagamento_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }
    
    function enviarEmailPagamento($dados) {
        $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $link = $base_url . "/index.php?modulo=visualizar_pagamento&id=" . $dados['id'];
    
        // Personalizar mensagem conforme status
        $assunto = "Atualização de Pagamento - {$dados['status']}";
        $mensagem_status = "";
    
        switch($dados['status']) {
            case 'Aprovado':
                $mensagem_status = "O pagamento foi aprovado. Por favor, anexe a Nota Fiscal.";
                break;
            case 'Pendente NF':
                $mensagem_status = "A Nota Fiscal foi anexada e está aguardando processamento do pagamento.";
                break;
            case 'Pago':
                $mensagem_status = "O pagamento foi processado e finalizado.";
                break;
            default:
                $mensagem_status = "O status do pagamento foi atualizado.";
        }
    
        $template = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .info-item { margin-bottom: 10px; }
                .status { padding: 5px 10px; border-radius: 3px; font-weight: bold; }
                .button { display: inline-block; padding: 10px 20px; background-color: #007bff; 
                         color: #fff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Atualização de Pagamento</h2>
                    <p>{$mensagem_status}</p>
                </div>
    
                <div class='info-item'>
                    <h3>Detalhes do Pagamento</h3>
                    <p><strong>Período:</strong> {$dados['mes']}/{$dados['ano']}</p>
                    <p><strong>Valor Total:</strong> R$ " . number_format($dados['valor_total'], 2, ',', '.') . "</p>
                    <p><strong>Solicitante:</strong> {$dados['solicitante_nome']}</p>
                    <p><strong>Aprovador:</strong> {$dados['aprovador_nome']}</p>
                    <p><strong>Status:</strong> {$dados['status']}</p>
                    <p><strong>Custos Incluídos:</strong> {$dados['total_custos']}</p>
                </div>
    
                <a href='{$link}' class='button'>Visualizar Pagamento</a>
    
                <div style='margin-top: 30px; font-size: 12px; color: #666;'>
                    <p>Este é um email automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>";
    
        // Headers do e-mail
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: Sistema GCP <eventos@inteegra.com.br>',
            'X-Mailer: PHP/' . phpversion()
        ];
    
        // Enviar para solicitante
        $resultado_solicitante = @mail($dados['solicitante_email'], $assunto, $template, implode("\r\n", $headers));
    
        // Enviar para aprovador se não estiver pago
        $resultado_aprovador = true;
        if ($dados['status'] !== 'Pago') {
            $resultado_aprovador = @mail($dados['aprovador_email'], $assunto, $template, implode("\r\n", $headers));
        }
    
        // Logar resultados
        if (!$resultado_solicitante) {
            error_log("Falha ao enviar email para solicitante: {$dados['solicitante_email']}", 3, 'logs/email_pagamento_erro.log');
        }
        
        if (!$resultado_aprovador) {
            error_log("Falha ao enviar email para aprovador: {$dados['aprovador_email']}", 3, 'logs/email_pagamento_erro.log');
        }
    
        return $resultado_solicitante && $resultado_aprovador;
    }