<?php
require_once 'config.php';
require_once 'mail_helper.php';

function generatePasswordResetToken($user_id) {
    $conn = getDatabaseConnection();
    $token = bin2hex(random_bytes(32));
    $expiryTime = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $conn->prepare("UPDATE auth_tokens SET used = TRUE 
                            WHERE user_id = ? AND token_type = 'password_reset' AND used = FALSE");
    $stmt->execute([$user_id]);

    $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token_type, token, expires_at) 
                            VALUES (?, 'password_reset', ?, ?)");
    $stmt->execute([$user_id, $token, $expiryTime]);

    return $token;
}

session_start();
$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    try {
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $reset_token = generatePasswordResetToken($user['id']);
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/secureaccess/reset_password.php?token=" . $reset_token;

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
                    <p>Veuillez cliquer sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
                    <p><a href='$reset_link' class='button'>Réinitialiser mon mot de passe</a></p>
                    <p>Ce lien expirera dans 1 heure.</p>
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

include 'views/forgot_password_view.php';
