<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
exigir_login();

$erro = '';

try {
    $consulta = banco()->query(
        'SELECT id, usuario, tipo_evento, demanda_id, detalhes, criado_em, ip
         FROM auditoria
         ORDER BY id DESC
         LIMIT 100'
    );
    $registros = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Erro ao consultar trilha de auditoria: ' . $e->getMessage());
    $registros = [];
    $erro = 'Não foi possível consultar os registros de auditoria.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trilha de Auditoria | Painel de Demandas de TI</title>
    <link rel="stylesheet" href="/estilo.css">
</head>
<body>
<main class="conteudo">
    <header class="topo">
        <div>
            <p class="identificacao">PROJETO APLICADO</p>
            <h1>Trilha de Auditoria</h1>
            <p>Registro histórico das 100 operações mais recentes do sistema.</p>
        </div>
        <nav class="acoes-topo">
            <a href="/painel.php">Voltar ao painel</a>
            <form method="post" action="/logout.php">
                <input type="hidden" name="csrf" value="<?= escapar(csrf_token()) ?>">
                <button class="secundario" type="submit">Sair</button>
            </form>
        </nav>
    </header>

    <?php if ($erro !== ''): ?>
        <p class="mensagem erro" role="alert"><?= escapar($erro) ?></p>
    <?php else: ?>
        <section class="cartao">
            <h2>Eventos registrados (exibindo até os 100 mais recentes)</h2>

            <?php if (!$registros): ?>
                <p>Nenhum registro de auditoria encontrado.</p>
            <?php else: ?>
                <div class="tabela-responsiva">
                    <table class="tabela-auditoria">
                        <thead>
                            <tr>
                                <th>Data / Hora</th>
                                <th>Usuário</th>
                                <th>IP</th>
                                <th>Evento</th>
                                <th>Demanda</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $reg): ?>
                                <tr>
                                    <td><?= escapar((string) $reg['criado_em']) ?></td>
                                    <td><strong><?= escapar((string) $reg['usuario']) ?></strong></td>
                                    <td><?= escapar((string) ($reg['ip'] ?? 'Indisponível')) ?></td>
                                    <td>
                                        <?php
                                        $classeBadge = match ($reg['tipo_evento']) {
                                            'LOGIN_SUCESSO' => 'badge badge-login',
                                            'CADASTRO_DEMANDA' => 'badge badge-demanda',
                                            'LOGOUT' => 'badge badge-logout',
                                            default => 'badge',
                                        };
                                        ?>
                                        <span class="<?= $classeBadge ?>"><?= escapar((string) $reg['tipo_evento']) ?></span>
                                    </td>
                                    <td>
                                        <?= $reg['demanda_id'] !== null ? '#' . escapar((string) $reg['demanda_id']) : '-' ?>
                                    </td>
                                    <td><?= escapar((string) ($reg['detalhes'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
