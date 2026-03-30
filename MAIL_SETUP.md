# OTP Email Setup Guide

OTP emails (verification codes) will **not** reach your inbox until mail is configured.

## Quick Test

```bash
php artisan mail:otp-test your@email.com
```

---

## Apna Server / Email Use Karein

`.env` mein apne SMTP server ke details bharo:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=info@yourdomain.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@yourdomain.com
MAIL_FROM_NAME="HAAT"
```

**Common servers:**
- **cPanel/hosting**: `MAIL_HOST=mail.yourdomain.com` (ya `smtp.yourdomain.com`)
- **Gmail**: `MAIL_HOST=smtp.gmail.com`, App Password use karo
- **Outlook**: `MAIL_HOST=smtp.office365.com`
- **Port**: 587 = TLS, 465 = SSL, 25 = plain (encryption=null)

Config change ke baad: `php artisan config:clear`

---

## Option: Gmail SMTP

1. Enable 2FA on your Gmail account
2. Create an [App Password](https://myaccount.google.com/apppasswords)
3. Add to `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your.email@gmail.com
MAIL_PASSWORD=your_16_char_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your.email@gmail.com
MAIL_FROM_NAME="HAAT"
```

4. Run: `php artisan config:clear`

---

## Option 3: Mailtrap (Testing)

1. Sign up at [mailtrap.io](https://mailtrap.io)
2. Copy SMTP credentials from your inbox
3. Add to `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@haat.com"
MAIL_FROM_NAME="HAAT"
```

---

## Option 4: Resend (Production)

1. Sign up at [resend.com](https://resend.com)
2. Add to `.env`:

```env
MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxxxxxx
MAIL_FROM_ADDRESS=onboarding@yourdomain.com
MAIL_FROM_NAME="HAAT"
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| OTP not in inbox | Check `MAIL_MAILER` – if `log`, emails go to `storage/logs/laravel.log` |
| Gmail "Less secure app" | Use App Password, not your normal password |
| Connection timeout | Check firewall, port 587 (TLS) or 465 (SSL) |
| 530 Must issue STARTTLS | Set `MAIL_ENCRYPTION=tls` for port 587 |
| Config not updating | Run `php artisan config:clear` |
