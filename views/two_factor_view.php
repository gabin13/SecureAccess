<!DOCTYPE html>
<html>
<head>
    <title>Two-Factor Authentication</title>
    <link rel="stylesheet" href="css/two_factor.css">
</head>
<body>
    <div class="a2f-container">
        <h2>Two-Factor Authentication</h2>
        <p>Un code à 6 chiffre vous à été envoyé</p>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <input type="text" name="a2f_code" placeholder="Enter 6-digit code" required maxlength="6">
            <button type="submit">Vérifier</button>
        </form>
    </div>
</body>
</html>
