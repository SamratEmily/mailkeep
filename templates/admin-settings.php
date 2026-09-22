<?php
/**
 * Admin Settings Template
 *
 * @package Samrat_Emily_Mail_Tracker
 *
 * @var array  $samrat_emily_mail_tracker_settings Current settings.
 * @var string $samrat_emily_mail_tracker_option   Settings option name.
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
				<?php esc_html_e( 'Mail Log Settings', 'samrat-emily-mail-tracker' ); ?>
			</h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'samrat_emily_mail_tracker_options_group' ); ?>

				<div class="settings-section">
					<label class="settings-label"><?php esc_html_e( 'Enable Logging', 'samrat-emily-mail-tracker' ); ?></label>
					<p class="settings-desc"><?php esc_html_e( 'Check disable logging if you wish to temporarily stop capturing emails.', 'samrat-emily-mail-tracker' ); ?></p>
					<div class="toggle-group">
						<input type="hidden" name="<?php echo esc_attr( $samrat_emily_mail_tracker_option ); ?>[enable_logging]" id="enable_logging_input" value="<?php echo esc_attr( $samrat_emily_mail_tracker_settings['enable_logging'] ); ?>">
						<button type="button" class="toggle-btn <?php echo ( 'yes' === $samrat_emily_mail_tracker_settings['enable_logging'] ) ? 'active' : ''; ?>" data-value="yes" data-target="enable_logging_input"><?php esc_html_e( 'Enabled', 'samrat-emily-mail-tracker' ); ?></button>
						<button type="button" class="toggle-btn <?php echo ( 'no' === $samrat_emily_mail_tracker_settings['enable_logging'] ) ? 'active' : ''; ?>" data-value="no" data-target="enable_logging_input"><?php esc_html_e( 'Disabled', 'samrat-emily-mail-tracker' ); ?></button>
					</div>
				</div>

				<div class="settings-section">
					<label class="settings-label"><?php esc_html_e( 'Log Message Body', 'samrat-emily-mail-tracker' ); ?></label>
					<p class="settings-desc"><?php esc_html_e( 'Disable to store only the recipient and subject, without the full email content.', 'samrat-emily-mail-tracker' ); ?></p>
					<div class="toggle-group">
						<input type="hidden" name="<?php echo esc_attr( $samrat_emily_mail_tracker_option ); ?>[log_body]" id="log_body_input" value="<?php echo esc_attr( $samrat_emily_mail_tracker_settings['log_body'] ); ?>">
						<button type="button" class="toggle-btn <?php echo ( 'yes' === $samrat_emily_mail_tracker_settings['log_body'] ) ? 'active' : ''; ?>" data-value="yes" data-target="log_body_input"><?php esc_html_e( 'Enabled', 'samrat-emily-mail-tracker' ); ?></button>
						<button type="button" class="toggle-btn <?php echo ( 'no' === $samrat_emily_mail_tracker_settings['log_body'] ) ? 'active' : ''; ?>" data-value="no" data-target="log_body_input"><?php esc_html_e( 'Disabled', 'samrat-emily-mail-tracker' ); ?></button>
					</div>
				</div>

				<div class="settings-section">
					<label class="settings-label"><?php esc_html_e( 'Redact Sensitive Links', 'samrat-emily-mail-tracker' ); ?></label>
					<p class="settings-desc">
						<?php esc_html_e( 'Recommended. Masks password reset keys, order keys and similar one-click login links before they are stored, so the log table can never be used to take over an account.', 'samrat-emily-mail-tracker' ); ?>
					</p>
					<div class="toggle-group">
						<input type="hidden" name="<?php echo esc_attr( $samrat_emily_mail_tracker_option ); ?>[redact_sensitive]" id="redact_sensitive_input" value="<?php echo esc_attr( $samrat_emily_mail_tracker_settings['redact_sensitive'] ); ?>">
						<button type="button" class="toggle-btn <?php echo ( 'yes' === $samrat_emily_mail_tracker_settings['redact_sensitive'] ) ? 'active' : ''; ?>" data-value="yes" data-target="redact_sensitive_input"><?php esc_html_e( 'Enabled', 'samrat-emily-mail-tracker' ); ?></button>
						<button type="button" class="toggle-btn <?php echo ( 'no' === $samrat_emily_mail_tracker_settings['redact_sensitive'] ) ? 'active' : ''; ?>" data-value="no" data-target="redact_sensitive_input"><?php esc_html_e( 'Disabled', 'samrat-emily-mail-tracker' ); ?></button>
					</div>
				</div>

				<div class="settings-section">
					<label class="settings-label" for="retention_days"><?php esc_html_e( 'Log Retention (Days)', 'samrat-emily-mail-tracker' ); ?></label>
					<p class="settings-desc"><?php esc_html_e( 'Number of days to keep logs before they are automatically deleted. Set to 0 to keep forever.', 'samrat-emily-mail-tracker' ); ?></p>
					<input type="number" name="<?php echo esc_attr( $samrat_emily_mail_tracker_option ); ?>[retention_days]" id="retention_days" class="form-control" value="<?php echo esc_attr( $samrat_emily_mail_tracker_settings['retention_days'] ); ?>" min="0">
				</div>

				<button type="submit" class="save-btn"><?php esc_html_e( 'Save Settings', 'samrat-emily-mail-tracker' ); ?></button>
			</form>

			<div class="settings-section danger-zone" style="margin-top: 50px; border-top: 2px solid #fee2e2; padding-top: 30px;">
				<h2 style="color: #dc2626; font-size: 18px; margin-top: 0;"><?php esc_html_e( 'Danger Zone', 'samrat-emily-mail-tracker' ); ?></h2>
				<p class="settings-desc"><?php esc_html_e( 'Permanently delete all captured email logs from the database. This action cannot be undone.', 'samrat-emily-mail-tracker' ); ?></p>
				<button type="button" id="clear-all-logs" class="btn-danger"><?php esc_html_e( 'Clear All Email Logs', 'samrat-emily-mail-tracker' ); ?></button>
			</div>
		</div>
	</div>
</div>
