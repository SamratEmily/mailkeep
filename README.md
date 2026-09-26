# 📧 Mailkeep

A lightweight WordPress plugin that captures every email your site sends through `wp_mail()` — WordPress core, WooCommerce, contact forms, membership plugins, anything — and lets you search and review them from your admin dashboard.

## Why

Outgoing email is one of the least visible parts of a WordPress site. When a customer says "I never got my order confirmation" or a password reset "doesn't work," there's usually no easy way to check what was actually sent, to whom, and when — you're stuck guessing whether it was a delivery problem, a broken template, or a plugin conflict. Mailkeep removes the guesswork by recording every email at the moment WordPress hands it off for sending, independent of whatever SMTP service or mail plugin actually delivers it.

## ✨ Features

- **Full Email Capture**: Logs recipient, subject, headers, message body, attachments and content type for every email sent through `wp_mail()`.
- **Sensitive Link Redaction**: Password reset keys, order keys and similar one-click login links are masked before being stored, on by default.
- **Search**: Find logged emails by recipient or subject.
- **Content Preview**: View the full email content in a modal without leaving the log list.
- **Auto-Cleanup**: A daily background task removes logs older than your configured retention period.
- **Global Toggle**: Pause logging at any time without deactivating the plugin.
- **Privacy Tools**: Registers with WordPress's personal data export and erasure tools, so logged mail is covered by privacy requests.
- **Multisite Aware**: Network activation provisions the log table on every site, and sites created afterward get it automatically.

## 🛠️ Installation

1. Upload the `mailkeep` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **'Plugins'** menu in WordPress.
3. Open the new **'Mails'** menu in the admin sidebar to view captured emails, or **'Mails → Settings'** to configure retention and redaction.

## How it works

- `Mailkeep::log_email()` hooks into the core `wp_mail` filter — the same choke point every plugin and theme sends mail through — so capture doesn't depend on knowing which plugin triggered a given email.
- Each email is written to its own table, `{$wpdb->prefix}mailkeep_logs`, created on activation and kept in sync across upgrades via a small schema-version check (`Mailkeep::DB_VERSION` / `maybe_upgrade()`).
- Redaction runs before the row is written: query arguments in a configurable list (`key`, `token`, `password`, `otp`, `order_key`, …) are masked in both the message body and headers. The list is filterable via `mailkeep_sensitive_keys`.
- Retention cleanup runs on a daily `wp_cron` event (`Mailkeep::CLEANUP_HOOK`) rather than inside the request that sends mail, so it never adds latency to checkout or form submissions.

## 📂 File Structure

```text
mailkeep/
├── assets/
│   ├── css/
│   │   └── admin.css                 # Admin dashboard styles
│   └── js/
│       └── admin.js                  # Log list interactions (search, delete, preview modal)
├── includes/
│   └── class-mailkeep.php        # Core plugin engine
├── templates/
│   ├── admin-logs.php                # Log archive screen
│   └── admin-settings.php            # Settings screen
├── mailkeep.php                  # Main entry point, constants, activation/deactivation
└── uninstall.php                     # Removes the log table and settings on plugin deletion
```

---

## 🔒 Security & Privacy

- **Admin Only**: Log access is restricted to users with the `manage_options` capability.
- **Safe Storage**: Emails are stored in a dedicated database table with proper sanitization and escaping.
- **Redaction by Default**: Credential-bearing links (password resets, order keys, tokens) are masked before storage unless explicitly disabled in Settings.
- **Configurable Retention**: Choose how long to keep logs, or disable body storage entirely and keep only metadata.
- **Privacy-Request Ready**: Supports WordPress's built-in data export and erasure tools (Tools → Export/Erase Personal Data).
- **Clean Uninstall**: Removing the plugin through the Plugins screen drops its database table, options and scheduled task; simply deactivating it does not.

---

## 🤝 Contribution

Contributions are welcome! If you have ideas for new features or find a bug, please feel free to open an issue or submit a pull request.

---

**Crafted with ❤️ for the WordPress Community.**
