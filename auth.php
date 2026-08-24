<?php
// Harden the session cookie before the session is created.
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

// Include secrets file
require_once 'config/secrets.php';

// Login attempts allowed per window, and the window length in seconds.
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 900;

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['login_time']) &&
           (time() - $_SESSION['login_time']) < $GLOBALS['session_timeout'];
}

// Function to check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Whether the current session has burned through its login attempts.
 */
function isLoginLocked() {
    if (empty($_SESSION['login_attempts']) || empty($_SESSION['first_attempt_time'])) {
        return false;
    }

    // The window has expired - let them start over.
    if ((time() - $_SESSION['first_attempt_time']) > LOGIN_LOCKOUT_SECONDS) {
        unset($_SESSION['login_attempts'], $_SESSION['first_attempt_time']);
        return false;
    }

    return $_SESSION['login_attempts'] >= LOGIN_MAX_ATTEMPTS;
}

/**
 * Record a failed login against the current session.
 */
function recordFailedLogin() {
    if (empty($_SESSION['first_attempt_time'])) {
        $_SESSION['first_attempt_time'] = time();
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts']++;
}

// Function to login user
function loginUser($username, $password) {
    global $users;

    // Compare against a dummy hash when the user is unknown so that a bad
    // username costs the same time as a bad password.
    $hash = $users[$username]['password']
        ?? '$2y$12$usesomesillystringfooosarrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr';

    if (!isset($users[$username]) || !password_verify($password, $hash)) {
        return false;
    }

    // Rotate the session ID so a pre-set cookie cannot be used to ride the
    // authenticated session (session fixation).
    session_regenerate_id(true);

    $_SESSION['user'] = $username;
    $_SESSION['role'] = $users[$username]['role'];
    $_SESSION['login_time'] = time();
    unset($_SESSION['login_attempts'], $_SESSION['first_attempt_time']);

    return true;
}

// Function to logout user
function logoutUser() {
    $_SESSION = [];

    // Expire the session cookie itself, not just the server-side data.
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    session_start();
    session_regenerate_id(true);
}

// Handle login form submission
if (isset($_POST['action']) && $_POST['action'] === 'login' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (isLoginLocked()) {
        $login_error = 'Too many failed attempts. Please try again later.';
    } elseif (loginUser($username, $password)) {
        header('Location: examples.php');
        exit;
    } else {
        recordFailedLogin();
        $login_error = 'Invalid username or password';
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
    header('Location: examples.php');
    exit;
}
?>
