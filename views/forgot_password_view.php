<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/forgot_password.css">
</head>
<body>
    <div class="forgot-container">
        <h2>Mot de passe oublié</h2>
        <?php 
        if (!empty($error)) echo "<p class='error'>$error</p>"; 
        if (!empty($success)) echo "<p class='success'>$success</p>";

        if (!empty($_SESSION['debug_reset_link'])) {
            echo "<p class='debug'>
                <strong>Mode développement</strong> - Lien: 
                <a href='{$_SESSION['debug_reset_link']}'>{$_SESSION['debug_reset_link']}</a>
                </p>";
            unset($_SESSION['debug_reset_link']);
        }
        ?>
        <form method="post" action="">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Réinitialiser le mot de passe</button>
        </form>
        <p><a href="login.php">Connexion</a></p>
    </div>
</body>
</html>
