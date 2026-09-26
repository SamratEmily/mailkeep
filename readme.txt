=== Mailkeep ===
Contributors: emily50
Tags: mail log, email log, email tracker, wp_mail, smtp
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Capture, search and review every email your WordPress site sends, without touching your theme or mail delivery setup.

== Description ==

Mailkeep is a lightweight logging tool for one of the most invisible parts of a WordPress site: outgoing email. Every time WordPress, a plugin or a theme calls `wp_mail()` — a password reset, a new-order notification, a contact form submission, an admin alert — Mailkeep records it to a dedicated database table, independent of whatever SMTP or mail-sending service actually delivers it.

This makes it easy to answer questions that are otherwise hard to check: Did that notification actually get sent? What did the email say? Who received it, and when? You don't need to reproduce the problem or dig through server mail logs — the answer is in your dashboard.

Because it hooks into `wp_mail()` itself rather than any particular plugin, Mailkeep works the same way regardless of what generates the email — core, WooCommerce, a contact form, a membership plugin, or custom code.

= How it works =

* Every outgoing email is written to its own table (`wp_mailkeep_logs`), recording the recipient, subject, message body, headers, attachments list and content type.
* The **Mails** admin screen lists every logged email with search, pagination and a modal preview of the full content.
* A background daily task removes logs older than your configured retention period, so the table doesn't grow forever.
* Logging can be paused at any time from Settings without deactivating the plugin.

= Built-in privacy protections =

Mail bodies routinely contain sensitive one-click links — password resets, order-pay links, login tokens. Storing those in plain text would turn the log table into a security liability. Mailkeep redacts known sensitive query parameters (`key`, `token`, `password`, `otp`, `order_key`, and more, filterable via `mailkeep_sensitive_keys`) before anything is written to the database, and this is **on by default**.

If you'd rather not store message content at all, you can disable body logging entirely and keep only the recipient, subject and timestamp. Mailkeep also registers with WordPress's built-in personal data export and erasure tools, so logged mail is included when you process a privacy request.

= Features =

* **Full email capture** — recipient, subject, headers, message body, attachments and content type.
* **Sensitive link redaction** — enabled by default; configurable via a settings toggle and a filter.
* **Search** — find logged mail by recipient or subject.
* **Content preview** — view the full rendered email in a modal, without leaving the log list.
* **Auto-cleanup** — a daily scheduled task removes logs past your chosen retention window.
* **Global on/off switch** — pause logging without deactivating the plugin.
* **Privacy-request ready** — integrates with `wp_privacy_personal_data_exporters` / `erasers`.
* **Multisite aware** — network activation provisions the log table on every site, and sites created later get it automatically.
* **Clean removal** — deleting the plugin removes its table and settings; deactivating it does not.

== Installation ==

1. Upload the `mailkeep` folder to the `/wp-content/plugins/` directory, or install it directly from the Plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open the new 'Mails' menu in your admin sidebar to view captured emails, or 'Mails → Settings' to configure retention and redaction.

== Frequently Asked Questions ==

= Does it log password reset emails? =
Yes — it logs every email sent via the standard WordPress `wp_mail()` function, including password reset notifications. By default the reset link's key is redacted before storage, so the log entry does not contain a working one-click login link. Redaction can be turned off in Settings if you need the raw content, but this is not recommended.

= Will this slow down my site or delay emails? =
No. Logging happens after `wp_mail()` builds the message and does not affect delivery. Retention cleanup runs on a daily background task rather than on every email sent, so it never adds overhead to a page request.

= Does it work with plugins other than WooCommerce? =
Yes. Mailkeep hooks into WordPress's own `wp_mail()` filter, which every plugin and theme uses to send mail, so it captures messages regardless of what triggered them.

= Can I disable logging for a specific period? =
Yes — toggle logging on or off from Mailkeep's settings page at any time, without deactivating the plugin.

= What happens to my logs if I deactivate or delete the plugin? =
Deactivating the plugin leaves your logged data untouched so you can safely reactivate it later. Deleting the plugin through the Plugins screen permanently removes the log table and all plugin settings.

== Screenshots ==

1. The main email log archive with search.
2. Detailed email content view in a modal.
3. Settings for logging, redaction and retention.

== Changelog ==

= 1.0.0 =
* Initial release.
