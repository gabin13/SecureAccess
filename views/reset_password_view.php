<!DOCTYPE html>
<html>
<head>
    <title>Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="css/reset_password.css">
</head>
<body>
    <div class="reset-container">
        <h2>Réinitialiser votre mot de passe</h2>
        <?php 
        if (isset($error)) echo "<p class='error'>$error</p>"; 
        if (isset($success)) echo "<p class='success'>$success</p>"; 

        if (!isset($success)):
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
