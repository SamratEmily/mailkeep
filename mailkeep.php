<?php
/**
 * Plugin Name: Mailkeep
 * Description: Captures and stores every email your site sends through wp_mail(), so you can search, review and audit them from the admin dashboard.
 * Version: 1.0.0
 * Author: Samrat Hossen
 * Author URI: https://samratemily.netlify.app/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mailkeep
 * Domain Path: /languages
 *
 * @package Mailkeep
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'MAILKEEP_VERSION', '1.0.0' );
define( 'MAILKEEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAILKEEP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAILKEEP_PLUGIN_FILE', __FILE__ );
define( 'MAILKEEP_PLUGIN_ASSETS_URL', MAILKEEP_PLUGIN_URL . 'assets/' );

/**
 * Load the plugin class.
 *
 * @return void
 */
function mailkeep_load() {
	require_once MAILKEEP_PLUGIN_DIR . 'includes/class-mailkeep.php';
}

/**
 * Initialize the plugin.
 *
 * The constructor is intentionally side effect free so that the activation
 * routine can build an instance without registering runtime hooks.
 *
 * @return void
 */
function mailkeep_init() {
	mailkeep_load();

	$mailkeep_plugin = new Mailkeep();
	$mailkeep_plugin->register_hooks();
}

add_action( 'plugins_loaded', 'mailkeep_init' );

/**
 * Activation hook.
 *
 * Creates the log table on every site of the install when network activated,
 * otherwise only on the current site.
 *
 * @param bool $network_wide Whether the plugin was network activated.
 * @return void
 */
function mailkeep_activate( $network_wide = false ) {
	mailkeep_load();

	$mailkeep_plugin = new Mailkeep();

	if ( $network_wide && is_multisite() ) {
		$mailkeep_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $mailkeep_sites as $mailkeep_site_id ) {
			switch_to_blog( $mailkeep_site_id );
			$mailkeep_plugin->install();
			restore_current_blog();
		}

		return;
	}

	$mailkeep_plugin->install();
}

register_activation_hook( __FILE__, 'mailkeep_activate' );

/**
 * Deactivation hook.
 *
 * Unschedules the retention cleanup event. Log data is left untouched here and
 * is only removed by uninstall.php.
 *
 * @param bool $network_wide Whether the plugin was network deactivated.
 * @return void
 */
function mailkeep_deactivate( $network_wide = false ) {
	mailkeep_load();

	if ( $network_wide && is_multisite() ) {
		$mailkeep_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $mailkeep_sites as $mailkeep_site_id ) {
			switch_to_blog( $mailkeep_site_id );
			wp_clear_scheduled_hook( Mailkeep::CLEANUP_HOOK );
			restore_current_blog();
		}

		return;
	}

	wp_clear_scheduled_hook( Mailkeep::CLEANUP_HOOK );
}

register_deactivation_hook( __FILE__, 'mailkeep_deactivate' );
