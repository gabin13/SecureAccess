<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

define('SMTP_CONFIG', [
    'gmail.com' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls'
    ]
]);

function getSmtpConfig($email) {
    $domain = substr(strrchr($email, "@"), 1);
    return array_key_exists($domain, SMTP_CONFIG) ? SMTP_CONFIG[$domain] : SMTP_CONFIG['default'];
}

function sendEmail($to, $subject, $message, $isHTML = true, $smtpHost = null, $smtpUser = null, $smtpPass = null, $smtpPort = null, $smtpSecure = null) {
    try {
        $mail = new PHPMailer(true);
        
        if ($smtpHost && $smtpUser && $smtpPass) {
            $host = $smtpHost;
            $user = $smtpUser;
            $pass = $smtpPass;
            $port = $smtpPort ?: 587;
            $secure = $smtpSecure ?: 'tls';
        } else {
            // Sinon, utiliser la configuration automatique
            $user = getenv('SMTP_USER') ?: 'gabingabin46@gmail.com';
            $pass = getenv('SMTP_PASS') ?: 'rzwz nacn uecm dpxt';
            $smtpConfig = getSmtpConfig($user);
            $host = $smtpConfig['host'];
            $port = $smtpConfig['port'];
            $secure = $smtpConfig['secure'];
        }

        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = $secure;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        
        if (strpos($host, 'gmail.com') !== false) {
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }

        // Expéditeur et destinataire
        $mail->setFrom($user, 'Système d\'authentification');
        $mail->addAddress($to);

        // Contenu du mail
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        // Envoi de l'email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur PHPMailer: " . $e->getMessage(), 3, "error.log");
        return false;
    }
}