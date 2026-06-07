<?php
/**
 * SMS System Configuration Template
 *
 * Copy this file to config.php and update with your own credentials.
 * config.php is git-ignored and must never be committed.
 */

return [
    // Database Configuration
    'database' => [
        'host'     => 'localhost',
        'dbname'   => 'sms_shortcode',
        'username' => 'root',
        'password' => '',
    ],

    // SMTP Configuration (Gmail App Password recommended)
    'smtp' => [
        'host'        => 'smtp.gmail.com',
        'username'    => 'your-email@gmail.com',
        'password'    => 'your-app-password-here',
        'smtp_secure' => 'tls',
        'port'        => 587,
        'from_email'  => 'your-email@gmail.com',
        'from_name'   => 'SMS System',
    ],

    // Notification Recipients
    'notifications' => [
        'default_recipients' => [
            'helpdesk@example.com',
            'alerts@example.com',
        ],
        // Inbox monitored by Power Automate (subject filter: [TICKET])
        'helpdesk_email' => 'helpdesk@example.com',
    ],

    // Category-to-technician routing (used for assignment emails)
    'assignments' => [
        'NETWORK'        => ['name' => 'Network Support', 'email' => 'network@example.com'],
        'ICT'            => ['name' => 'ICT Support', 'email' => 'ict@example.com'],
        'SYSTEMS'        => ['name' => 'Systems Support', 'email' => 'systems@example.com'],
        'ICT-CONSUMABLE' => ['name' => 'ICT Consumables', 'email' => 'consumables@example.com'],
    ],

    'default_assignment' => [
        'name'  => 'General Support',
        'email' => 'helpdesk@example.com',
    ],

    // Log Configuration
    'logs' => [
        'path'             => __DIR__ . '/logs',
        'sms_log_file'     => 'sms_log.txt',
        'smtp_debug_file'  => 'smtp_debug.txt',
    ],

    // Application Settings
    'app' => [
        'default_channel' => 'sms',
        'default_status'  => 'new',
        'ticket_prefix'   => 'SR',
    ],
];
