# Setup Guide

Follow these steps to run the SMS Shortcode MVP locally with XAMPP.

## 1. Prerequisites

- PHP 7.4 or newer
- MySQL or MariaDB (included with XAMPP)
- A Gmail account with an [App Password](https://support.google.com/accounts/answer/185833) (for email alerts)

## 2. Install the project

1. Clone the repository into your web root, for example:
   ```
   C:/xampp/htdocs/sms-shortcode-mvp/
   ```
2. Copy the configuration template:
   ```
   copy config.example.php config.php
   ```
3. Edit `config.php` with your database, SMTP, and notification settings.

## 3. Create the database

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin or the MySQL CLI.
3. Import `database/schema.sql` to create the `sms_shortcode` database and tables.

## 4. Configure email (optional but recommended)

In `config.php`:

- Set `smtp.username` and `smtp.password` (Gmail App Password).
- Set `notifications.default_recipients` for alert emails.
- Set `notifications.helpdesk_email` for structured `[TICKET]` emails (Power Automate / SharePoint).
- Update `assignments` with your team routing addresses.

## 5. Log in

Open:

```
http://localhost/sms-shortcode-mvp/login.php
```

**Demo admin account (change before production):**

| Field    | Value   |
| -------- | ------- |
| Username | `demo`  |
| Password | `demo123` |

Technicians can also register via `register.php` and log in with their email address.

## 6. Test message flow

| Tool | URL | Purpose |
| ---- | --- | ------- |
| SMS Simulator | `/simulate.php` | Send test SMS messages |
| WhatsApp Simulator | `/whatsapp_simulate.php` | Send test WhatsApp messages |
| Process SMS API | `/process_sms.php?sender=27123456789&message=Hello&category=ICT` | Direct API test |
| Twilio Webhook | `/inbound.php` | Receives live WhatsApp messages from Twilio |

## 7. Logs

Runtime logs are written to `logs/` (git-ignored):

- `logs/sms_log.txt` — message activity
- `logs/smtp_debug.txt` — SMTP debug output

## 8. Production checklist

- Replace demo login credentials in `login.php`
- Use strong passwords and `password_hash()` for all accounts
- Move secrets to environment variables or a vault
- Disable SMTP debug output in production
- Restrict webhook endpoints (Twilio signature validation recommended)
- Never commit `config.php` or log files
