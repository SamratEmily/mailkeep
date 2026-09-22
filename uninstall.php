<?php
/**
 * Uninstall routine.
 *
 * Removes the log table, plugin option and schema version marker on every
 * site of a multisite network. Runs only when WordPress deletes the plugin
 * through the admin UI, never on deactivation.
 *
 * @package Samrat_Emily_Mail_Tracker
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drop the log table and delete the plugin's options on the current site.
 *
 * @return void
 */
function samrat_emily_mail_tracker_uninstall_site() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'samrat_emily_mail_tracker_logs';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

	delete_option( 'samrat_emily_mail_tracker_settings' );
	delete_option( 'samrat_emily_mail_tracker_db_version' );
	delete_transient( 'samrat_emily_mail_tracker_cleanup_lock' );

	wp_clear_scheduled_hook( 'samrat_emily_mail_tracker_cleanup' );
}

if ( is_multisite() ) {
	$samrat_emily_mail_tracker_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	foreach ( $samrat_emily_mail_tracker_site_ids as $samrat_emily_mail_tracker_site_id ) {
		switch_to_blog( $samrat_emily_mail_tracker_site_id );
		samrat_emily_mail_tracker_uninstall_site();
		restore_current_blog();
	}
} else {
	samrat_emily_mail_tracker_uninstall_site();
}
