<?php
require_once 'config.php';
require_once 'mail_helper.php';

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

function markTokenAsUsed($token_id) {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("UPDATE auth_tokens SET used = TRUE WHERE id = ?");
    $stmt->execute([$token_id]);
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$token_data = $token ? verifyPasswordResetToken($token) : null;

if (!$token_data) {
    die("Lien de réinitialisation invalide ou expiré. Veuillez en demander un nouveau.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $error = "Le mot de passe doit comporter au moins 8 caractères";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        try {
            $conn = getDatabaseConnection();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $token_data['user_id']]);

            markTokenAsUsed($token_data['id']);

            if (function_exists('logUserActivity')) {
                logUserActivity($token_data['username'], "Password Reset Completed");
            }

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

            sendEmail($token_data['email'], $subject, $message);

            $success = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant <a href='login.php'>vous connecter</a>.";
        } catch(PDOException $e) {
            error_log($e->getMessage(), 3, "error.log");
            $error = "Une erreur de base de données s'est produite";
        }
    }
}

// Inclusion de la vue
include 'views/reset_password_view.php';
