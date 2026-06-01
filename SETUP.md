# SMS System - Setup Instructions

## ⚠️ Before Running Locally

This is the **PUBLIC** version with demo/placeholder credentials. You need to configure your own credentials to run it.

### 1. Database Configuration

Edit these files and replace with YOUR database credentials:

- `process_sms.php` (line ~8)
- `simulate_handler.php` (line ~8)
- `dashboard/dashboard.php` (line ~8)

Replace:
```php
$pdo = new PDO("mysql:host=localhost;dbname=sms_shortcode", "root", "");
```

With your credentials:
```php
$pdo = new PDO("mysql:host=YOUR_HOST;dbname=YOUR_DB", "YOUR_USER", "YOUR_PASS");
```

### 2. Email Configuration (Gmail SMTP)

Edit these files:
- `process_sms.php` (line ~101-106)
- `simulate_handler.php` (line ~99-104)

Replace:
```php
$mail->Username   = "demo@gmail.com";  // YOUR EMAIL
$mail->Password   = "your_app_password";  // YOUR APP PASSWORD (not regular password!)
$mail->addAddress("recipient@example.com");  // WHERE TO SEND ALERTS
```

**⚠️ Important:** Use an [App Password](https://support.google.com/accounts/answer/185833), not your regular Gmail password!

### 3. Login Credentials

Edit `login.php` (line ~20) to change the demo credentials:

```php
if ($username === "your_username" && $password === "your_password") {
```

### 4. Database Schema

Create the `inbound_messages` table:

```sql
CREATE TABLE inbound_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100),
    sender VARCHAR(20),
    message TEXT,
    category VARCHAR(50),
    status VARCHAR(20),
    message_id INT,
    source VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Running the System

1. Place this folder in `htdocs/` (or your web root)
2. Configure credentials as above
3. Navigate to `http://localhost/sms-system-public/login.php`
4. Login with your configured credentials

## Features

- ✅ Inbound SMS logging
- ✅ Email alerts (Gmail SMTP)
- ✅ Dashboard with reports
- ✅ Message simulator for testing

## Support

For issues, check the `logs/` folder for error details.
