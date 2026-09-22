=== Mail Logbook ===
Contributors: emily50
Tags: mail log, email log, email tracker, wp_mail, smtp
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Capture and log every email sent from your WordPress site.

== Description ==

Mail Logbook is a lightweight and powerful solution for monitoring all outgoing emails on your WordPress site. Whether you are troubleshooting a broken notification or auditing what your site sends, Mail Logbook captures every message sent through `wp_mail()`, no matter which plugin or theme triggered it.

### Features
* **Full Email Capture**: Logs recipient, subject, headers, and full message content.
* **Sensitive Link Redaction**: Password reset keys and other one-click login links are masked before storage, on by default.
* **Search**: Find logs by recipient or subject.
* **Premium Admin UI**: A modern and clean dashboard to manage your logs.
* **Content Preview**: High-end modal integration to view email content.
* **Auto-Cleanup**: Automatically deletes old logs on a daily schedule based on your preferred retention period.
* **Global Toggle**: Pause logging anytime without deactivating the plugin.
* **Privacy Tools**: Supports WordPress's personal data export and erasure requests.

== Installation ==

1. Upload the `mail-logbook` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Your logs will appear under the new 'Mails' menu in your admin dashboard.

== Frequently Asked Questions ==

= Does it log password reset emails? =
Yes, it logs every email sent via the standard WordPress `wp_mail()` function, including password reset notifications. By default, the reset link's key is redacted before it's stored, so the log entry does not contain a working one-click login link. You can turn redaction off in Settings if you need the full raw content, but this is not recommended.

= Can I disable logging for a specific period? =
Yes, you can toggle logging on or off in the plugin settings.

= What happens to my logs if I deactivate or delete the plugin? =
Deactivating the plugin leaves your logged data untouched so you can safely re-activate it later. Deleting the plugin through the Plugins screen permanently removes the log table and all plugin settings.

== Screenshots ==

1. The main email log archive.
2. Detailed email content view in a modern modal.
3. Flexible settings for logging and retention.

== Changelog ==

= 1.2.0 =
* Removed the WooCommerce/Dokan source detection and badges; the plugin now logs every email generically, regardless of what sent it.
* Dropped the `source` column from the log table on upgrade.

= 1.1.0 =
* Added sensitive-link redaction, enabled by default, so password reset and order keys are masked before being stored.
* Added an option to disable storing the message body while still logging recipient and subject.
* Added search filtering to the log archive.
* Added WordPress personal data export and erasure support.
* Added a proper uninstall routine that removes the log table and settings.
* Moved log retention cleanup to a daily scheduled task instead of running during mail sending.
* Added an index on the log table so filtering and cleanup stay fast as logs grow.
* Added an upgrade routine so existing installs receive schema changes automatically.
* Added multisite support: network activation creates the table on every site, and new sites get it automatically.
* Fixed plain-text email bodies rendering without line breaks in the preview.
* Fixed the log table's empty-state row not spanning all columns.
* Fixed AJAX actions not reporting network errors to the admin.
* General hardening of the AJAX delete and bulk-delete handlers.

= 1.0.0 =
* Initial release.
* Added source detection for WooCommerce and Dokan.
* Implemented auto-cleanup engine.
