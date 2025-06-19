<?php
session_start();

// Include secrets file
require_once 'config/secrets.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['login_time']) && 
           (time() - $_SESSION['login_time']) < $GLOBALS['session_timeout'];
}

// Function to check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Function to login user
function loginUser($username, $password) {
    global $users;
    
    if (isset($users[$username]) && $users[$username]['password'] === $password) {
        $_SESSION['user'] = $username;
        $_SESSION['role'] = $users[$username]['role'];
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

// Function to logout user
function logoutUser() {
    session_destroy();
    session_start();
}

// Handle login form submission
if ($_POST['action'] === 'login' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (loginUser($username, $password)) {
        header('Location: examples.php');
        exit;
    } else {
        $login_error = 'Invalid username or password';
    }
}

// Handle logout
if ($_GET['action'] === 'logout') {
    logoutUser();
    header('Location: examples.php');
    exit;
}
?>