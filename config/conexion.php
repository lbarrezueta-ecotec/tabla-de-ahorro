<?php
// Soporta ambos entornos: hosting (sql110) y local (localhost)
$host = 'sql110.infinityfree.com';
$db   = 'if0_42868444_integradora';
$user = 'if0_42868444';
$pass = '54NVhQoDKx';
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
    // Fallback a local (XAMPP) si falla en hosting
    if ($host !== 'localhost') {
        try {
            $host = 'localhost';
            $db   = 'integradora';
            $user = 'root';
            $pass = '';
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e2) {
            error_log("Error MySQL [hosting:$host/$db]: " . $e->getMessage() . " | [local]: " . $e2->getMessage());
            if (php_sapi_name() !== 'cli' && !headers_sent()) {
                http_response_code(500);
                die("<div style='max-width:600px;margin:40px auto;padding:20px;background:#f8d7da;color:#721c24;border-radius:8px'><h3>Error de conexión</h3><p>No se pudo conectar a <strong>$db</strong> en <strong>$host</strong> ni a hosting.</p><p>Verificá phpMyAdmin y <code>sql/esquema.sql</code>.</p><p><small>Hosting: " . htmlspecialchars($e->getMessage()) . "<br>Local: " . htmlspecialchars($e2->getMessage()) . "</small></p></div>");
            }
            throw new \PDOException("No se pudo conectar ni a hosting ni a local. Hosting: " . $e->getMessage() . " | Local: " . $e2->getMessage(), (int)$e2->getCode());
        }
    } else {
        error_log("Error MySQL [integradora]: " . $e->getMessage());
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(500);
            die("<div style='max-width:600px;margin:40px auto;padding:20px;background:#f8d7da;color:#721c24;border-radius:8px'><h3>Error de conexión</h3><p>No se pudo conectar a <strong>$db</strong> en <strong>$host</strong>.</p><p>Verificá que la DB exista y que hayas importado <code>sql/esquema.sql</code>.</p><p><small>Detalle: " . htmlspecialchars($e->getMessage()) . "</small></p></div>");
        }
        throw new \PDOException("No se pudo conectar a $db. " . $e->getMessage(), (int)$e->getCode());
    }
}
    throw new \PDOException("No se pudo conectar a la BD 'integradora' (MySQL root sin clave, host localhost). Verificá MySQL y sql/esquema.sql. Detalle: " . $e->getMessage(), (int)$e->getCode());
}
