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

// Impede que visitantes não autenticados acessem páginas internas
// e encerra sessões inativas há mais de 30 minutos ou sem marcadores válidos.
function exigir_login(): void
{
    $limiteInatividade = 1800; // 30 minutos em segundos
    $agora = time();
    $ultimoAcesso = $_SESSION['ultimo_acesso'] ?? null;

    if (
        empty($_SESSION['usuario_id']) ||
        !is_int($ultimoAcesso) ||
        ($agora - $ultimoAcesso) > $limiteInatividade
    ) {
        if (!empty($_SESSION['usuario_id'])) {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $opcoes = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => $agora - 3600,
                    'path' => $opcoes['path'],
                    'domain' => $opcoes['domain'],
                    'secure' => $opcoes['secure'],
                    'httponly' => $opcoes['httponly'],
                    'samesite' => $opcoes['samesite'],
                ]);
            }

            session_destroy();
        }

        header('Location: /login.php');
        exit;
    }

    $_SESSION['ultimo_acesso'] = $agora;
}

/**
 * Registra eventos na trilha de auditoria do sistema.
 * Não registra senhas, hashes, tokens, cookies, credenciais ou o corpo completo de demandas.
 */
function registrar_auditoria(
    PDO $pdo,
    string $tipoEvento,
    ?int $usuarioId,
    string $usuario,
    ?int $demandaId = null,
    ?string $detalhes = null
): void {
    if ($detalhes !== null && strlen($detalhes) > 255) {
        throw new InvalidArgumentException('Os detalhes da auditoria excedem o limite de 255 bytes.');
    }

    // Usa o endereço recebido pelo servidor, sem confiar em cabeçalhos do cliente.
    $endereco = $_SERVER['REMOTE_ADDR'] ?? null;
    $ip = is_string($endereco) && filter_var($endereco, FILTER_VALIDATE_IP)
        ? $endereco
        : null;

    $comando = $pdo->prepare(
        'INSERT INTO auditoria (usuario_id, usuario, tipo_evento, demanda_id, detalhes, ip)
         VALUES (:usuario_id, :usuario, :tipo_evento, :demanda_id, :detalhes, :ip)'
    );

    $comando->execute([
        'usuario_id' => $usuarioId,
        'usuario' => $usuario,
        'tipo_evento' => $tipoEvento,
        'demanda_id' => $demandaId,
        'detalhes' => $detalhes,
        'ip' => $ip,
    ]);
}
