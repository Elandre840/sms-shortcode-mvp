<?php
require __DIR__ . '/sms_processor.php';

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = $_REQUEST;
}

$sender      = trim($payload['sender'] ?? $payload['from'] ?? '');
$message     = trim($payload['message'] ?? $payload['text'] ?? $payload['body'] ?? '');
$category    = trim($payload['category'] ?? 'General');
$full_name   = trim($payload['full_name'] ?? $payload['name'] ?? '') ?: 'Unknown';
$user_email  = trim($payload['user_email'] ?? '');
$external_id = trim($payload['external_id'] ?? $payload['call_id'] ?? '');

$result = processSMS($sender, $message, $category, $full_name, 'inbound', 'sms', 'text', $user_email, $external_id);
http_response_code($result['http_code']);
if ($result['status'] === 'success') {
    echo "OK";
} else {
    echo $result['message'];
}
exit();
