<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['logout'])) {
    logUserActivity($_SESSION['username'], "Logged Out");
    session_destroy();
    header("Location: login.php");
    exit();
}



include 'views/welcome_view.php';
