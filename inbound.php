<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/sms_processor.php';

// ---------------------------
// ✅ PARSE TWILIO INPUT (form-encoded)
// ---------------------------
$from        = trim($_POST['From'] ?? '');              // e.g. whatsapp:+27123456789
$body        = trim($_POST['Body'] ?? '');              // Message text
$profile     = trim($_POST['ProfileName'] ?? 'Unknown'); // WhatsApp contact name
$channel     = 'whatsapp';                              // Twilio channel

// Extract phone number from "whatsapp:+27123456789" format
$sender = preg_replace('/^whatsapp:\+?/', '', $from) ?: $from;

// Extract category from message (if starts with [CATEGORY])
$category = 'General';
if (preg_match('/^\[([A-Z\-]+)\]/', $body, $matches)) {
    $category = strtoupper($matches[1]);
    $body = trim(substr($body, strlen($matches[0])));
}

// Process the SMS
processSMS(
    $sender,
    $body,
    $category,
    $profile,
    'inbound',
    $channel,
    'text',
    '',  // user_email
    ''   // external_id
);

// Always respond to Twilio with TwiML (XML format) for 200 OK
header("Content-Type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<Response>\n";
echo "    <Message>✅ Message received and processed</Message>\n";
echo "</Response>";
exit();
?>

