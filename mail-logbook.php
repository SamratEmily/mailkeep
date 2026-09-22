<?php
/**
 * Plugin Name: Mail Logbook
 * Description: Logs all emails sent from WordPress via wp_mail().
 * Version: 1.2.0
 * Author: Samrat Hossen
 * Author URI: https://samrat-personal-portfolio.netlify.app/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mail-logbook
 * Domain Path: /languages
 *
 * @package Mail_Logbook
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'MAIL_LOGBOOK_VERSION', '1.2.0' );
define( 'MAIL_LOGBOOK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAIL_LOGBOOK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAIL_LOGBOOK_PLUGIN_FILE', __FILE__ );
define( 'MAIL_LOGBOOK_PLUGIN_ASSETS_URL', MAIL_LOGBOOK_PLUGIN_URL . 'assets/' );

/**
 * Load the plugin class.
 *
 * @return void
 */
function mail_logbook_load() {
	require_once MAIL_LOGBOOK_PLUGIN_DIR . 'includes/class-mail-logbook.php';
}

/**
 * Initialize the plugin.
 *
 * The constructor is intentionally side effect free so that the activation
 * routine can build an instance without registering runtime hooks.
 *
 * @return void
 */
function mail_logbook_init() {
	mail_logbook_load();

	$mail_logbook_plugin = new Mail_Logbook();
	$mail_logbook_plugin->register_hooks();
}

add_action( 'plugins_loaded', 'mail_logbook_init' );

/**
 * Activation hook.
 *
 * Creates the log table on every site of the install when network activated,
 * otherwise only on the current site.
 *
 * @param bool $network_wide Whether the plugin was network activated.
 * @return void
 */
function mail_logbook_activate( $network_wide = false ) {
	mail_logbook_load();

	$mail_logbook_plugin = new Mail_Logbook();

	if ( $network_wide && is_multisite() ) {
		$mail_logbook_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $mail_logbook_sites as $mail_logbook_site_id ) {
			switch_to_blog( $mail_logbook_site_id );
			$mail_logbook_plugin->install();
			restore_current_blog();
		}

		return;
	}

	$mail_logbook_plugin->install();
}

register_activation_hook( __FILE__, 'mail_logbook_activate' );

/**
 * Deactivation hook.
 *
 * Unschedules the retention cleanup event. Log data is left untouched here and
 * is only removed by uninstall.php.
 *
 * @param bool $network_wide Whether the plugin was network deactivated.
 * @return void
 */
function mail_logbook_deactivate( $network_wide = false ) {
	mail_logbook_load();

	if ( $network_wide && is_multisite() ) {
		$mail_logbook_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $mail_logbook_sites as $mail_logbook_site_id ) {
			switch_to_blog( $mail_logbook_site_id );
			wp_clear_scheduled_hook( Mail_Logbook::CLEANUP_HOOK );
			restore_current_blog();
		}

		return;
	}

	wp_clear_scheduled_hook( Mail_Logbook::CLEANUP_HOOK );
}

register_deactivation_hook( __FILE__, 'mail_logbook_deactivate' );
