<?php

session_start();
require __DIR__ . '/sms_processor.php';

$full_name   = trim($_POST['full_name'] ?? '');
$sender      = trim($_POST['sender'] ?? '');
$message     = trim($_POST['message'] ?? '');
$category    = trim($_POST['category'] ?? 'General');
$message_type= trim($_POST['message_type'] ?? 'text');
$user_email  = trim($_POST['user_email'] ?? '');

if ($full_name === '') {
    $full_name = 'Unknown';
}

$result = processSMS($sender, $message, $category, $full_name, 'simulator', 'whatsapp', $message_type, $user_email);

if ($result['status'] === 'success') {
    $_SESSION['flash'] = 'WhatsApp saved ✅, email sent ✅, logs updated ✅';
} else {
    $_SESSION['flash'] = 'WhatsApp saved ❌, email sent ❌, logs updated ❌: ' . $result['message'];
}

header('Location: dashboard/dashboard.php');
exit();
