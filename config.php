<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '_root453*');
define('DB_NAME', 'secureaccess');

// Secure database connection function with prepared statements
function getDatabaseConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        // Log the error securely
        error_log("Database Connection Error: " . $e->getMessage(), 3, "error.log");
        die("Connection failed: " . $e->getMessage());
    }
}

// Function to log user activities
function logUserActivity($username, $activity) {
    $logFile = "user_logs.txt";
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "$timestamp - User: $username - Activity: $activity\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>