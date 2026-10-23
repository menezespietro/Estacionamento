<?php
require "../config/banco.php";
$tituloPagina = "Inserção manual no banco";
$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST["acao"] ?? "";
    try {
        if ($acao === "cliente") {
            $nome = trim($_POST["nome"] ?? "");
            $cpf = trim($_POST["cpf"] ?? "");
            $telefone = trim($_POST["telefone"] ?? "");
            $preferencial = isset($_POST["preferencial"]) ? 1 : 0;
            if ($nome === "" || $cpf === "") throw new Exception("Nome e CPF são obrigatórios.");
            $st = $db->prepare("INSERT INTO clientes (nome, cpf, telefone, preferencial) VALUES (?, ?, ?, ?)");
            $st->execute([$nome, $cpf, $telefone, $preferencial]);
            $mensagem = "Cliente inserido no banco com sucesso!";
        } elseif ($acao === "veiculo") {
            $placa = strtoupper(trim($_POST["placa"] ?? ""));
            $marca = trim($_POST["marca"] ?? ""); $modelo = trim($_POST["modelo"] ?? "");
            $cor = trim($_POST["cor"] ?? ""); $ano = trim($_POST["ano"] ?? "");
            $tamanho = $_POST["tamanho"] ?? "Médio"; $tipo = $_POST["tipo"] ?? "Carro";
            $cliente = $_POST["cliente_id"] ?? "";
            if ($placa === "" || $marca === "" || $modelo === "") throw new Exception("Placa, marca e modelo são obrigatórios.");
            $st = $db->prepare("INSERT INTO veiculos (placa, marca, modelo, cor, ano, tamanho, tipo, cliente_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([$placa, $marca, $modelo, $cor ?: null, $ano !== "" ? (int)$ano : null, $tamanho, $tipo, $cliente !== "" ? (int)$cliente : null]);
            $mensagem = "Veículo inserido no banco com sucesso!";
        } elseif ($acao === "vaga") {
            $numero = (int)($_POST["numero"] ?? 0); $tipo = $_POST["tipo_vaga"] ?? "Normal";
            $tamanho = $_POST["tamanho_vaga"] ?? "Médio"; $status = $_POST["status"] ?? "Livre";
            if ($numero <= 0) throw new Exception("Informe um número de vaga maior que zero.");
            $st = $db->prepare("INSERT INTO vagas (numero, tipo, tamanho, status) VALUES (?, ?, ?, ?)");
            $st->execute([$numero, $tipo, $tamanho, $status]);
            $mensagem = "Vaga inserida no banco com sucesso!";
        } elseif ($acao === "preco") {
            $tipo = trim($_POST["tipo_preco"] ?? ""); $valor = str_replace(',', '.', trim($_POST["valor"] ?? ""));
            if ($tipo === "" || !is_numeric($valor) || (float)$valor < 0) throw new Exception("Informe um tipo e um valor válido.");
            $st = $db->prepare("INSERT INTO precos (tipo, valor) VALUES (?, ?) ON CONFLICT(tipo) DO UPDATE SET valor = excluded.valor");
            $st->execute([$tipo, (float)$valor]);
            $mensagem = "Preço inserido/atualizado com sucesso!";
        } else { throw new Exception("Selecione um tipo de cadastro válido."); }
        $tipoMensagem = "success";
    } catch (Throwable $e) {
        $mensagem = ($e instanceof PDOException && strpos($e->getMessage(), 'UNIQUE') !== false)
            ? "Já existe um registro com esse CPF, placa, número de vaga ou tipo de preço."
            : $e->getMessage();
        $tipoMensagem = "danger";
    }
}
$clientes = $db->query("SELECT id, nome, cpf FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
require "../includes/header.php";
?>
<div class="mb-4"><h1 class="page-title">Inserção manual no banco</h1><p class="page-subtitle">Cadastre diretamente no SQLite, sem passar pelas telas normais do sistema.</p></div>
<?php if ($mensagem !== ""): ?><div class="alert alert-<?= htmlspecialchars($tipoMensagem) ?>"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
<div class="alert alert-warning"><strong>Atenção:</strong> os cadastros respeitam as regras do banco. CPF, placa e número de vaga precisam ser únicos. Para registrar a entrada de um veículo, use a tela <a href="entrada.php">Entrada</a>, que também atualiza o estado da vaga.</div>
<div class="row g-4">
<div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-body"><h4>Adicionar cliente</h4><form method="post"><input type="hidden" name="acao" value="cliente"><div class="mb-3"><label class="form-label">Nome *</label><input class="form-control" name="nome" required></div><div class="mb-3"><label class="form-label">CPF *</label><input class="form-control" name="cpf" required></div><div class="mb-3"><label class="form-label">Telefone</label><input class="form-control" name="telefone"></div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="preferencial" id="pref"><label class="form-check-label" for="pref">Cliente preferencial</label></div><button class="btn btn-primary">Salvar cliente</button></form></div></div></div>
<div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-body"><h4>Adicionar veículo</h4><form method="post"><input type="hidden" name="acao" value="veiculo"><div class="row"><div class="col-md-6 mb-3"><label class="form-label">Placa *</label><input class="form-control" name="placa" required></div><div class="col-md-6 mb-3"><label class="form-label">Marca *</label><input class="form-control" name="marca" required></div><div class="col-md-6 mb-3"><label class="form-label">Modelo *</label><input class="form-control" name="modelo" required></div><div class="col-md-6 mb-3"><label class="form-label">Cor</label><input class="form-control" name="cor"></div><div class="col-md-6 mb-3"><label class="form-label">Ano</label><input class="form-control" type="number" name="ano" min="1900" max="2100"></div><div class="col-md-6 mb-3"><label class="form-label">Tamanho</label><select class="form-select" name="tamanho"><option>Pequeno</option><option selected>Médio</option><option>Grande</option></select></div><div class="col-md-6 mb-3"><label class="form-label">Tipo</label><select class="form-select" name="tipo"><option>Carro</option><option>Moto</option><option>Utilitário</option><option>Caminhão</option><option>Van</option><option>Outro</option></select></div><div class="col-md-6 mb-3"><label class="form-label">Proprietário</label><select class="form-select" name="cliente_id"><option value="">Sem cliente vinculado</option><?php foreach ($clientes as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome'].' — '.$c['cpf']) ?></option><?php endforeach; ?></select></div></div><button class="btn btn-primary">Salvar veículo</button></form></div></div></div>
<div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-body"><h4>Adicionar vaga</h4><form method="post"><input type="hidden" name="acao" value="vaga"><div class="mb-3"><label class="form-label">Número *</label><input class="form-control" type="number" name="numero" min="1" required></div><div class="mb-3"><label class="form-label">Tipo</label><select class="form-select" name="tipo_vaga"><option>Normal</option><option>Preferencial</option></select></div><div class="mb-3"><label class="form-label">Tamanho</label><select class="form-select" name="tamanho_vaga"><option>Pequeno</option><option selected>Médio</option><option>Grande</option></select></div><div class="mb-3"><label class="form-label">Estado</label><select class="form-select" name="status"><option>Livre</option><option>Ocupada</option></select></div><button class="btn btn-primary">Salvar vaga</button></form></div></div></div>
<div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-body"><h4>Adicionar ou alterar preço</h4><p class="text-muted">Se o tipo já existir, o valor será atualizado.</p><form method="post"><input type="hidden" name="acao" value="preco"><div class="mb-3"><label class="form-label">Tipo *</label><select class="form-select" name="tipo_preco"><option>Hora</option><option>Mensal</option><option>Anual</option></select></div><div class="mb-3"><label class="form-label">Valor em R$ *</label><input class="form-control" type="number" name="valor" min="0" step="0.01" required></div><button class="btn btn-primary">Salvar preço</button></form></div></div></div>
</div>
<?php require "../includes/footer.php"; ?>
