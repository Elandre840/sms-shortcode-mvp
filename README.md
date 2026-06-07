# SMS Shortcode MVP

A PHP-based helpdesk platform for managing inbound **SMS** and **WhatsApp** requests, routing them to technicians, sending email alerts, and feeding structured tickets into **Power Automate** / **SharePoint** workflows.

**Repository:** [github.com/Elandre840/sms-shortcode-mvp](https://github.com/Elandre840/sms-shortcode-mvp)

## Security notice

This is the **public demo** version. Credentials in the repo are placeholders only. Copy `config.example.php` to `config.php` and add your own settings locally. See [SETUP.md](SETUP.md) for full instructions.

## Core features

### Multi-channel messaging

- **SMS ingestion** via REST endpoint and local simulator
- **WhatsApp integration** via Twilio webhook (`inbound.php`)
- **WhatsApp call / JSON API** endpoint for extended message types
- Category tags in messages (e.g. `[ICT] My issue`) for automatic routing

### Ticket management

- Auto-logged inbound messages with reference IDs
- Status workflow: **New → Processed → Resolved**
- Category-based technician assignment
- Dashboard filtering by sender, category, ticket ID, and date range

### Authentication

- Demo admin login for quick access
- Technician **registration**, **login**, and **password reset**
- Secure password storage with `password_hash()` (legacy MD5 rows upgraded on login)

### Dashboard & reporting

- Real-time stats (total, new, processed, resolved)
- Ticket list with inline status updates
- **Reports module** with Chart.js visualisations:
  - Messages per day
  - Category distribution
  - Status breakdown
  - Channel analysis
- **CSV export** (raw data and summary reports)

### Email & automation

- **PHPMailer** SMTP alerts to configured recipients
- **Structured `[TICKET]` emails** with key=value body format for Power Automate parsing
- SharePoint-ready fields: Title, IssueDescription, Client, AssignedTo, Category, Priority, District, Programme, Status
- Configurable category-to-technician routing map

### Simulators (local testing)

- `simulate.php` — SMS message simulator UI
- `whatsapp_simulate.php` — WhatsApp message simulator UI

## Tech stack

| Layer | Technology |
| ----- | ---------- |
| Backend | PHP (PDO), MySQL |
| Frontend | HTML5, Bootstrap 5, Bootstrap Icons |
| Charts | Chart.js 4 |
| Email | PHPMailer (Gmail SMTP) |
| Integrations | Twilio (WhatsApp), Power Automate |

## Requirements

- PHP 7.4+
- MySQL / MariaDB
- XAMPP or similar local stack
- Gmail account with App Password (for email alerts)

## Quick start

```bash
git clone https://github.com/Elandre840/sms-shortcode-mvp.git
cd sms-shortcode-mvp
copy config.example.php config.php   # Windows
# cp config.example.php config.php   # Linux/macOS
```

1. Import `database/schema.sql` into MySQL.
2. Edit `config.php` with your credentials.
3. Visit `http://localhost/sms-shortcode-mvp/login.php`.

| Demo login | Value |
| ---------- | ----- |
| Username | `demo` |
| Password | `demo123` |

See [SETUP.md](SETUP.md) for detailed configuration.

## Project structure

```
sms-shortcode-mvp/
├── login.php                  # Login page
├── register.php               # Technician registration
├── reset_password.php         # Password reset
├── process_sms.php            # SMS processing API
├── inbound.php                # Twilio WhatsApp webhook
├── whatsapp_call.php          # WhatsApp JSON API
├── sms_processor.php          # Core message processing & email logic
├── simulate.php               # SMS simulator UI
├── whatsapp_simulate.php      # WhatsApp simulator UI
├── config.example.php         # Configuration template
├── database/schema.sql        # Database schema
├── dashboard/
│   ├── dashboard.php          # Main dashboard
│   └── reports.php            # Analytics & CSV export
├── includes/db.php            # Shared database helpers
├── logs/                      # Runtime logs (git-ignored)
└── PHPMailer/                 # Email library
```

## API endpoints

### Process SMS (GET/POST)

```
/process_sms.php?sender=27123456789&message=Hello&category=ICT
```

### Twilio WhatsApp webhook (POST)

```
/inbound.php
```

Twilio sends form-encoded fields: `From`, `Body`, `ProfileName`.

### WhatsApp JSON API (POST)

```
/whatsapp_call.php
```

JSON body: `sender`, `message`, `category`, `name`, `user_email`, `type`, `call_id`.

## Integration status

| Integration | Status |
| ----------- | ------ |
| WhatsApp (Twilio Sandbox) | Implemented |
| PHPMailer email alerts | Implemented |
| Power Automate structured tickets | Implemented |
| SMS gateway (carrier shortcode) | Simulated locally; production gateway pending |
| SharePoint direct sync | Via Power Automate email trigger |

## Planned enhancements

- Auto-assignment of tickets to technicians by district
- AI-assisted categorisation
- Twilio webhook signature validation
- Environment-variable based configuration
- Mobile-responsive dashboard refinements

## Troubleshooting

| Issue | Fix |
| ----- | --- |
| Database connection failed | Check MySQL is running and `config.php` credentials match |
| Emails not sending | Verify Gmail App Password and SMTP settings in `config.php` |
| Login fails | Clear cookies; confirm demo credentials or registered technician email |
| Debug output | Check `logs/sms_log.txt` and `logs/smtp_debug.txt` |

## License

PHPMailer is licensed separately — see [PHPMailer/LICENSE](PHPMailer/LICENSE).

---

**Last updated:** June 2026 · Public demo (safe for GitHub)
