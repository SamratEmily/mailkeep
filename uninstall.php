<?php
/**
 * Uninstall routine.
 *
 * Removes the log table, plugin option and schema version marker on every
 * site of a multisite network. Runs only when WordPress deletes the plugin
 * through the admin UI, never on deactivation.
 *
 * @package Mail_Logbook
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drop the log table and delete the plugin's options on the current site.
 *
 * @return void
 */
function mail_logbook_uninstall_site() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'mail_logbook_logs';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

	delete_option( 'mail_logbook_settings' );
	delete_option( 'mail_logbook_db_version' );
	delete_transient( 'mail_logbook_cleanup_lock' );

	wp_clear_scheduled_hook( 'mail_logbook_cleanup' );

	// Safety net: this plugin was previously named "samrat-emily-mail-tracker".
	// If it is deleted before ever being reactivated under the new name, the
	// migration in the class never ran, so its old data is cleaned up here too.
	$legacy_table = $wpdb->prefix . 'samrat_emily_mail_tracker_logs';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS $legacy_table" );

	delete_option( 'samrat_emily_mail_tracker_settings' );
	delete_option( 'samrat_emily_mail_tracker_db_version' );
	delete_transient( 'samrat_emily_mail_tracker_cleanup_lock' );

	wp_clear_scheduled_hook( 'samrat_emily_mail_tracker_cleanup' );
}

if ( is_multisite() ) {
	$mail_logbook_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	foreach ( $mail_logbook_site_ids as $mail_logbook_site_id ) {
		switch_to_blog( $mail_logbook_site_id );
		mail_logbook_uninstall_site();
		restore_current_blog();
	}
} else {
	mail_logbook_uninstall_site();
}
