<?php
/**
 * Plugin Name: Samrat Emily Mail Tracker
 * Description: Logs all emails sent from WordPress via wp_mail().
 * Version: 1.2.0
 * Author: Samrat Hossen
 * Author URI: https://samrat-personal-portfolio.netlify.app/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: samrat-emily-mail-tracker
 * Domain Path: /languages
 *
 * @package Samrat_Emily_Mail_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'SAMRAT_EMILY_MAIL_TRACKER_VERSION', '1.2.0' );
define( 'SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_FILE', __FILE__ );
define( 'SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_ASSETS_URL', SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_URL . 'assets/' );

/**
 * Load the plugin class.
 *
 * @return void
 */
function samrat_emily_mail_tracker_load() {
	require_once SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR . 'includes/class-samrat-emily-mail-tracker.php';
}

/**
 * Initialize the plugin.
 *
 * The constructor is intentionally side effect free so that the activation
 * routine can build an instance without registering runtime hooks.
 *
 * @return void
 */
function samrat_emily_mail_tracker_init() {
	samrat_emily_mail_tracker_load();

	$samrat_emily_mail_tracker_plugin = new Samrat_Emily_Mail_Tracker();
	$samrat_emily_mail_tracker_plugin->register_hooks();
}

add_action( 'plugins_loaded', 'samrat_emily_mail_tracker_init' );

/**
 * Activation hook.
 *
 * Creates the log table on every site of the install when network activated,
 * otherwise only on the current site.
 *
 * @param bool $network_wide Whether the plugin was network activated.
 * @return void
 */
function samrat_emily_mail_tracker_activate( $network_wide = false ) {
	samrat_emily_mail_tracker_load();

	$samrat_emily_mail_tracker_plugin = new Samrat_Emily_Mail_Tracker();

	if ( $network_wide && is_multisite() ) {
		$samrat_emily_mail_tracker_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $samrat_emily_mail_tracker_sites as $samrat_emily_mail_tracker_site_id ) {
			switch_to_blog( $samrat_emily_mail_tracker_site_id );
			$samrat_emily_mail_tracker_plugin->install();
			restore_current_blog();
		}

		return;
	}

	$samrat_emily_mail_tracker_plugin->install();
}

register_activation_hook( __FILE__, 'samrat_emily_mail_tracker_activate' );

/**
 * Deactivation hook.
 *
 * Unschedules the retention cleanup event. Log data is left untouched here and
 * is only removed by uninstall.php.
 *
 * @param bool $network_wide Whether the plugin was network deactivated.
 * @return void
 */
function samrat_emily_mail_tracker_deactivate( $network_wide = false ) {
	samrat_emily_mail_tracker_load();

	if ( $network_wide && is_multisite() ) {
		$samrat_emily_mail_tracker_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $samrat_emily_mail_tracker_sites as $samrat_emily_mail_tracker_site_id ) {
			switch_to_blog( $samrat_emily_mail_tracker_site_id );
			wp_clear_scheduled_hook( Samrat_Emily_Mail_Tracker::CLEANUP_HOOK );
			restore_current_blog();
		}

		return;
	}

	wp_clear_scheduled_hook( Samrat_Emily_Mail_Tracker::CLEANUP_HOOK );
}

register_deactivation_hook( __FILE__, 'samrat_emily_mail_tracker_deactivate' );
