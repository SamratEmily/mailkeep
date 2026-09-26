<?php
/**
 * Uninstall routine.
 *
 * Removes the log table, plugin option and schema version marker on every
 * site of a multisite network. Runs only when WordPress deletes the plugin
 * through the admin UI, never on deactivation.
 *
 * @package Mailkeep
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drop the log table and delete the plugin's options on the current site.
 *
 * @return void
 */
function mailkeep_uninstall_site() {
	global $wpdb;

	$table_name = esc_sql( $wpdb->prefix . 'mailkeep_logs' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table_name is built from $wpdb->prefix and a literal suffix, never from user input, and is escaped by esc_sql().
	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

	delete_option( 'mailkeep_settings' );
	delete_option( 'mailkeep_db_version' );
	delete_transient( 'mailkeep_cleanup_lock' );

	wp_clear_scheduled_hook( 'mailkeep_cleanup' );
}

if ( is_multisite() ) {
	$mailkeep_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	foreach ( $mailkeep_site_ids as $mailkeep_site_id ) {
		switch_to_blog( $mailkeep_site_id );
		mailkeep_uninstall_site();
		restore_current_blog();
	}
} else {
	mailkeep_uninstall_site();
}
