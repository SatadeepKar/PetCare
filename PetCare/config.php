<?php
/**
 * Shared database configuration for PetCare PHP app.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'petcare');

// Vet locator URL: dynamically uses local path for localhost and production Render URL for live deployment
if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') === 0)) {
    define('VET_LOCATOR_URL', 'VetShopsLocator/public/index.html');
} else {
    // Replace this URL with your actual deployed Render URL
    define('VET_LOCATOR_URL', 'https://petcare-vet-locator.onrender.com/index.html');
}


function get_db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
