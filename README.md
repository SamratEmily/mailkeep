# 📧 Samrat Emily Mail Tracker

A premium, lightweight, and powerful email logging solution for WordPress. Captures every email sent through `wp_mail()`, regardless of which plugin triggered it.

## ✨ Features

- **Full Email Capture**: Logs recipient, subject, headers, and full message content.
- **Sensitive Link Redaction**: Password reset keys, order keys and similar one-click login links are masked before being stored, on by default.
- **Search**: Find logs by recipient or subject.
- **Premium Admin UI**: A modern and clean dashboard to manage your logs.
- **Content Preview**: High-end modal integration to view email content.
- **Auto-Cleanup**: Automatically deletes old logs on a daily schedule based on your preferred retention period.
- **Global Toggle**: Pause logging anytime without deactivating the plugin.
- **Privacy Tools**: Integrates with WordPress's personal data export and erasure tools.

## 🛠️ Installation

1. Upload the `samrat-emily-mail-tracker` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **'Plugins'** menu in WordPress.
3. Access your logs via the new **'Mails'** menu in the admin sidebar.

## 📂 File Structure

```text
samrat-emily-mail-tracker/
├── assets/
│   ├── css/
│   │   └── admin.css             # Premium administrative styles
│   └── js/
│       └── admin.js              # Interactive UI logic
├── includes/
│   └── class-samrat-emily-mail-tracker.php # Core plugin engine
├── templates/
│   ├── admin-logs.php            # Mail logs display archive
│   └── admin-settings.php        # Plugin configuration page
├── samrat-emily-mail-tracker.php          # Main entry point & constants
└── uninstall.php                          # Cleans up on plugin deletion
```

---

## 🔒 Security & Privacy

- **Admin Only**: Log access is restricted to users with the `manage_options` capability.
- **Safe Storage**: Emails are stored in a dedicated database table with proper sanitization.
- **Redaction by Default**: Credential-bearing links (password resets, order keys, tokens) are masked before storage unless explicitly disabled.
- **Privacy First**: Choose how long to keep logs to comply with your data retention policies, and supports WordPress's built-in data export/erasure requests.
- **Clean Uninstall**: Removing the plugin drops its database table and settings; deactivating it does not.

---

## 🤝 Contribution

Contributions are welcome! If you have ideas for new features or find a bug, please feel free to open an issue or submit a pull request.

---

**Crafted with ❤️ for the WordPress Community.**
