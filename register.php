<?php
session_start();
require_once 'config.php';

$errors = [];
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        try {
            $conn = getDatabaseConnection();
            
            // Vérifier que l'email existe
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                if ($existingUser['username'] === $username) {
                    $errors[] = "Username already exists";
                }
                if ($existingUser['email'] === $email) {
                    $errors[] = "Email already exists";
                }
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$username, $email, $hashed_password]);
                
                logUserActivity($username, "User Registration");
                
                $success = "Registration successful! You can now login.";
            }
        } catch(PDOException $e) {
            error_log($e->getMessage(), 3, "error.log");
            $errors[] = "Database error occurred";
        }
    }
}

// Include the view file
include './views/register_views.php';?>