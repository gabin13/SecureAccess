<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Logout handling
if (isset($_POST['logout'])) {
    logUserActivity($_SESSION['username'], "Logged Out");
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get user role for potential role-based access
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT role FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: #f0f2f5; 
        }
        .welcome-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }
        .logout-btn {
            width: 100%;
            padding: 10px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>Your role: <?php echo htmlspecialchars($user['role']); ?></p>
        
        <form method="post">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>
</body>
</html>