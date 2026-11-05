<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

verificar_csrf();

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

header('Location: /login.php');
exit;
