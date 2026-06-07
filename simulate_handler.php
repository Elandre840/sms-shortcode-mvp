<?php

session_start();
require __DIR__ . '/sms_processor.php';

$full_name  = trim($_POST['full_name'] ?? '');
$sender     = trim($_POST['sender'] ?? '');
$message    = trim($_POST['message'] ?? '');
$category   = trim($_POST['category'] ?? 'General');
$user_email = trim($_POST['user_email'] ?? '');

if ($full_name === '') {
    $full_name = 'Unknown';
}

$result = processSMS($sender, $message, $category, $full_name, 'simulator', 'sms', 'text', $user_email);

if ($result['status'] === 'success') {
    $_SESSION['flash'] = 'SMS saved ✅, email sent ✅, logs updated ✅';
} else {
    $_SESSION['flash'] = 'SMS saved ❌, email sent ❌, logs updated ❌: ' . $result['message'];
}

header('Location: dashboard/dashboard.php');
exit();
