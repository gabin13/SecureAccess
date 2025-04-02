<?php
require_once 'config.php';
require_once 'mail_helper.php';  // Inclure le fichier d'aide pour les emails

// Verify password reset token
function verifyPasswordResetToken($token) {
    $conn = getDatabaseConnection();
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("SELECT t.*, u.username, u.email FROM auth_tokens t
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.token = ? 
                        AND t.token_type = 'password_reset' 
                        AND t.expires_at > ? 
                        AND t.used = FALSE");
    $stmt->execute([$token, $now]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Mark token as used
function markTokenAsUsed($token_id) {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("UPDATE auth_tokens SET used = TRUE WHERE id = ?");
    $stmt->execute([$token_id]);
}

// Check if token is valid
$token = isset($_GET['token']) ? $_GET['token'] : '';
$token_data = $token ? verifyPasswordResetToken($token) : null;

if (!$token_data) {
    die("Lien de réinitialisation invalide ou expiré. Veuillez en demander un nouveau.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if (strlen($password) < 8) {
        $error = "Le mot de passe doit comporter au moins 8 caractères";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        try {
            $conn = getDatabaseConnection();
            
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user's password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $token_data['user_id']]);
            
            // Mark token as used
            markTokenAsUsed($token_data['id']);
            
            // Log password reset
            if (function_exists('logUserActivity')) {
                logUserActivity($token_data['username'], "Password Reset Completed");
            }
            
            // Envoyer un email de confirmation
            $subject = "Confirmation de réinitialisation de mot de passe";
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { padding: 20px; max-width: 600px; margin: 0 auto; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Mot de passe réinitialisé avec succès</h2>
                    <p>Bonjour {$token_data['username']},</p>
                    <p>Votre mot de passe a été réinitialisé avec succès.</p>
                    <p>Si vous n'avez pas effectué cette action, veuillez nous contacter immédiatement.</p>
                </div>
            </body>
            </html>";
            
            // Utiliser la nouvelle fonction d'envoi d'email
            sendEmail($token_data['email'], $subject, $message);
            
            $success = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant <a href='login.php'>vous connecter</a> avec votre nouveau mot de passe.";
        } catch(PDOException $e) {
            error_log($e->getMessage(), 3, "error.log");
            $error = "Une erreur de base de données s'est produite";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: #f0f2f5; 
        }
        .reset-container {
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
            margin: 10px 0;
        }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Réinitialiser votre mot de passe</h2>
        <?php 
        if(isset($error)) echo "<p class='error'>$error</p>"; 
        if(isset($success)) echo "<p class='success'>$success</p>"; 
        
        if (!isset($success)): // Only show form if password hasn't been successfully reset
        ?>
        <form method="post">
            <input type="password" name="password" placeholder="Nouveau mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le nouveau mot de passe" required>
            <button type="submit">Réinitialiser le mot de passe</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>