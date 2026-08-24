<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include secrets file
require_once 'config/secrets.php';

// Set response header to JSON
header('Content-Type: application/json');

// Basic spam protection (honeypot)
if (!empty($_POST['website'])) {
    echo json_encode(['success' => false, 'message' => 'Spam detected.']);
    exit;
}

// Validate form data
$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? ''); // Added
$subject_field = trim($_POST['subject'] ?? ''); // Added
$service = trim($_POST['service'] ?? ''); // Added
$message = trim($_POST['message'] ?? '');

if (empty($name)) {
    $errors[] = 'Name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
}

// If there are validation errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Verify reCAPTCHA v3
$recaptcha_response = $_POST['recaptcha_response'] ?? '';
if (empty($recaptcha_response)) {
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA token not found.']);
    exit;
}

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = [
    'secret'   => $recaptcha_secret_key,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR'],
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($recaptcha_data),
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($recaptcha_url, false, $context);
$result_json = json_decode($result, true);

if (!$result_json['success'] || $result_json['score'] < $recaptcha_v3_threshold) {
    // Log the failed attempt for monitoring
    error_log('reCAPTCHA verification failed. Score: ' . ($result_json['score'] ?? 'N/A'));
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again.']);
    exit;
}

// Prepare email content
$subject = "$email_subject_prefix " . ($subject_field ?: "New message from $name");
$body = "You have received a new message from your website contact form.\n\n";
$body .= "Here are the details:\n";
$body .= "Name: $name\n";
$body .= "Email: $email\n";
if ($phone) {
    $body .= "Phone: $phone\n";
}
if ($service) {
    $body .= "Service of Interest: $service\n";
}
$body .= "Subject: " . ($subject_field ?: 'N/A') . "\n";
$body .= "----------------------------------------\n\n";
$body .= "Message:\n$message\n";

// For best deliverability, the "From" address should be a real email on your domain.
$headers = "From: webmaster@" . ($_SERVER['SERVER_NAME'] ?? 'yourdomain.com') . "\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email
if (mail($to_email, $subject, $body, $headers)) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your message! We will get back to you soon.']);
} else {
    // In a real-world scenario, you'd log this error
    error_log("Mail failed to send. To: $to_email, Subject: $subject");
    echo json_encode(['success' => false, 'message' => 'Sorry, there was an error sending your message. Please try again later.']);
}
?>