<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
    <link rel="stylesheet" href="css/welcome.css">
</head>
<body>
    <div class="welcome-container">
        <h2>Hello World, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

        <form method="post">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>
</body>
</html>
