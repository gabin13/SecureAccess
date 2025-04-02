<?php
session_start();
require_once 'config.php';

// Verify token from database
function verifyTwoFactorToken($user_id, $token) {
    $conn = getDatabaseConnection();
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("SELECT * FROM auth_tokens 
                           WHERE user_id = ? 
                           AND token_type = 'two_factor' 
                           AND token = ? 
                           AND expires_at > ? 
                           AND used = FALSE");
    $stmt->execute([$user_id, $token, $now]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Mark token as used
        $stmt = $conn->prepare("UPDATE auth_tokens SET used = TRUE WHERE id = ?");
        $stmt->execute([$result['id']]);
        return true;
    }
    
    return false;
}

// Get user details by ID
function getUserById($user_id) {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Verify A2F
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_code = $_POST['a2f_code'];
    
    if (!isset($_SESSION['pending_user_id']) || time() > $_SESSION['a2f_expires']) {
        $error = "Session expired";
    } else {
        $user_id = $_SESSION['pending_user_id'];
        
        // Verify code against database
        if (verifyTwoFactorToken($user_id, $entered_code)) {
            // Get user details
            $user = getUserById($user_id);
            
            if ($user) {
                // Create full user session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Clear 2FA session variables
                unset($_SESSION['pending_user_id']);
                unset($_SESSION['a2f_expires']);
                
                // Log successful login
                logUserActivity($user['username'], "Successful Login with 2FA");
                
                // Redirect to welcome page
                header("Location: welcome.php");
                exit();
            } else {
                $error = "User not found";
            }
        } else {
            $error = "Invalid verification code";
        }
    }
}

// Prevent direct access without pending authentication
if (!isset($_SESSION['pending_user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Two-Factor Authentication</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: #f0f2f5; 
        }
        .a2f-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="a2f-container">
        <h2>Two-Factor Authentication</h2>
        <p>A 6-digit code has been sent to your email</p>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <input type="text" name="a2f_code" placeholder="Enter 6-digit code" required maxlength="6">
            <button type="submit">Verify</button>
        </form>
    </div>
</body>
</html>