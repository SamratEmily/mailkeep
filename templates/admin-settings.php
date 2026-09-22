<?php
/**
 * Admin Settings Template
 *
 * @package Mail_Logbook
 *
 * @var array  $mail_logbook_settings Current settings.
 * @var string $mail_logbook_option   Settings option name.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<div class="mail-log-wrapper">
		<div class="mail-log-content">
			<h1 class="mail-log-title">
				<span class="dashicons dashicons-email-alt" style="font-size: 28px; width: 28px; height: 28px;" aria-hidden="true"></span>
				<?php esc_html_e( 'Mail Log Settings', 'mail-logbook' ); ?>
			</h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'mail_logbook_options_group' ); ?>

				<div class="settings-section">
					<label class="settings-label"><?php esc_html_e( 'Enable Logging', 'mail-logbook' ); ?></label>
					<p class="settings-desc"><?php esc_html_e( 'Check disable logging if you wish to temporarily stop capturing emails.', 'mail-logbook' ); ?></p>
					<div class="toggle-group">
						<input type="hidden" name="<?php echo esc_attr( $mail_logbook_option ); ?>[enable_logging]" id="enable_logging_input" value="<?php echo esc_attr( $mail_logbook_settings['enable_logging'] ); ?>">
						<button type="button" class="toggle-btn <?php echo ( 'yes' === $mail_logbook_settings['enable_logging'] ) ? 'active' : ''; ?>" data-value="yes" data-target="enable_logging_input"><?php esc_html_e( 'Enabled', 'mail-logbook' ); ?></button>
						<button type="button" class="toggle-btn <?php echo ( 'no' === $mail_logbook_settings['enable_logging'] ) ? 'active' : ''; ?>" data-value="no" data-target="enable_logging_input"><?php esc_html_e( 'Disabled', 'mail-logbook' ); ?></button>
					</div>
				</div>

				<div class="settings-section">
					<label class="settings-label"><?php esc_html_e( 'Log Message Body', 'mail-logbook' ); ?></label>
					<p class="settings-desc"><?php esc_html_e( 'Disable to store only the recipient and subject, without the full email content.', 'mail-logbook' ); ?></p>
					<div class="toggle-group">
						<input type="hidden" name="<?php echo esc_attr( $mail_logbook_option ); ?>[log_body]" id="log_body_input" value="<?php echo esc_attr( $mail_logbook_settings['log_body'] ); ?>">
						<button type="button" class="toggle-btn <?php echo ( 'yes' === $mail_logbook_settings['log_body'] ) ? 'active' : ''; ?>" data-value="yes" data-target="log_body_input"><?php esc_html_e( 'Enabled', 'mail-logbook' ); ?></button>
						<button type="button" class="toggle-btn <?php echo ( 'no' === $mail_logbook_settings['log_body'] ) ? 'active' : ''; ?>" data-value="no" data-target="log_body_input"><?php esc_html_e( 'Disabled', 'mail-logbook' ); ?></button>
					</div>
				</div>

				<div class="settings-section">
					<label class="settings-label"><?php esc_html_e( 'Redact Sensitive Links', 'mail-logbook' ); ?></label>
					<p class="settings-desc">
						<?php esc_html_e( 'Recommended. Masks password reset keys, order keys and similar one-click login links before they are stored, so the log table can never be used to take over an account.', 'mail-logbook' ); ?>
					</p>
					<div class="toggle-group">
						<input type="hidden" name="<?php echo esc_attr( $mail_logbook_option ); ?>[redact_sensitive]" id="redact_sensitive_input" value="<?php echo esc_attr( $mail_logbook_settings['redact_sensitive'] ); ?>">
						<button type="button" class="toggle-btn <?php echo ( 'yes' === $mail_logbook_settings['redact_sensitive'] ) ? 'active' : ''; ?>" data-value="yes" data-target="redact_sensitive_input"><?php esc_html_e( 'Enabled', 'mail-logbook' ); ?></button>
						<button type="button" class="toggle-btn <?php echo ( 'no' === $mail_logbook_settings['redact_sensitive'] ) ? 'active' : ''; ?>" data-value="no" data-target="redact_sensitive_input"><?php esc_html_e( 'Disabled', 'mail-logbook' ); ?></button>
					</div>
				</div>

				<div class="settings-section">
					<label class="settings-label" for="retention_days"><?php esc_html_e( 'Log Retention (Days)', 'mail-logbook' ); ?></label>
					<p class="settings-desc"><?php esc_html_e( 'Number of days to keep logs before they are automatically deleted. Set to 0 to keep forever.', 'mail-logbook' ); ?></p>
					<input type="number" name="<?php echo esc_attr( $mail_logbook_option ); ?>[retention_days]" id="retention_days" class="form-control" value="<?php echo esc_attr( $mail_logbook_settings['retention_days'] ); ?>" min="0">
				</div>

				<button type="submit" class="save-btn"><?php esc_html_e( 'Save Settings', 'mail-logbook' ); ?></button>
			</form>

			<div class="settings-section danger-zone" style="margin-top: 50px; border-top: 2px solid #fee2e2; padding-top: 30px;">
				<h2 style="color: #dc2626; font-size: 18px; margin-top: 0;"><?php esc_html_e( 'Danger Zone', 'mail-logbook' ); ?></h2>
				<p class="settings-desc"><?php esc_html_e( 'Permanently delete all captured email logs from the database. This action cannot be undone.', 'mail-logbook' ); ?></p>
				<button type="button" id="clear-all-logs" class="btn-danger"><?php esc_html_e( 'Clear All Email Logs', 'mail-logbook' ); ?></button>
			</div>
		</div>
	</div>
</div>
