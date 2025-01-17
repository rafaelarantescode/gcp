<?php
require_once 'session.php';
require_once 'db_connection.php';
verificarLogin();

$conexao = conectar();

// Estados brasileiros
$estados = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 
    'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará', 
    'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 
    'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 
    'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais', 
    'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 
    'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 
    'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul', 
    'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 
    'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
];

// Definição de gêneros inclusivos
$generos = [
    'Masculino',
    'Feminino', 
    'Não-Binário', 
    'Transgênero', 
    'Gênero Fluido', 
    'Prefiro não declarar'
];

// Tipos de deficiência
$deficiencias = [
    'Nenhuma', 
    'Física', 
    'Visual', 
    'Auditiva', 
    'Intelectual', 
    'Múltipla',
    'Outra'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validação e sanitização dos dados
        $nome = trim($_POST['nome']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $data_nascimento = $_POST['data_nascimento'];
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $rg = preg_replace('/[^0-9]/', '', $_POST['rg']);
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj']);
        $razao_social = trim($_POST['razao_social']);
        $celular = preg_replace('/[^0-9]/', '', $_POST['celular']);
        $endereco = trim($_POST['endereco']);
        $cidade = trim($_POST['cidade']);
        $estado = trim($_POST['estado']);
        $tamanho_camiseta = $_POST['tamanho_camiseta'];
        $habilitacao = $_POST['habilitacao'];
        $genero = $_POST['genero'];
        $deficiencia = $_POST['deficiencia'];
        $blacklist = isset($_POST['blacklist']) ? 1 : 0;

        // Validações
        if (empty($nome)) {
            throw new Exception("Nome é obrigatório");
        }
        if (!$email) {
            throw new Exception("Email inválido");
        }
        if (empty($data_nascimento)) {
            throw new Exception("Data de nascimento é obrigatória");
        }
        if (strlen($cpf) != 11) {
            throw new Exception("CPF inválido");
        }
        if (empty($rg)) {
            throw new Exception("RG é obrigatório");
        }
        if (empty($celular)) {
            throw new Exception("Celular é obrigatório");
        }
        if (empty($endereco)) {
            throw new Exception("Endereço é obrigatório");
        }
        if (empty($cidade)) {
            throw new Exception("Cidade é obrigatória");
        }
        if (empty($estado)) {
            throw new Exception("Estado é obrigatório");
        }
        if (empty($tamanho_camiseta)) {
            throw new Exception("Tamanho de camiseta é obrigatório");
        }

        // Preparar query
        $query = "INSERT INTO prestadores (
            nome, email, data_nascimento, cpf, rg, cnpj, razao_social, 
            celular, endereco, cidade, estado, tamanho_camiseta, 
            habilitacao, genero, deficiencia, blacklist
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexao->prepare($query);
        $stmt->bind_param(
            "sssssssssssssssi", 
            $nome, $email, $data_nascimento, $cpf, $rg, $cnpj, 
            $razao_social, $celular, $endereco, $cidade, $estado, 
            $tamanho_camiseta, $habilitacao, $genero, $deficiencia, $blacklist
        );

        if ($stmt->execute()) {
            $novo_prestador_id = $conexao->insert_id;
            registrarLog($_SESSION['usuario_id'], "Criou prestador ID $novo_prestador_id");
            $_SESSION['success_message'] = "Prestador criado com sucesso!";
            header("Location: index.php?modulo=listar_prestadores");
            exit();
        } else {
            throw new Exception("Erro ao criar prestador: " . $stmt->error);
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: index.php?modulo=criar_prestador");
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Criar Novo Prestador</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="index.php?modulo=criar_prestador" method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="col-md-4">
                        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                        <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
                    </div>

                    <div class="col-md-4">
                        <label for="cpf" class="form-label">CPF</label>
                        <input type="text" class="form-control" id="cpf" name="cpf" required data-mask="000.000.000-00">
                    </div>

                    <div class="col-md-4">
                        <label for="rg" class="form-label">RG</label>
                        <input type="text" class="form-control" id="rg" name="rg" required>
                    </div>

                    <div class="col-md-6">
                        <label for="cnpj" class="form-label">CNPJ</label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj" data-mask="00.000.000/0000-00">
                    </div>

                    <div class="col-md-6">
                        <label for="razao_social" class="form-label">Razão Social</label>
                        <input type="text" class="form-control" id="razao_social" name="razao_social">
                    </div>

                    <div class="col-md-4">
                        <label for="celular" class="form-label">Celular</label>
                        <input type="text" class="form-control" id="celular" name="celular" required data-mask="(00) 00000-0000">
                    </div>

                    <div class="col-md-8">
                        <label for="endereco" class="form-label">Endereço Completo</label>
                        <input type="text" class="form-control" id="endereco" name="endereco" required>
                    </div>

                    <div class="col-md-4">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Selecione</option>
                            <?php foreach ($estados as $sigla => $nome): ?>
                                <option value="<?php echo $sigla; ?>"><?php echo $nome; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade" required>
                    </div>

                    <div class="col-md-2">
                        <label for="tamanho_camiseta" class="form-label">Camiseta</label>
                        <select class="form-select" id="tamanho_camiseta" name="tamanho_camiseta" required>
                            <option value="">Selecione</option>
                            <option value="PP">PP</option>
                            <option value="P">P</option>
                            <option value="M">M</option>
                            <option value="G">G</option>
                            <option value="GG">GG</option>
                            <option value="XG">XG</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="habilitacao" class="form-label">Habilitação e Dirige?</label>
                        <select class="form-select" id="habilitacao" name="habilitacao" required>
                            <option value="">Selecione</option>
                            <option value="Sim">Sim</option>
                            <option value="Não">Não</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="genero" class="form-label">Gênero</label>
                        <select class="form-select" id="genero" name="genero" required>
                            <option value="">Selecione</option>
                            <?php foreach ($generos as $gen): ?>
                                <option value="<?php echo $gen; ?>"><?php echo $gen; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="deficiencia" class="form-label">Deficiência</label>
                        <select class="form-select" id="deficiencia" name="deficiencia" required>
                            <option value="">Selecione</option>
                            <?php foreach ($deficiencias as $def): ?>
                                <option value="<?php echo $def; ?>"><?php echo $def; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="blacklist" name="blacklist" value="1">
                            <label class="form-check-label" for="blacklist">Blacklist</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php?modulo=listar_prestadores" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#cpf').mask('000.000.000-00');
    $('#celular').mask('(00) 00000-0000');
    $('#cnpj').mask('00.000.000/0000-00');
});
</script>