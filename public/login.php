<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (!empty($_SESSION['usuario_id'])) {
    $limiteInatividade = 1800; // 30 minutos
    $ultimoAcesso = $_SESSION['ultimo_acesso'] ?? null;

    if (
        is_int($ultimoAcesso) &&
        (time() - $ultimoAcesso) <= $limiteInatividade
    ) {
        header('Location: /painel.php');
        exit;
    }

    // Sessão expirada ou legada sem marcadores: encerra a sessão anterior
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $opcoes = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $opcoes['path'],
            'domain' => $opcoes['domain'],
            'secure' => $opcoes['secure'],
            'httponly' => $opcoes['httponly'],
            'samesite' => $opcoes['samesite'],
        ]);
    }
    session_destroy();

    // Redireciona para uma nova requisição GET limpa, garantindo que a página
    // não continue sem sessão ativa e permitindo que o bootstrap inicie uma nova
    // sessão com token CSRF renovado.
    header('Location: /login.php');
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '');

    if ($ip === false) {
        http_response_code(400);
        exit('Não foi possível identificar a conexão.');
    }

    try {
        $pdo = banco();

        // Conta falhas registradas para o IP nos últimos 15 minutos.
        $consulta = $pdo->prepare(
            'SELECT COUNT(*) FROM tentativas_login
             WHERE ip = :ip
             AND ocorreu_em >= NOW() - INTERVAL 15 MINUTE'
        );
        $consulta->execute(['ip' => $ip]);
        $falhas = (int) $consulta->fetchColumn();

        if ($falhas >= 5) {
            $mensagem = 'Muitas tentativas. Aguarde 15 minutos e tente novamente.';
        } elseif (
            $usuario === '' || strlen($usuario) > 100 ||
            $senha === '' || strlen($senha) > 256
        ) {
            $mensagem = 'Usuário ou senha inválidos.';
        } else {
            $consulta = $pdo->prepare(
                'SELECT id, nome, senha_hash FROM usuarios
                 WHERE usuario = :usuario LIMIT 1'
            );
            $consulta->execute(['usuario' => $usuario]);
            $registro = $consulta->fetch(PDO::FETCH_ASSOC);

            // Hash de referência fictício para atuar como mitigação que reduz diferenças
            // de tempo de resposta (timing attacks) entre usuários existentes e inexistentes,
            // sem expor credenciais reais.
            $hashReferencia = '$2y$10$nOUIs54PnAhSRe8BCqYguOkDEo52qA15C/g/w.3kQeW5x8b1T8zK.';
            $hashAlvo = ($registro && !empty($registro['senha_hash']))
                ? (string) $registro['senha_hash']
                : $hashReferencia;

            $senhaValida = password_verify($senha, $hashAlvo);

            if ($registro && $senhaValida) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = (int) $registro['id'];
                $_SESSION['nome'] = $registro['nome'];
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                $_SESSION['ultimo_acesso'] = time();

                header('Location: /painel.php');
                exit;
            }

            $inserir = $pdo->prepare(
                'INSERT INTO tentativas_login (ip) VALUES (:ip)'
            );
            $inserir->execute(['ip' => $ip]);

            $mensagem = 'Usuário ou senha inválidos.';
        }
    } catch (Throwable $erro) {
        error_log('Falha no login do painel: ' . $erro->getMessage());
        http_response_code(500);
        $mensagem = 'Não foi possível acessar o sistema neste momento.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar | Painel de Demandas de TI</title>
    <link rel="stylesheet" href="/estilo.css">
</head>
<body>
<main class="acesso">
    <section class="cartao">
        <p class="identificacao">PROJETO APLICADO</p>
        <h1>Painel de Demandas de TI</h1>
        <p>Acesse para acompanhar as demandas de TI.</p>

        <?php if ($mensagem !== ''): ?>
            <p class="mensagem erro" role="alert">
                <?= escapar($mensagem) ?>
            </p>
        <?php endif; ?>

        <form method="post" action="/login.php">
            <input type="hidden" name="csrf" value="<?= escapar(csrf_token()) ?>">

            <label for="usuario">Usuário</label>
            <input id="usuario" name="usuario" type="text"
                   maxlength="100" autocomplete="username" required>

            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password"
                   maxlength="256" autocomplete="current-password" required>

            <button type="submit">Entrar</button>
        </form>
    </section>
</main>
</body>
</html>
