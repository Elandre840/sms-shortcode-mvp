<?php

require __DIR__ . '/sms_processor.php';

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit();
}

$sender       = trim($data['sender'] ?? '');
$message      = trim($data['message'] ?? '');
$category     = trim($data['category'] ?? 'General');
$full_name    = trim($data['name'] ?? $data['caller_name'] ?? 'Unknown');
$message_type = trim($data['type'] ?? 'call');
$source       = trim($data['source'] ?? 'whatsapp');
$user_email   = trim($data['user_email'] ?? '');
$external_id  = trim($data['call_id'] ?? $data['external_id'] ?? '');

$result = processSMS($sender, $message, $category, $full_name, $source, 'whatsapp', $message_type, $user_email, $external_id);

http_response_code($result['http_code']);
echo json_encode($result);

?>