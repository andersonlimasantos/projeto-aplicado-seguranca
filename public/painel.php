<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
exigir_login();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    $prioridade = (string) ($_POST['prioridade'] ?? '');

    if (
        $titulo === '' || strlen($titulo) > 150 ||
        $descricao === '' || strlen($descricao) > 5000 ||
        !in_array($prioridade, ['Baixa', 'Média', 'Alta'], true)
    ) {
        $erro = 'Confira os campos e tente novamente.';
    } else {
        try {
            $pdo = banco();
            $pdo->beginTransaction();

            $comando = $pdo->prepare(
                'INSERT INTO demandas (titulo, descricao, prioridade)
                 VALUES (:titulo, :descricao, :prioridade)'
            );

            $comando->execute([
                'titulo' => $titulo,
                'descricao' => $descricao,
                'prioridade' => $prioridade,
            ]);

            $demandaId = (int) $pdo->lastInsertId();

            registrar_auditoria(
                $pdo,
                'CADASTRO_DEMANDA',
                isset($_SESSION['usuario_id']) && is_int($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null,
                (string) ($_SESSION['usuario'] ?? $_SESSION['nome'] ?? 'desconhecido'),
                $demandaId,
                'Prioridade: ' . $prioridade
            );

            $pdo->commit();

            header('Location: /painel.php?salvo=1');
            exit;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao cadastrar demanda: ' . $e->getMessage());
            $erro = 'Não foi possível cadastrar a demanda.';
        }
    }
}

if (isset($_GET['salvo'])) {
    $mensagem = 'Demanda cadastrada com sucesso.';
}

try {
    $consulta = banco()->query(
        'SELECT titulo, descricao, prioridade, situacao, criado_em
         FROM demandas ORDER BY id DESC LIMIT 100'
    );
    $demandas = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Erro ao consultar demandas: ' . $e->getMessage());
    $demandas = [];
    $erro = 'Não foi possível consultar as demandas.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel de Demandas de TI</title>
    <link rel="stylesheet" href="/estilo.css">
</head>
<body>
<main class="conteudo">
    <header class="topo">
        <div>
            <p class="identificacao">PROJETO APLICADO</p>
            <h1>Painel de Demandas de TI</h1>
            <p>Olá, <?= escapar((string) ($_SESSION['nome'] ?? '')) ?>.</p>
        </div>
        <nav class="acoes-topo">
            <a href="/auditoria.php">Trilha de auditoria</a>
            <form method="post" action="/logout.php">
                <input type="hidden" name="csrf" value="<?= escapar(csrf_token()) ?>">
                <button class="secundario" type="submit">Sair</button>
            </form>
        </nav>
    </header>

    <?php if ($mensagem !== ''): ?>
        <p class="mensagem sucesso" role="status"><?= escapar($mensagem) ?></p>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <p class="mensagem erro" role="alert"><?= escapar($erro) ?></p>
    <?php endif; ?>

    <section class="cartao">
        <h2>Nova demanda</h2>
        <form method="post" action="/painel.php">
            <input type="hidden" name="csrf" value="<?= escapar(csrf_token()) ?>">

            <label for="titulo">Título</label>
            <input id="titulo" name="titulo" maxlength="150" required>

            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao"
                      maxlength="5000" rows="4" required></textarea>

            <label for="prioridade">Prioridade</label>
            <select id="prioridade" name="prioridade" required>
                <option value="Baixa">Baixa</option>
                <option value="Média">Média</option>
                <option value="Alta">Alta</option>
            </select>

            <button type="submit">Cadastrar demanda</button>
        </form>
    </section>

    <section class="cartao">
        <h2>Demandas cadastradas</h2>

        <?php if (!$demandas): ?>
            <p>Nenhuma demanda para exibir.</p>
        <?php else: ?>
            <?php foreach ($demandas as $demanda): ?>
                <article class="demanda">
                    <h3><?= escapar($demanda['titulo']) ?></h3>
                    <p><?= nl2br(escapar($demanda['descricao'])) ?></p>
                    <p>
                        <strong>Prioridade:</strong>
                        <?= escapar($demanda['prioridade']) ?>
                        |
                        <strong>Situação:</strong>
                        <?= escapar($demanda['situacao']) ?>
                    </p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
