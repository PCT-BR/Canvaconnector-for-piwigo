<?php
defined('CANVA_CONNECTOR_BOOTSTRAP') or define('CANVA_CONNECTOR_BOOTSTRAP', true);

if (ob_get_level() === 0) {
  ob_start();
}

if (!defined('PHPWG_ROOT_PATH')) {
  define('PHPWG_ROOT_PATH', dirname(__FILE__, 4) . '/');
}

include_once(PHPWG_ROOT_PATH . 'include/common.inc.php');

define('CANVA_CONNECTOR_ORIGIN', 'https://app-aahaaekscy8.canva-apps.com');
define('CANVA_CONNECTOR_TOKEN_FILE', PHPWG_ROOT_PATH . '_data/canva_connector_tokens.json');

function canva_connector_json($payload, int $status = 200): void
{
  while (ob_get_level() > 0) {
    ob_end_clean();
  }

  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}

function canva_connector_cors(): void
{
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin === CANVA_CONNECTOR_ORIGIN) {
    header('Access-Control-Allow-Origin: ' . CANVA_CONNECTOR_ORIGIN);
    header('Vary: Origin');
  }

  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Authorization, Content-Type');
  header('Access-Control-Max-Age: 86400');

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

function canva_connector_require_admin(): void
{
  global $user;

  $status = $user['status'] ?? 'guest';
  if (!in_array($status, array('webmaster', 'admin'), true)) {
    http_response_code(403);
    echo 'Only Piwigo administrators can manage Canva Connector tokens.';
    exit;
  }
}

function canva_connector_csrf_token(): string
{
  if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    @session_start();
  }

  if (empty($_SESSION['canva_connector_csrf'])) {
    $_SESSION['canva_connector_csrf'] = bin2hex(random_bytes(16));
  }

  return (string) $_SESSION['canva_connector_csrf'];
}

function canva_connector_verify_csrf(): void
{
  $posted = (string) ($_POST['csrf_token'] ?? '');
  if (!$posted || !hash_equals(canva_connector_csrf_token(), $posted)) {
    http_response_code(400);
    echo 'Invalid request token.';
    exit;
  }
}

function canva_connector_base_url(): string
{
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = $_SERVER['SCRIPT_NAME'] ?? '';
  $pos = strpos($script, '/plugins/canva_connector/');
  $path = $pos === false ? '/' : substr($script, 0, $pos + 1);

  return rtrim($scheme . '://' . $host . $path, '/');
}

function canva_connector_read_tokens(): array
{
  if (!is_file(CANVA_CONNECTOR_TOKEN_FILE)) {
    return array();
  }

  $raw = file_get_contents(CANVA_CONNECTOR_TOKEN_FILE);
  $tokens = json_decode($raw ?: '[]', true);
  return is_array($tokens) ? $tokens : array();
}

function canva_connector_write_tokens(array $tokens): void
{
  $dir = dirname(CANVA_CONNECTOR_TOKEN_FILE);
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  file_put_contents(
    CANVA_CONNECTOR_TOKEN_FILE,
    json_encode(array_values($tokens), JSON_PRETTY_PRINT)
  );
}

function canva_connector_generate_token(string $label, int $user_id): string
{
  $plain = 'pwgcc_' . bin2hex(random_bytes(32));
  $tokens = canva_connector_read_tokens();
  $tokens[] = array(
    'id' => bin2hex(random_bytes(8)),
    'label' => $label ?: 'Canva',
    'hash' => hash('sha256', $plain),
    'created_by' => $user_id,
    'created_at' => gmdate('c'),
    'revoked_at' => null,
    'last_used_at' => null,
  );

  canva_connector_write_tokens($tokens);
  return $plain;
}

function canva_connector_revoke_token(string $id): void
{
  $tokens = canva_connector_read_tokens();
  foreach ($tokens as &$token) {
    if (($token['id'] ?? '') === $id) {
      $token['revoked_at'] = gmdate('c');
    }
  }
  unset($token);

  canva_connector_write_tokens($tokens);
}

function canva_connector_current_token(): array
{
  $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!$header && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
  }

  $token = '';
  if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
    $token = trim($matches[1]);
  }

  if (!$token) {
    canva_connector_json(array('error' => 'Missing connector token'), 401);
  }

  $hash = hash('sha256', $token);
  $tokens = canva_connector_read_tokens();

  foreach ($tokens as $index => $record) {
    if (!empty($record['revoked_at'])) {
      continue;
    }

    if (hash_equals((string) ($record['hash'] ?? ''), $hash)) {
      $tokens[$index]['last_used_at'] = gmdate('c');
      canva_connector_write_tokens($tokens);
      return $record;
    }
  }

  canva_connector_json(array('error' => 'Invalid connector token'), 401);
}

function canva_connector_require_token(): array
{
  canva_connector_cors();
  return canva_connector_current_token();
}

function canva_connector_clean_text($value): string
{
  return html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
}

function canva_connector_media_url(int $photo_id, string $variant = 'full'): string
{
  $base = canva_connector_base_url();
  return $base . '/plugins/canva_connector/api/media.php?photoId=' . $photo_id . '&variant=' . rawurlencode($variant);
}

function canva_connector_clean_output_buffer(): void
{
  while (ob_get_level() > 0) {
    ob_end_clean();
  }
}
