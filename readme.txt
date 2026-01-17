=== Samrat Emily Mail Tracker ===
Contributors: emily50
Tags: mail log, email log, woocommerce mail, dokan mail, email tracker
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Capture and log every email sent from your WordPress site. Specifically designed to categorize mails from WordPress, WooCommerce, and Dokan.

== Description ==

Samrat Emily Mail Tracker is a lightweight and powerful solution for monitoring all outgoing emails on your WordPress site. Whether you are troubleshooting checkout notifications or tracking vendor communication, Samrat Emily Mail Tracker captures it all.

### Features
* **Full Email Capture**: Logs recipient, subject, headers, and full message content.
* **Source Intelligence**: Automatically detects if an email was triggered by WordPress, WooCommerce, or Dokan.
* **Premium Admin UI**: A modern and clean dashboard to manage your logs.
* **Content Preview**: High-end modal integration to view email content.
* **Auto-Cleanup**: Automatically deletes old logs based on your preferred retention period.
* **Global Toggle**: Pause logging anytime without deactivating the plugin.

== Installation ==

1. Upload the `samrat-emily-mail-tracker` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Your logs will appear under the new 'Mails' menu in your admin dashboard.

== Frequently Asked Questions ==

= Does it log password reset emails? =
Yes, it logs every email sent via the standard WordPress `wp_mail()` function.

= Can I disable logging for a specific period? =
Yes, you can toggle logging on or off in the plugin settings.

== Screenshots ==

1. The main email log archive with source badges.
2. Detailed email content view in a modern modal.
3. Flexible settings for logging and retention.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added source detection for WooCommerce and Dokan.
* Implemented auto-cleanup engine.
