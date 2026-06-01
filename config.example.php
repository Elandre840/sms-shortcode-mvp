<?php
/**
 * Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to: config.local.php (in same directory as login.php)
 * 2. Fill in your actual credentials
 * 3. Update the PHP files to require this config
 * 
 * ⚠️ IMPORTANT: Add config.local.php to .gitignore (already done)
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sms_shortcode');

// Email Configuration (Gmail SMTP)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USER', 'your_email@gmail.com');  // TODO: Set your Gmail
define('MAIL_PASS', 'your_app_password');      // TODO: Set your App Password
define('MAIL_FROM', 'your_email@gmail.com');   // TODO: Set your Gmail
define('MAIL_TO', 'alert_recipient@example.com'); // TODO: Set recipient email

// Login Credentials
define('LOGIN_USER', 'demo');
define('LOGIN_PASS', 'demo123');
