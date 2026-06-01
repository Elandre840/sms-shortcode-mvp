# SMS System - Public Version

A PHP-based SMS management system with inbound message logging, email alerts, and dashboard reporting.

## 🔐 Security First

This is the **PUBLIC** version with demo/placeholder credentials. **Do NOT use these in production!**

All real credentials have been removed. See [SETUP.md](SETUP.md) for configuration instructions.

## ✨ Core Features

### 📨 Message Simulation & Routing
- Simulate SMS creation and message flow
- Route messages through system workflows
- Track message movement between stages

### 📋 Dashboard Management
- View message statistics
- See communication activity summaries
- Monitor routed and pending messages

### 🔎 Search & Filtering
- Filter by sender, receiver, or status
- Search message logs
- Review system activity quickly

### 🧾 Logging & Traceability
- Record message history
- Track communication states
- Improve visibility of communication flow

### 📊 Email Alerts & Reporting
- Get real-time notifications via Gmail SMTP
- Display message activity in visual format
- Prepare operational summaries
- Support demo and stakeholder reporting

## 📋 Requirements

- PHP 7.4+
- MySQL/MariaDB
- XAMPP (or similar local development environment)
- Gmail account with [App Password](https://support.google.com/accounts/answer/185833) enabled

## 🚀 Quick Start

1. **Clone or download** this project
2. **Place** in `C:/xampp/htdocs/sms-system-public/`
3. **Create database** - See [SETUP.md](SETUP.md)
4. **Configure credentials** - See [SETUP.md](SETUP.md)
5. **Access** http://localhost/sms-system-public/login.php

## 📂 Project Structure

```
sms-system-public/
├── login.php              # Login page (demo/demo123)
├── process_sms.php        # SMS processing endpoint
├── simulate.php           # Message simulator UI
├── simulate_handler.php   # Simulator backend
├── inbound.php            # Webhook receiver
├── logout.php             # Logout handler
├── config.example.php     # Configuration template
├── SETUP.md               # Setup instructions
├── dashboard/
│   ├── dashboard.php      # Main dashboard
│   ├── reports.php        # Reports page
│   ├── view.php           # Message viewer
│   └── images/            # Dashboard assets
├── assets/                # Frontend assets
├── logs/                  # SMS/debug logs (git-ignored)
└── PHPMailer/             # Email library

```

## 🔧 Configuration

Before running:

1. **Database** - Update credentials in all `*.php` files (marked with `TODO:`)
2. **Email** - Set Gmail and App Password
3. **Recipient** - Set where alerts should be sent
4. **Login** - Change demo credentials in `login.php`

See [SETUP.md](SETUP.md) for detailed instructions.

## 📝 Demo Credentials

| Field | Value |
|-------|-------|
| **Username** | `demo` |
| **Password** | `demo123` |
| **Database** | `sms_shortcode` |

⚠️ **Change these before production deployment!**

## 🐛 Troubleshooting

- **Can't connect to database?** - Check your MySQL credentials
- **Emails not sending?** - Verify Gmail App Password is correct
- **Login fails?** - Clear browser cache and cookies
- **Check logs** - See `logs/sms_log.txt` and `logs/smtp_debug.txt` for errors

## 📞 API Endpoints

### Process SMS (GET/POST)
```
/process_sms.php?sender=27123456789&message=Hello&category=General
```

### Inbound Webhook
```
/inbound.php?num=27123456789&tonum=43619&mesg=Hello&id=12345
```

### Message Simulator
```
/simulate.php - Web form UI
/simulate_handler.php - Form submission
```

## 🎬 See It In Action

- **[Watch Demo Video](#)** - Full walkthrough (add your YouTube/video link)
- **[Live Site](#)** - Try the system yourself (add your live link)
- **[GitHub Repository](#)** - Source code

## 📜 License

Check the [PHPMailer LICENSE](PHPMailer/LICENSE) for library licensing.

## ⚠️ Important Notes

- This is a **demo/educational** version
- Use proper credentials management in production
- Never commit real credentials to version control
- Use `.env` files or environment variables in production
- Consider adding authentication database instead of hardcoded credentials

---

**Last Updated:** June 1, 2026  
**Status:** Public (Safe for GitHub) ✅
