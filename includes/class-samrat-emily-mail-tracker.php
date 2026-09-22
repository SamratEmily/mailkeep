<?php
/**
 * Main Samrat_Emily_Mail_Tracker class file.
 *
 * @package Samrat_Emily_Mail_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin engine.
 */
class Samrat_Emily_Mail_Tracker {

	/**
	 * Schema revision. Bump whenever create_table() changes so that existing
	 * installs pick the change up through maybe_upgrade().
	 */
	const DB_VERSION = '3';

	/**
	 * Option holding the installed schema revision.
	 */
	const DB_VERSION_OPTION = 'samrat_emily_mail_tracker_db_version';

	/**
	 * Cron hook running the retention cleanup.
	 */
	const CLEANUP_HOOK = 'samrat_emily_mail_tracker_cleanup';

	/**
	 * Replacement written over credential bearing query arguments.
	 */
	const REDACTED = 'REDACTED-BY-MAIL-TRACKER';

	/**
	 * Rows read per admin page.
	 */
	const PER_PAGE = 20;

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private $option_name = 'samrat_emily_mail_tracker_settings';

	/**
	 * Register runtime hooks.
	 *
	 * Kept out of the constructor so activation can instantiate the class
	 * without side effects.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'maybe_upgrade' ) );
		add_action( 'init', array( $this, 'maybe_schedule_cleanup' ) );

		add_filter( 'wp_mail', array( $this, 'log_email' ) );
		add_action( self::CLEANUP_HOOK, array( $this, 'cleanup_logs' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_FILE ), array( $this, 'add_plugin_action_links' ) );

		// Create the table on sites added to a network after activation.
		add_action( 'wp_initialize_site', array( $this, 'on_new_site' ), 20 );

		// Privacy tooling.
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_privacy_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_privacy_eraser' ) );

		// AJAX Handlers.
		add_action( 'wp_ajax_samrat_emily_mail_tracker_delete_log', array( $this, 'ajax_delete_log' ) );
		add_action( 'wp_ajax_samrat_emily_mail_tracker_bulk_delete', array( $this, 'ajax_bulk_delete' ) );
		add_action( 'wp_ajax_samrat_emily_mail_tracker_clear_all', array( $this, 'ajax_clear_all' ) );
	}

	/**
	 * Resolve the log table name for the current site.
	 *
	 * Read live rather than cached in a property so the name stays correct
	 * across switch_to_blog().
	 *
	 * @return string
	 */
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'samrat_emily_mail_tracker_logs';
	}

	/**
	 * Load translations for installs outside of WordPress.org.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'samrat-emily-mail-tracker',
			false,
			dirname( plugin_basename( SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/* ---------------------------------------------------------------------
	 * Installation and schema
	 * ------------------------------------------------------------------ */

	/**
	 * Full install routine: schema, defaults and cron.
	 *
	 * @return void
	 */
	public function install() {
		$this->create_table();

		if ( ! get_option( $this->option_name ) ) {
			update_option( $this->option_name, $this->get_default_settings() );
		}

		$this->maybe_schedule_cleanup();
	}

	/**
	 * Create or update the log table.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$table_name      = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			to_email text NOT NULL,
			subject text NOT NULL,
			message longtext NOT NULL,
			headers text,
			attachments text,
			content_type varchar(100) NOT NULL DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$this->maybe_drop_source_column();

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Drop the legacy source column.
	 *
	 * dbDelta() only ever adds columns, so installs upgrading from a version
	 * that tracked WooCommerce/Dokan as a "source" need this run once to
	 * remove it. Checked via information_schema so it is safe to call on a
	 * table that never had the column.
	 *
	 * @return void
	 */
	private function maybe_drop_source_column() {
		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
				DB_NAME,
				$table_name,
				'source'
			)
		);

		if ( ! $column_exists ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "ALTER TABLE $table_name DROP COLUMN source" );
	}

	/**
	 * Run dbDelta when the installed schema is behind the shipped one.
	 *
	 * Without this, installs that activated an older version never receive new
	 * columns or indexes because create_table() only ran on activation.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}

		$this->create_table();
	}

	/**
	 * Create the table when a new site joins a network the plugin is active on.
	 *
	 * @param WP_Site $new_site Newly created site.
	 * @return void
	 */
	public function on_new_site( $new_site ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active_for_network( plugin_basename( SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_FILE ) ) ) {
			return;
		}

		switch_to_blog( (int) $new_site->blog_id );
		$this->install();
		restore_current_blog();
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	/**
	 * Default settings.
	 *
	 * Redaction defaults to on so that a fresh install never stores a working
	 * password reset link.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array(
			'enable_logging'   => 'yes',
			'retention_days'   => 30,
			'log_body'         => 'yes',
			'redact_sensitive' => 'yes',
		);
	}

	/**
	 * Register the settings group.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'samrat_emily_mail_tracker_options_group',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'validate_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function validate_settings( $input ) {
		$defaults = $this->get_default_settings();

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$valid = array();

		$valid['enable_logging']   = ( isset( $input['enable_logging'] ) && 'no' === $input['enable_logging'] ) ? 'no' : 'yes';
		$valid['log_body']         = ( isset( $input['log_body'] ) && 'no' === $input['log_body'] ) ? 'no' : 'yes';
		$valid['redact_sensitive'] = ( isset( $input['redact_sensitive'] ) && 'no' === $input['redact_sensitive'] ) ? 'no' : 'yes';
		$valid['retention_days']   = isset( $input['retention_days'] ) ? absint( $input['retention_days'] ) : $defaults['retention_days'];

		return $valid;
	}

	/**
	 * Read settings merged over defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$stored = get_option( $this->option_name );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, $this->get_default_settings() );
	}

	/* ---------------------------------------------------------------------
	 * Logging
	 * ------------------------------------------------------------------ */

	/**
	 * Capture an outgoing mail.
	 *
	 * Hooked on the wp_mail filter, so the arguments are returned untouched.
	 *
	 * @param mixed $args wp_mail() arguments.
	 * @return mixed
	 */
	public function log_email( $args ) {
		if ( ! is_array( $args ) ) {
			return $args;
		}

		$settings = $this->get_settings();

		if ( 'yes' !== $settings['enable_logging'] ) {
			return $args;
		}

		global $wpdb;

		// Another plugin filtering wp_mail earlier may hand us a partial array.
		$to          = isset( $args['to'] ) ? $args['to'] : '';
		$subject     = isset( $args['subject'] ) ? $args['subject'] : '';
		$message     = isset( $args['message'] ) ? $args['message'] : '';
		$headers     = isset( $args['headers'] ) ? $args['headers'] : '';
		$attachments = isset( $args['attachments'] ) ? $args['attachments'] : '';

		$to          = is_array( $to ) ? implode( ', ', $to ) : (string) $to;
		$subject     = is_string( $subject ) ? $subject : '';
		$message     = is_string( $message ) ? $message : '';
		$attachments = is_array( $attachments ) ? implode( ', ', $attachments ) : (string) $attachments;

		$content_type   = $this->detect_content_type( $headers );
		$headers_string = is_array( $headers ) ? implode( "\n", array_filter( $headers, 'is_string' ) ) : (string) $headers;

		if ( 'yes' === $settings['redact_sensitive'] ) {
			$message        = $this->redact_sensitive_content( $message );
			$headers_string = $this->redact_sensitive_content( $headers_string );
		}

		if ( 'yes' !== $settings['log_body'] ) {
			$message = '';
		}

		$data = array(
			'to_email'     => $to,
			'subject'      => $subject,
			'message'      => $message,
			'headers'      => $headers_string,
			'attachments'  => $attachments,
			'content_type' => $content_type,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$this->get_table_name(),
			$data,
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			/**
			 * Fires when a mail could not be written to the log table.
			 *
			 * @param string $error Last database error.
			 * @param array  $data  Row that failed to insert.
			 */
			do_action( 'samrat_emily_mail_tracker_log_failed', $wpdb->last_error, $data );
		}

		return $args;
	}

	/**
	 * Determine the content type of an outgoing mail.
	 *
	 * @param mixed $headers wp_mail() headers.
	 * @return string
	 */
	private function detect_content_type( $headers ) {
		$lines = array();

		if ( is_array( $headers ) ) {
			$lines = $headers;
		} elseif ( is_string( $headers ) && '' !== $headers ) {
			$lines = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		}

		foreach ( $lines as $line ) {
			if ( ! is_string( $line ) || false === stripos( $line, 'content-type' ) ) {
				continue;
			}

			$parts = explode( ':', $line, 2 );

			if ( 'content-type' !== strtolower( trim( $parts[0] ) ) || ! isset( $parts[1] ) ) {
				continue;
			}

			$value = explode( ';', $parts[1] );

			return substr( strtolower( trim( $value[0] ) ), 0, 100 );
		}

		/** This filter is documented in wp-includes/pluggable.php */
		return substr( strtolower( (string) apply_filters( 'wp_mail_content_type', 'text/plain' ) ), 0, 100 );
	}

	/**
	 * Mask credential bearing query arguments in stored content.
	 *
	 * Password reset links, WooCommerce order keys and similar one-click login
	 * URLs would otherwise sit in the log table in plain text, turning any read
	 * of that table into account takeover.
	 *
	 * @param string $content Content to redact.
	 * @return string
	 */
	public function redact_sensitive_content( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}

		/**
		 * Filter the query argument names whose values get masked.
		 *
		 * @param array $keys Query argument names.
		 */
		$keys = apply_filters(
			'samrat_emily_mail_tracker_sensitive_keys',
			array(
				'key',
				'token',
				'access_token',
				'refresh_token',
				'reset_key',
				'activation_key',
				'activate_key',
				'auth_key',
				'api_key',
				'secret',
				'password',
				'pwd',
				'pass',
				'otp',
				'code',
				'nonce',
				'_wpnonce',
				'order_key',
				'confirm_key',
			)
		);

		$keys = array_filter( array_map( 'strval', (array) $keys ) );

		if ( empty( $keys ) ) {
			return $content;
		}

		$pattern = '/([?&;](?:' . implode( '|', array_map( 'preg_quote', $keys, array_fill( 0, count( $keys ), '/' ) ) ) . ')=)[^\s&"\'<>\]]+/i';

		$redacted = preg_replace( $pattern, '${1}' . self::REDACTED, $content );

		// preg_replace returns null on failure, e.g. backtrack limit on a huge body.
		return ( null === $redacted ) ? $content : $redacted;
	}

	/* ---------------------------------------------------------------------
	 * Retention
	 * ------------------------------------------------------------------ */

	/**
	 * Make sure the daily cleanup event exists.
	 *
	 * @return void
	 */
	public function maybe_schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	/**
	 * Delete logs past the retention window.
	 *
	 * Runs on cron rather than inside wp_mail() so that a customer's checkout
	 * request never pays for the DELETE.
	 *
	 * @return void
	 */
	public function cleanup_logs() {
		$settings = $this->get_settings();
		$days     = absint( $settings['retention_days'] );

		if ( $days < 1 ) {
			return;
		}

		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ) );
	}

	/* ---------------------------------------------------------------------
	 * Privacy
	 * ------------------------------------------------------------------ */

	/**
	 * Register the personal data exporter.
	 *
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public function register_privacy_exporter( $exporters ) {
		$exporters['samrat-emily-mail-tracker'] = array(
			'exporter_friendly_name' => __( 'Mail Tracker Logs', 'samrat-emily-mail-tracker' ),
			'callback'               => array( $this, 'privacy_exporter' ),
		);

		return $exporters;
	}

	/**
	 * Register the personal data eraser.
	 *
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	public function register_privacy_eraser( $erasers ) {
		$erasers['samrat-emily-mail-tracker'] = array(
			'eraser_friendly_name' => __( 'Mail Tracker Logs', 'samrat-emily-mail-tracker' ),
			'callback'             => array( $this, 'privacy_eraser' ),
		);

		return $erasers;
	}

	/**
	 * Export logged mails addressed to a given user.
	 *
	 * @param string $email_address Address being exported.
	 * @param int    $page          1 based page number.
	 * @return array
	 */
	public function privacy_exporter( $email_address, $page = 1 ) {
		global $wpdb;

		$page       = max( 1, (int) $page );
		$limit      = 50;
		$offset     = ( $page - 1 ) * $limit;
		$table_name = $this->get_table_name();
		$like       = '%' . $wpdb->esc_like( $email_address ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE to_email LIKE %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$like,
				$limit,
				$offset
			)
		);

		$export_items = array();

		foreach ( (array) $rows as $row ) {
			$export_items[] = array(
				'group_id'    => 'samrat-emily-mail-tracker',
				'group_label' => __( 'Logged Emails', 'samrat-emily-mail-tracker' ),
				'item_id'     => 'samrat-emily-mail-tracker-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Date', 'samrat-emily-mail-tracker' ),
						'value' => $row->created_at,
					),
					array(
						'name'  => __( 'Recipient', 'samrat-emily-mail-tracker' ),
						'value' => $row->to_email,
					),
					array(
						'name'  => __( 'Subject', 'samrat-emily-mail-tracker' ),
						'value' => $row->subject,
					),
					array(
						'name'  => __( 'Message', 'samrat-emily-mail-tracker' ),
						'value' => $row->message,
					),
				),
			);
		}

		return array(
			'data' => $export_items,
			'done' => count( (array) $rows ) < $limit,
		);
	}

	/**
	 * Erase logged mails addressed to a given user.
	 *
	 * @param string $email_address Address being erased.
	 * @param int    $page          1 based page number.
	 * @return array
	 */
	public function privacy_eraser( $email_address, $page = 1 ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$like       = '%' . $wpdb->esc_like( $email_address ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE to_email LIKE %s LIMIT 500", $like ) );

		return array(
			'items_removed'  => ( $deleted > 0 ),
			'items_retained' => false,
			'messages'       => array(),
			'done'           => ( $deleted < 500 ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Admin assets and menu
	 * ------------------------------------------------------------------ */

	/**
	 * Enqueue admin assets on the plugin screens only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'samrat-emily-mail-tracker' ) ) {
			return;
		}

		wp_enqueue_style( 'samrat-emily-mail-tracker-admin', SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_ASSETS_URL . 'css/admin.css', array(), SAMRAT_EMILY_MAIL_TRACKER_VERSION );
		wp_enqueue_script( 'samrat-emily-mail-tracker-admin', SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_ASSETS_URL . 'js/admin.js', array( 'jquery' ), SAMRAT_EMILY_MAIL_TRACKER_VERSION, true );

		wp_localize_script(
			'samrat-emily-mail-tracker-admin',
			'samratEmilyMailTracker',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'samrat_emily_mail_tracker_nonce' ),
				'confirm_clear'  => __( 'Are you sure you want to clear all email logs? This action cannot be undone.', 'samrat-emily-mail-tracker' ),
				'confirm_delete' => __( 'Are you sure you want to delete this log?', 'samrat-emily-mail-tracker' ),
				'confirm_bulk'   => __( 'Are you sure you want to delete selected logs?', 'samrat-emily-mail-tracker' ),
				'select_one'     => __( 'Please select at least one log.', 'samrat-emily-mail-tracker' ),
				'generic_error'  => __( 'Something went wrong. Please reload the page and try again.', 'samrat-emily-mail-tracker' ),
				'clearing'       => __( 'Clearing...', 'samrat-emily-mail-tracker' ),
			)
		);
	}

	/**
	 * Add View Logs and Settings links on the plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$new_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=samrat-emily-mail-tracker' ) ) . '">' . esc_html__( 'View Logs', 'samrat-emily-mail-tracker' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=samrat-emily-mail-tracker-settings' ) ) . '">' . esc_html__( 'Settings', 'samrat-emily-mail-tracker' ) . '</a>',
		);

		return array_merge( $new_links, $links );
	}

	/**
	 * Register the admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Mails', 'samrat-emily-mail-tracker' ),
			__( 'Mails', 'samrat-emily-mail-tracker' ),
			'manage_options',
			'samrat-emily-mail-tracker',
			array( $this, 'render_admin_page' ),
			'dashicons-email-alt',
			26
		);

		add_submenu_page( 'samrat-emily-mail-tracker', __( 'View Logs', 'samrat-emily-mail-tracker' ), __( 'View Logs', 'samrat-emily-mail-tracker' ), 'manage_options', 'samrat-emily-mail-tracker', array( $this, 'render_admin_page' ) );
		add_submenu_page( 'samrat-emily-mail-tracker', __( 'Settings', 'samrat-emily-mail-tracker' ), __( 'Settings', 'samrat-emily-mail-tracker' ), 'manage_options', 'samrat-emily-mail-tracker-settings', array( $this, 'render_settings_page' ) );
	}

	/* ---------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------ */

	/**
	 * Shared guard for the AJAX endpoints.
	 *
	 * @return void
	 */
	private function verify_ajax_request() {
		check_ajax_referer( 'samrat_emily_mail_tracker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'samrat-emily-mail-tracker' ), 403 );
		}
	}

	/**
	 * Count all rows in the log table.
	 *
	 * @return int
	 */
	private function get_total_logs() {
		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
	}

	/**
	 * AJAX: delete a single log.
	 *
	 * @return void
	 */
	public function ajax_delete_log() {
		$this->verify_ajax_request();

		$log_id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

		if ( ! $log_id ) {
			wp_send_json_error( __( 'Invalid ID', 'samrat-emily-mail-tracker' ) );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( $this->get_table_name(), array( 'id' => $log_id ), array( '%d' ) );

		// delete() returns 0 when the row is already gone, which is not a failure.
		if ( false === $deleted ) {
			wp_send_json_error( __( 'Delete failed', 'samrat-emily-mail-tracker' ) );
		}

		wp_send_json_success( array( 'total' => $this->get_total_logs() ) );
	}

	/**
	 * AJAX: delete selected logs.
	 *
	 * @return void
	 */
	public function ajax_bulk_delete() {
		$this->verify_ajax_request();

		// array_map() on a scalar is a TypeError on PHP 8, so the shape is checked first.
		$raw_ids = isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ? wp_unslash( $_POST['ids'] ) : array();
		$ids     = array_values( array_unique( array_filter( array_map( 'absint', $raw_ids ) ) ) );

		if ( empty( $ids ) ) {
			wp_send_json_error( __( 'No IDs provided', 'samrat-emily-mail-tracker' ) );
		}

		global $wpdb;

		$table_name      = $this->get_table_name();
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id IN ($ids_placeholder)", $ids ) );

		if ( false === $deleted ) {
			wp_send_json_error( __( 'Bulk delete failed', 'samrat-emily-mail-tracker' ) );
		}

		wp_send_json_success( array( 'total' => $this->get_total_logs() ) );
	}

	/**
	 * AJAX: clear every log.
	 *
	 * @return void
	 */
	public function ajax_clear_all() {
		$this->verify_ajax_request();

		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( "TRUNCATE TABLE $table_name" );

		if ( false === $result ) {
			// TRUNCATE needs DROP privileges, which some hosts withhold.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->query( "DELETE FROM $table_name" );
		}

		if ( false === $result ) {
			wp_send_json_error( __( 'Clearing the logs failed.', 'samrat-emily-mail-tracker' ) );
		}

		wp_send_json_success( array( 'total' => 0 ) );
	}

	/* ---------------------------------------------------------------------
	 * Screens
	 * ------------------------------------------------------------------ */

	/**
	 * Render the settings screen.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$samrat_emily_mail_tracker_settings = $this->get_settings();
		$samrat_emily_mail_tracker_option   = $this->option_name;

		include SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	/**
	 * Render the log archive screen.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read only filters.
		$samrat_emily_mail_tracker_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$samrat_emily_mail_tracker_page   = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$samrat_emily_mail_tracker_limit  = self::PER_PAGE;
		$samrat_emily_mail_tracker_offset = ( $samrat_emily_mail_tracker_page - 1 ) * $samrat_emily_mail_tracker_limit;

		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $samrat_emily_mail_tracker_search ) {
			$like     = '%' . $wpdb->esc_like( $samrat_emily_mail_tracker_search ) . '%';
			$where[]  = '(to_email LIKE %s OR subject LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(id) FROM $table_name WHERE $where_sql";

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		// prepare() errors when handed no placeholders, so the unfiltered count runs raw.
		$samrat_emily_mail_tracker_total_items = empty( $params )
			? (int) $wpdb->get_var( $count_sql )
			: (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

		$samrat_emily_mail_tracker_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d",
				array_merge( $params, array( $samrat_emily_mail_tracker_limit, $samrat_emily_mail_tracker_offset ) )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$samrat_emily_mail_tracker_total_pages = (int) ceil( $samrat_emily_mail_tracker_total_items / $samrat_emily_mail_tracker_limit );

		include SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR . 'templates/admin-logs.php';
	}

	/**
	 * Decide how a stored body should be rendered.
	 *
	 * Rows written before the content_type column existed carry an empty value,
	 * so those fall back to sniffing the body for markup.
	 *
	 * @param object $row Log row.
	 * @return bool
	 */
	public static function is_html_body( $row ) {
		$content_type = isset( $row->content_type ) ? (string) $row->content_type : '';

		if ( '' !== $content_type ) {
			return ( false !== stripos( $content_type, 'html' ) );
		}

		return (bool) preg_match( '/<(?:html|body|div|p|br|table|a)\b/i', (string) $row->message );
	}
}
