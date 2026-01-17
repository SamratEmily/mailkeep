# 📧 Samrat Emily Mail Tracker
 
 A premium, lightweight, and powerful email logging solution for WordPress. Specifically designed to capture and categorize all outgoing emails from **WordPress Core**, **WooCommerce**, and **Dokan**.
 
@@ -22,7 +22,7 @@
 
 ## 🛠️ Installation
 
-1. Upload the `samrat-emily-mail-tracker` folder to your `/wp-content/plugins/` directory.
+1. Upload the `samrat-emily-mail-tracker` folder to your `/wp-content/plugins/` directory.
 2. Activate the plugin through the **'Plugins'** menu in WordPress.
 3. Access your logs via the new **'Mails'** menu in the admin sidebar.
 
@@ -31,18 +31,18 @@
 
 ## 📂 File Structure
 
 ```text
-samrat-emily-mail-tracker/
+samrat-emily-mail-tracker/
 ├── assets/
 │   ├── css/
 │   │   └── admin.css             # Premium administrative styles
 │   └── js/
 │       └── admin.js              # Interactive UI logic
 ├── includes/
-│   └── class-samrat-emily-mail-tracker.php # Core plugin engine
+│   └── class-samrat-emily-mail-tracker.php # Core plugin engine
 ├── templates/
 │   ├── admin-logs.php            # Mail logs display archive
 │   └── admin-settings.php        # Plugin configuration page
-└── samrat-emily-mail-tracker.php          # Main entry point & constants
+└── samrat-emily-mail-tracker.php          # Main entry point & constants
 ```

---

## 🔒 Security & Privacy

- **Admin Only**: Log access is restricted to users with the `manage_options` capability.
- **Safe Storage**: Emails are stored in a dedicated database table with proper sanitization.
- **Privacy First**: Choose how long to keep logs to comply with your data retention policies.

---

## 🤝 Contribution

Contributions are welcome! If you have ideas for new features or find a bug, please feel free to open an issue or submit a pull request.

---

**Crafted with ❤️ for the WordPress Community.**
