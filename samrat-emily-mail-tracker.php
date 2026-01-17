<?php
/**
 * Plugin Name: Samrat Emily Mail Tracker
 * Description: Logs all mails triggered by WordPress, WooCommerce and Dokan.
 * Version: 1.0.0
 * Author: Samrat Hossen
 * Author URI: https://samrat-personal-portfolio.netlify.app/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: samrat-emily-mail-tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'SAMRAT_EMILY_MAIL_TRACKER_VERSION', '1.0.0' );
define( 'SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_FILE', __FILE__ );
define( 'SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_ASSETS_URL', SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_URL . 'assets/' );

/**
 * Initialize the plugin
 */
function samrat_emily_mail_tracker_init() {
	require_once SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR . 'includes/class-samrat-emily-mail-tracker.php';
	new Samrat_Emily_Mail_Tracker();
}

add_action( 'plugins_loaded', 'samrat_emily_mail_tracker_init' );

/**
 * Activation hook
 */
function samrat_emily_mail_tracker_activate() {
	require_once SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR . 'includes/class-samrat-emily-mail-tracker.php';
	$samrat_emily_mail_tracker_plugin = new Samrat_Emily_Mail_Tracker();
	$samrat_emily_mail_tracker_plugin->create_table();
}

register_activation_hook( __FILE__, 'samrat_emily_mail_tracker_activate' );
