<?php
$host = 'localhost';
$db   = 'integradora';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Error de conexión MySQL [integradora]: " . $e->getMessage());
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        http_response_code(500);
        die("<div style='max-width:600px;margin:40px auto;padding:20px;background:#f8d7da;color:#721c24;border-radius:8px;font-family:sans-serif'><h3>Error de conexión a la base de datos</h3><p>No se pudo conectar a <strong>integradora</strong> (MySQL host localhost, usuario root sin clave).</p><p>Verificá que MySQL esté activo y que hayas importado <code>sql/esquema.sql</code>.</p><p><small>Detalle: " . htmlspecialchars($e->getMessage()) . "</small></p></div>");
    }
    throw new \PDOException("No se pudo conectar a la BD 'integradora' (MySQL root sin clave, host localhost). Verificá MySQL y sql/esquema.sql. Detalle: " . $e->getMessage(), (int)$e->getCode());
}
