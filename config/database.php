<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '<Admin123!@#>');
define('DB_NAME', 'urology_db');

// Connection mode: 'local' for XAMPP, 'remote' for cPanel
define('CONNECTION_MODE', 'local');

// Remote DB (for cPanel cross-connection)
define('REMOTE_DB_HOST', 'your-cpanel-host.com');
define('REMOTE_DB_USER', 'cpanel_user');
define('REMOTE_DB_PASS', 'cpanel_pass');
define('REMOTE_DB_NAME', 'urology_db');

// Site settings
define('SITE_NAME', 'Urology Patient Registry');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');
define('BASE_URL', 'http://localhost/urology-registry/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create connection
function getDB($mode = CONNECTION_MODE) {
    try {
        if ($mode === 'remote') {
            $conn = new mysqli(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME);
        } else {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        }
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage());
    }
}

$conn = getDB();
?>