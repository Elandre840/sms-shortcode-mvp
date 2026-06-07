<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function loadConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $path = __DIR__ . '/config.php';
    if (!file_exists($path)) {
        return [];
    }

    $config = require $path;
    return is_array($config) ? $config : [];
}

function getPdo(): PDO
{
    $config = loadConfig();
    $db = $config['database'] ?? [];

    try {
        $pdo = new PDO(
            'mysql:host=' . ($db['host'] ?? 'localhost') . ';dbname=' . ($db['dbname'] ?? 'sms_shortcode') . ';charset=utf8mb4',
            $db['username'] ?? 'root',
            $db['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database unavailable"]);
        exit();
    }
}

function ensureInboundMessagesSchema(PDO $pdo): void
{
    $columns = [
        'channel' => "VARCHAR(50) NOT NULL DEFAULT 'sms'",
        'message_type' => "VARCHAR(50) NOT NULL DEFAULT 'text'",
        'source' => "VARCHAR(50) NOT NULL DEFAULT 'unknown'",
        'caller_name' => "VARCHAR(255) NULL",
        'user_email' => "VARCHAR(255) NULL",
        'external_id' => "VARCHAR(255) NULL",
        'ticket_status' => "VARCHAR(50) NOT NULL DEFAULT 'new'",
        'assigned_to' => "VARCHAR(255) NULL",
        'assigned_at' => "DATETIME NULL"
    ];

    foreach ($columns as $name => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM inbound_messages LIKE '" . $name . "'");
        if (!$stmt || $stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE inbound_messages ADD COLUMN $name $definition");
        }
    }
}

function getLogPaths(): array
{
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    return [
        'sms_log' => $logDir . '/sms_log.txt',
        'smtp_debug' => $logDir . '/smtp_debug.txt',
    ];
}

function assignTechnician(string $category, string $channel): array
{
    $config = loadConfig();
    $map = $config['assignments'] ?? [];
    $default = $config['default_assignment'] ?? ['name' => 'General Support', 'email' => 'helpdesk@example.com'];

    $key = strtoupper($category);
    if (isset($map[$key])) {
        return $map[$key];
    }

    return $default;
}

/**
 * ✅ STRUCTURED EMAIL FOR POWER AUTOMATE & SHAREPOINT
 * Sends email with key=value format that Power Automate can parse
 * This is the BRIDGE between SMS system and SharePoint tickets
 */
function sendStructuredTicketEmail(
    string $channel,
    string $category,
    string $sender,
    string $message,
    string $full_name,
    int $message_id,
    array $assigned,
    string $user_email,
    string $external_id,
    array $debugPaths
): bool {
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';

    $config = loadConfig();
    $smtpConfig = $config['smtp'] ?? [];
    $notifyConfig = $config['notifications'] ?? [];

    $host       = $smtpConfig['host'] ?? 'smtp.gmail.com';
    $username   = $smtpConfig['username'] ?? '';
    $password   = $smtpConfig['password'] ?? '';
    $secure     = $smtpConfig['smtp_secure'] ?? 'tls';
    $port       = $smtpConfig['port'] ?? 587;
    $fromEmail  = $smtpConfig['from_email'] ?? $username;
    $fromName   = $smtpConfig['from_name'] ?? 'SMS System';

    // Helpdesk inbox — Power Automate triggers on new mail (subject filter: [TICKET])
    $helpdeskEmail = trim($notifyConfig['helpdesk_email'] ?? '');
    if ($helpdeskEmail === '') {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = $secure;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        $mail->isHTML(false);

        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function ($str, $level) use ($debugPaths) {
            file_put_contents($debugPaths['smtp_debug'], date('Y-m-d H:i:s') . " [STRUCTURED] $str", FILE_APPEND | LOCK_EX);
        };

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($helpdeskEmail);

        // ✅ SUBJECT (for Power Automate to recognize this is a ticket email)
        $mail->Subject = "[TICKET] " . strtoupper($category) . " - " . $full_name;

        // ✅ STRUCTURED BODY (exact, machine-parsable KEY:value per line)
        // Sanitise values to avoid newlines and ensure consistent formatting
        $issueDescription = str_replace(["\r\n", "\r", "\n"], ' ', $message);
        $clientField = $user_email !== '' ? $user_email : $full_name;
        $assignedEmail = $assigned['email'] ?? '';
        $priority = 'High';
        $district = 'Head Office';
        $programme = 'Programme 1';

        $structuredBody = '';
        $structuredBody .= "Title:" . $category . "\n";
        $structuredBody .= "IssueDescription:" . $issueDescription . "\n";
        $structuredBody .= "Client:" . $clientField . "\n";
        $structuredBody .= "AssignedTo:" . $assignedEmail . "\n";
        $structuredBody .= "Category:" . $category . "\n";
        $structuredBody .= "IssueCategory:" . $category . "\n";
        $structuredBody .= "Priority:" . $priority . "\n";
        $structuredBody .= "District:" . $district . "\n";
        $structuredBody .= "Programme:" . $programme . "\n";
        $structuredBody .= "Status:Open\n";

        // No extra text; exact KEY:value lines only
        $mail->Body = $structuredBody;

        $mail->send();

        $successLine = date('Y-m-d H:i:s') . " [STRUCTURED EMAIL SUCCESS] MessageID: $message_id -> $helpdeskEmail\n";
        file_put_contents($debugPaths['smtp_debug'], $successLine, FILE_APPEND | LOCK_EX);

        return true;
    } catch (Exception $e) {
        $errLine = date('Y-m-d H:i:s') . " [STRUCTURED EMAIL FAILED] " . $e->getMessage() . "\n";
        file_put_contents($debugPaths['smtp_debug'], $errLine, FILE_APPEND | LOCK_EX);
        return false;
    }
}

function sendNotificationEmail(string $channel, string $message_type, string $category, string $sender, string $message, string $full_name, int $message_id, array $assigned, string $user_email, array $debugPaths): bool
{
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';

    $config = loadConfig();
    $smtpConfig = $config['smtp'] ?? [];
    $notifyConfig = $config['notifications'] ?? [];

    $host       = $smtpConfig['host'] ?? 'smtp.gmail.com';
    $username   = $smtpConfig['username'] ?? '';
    $password   = $smtpConfig['password'] ?? '';
    $secure     = $smtpConfig['smtp_secure'] ?? 'tls';
    $port       = $smtpConfig['port'] ?? 587;
    $fromEmail  = $smtpConfig['from_email'] ?? $username;
    $fromName   = $smtpConfig['from_name'] ?? 'SMS System';

    $defaultRecipients = $notifyConfig['default_recipients'] ?? [];

    $recipients = array_unique(array_filter(array_merge($defaultRecipients, [$assigned['email']])));

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = $secure;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        $mail->isHTML(false);

        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function ($str, $level) use ($debugPaths) {
            file_put_contents($debugPaths['smtp_debug'], date('Y-m-d H:i:s') . " [DEBUG] $str", FILE_APPEND | LOCK_EX);
        };

        $mail->setFrom($fromEmail, $fromName);

        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }

        if ($user_email !== '') {
            $mail->addCC($user_email);
        }

        $mail->Subject = strtoupper($channel) . " Alert - $category - $sender";
        $mail->Body = "Communication Alert\n\n" .
            "Channel: $channel\n" .
            "Type: $message_type\n" .
            "Name: $full_name\n" .
            "Sender: $sender\n" .
            "Category: $category\n" .
            "Message ID: $message_id\n" .
            "Assigned To: " . $assigned['name'] . "\n" .
            "Time: " . date("Y-m-d H:i:s") . "\n\n" .
            "Message:\n$message\n";

        $mail->send();

        $successLine = date('Y-m-d H:i:s') . " SUCCESS -> $category -> " . implode(',', $recipients) . "\n";
        file_put_contents($debugPaths['smtp_debug'], $successLine, FILE_APPEND | LOCK_EX);

        return true;
    } catch (Exception $e) {
        $errLine = date('Y-m-d H:i:s') . " FAILED -> " . $e->getMessage() . "\n";
        file_put_contents($debugPaths['smtp_debug'], $errLine, FILE_APPEND | LOCK_EX);
        return false;
    }
}

