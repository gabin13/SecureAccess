<?php
require_once 'config.php';
require_once 'mail_helper.php';  // Inclure le fichier d'aide pour les emails

// Generate password reset token
function generatePasswordResetToken($user_id) {
    $conn = getDatabaseConnection();
    
    // Generate 32-byte random token
    $token = bin2hex(random_bytes(32));
    
    // Set expiry time (1 hour from now)
    $expiryTime = date('Y-m-d H:i:s', time() + 3600);
    
    // Clear any existing unused reset tokens for this user
    $stmt = $conn->prepare("UPDATE auth_tokens SET used = TRUE 
                            WHERE user_id = ? AND token_type = 'password_reset' AND used = FALSE");
    $stmt->execute([$user_id]);
    
    // Create new token
    $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token_type, token, expires_at) 
                            VALUES (?, 'password_reset', ?, ?)");
    $stmt->execute([$user_id, $token, $expiryTime]);
    
    return $token;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    try {
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate reset token
            $reset_token = generatePasswordResetToken($user['id']);

            // Create reset link - REMPLACEZ PAR VOTRE URL RÉELLE
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/secureaccess/reset_password.php?token=" . $reset_token;
            
            // Préparer l'email en HTML pour un meilleur affichage
            $subject = "Demande de réinitialisation de mot de passe";
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { padding: 20px; max-width: 600px; margin: 0 auto; }
                    .button { 
                        display: inline-block; 
                        padding: 10px 20px; 
                        background-color: #4CAF50; 
                        color: white; 
                        text-decoration: none; 
                        border-radius: 4px; 
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Réinitialisation de mot de passe</h2>
                    <p>Bonjour {$user['username']},</p>
                    <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
                    <p>Veuillez cliquer sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
                    <p><a href='$reset_link' class='button'>Réinitialiser mon mot de passe</a></p>
                    <p>Ce lien expirera dans 1 heure.</p>
                    <p>Si vous n'avez pas demandé de réinitialisation de mot de passe, veuillez ignorer cet email.</p>
                </div>
            </body>
            </html>";
            
            $emailSent = sendEmail(
                $email, 
                $subject, 
                $message, 
                true, 
                'smtp.gmail.com', 
                'gabingabin46@gmail.com',
                'rzwz nacn uecm dpxt'
                );
            
            if (!$emailSent) {
                error_log("Échec d'envoi du lien de réinitialisation à $email", 3, "error.log");
                
                $_SESSION['debug_reset_link'] = $reset_link;
            }

            // Log the password reset request
            if (function_exists('logUserActivity')) {
                logUserActivity($user['username'], "Password Reset Requested");
            }

            $success = "Lien de réinitialisation de mot de passe envoyé à votre email";
        } else {
            $error = "Email non trouvé";
        }
    } catch(PDOException $e) {
        error_log($e->getMessage(), 3, "error.log");
        $error = "Une erreur de base de données s'est produite";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: #f0f2f5; 
        }
        .forgot-container {
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
        .error, .success {
            text-align: center;
            margin-top: 10px;
        }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h2>Forgot Password</h2>
        <?php 
        if(isset($error)) echo "<p class='error'>$error</p>"; 
        if(isset($success)) echo "<p class='success'>$success</p>";
        
        // UNIQUEMENT POUR LE DÉVELOPPEMENT - Afficher le lien de réinitialisation pour les tests
        if (isset($_SESSION['debug_reset_link'])) {
            echo "<p style='background-color: #fff3cd; padding: 10px; border-radius: 4px;'>
                  <strong>Mode développement</strong> - Lien: <a href='{$_SESSION['debug_reset_link']}'>
                  {$_SESSION['debug_reset_link']}</a></p>";
            unset($_SESSION['debug_reset_link']);
        }
        ?>
        <form method="post">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Reset Password</button>
        </form>
        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>