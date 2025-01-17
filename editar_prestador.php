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

// Gêneros inclusivos
$generos = [
    'Masculino', 'Feminino', 'Não-Binário', 
    'Transgênero', 'Gênero Fluido', 'Prefiro não declarar'
];

// Tipos de deficiência
$deficiencias = [
    'Nenhuma', 'Física', 'Visual', 'Auditiva', 
    'Intelectual', 'Múltipla', 'Outra'
];

try {
    if (!isset($_GET['id'])) {
        throw new Exception("ID do prestador não especificado");
    }

    $id = intval($_GET['id']);
    $stmt = $conexao->prepare("SELECT * FROM prestadores WHERE id = ? AND ativo = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result->num_rows) {
        throw new Exception("Prestador não encontrado");
    }
    
    $prestador = $result->fetch_assoc();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: index.php?modulo=listar_prestadores");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

        $query = "UPDATE prestadores SET 
            nome = ?, email = ?, data_nascimento = ?, cpf = ?, rg = ?, 
            cnpj = ?, razao_social = ?, celular = ?, endereco = ?, 
            cidade = ?, estado = ?, tamanho_camiseta = ?, 
            habilitacao = ?, genero = ?, deficiencia = ?, blacklist = ?
            WHERE id = ?";

        $stmt = $conexao->prepare($query);
        $stmt->bind_param(
            "sssssssssssssssis", 
            $nome, $email, $data_nascimento, $cpf, $rg, 
            $cnpj, $razao_social, $celular, $endereco, 
            $cidade, $estado, $tamanho_camiseta, 
            $habilitacao, $genero, $deficiencia, $blacklist, $id
        );

        if ($stmt->execute()) {
            registrarLog($_SESSION['usuario_id'], "Atualizou prestador ID $id");
            $_SESSION['success_message'] = "Prestador atualizado com sucesso!";
            header("Location: index.php?modulo=listar_prestadores");
            exit();
        } else {
            throw new Exception("Erro ao atualizar prestador: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: index.php?modulo=editar_prestador&id=" . $id);
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Editar Prestador</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="index.php?modulo=editar_prestador&id=<?php echo $id; ?>" method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($prestador['nome']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($prestador['email']); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                        <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" 
                               value="<?php echo $prestador['data_nascimento']; ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="cpf" class="form-label">CPF</label>
                        <input type="text" class="form-control" id="cpf" name="cpf" 
                               value="<?php echo preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $prestador['cpf']); ?>" 
                               required data-mask="000.000.000-00">
                    </div>

                    <div class="col-md-4">
                        <label for="rg" class="form-label">RG</label>
                        <input type="text" class="form-control" id="rg" name="rg" 
                               value="<?php echo htmlspecialchars($prestador['rg']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="cnpj" class="form-label">CNPJ</label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj" 
                               value="<?php echo $prestador['cnpj'] ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $prestador['cnpj']) : ''; ?>" 
                               data-mask="00.000.000/0000-00">
                    </div>

                    <div class="col-md-6">
                        <label for="razao_social" class="form-label">Razão Social</label>
                        <input type="text" class="form-control" id="razao_social" name="razao_social" 
                               value="<?php echo htmlspecialchars($prestador['razao_social']); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="celular" class="form-label">Celular</label>
                        <input type="text" class="form-control" id="celular" name="celular" 
                               value="<?php echo preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $prestador['celular']); ?>" 
                               required data-mask="(00) 00000-0000">
                    </div>

                    <div class="col-md-8">
                        <label for="endereco" class="form-label">Endereço Completo</label>
                        <input type="text" class="form-control" id="endereco" name="endereco" 
                               value="<?php echo htmlspecialchars($prestador['endereco']); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Selecione</option>
                            <?php foreach ($estados as $sigla => $nome): ?>
                                <option value="<?php echo $sigla; ?>" 
                                        <?php echo $prestador['estado'] == $sigla ? 'selected' : ''; ?>>
                                    <?php echo $nome; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade" 
                               value="<?php echo htmlspecialchars($prestador['cidade']); ?>" required>
                    </div>


                    <div class="col-md-2">
                        <label for="tamanho_camiseta" class="form-label">Camiseta</label>
                        <select class="form-select" id="tamanho_camiseta" name="tamanho_camiseta" required>
                            <option value="">Selecione</option>
                            <?php $tamanhos = ['PP', 'P', 'M', 'G', 'GG', 'XG']; ?>
                            <?php foreach ($tamanhos as $tamanho): ?>
                                <option value="<?php echo $tamanho; ?>" 
                                        <?php echo $prestador['tamanho_camiseta'] == $tamanho ? 'selected' : ''; ?>>
                                    <?php echo $tamanho; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="habilitacao" class="form-label">Habilitação e Dirige?</label>
                        <select class="form-select" id="habilitacao" name="habilitacao" required>
                            <option value="">Selecione</option>
                            <option value="Sim" <?php echo $prestador['habilitacao'] == 'Sim' ? 'selected' : ''; ?>>Sim</option>
                            <option value="Não" <?php echo $prestador['habilitacao'] == 'Não' ? 'selected' : ''; ?>>Não</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="genero" class="form-label">Gênero</label>
                        <select class="form-select" id="genero" name="genero" required>
                            <option value="">Selecione</option>
                            <?php foreach ($generos as $gen): ?>
                                <option value="<?php echo $gen; ?>" 
                                        <?php echo $prestador['genero'] == $gen ? 'selected' : ''; ?>>
                                    <?php echo $gen; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="deficiencia" class="form-label">Deficiência</label>
                        <select class="form-select" id="deficiencia" name="deficiencia" required>
                            <option value="">Selecione</option>
                            <?php foreach ($deficiencias as $def): ?>
                                <option value="<?php echo $def; ?>" 
                                        <?php echo $prestador['deficiencia'] == $def ? 'selected' : ''; ?>>
                                    <?php echo $def; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="blacklist" name="blacklist" value="1"
                                   <?php echo $prestador['blacklist'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="blacklist">Blacklist</label>
                        </div>
                    </div>
                </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="index.php?modulo=listar_prestadores" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
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