function logCommunication(string $full_name, string $sender, string $category, string $message, int $message_id, string $channel, string $message_type, string $source, string $assigned_to, array $paths): void
{
    $logLine  = "========================================\n";
    $logLine .= "Communication Log\n";
    $logLine .= "Name: $full_name\n";
    $logLine .= "Sender: $sender\n";
    $logLine .= "Category: $category\n";
    $logLine .= "Channel: $channel\n";
    $logLine .= "Type: $message_type\n";
    $logLine .= "Source: $source\n";
    $logLine .= "Assigned To: $assigned_to\n";
    $logLine .= "Message ID: $message_id\n";
    $logLine .= "Sent At: " . date("Y-m-d H:i:s") . "\n";
    $logLine .= "Message:\n$message\n\n";

    file_put_contents($paths['sms_log'], $logLine, FILE_APPEND | LOCK_EX);
}

function processSMS(string $sender, string $message, string $category = 'General', string $full_name = 'Unknown', string $source = 'inbound', string $channel = 'sms', string $message_type = 'text', string $user_email = '', string $external_id = ''): array
{
    if ($sender === '' || $message === '') {
        return [
            'status' => 'error',
            'http_code' => 400,
            'message' => 'Missing sender/message'
        ];
    }

    $pdo = getPdo();
    ensureInboundMessagesSchema($pdo);

    $message_id = rand(1000, 9999);
    $assigned = assignTechnician($category, $channel);
    $assigned_to = $assigned['name'];
    $assigned_email = $assigned['email'];
    $ticket_status = 'assigned';
    $assigned_at = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("INSERT INTO inbound_messages (sender, full_name, message, category, status, channel, message_type, source, caller_name, user_email, external_id, ticket_status, assigned_to, assigned_at, created_at) VALUES (?, ?, ?, ?, 'new', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $sender,
            $full_name,
            $message,
            $category,
            $channel,
            $message_type,
            $source,
            $full_name,
            $user_email,
            $external_id,
            $ticket_status,
            $assigned_to,
            $assigned_at
        ]);

        $recordId = (int)$pdo->lastInsertId();
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'Database insert failed: ' . $e->getMessage()
        ];
    }

    $paths = getLogPaths();
    logCommunication($full_name, $sender, $category, $message, $message_id, $channel, $message_type, $source, $assigned_to, $paths);
    
    // ✅ Send notification email to support team
    $emailSent = sendNotificationEmail($channel, $message_type, $category, $sender, $message, $full_name, $message_id, $assigned, $user_email, $paths);
    
    // ✅ Send STRUCTURED email to helpdesk (Power Automate will trigger on this and create SharePoint ticket)
    $structuredEmailSent = sendStructuredTicketEmail($channel, $category, $sender, $message, $full_name, $message_id, $assigned, $user_email, $external_id, $paths);

    return [
        'status' => 'success',
        'http_code' => 200,
        'message' => 'Communication received and logged',
        'record_id' => $recordId,
        'message_id' => $message_id,
        'email_sent' => $emailSent,
        'structured_email_sent' => $structuredEmailSent,
        'assigned_to' => $assigned_to,
        'ticket_status' => $ticket_status
    ];
}
