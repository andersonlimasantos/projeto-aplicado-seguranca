<?php
declare(strict_types=1);

// Configurações de segurança da sessão.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Lax',
    'path' => '/',
]);

session_start();

// Cabeçalhos de proteção para as páginas.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header(
    "Content-Security-Policy: default-src 'self'; " .
    "style-src 'self'; frame-ancestors 'none'; " .
    "base-uri 'self'; form-action 'self'"
);

// As credenciais ficam somente na VM.
function banco(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = require '/etc/painel-ti/db.php';

        $pdo = new PDO(
            'mysql:host=' . $config['host'] .
            ';dbname=' . $config['database'] .
            ';charset=utf8mb4',
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    return $pdo;
}

// Trata textos antes de inseri-los no HTML.
function escapar(string $valor): string
{
    return htmlspecialchars(
        $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// Token para proteger formulários contra requisições indevidas.
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function verificar_csrf(): void
{
    $recebido = $_POST['csrf'] ?? '';
    $esperado = $_SESSION['csrf'] ?? '';

    if (
        !is_string($recebido) ||
        !is_string($esperado) ||
        $esperado === '' ||
        !hash_equals($esperado, $recebido)
    ) {
        http_response_code(403);
        exit('Requisição inválida.');
    }
}

// Impede que visitantes não autenticados acessem páginas internas.
function exigir_login(): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit;
    }
}
