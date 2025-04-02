<?php
session_start();
require_once 'config.php';
require_once 'mail_helper.php'; // Inclure le fichier d'aide pour les emails

function generateTwoFactorToken($user_id) {
    $conn = getDatabaseConnection();
    $token = sprintf("%06d", mt_rand(0, 999999));
    $expiryTime = date('Y-m-d H:i:s', time() + 300);
    
    $stmt = $conn->prepare("UPDATE auth_tokens SET used = TRUE 
                            WHERE user_id = ? AND token_type = 'two_factor' AND used = FALSE");
    $stmt->execute([$user_id]);
    
    $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token_type, token, expires_at) 
                            VALUES (?, 'two_factor', ?, ?)");
    $stmt->execute([$user_id, $token, $expiryTime]);
    
    return $token;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    

    try {
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $token = generateTwoFactorToken($user['id']);
            $to = $user['email'];
            $subject = "Votre code d'authentification";
            $message = "<h2>Code d'authentification</h2>
                        <p>Votre code à 6 chiffres est: <strong>$token</strong></p>
                        <p>Ce code expirera dans 5 minutes.</p>";
            
                        $emailSent = sendEmail(
                            $to, 
                            $subject, 
                            $message, 
                            true, 
                            'smtp.gmail.com', 
                            'gabingabin46@gmail.com',  // Remplacez par votre adresse Gmail
                            'rzwz nacn uecm dpxt'  // Remplacez par le mot de passe d'application généré
                        );
            
            if (!$emailSent) {
                error_log("Échec d'envoi du code 2FA à {$user['email']}", 3, "error.log");
                $_SESSION['debug_2fa_token'] = $token;
            }

            $_SESSION['pending_user_id'] = $user['id'];
            $_SESSION['a2f_expires'] = time() + 300;
            header("Location: two_factor.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } catch(PDOException $e) {
        error_log($e->getMessage(), 3, "error.log");
        $error = "Database error occurred";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secure Login</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: #f0f2f5; 
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 300px;
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
            text-align: center;
        }
        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Secure Login</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php 
        // UNIQUEMENT POUR LE DÉVELOPPEMENT - Afficher le code 2FA pour les tests
        if (isset($_SESSION['debug_2fa_token'])) {
            echo "<p style='background-color: #fff3cd; padding: 10px; border-radius: 4px;'>
                <strong>Mode développement</strong> - Code 2FA: {$_SESSION['debug_2fa_token']}</p>";
            unset($_SESSION['debug_2fa_token']);
        }
        ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="links">
            <a href="forgot_password.php">Forgot Password?</a>
            <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>