<?php
session_start();
require_once 'config.php';

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
        $stmt = $conn->prepare("UPDATE auth_tokens SET used = TRUE WHERE id = ?");
        $stmt->execute([$result['id']]);
        return true;
    }
    return false;
}

function getUserById($user_id) {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_code = $_POST['a2f_code'];

    if (!isset($_SESSION['pending_user_id']) || time() > $_SESSION['a2f_expires']) {
        $error = "Session expired";
    } else {
        $user_id = $_SESSION['pending_user_id'];

        if (verifyTwoFactorToken($user_id, $entered_code)) {
            $user = getUserById($user_id);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                unset($_SESSION['pending_user_id']);
                unset($_SESSION['a2f_expires']);

                logUserActivity($user['username'], "Successful Login with 2FA");

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

if (!isset($_SESSION['pending_user_id'])) {
    header("Location: login.php");
    exit();
}

include 'views/two_factor_view.php';
