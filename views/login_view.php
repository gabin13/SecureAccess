<!DOCTYPE html>
<html>
<head>
    <title>Secure Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Connexion</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

        <?php 
        if (isset($_SESSION['debug_2fa_token'])) {
            echo "<p class='dev-mode'>
                <strong>Mode développement</strong> - Code 2FA: {$_SESSION['debug_2fa_token']}</p>";
            unset($_SESSION['debug_2fa_token']);
        }
        ?>

        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Connexion</button>
        </form>

        <div class="links">
            <a href="forgot_password.php">Mot de passe oublié ?</a>
            <a href="register.php">Créer un compte</a>
        </div>
    </div>
</body>
</html>
