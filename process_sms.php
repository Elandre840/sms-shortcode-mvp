
<?php
// process_sms.php

// ---------------------------
// ✅ DB CONNECTION
// ---------------------------
// TODO: Update with your database credentials
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sms_shortcode";

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database unavailable";
    exit();
}

// ---------------------------
// ✅ CAPTURE INPUT
// ---------------------------
$sender     = trim($_GET['sender'] ?? $_GET['from'] ?? '');
$message    = trim($_GET['message'] ?? $_GET['text'] ?? '');
$category   = trim($_GET['category'] ?? 'General');
$full_name  = trim($_GET['full_name'] ?? '') ?: "Unknown";
$message_id = rand(1000, 9999);
$source     = 'inbound';

if ($sender === '' || $message === '') {
    http_response_code(400);
    echo "Missing sender/message";
    exit();
}

// ---------------------------
// ✅ INSERT INTO DATABASE
// ---------------------------
$stmt = $pdo->prepare("
    INSERT INTO inbound_messages 
    (full_name, sender, message, category, status, message_id, source, created_at)
    VALUES (?, ?, ?, ?, 'new', ?, ?, NOW())
");

$stmt->execute([
    $full_name,
    $sender,
    $message,
    $category,
    $message_id,
    $source
]);

// ---------------------------
// ✅ LOG PATHS (write to BOTH logs folder + root to avoid confusion)
// ---------------------------
$logDir = __DIR__ . "/logs";
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$logPathLogs     = __DIR__ . "/logs/sms_log.txt";
$debugPathLogs   = __DIR__ . "/logs/smtp_debug.txt";
$logPathRoot     = __DIR__ . "/sms_log.txt";
$debugPathRoot   = __DIR__ . "/smtp_debug.txt";

// Ensure files exist
foreach ([$logPathLogs, $debugPathLogs, $logPathRoot, $debugPathRoot] as $f) {
    if (!file_exists($f)) {
        file_put_contents($f, "");
    }
}

// ---------------------------
// ✅ WRITE TO SMS LOG FILE (BOTH)
// ---------------------------
$logLine  = "========================================\n";
$logLine .= "Short-Code SMS Message\n";
$logLine .= "Name: $full_name\n";
$logLine .= "Message from: $sender\n";
$logLine .= "Category: $category\n";
$logLine .= "Message ID: $message_id\n";
$logLine .= "Source: inbound\n";
$logLine .= "Sent At: " . date("Y-m-d H:i:s") . "\n";
$logLine .= "Message:\n$message\n\n";

file_put_contents($logPathLogs, $logLine, FILE_APPEND | LOCK_EX);
file_put_contents($logPathRoot, $logLine, FILE_APPEND | LOCK_EX);

// ---------------------------
// ✅ EMAIL SECTION (UNCHANGED)
// ---------------------------
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = "smtp.gmail.com";
    $mail->SMTPAuth   = true;
    $mail->Username   = "your_email@gmail.com";  // TODO: Set your Gmail
    $mail->Password   = "your_app_password";  // TODO: Set your App Password
    $mail->SMTPSecure = "tls";
    $mail->Port       = 587;

    $mail->setFrom("your_email@gmail.com", "SMS System");  // TODO: Set your Gmail
    $mail->addAddress("alert_recipient@example.com");  // TODO: Set recipient email

    $mail->Subject = "SMS Alert - $category - $sender";

    $mail->Body = "
Short-Code SMS Message

Name: $full_name
Sender: $sender
Category: $category
Message ID: $message_id
Time: " . date("Y-m-d H:i:s") . "

Message:
$message
";

    $mail->send();

} catch (Exception $e) {
    $errLine = date("Y-m-d H:i:s") . " - " . $mail->ErrorInfo . "\n";
    file_put_contents($debugPathLogs, $errLine, FILE_APPEND | LOCK_EX);
    file_put_contents($debugPathRoot, $errLine, FILE_APPEND | LOCK_EX);
}

// ---------------------------
// ✅ RESPONSE
// ---------------------------
http_response_code(200);
echo "OK";
exit();
