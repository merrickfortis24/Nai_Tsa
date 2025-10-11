<?php
// Copy this file to .mail.env.php (same folder) and fill in your credentials.
// Do NOT commit .mail.env.php; it is git-ignored.

// Mode: 'sandbox' (Mailtrap Testing), 'live' (Mailtrap Email Delivery), or 'smtp' (generic SMTP)
putenv('MAIL_MODE=sandbox');

// Mailtrap Sandbox credentials (from your Inbox > SMTP Settings)
putenv('MAILTRAP_USER=YOUR_SANDBOX_USERNAME');
putenv('MAILTRAP_PASS=YOUR_SANDBOX_PASSWORD');

// From identity (use something neutral for sandbox)
putenv('MAIL_FROM=test@example.com');
putenv('MAIL_FROM_NAME=Nai Tsa');

// Optional: force all outgoing test emails to one address (safe for dev)
// putenv('MAIL_FORCE_TO=you@example.com');

// Optional: add CC/BCC recipients (comma or semicolon separated)
// putenv('MAIL_CC=yourgmail@gmail.com, teammate@example.com');
// putenv('MAIL_BCC=archive@example.com');

// Optional: enable SMTP debug (logs via PHP error_log)
// putenv('MAIL_DEBUG=1');
// Optional: contact form rate limit controls (for testing you can disable)
// Disable rate limiting entirely (any non-empty value turns it on)
putenv('CONTACT_LIMIT_DISABLE=1');
// Customize limits (only used if not disabled). Defaults: 5 per 3600s
// putenv('CONTACT_LIMIT_PER_HOUR=10');
// putenv('CONTACT_LIMIT_WINDOW=3600');
// Whitelist IPs that bypass limits (comma/semicolon/space separated)
// putenv('CONTACT_LIMIT_WHITELIST=127.0.0.1; ::1');

// --- Live (Mailtrap Email Delivery) example ---
// putenv('MAIL_MODE=live');
// putenv('MAILTRAP_LIVE_HOST=live.smtp.mailtrap.io');
// putenv('MAILTRAP_LIVE_PORT=587');
// putenv('MAILTRAP_LIVE_USER=api');
// putenv('MAILTRAP_TOKEN=YOUR_LIVE_API_TOKEN');
// putenv('MAIL_FROM=you@your-verified-domain.com');

// --- Generic SMTP example (e.g., Gmail with an App Password) ---
// putenv('MAIL_MODE=smtp');
// putenv('SMTP_HOST=smtp.gmail.com');
// putenv('SMTP_PORT=587');
// putenv('SMTP_USER=your@gmail.com');
// putenv('SMTP_PASS=your_app_password');
// putenv('MAIL_FROM=your@gmail.com');
