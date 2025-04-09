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

$error = null;

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
                'votre_email@gmail.com',  // Adresse Gmail
                'votre_mot_de_passe_app'       // Mot de passe d'application
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
            $error = "Nom d'utilisateur ou mot de passe invalide.";
        }
    } catch(PDOException $e) {
        error_log($e->getMessage(), 3, "error.log");
        $error = "Erreur de base de données.";
    }
}

include 'views/login_view.php';
