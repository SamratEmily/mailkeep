<?php
/**
 * Main Samrat_Emily_Mail_Tracker class file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Samrat_Emily_Mail_Tracker {

	private $table_name;
	private $option_name = 'samrat_emily_mail_tracker_settings';

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'samrat_emily_mail_tracker_logs';
		
		add_filter( 'wp_mail', array( $this, 'log_email' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_FILE ), array( $this, 'add_plugin_action_links' ) );

		// AJAX Handlers
		add_action( 'wp_ajax_samrat_emily_mail_tracker_delete_log', array( $this, 'ajax_delete_log' ) );
		add_action( 'wp_ajax_samrat_emily_mail_tracker_bulk_delete', array( $this, 'ajax_bulk_delete' ) );
		add_action( 'wp_ajax_samrat_emily_mail_tracker_clear_all', array( $this, 'ajax_clear_all' ) );
	}

	/**
	 * AJAX Delete Single Log
	 */
	public function ajax_delete_log() {
		check_ajax_referer( 'samrat_emily_mail_tracker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$log_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $log_id ) {
			wp_send_json_error( 'Invalid ID' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( $this->table_name, array( 'id' => $log_id ) );

		if ( $deleted ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( 'Delete failed' );
		}
	}

	/**
	 * AJAX Bulk Delete Logs
	 */
	public function ajax_bulk_delete() {
		check_ajax_referer( 'samrat_emily_mail_tracker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$ids = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();
		if ( empty( $ids ) ) {
			wp_send_json_error( 'No IDs provided' );
		}

		global $wpdb;
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE id IN ($ids_placeholder)", $ids ) );

		if ( $deleted !== false ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( 'Bulk delete failed' );
		}
	}

	/**
	 * AJAX Clear All Logs
	 */
	public function ajax_clear_all() {
		check_ajax_referer( 'samrat_emily_mail_tracker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( "TRUNCATE TABLE $this->table_name" );

		if ( $deleted === false ) {
			// TRUNCATE might fail due to permissions, fallback to DELETE
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DELETE FROM $this->table_name" );
		}

		wp_send_json_success();
	}

	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $this->table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			to_email text NOT NULL,
			subject text NOT NULL,
			message longtext NOT NULL,
			headers text,
			attachments text,
			source varchar(50) DEFAULT 'WordPress',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		
		if ( ! get_option( $this->option_name ) ) {
			update_option( $this->option_name, array(
				'enable_logging' => 'yes',
				'retention_days' => 30,
			) );
		}
	}

	public function register_settings() {
		register_setting( 'samrat_emily_mail_tracker_options_group', $this->option_name, array(
			'sanitize_callback' => array( $this, 'validate_settings' ),
		) );
	}

	public function validate_settings( $input ) {
		$valid = array();
		
		$valid['enable_logging'] = ( isset( $input['enable_logging'] ) && $input['enable_logging'] === 'no' ) ? 'no' : 'yes';
		$valid['retention_days'] = isset( $input['retention_days'] ) ? absint( $input['retention_days'] ) : 30;

		return $valid;
	}

	public function get_settings() {
		return wp_parse_args( get_option( $this->option_name ), array(
			'enable_logging' => 'yes',
			'retention_days' => 30,
		) );
	}

	public function enqueue_assets( $hook ) {
		// Only enqueue on plugin pages
		if ( strpos( $hook, 'samrat-emily-mail-tracker' ) === false ) {
			return;
		}

		wp_enqueue_style( 'samrat-emily-mail-tracker-admin', SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_ASSETS_URL . 'css/admin.css', array(), SAMRAT_EMILY_MAIL_TRACKER_VERSION );
		wp_enqueue_script( 'samrat-emily-mail-tracker-admin', SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_ASSETS_URL . 'js/admin.js', array( 'jquery' ), SAMRAT_EMILY_MAIL_TRACKER_VERSION, true );

		wp_localize_script( 'samrat-emily-mail-tracker-admin', 'samratEmilyMailTracker', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'samrat_emily_mail_tracker_nonce' ),
			'confirm_clear' => __( 'Are you sure you want to clear all email logs? This action cannot be undone.', 'samrat-emily-mail-tracker' ),
			'confirm_delete' => __( 'Are you sure you want to delete this log?', 'samrat-emily-mail-tracker' ),
			'confirm_bulk' => __( 'Are you sure you want to delete selected logs?', 'samrat-emily-mail-tracker' ),
		) );
	}

	public function add_plugin_action_links( $links ) {
		$new_links = array(
			'<a href="' . admin_url( 'admin.php?page=samrat-emily-mail-tracker' ) . '">' . __( 'View Logs', 'samrat-emily-mail-tracker' ) . '</a>',
			'<a href="' . admin_url( 'admin.php?page=samrat-emily-mail-tracker-settings' ) . '">' . __( 'Settings', 'samrat-emily-mail-tracker' ) . '</a>',
		);
		return array_merge( $new_links, $links );
	}

	public function log_email( $args ) {
		$settings = $this->get_settings();
		if ( $settings['enable_logging'] !== 'yes' ) {
			return $args;
		}

		global $wpdb;

		$to          = is_array( $args['to'] ) ? implode( ', ', $args['to'] ) : $args['to'];
		$subject     = $args['subject'];
		$message     = $args['message'];
		$headers     = is_array( $args['headers'] ) ? implode( "\n", $args['headers'] ) : $args['headers'];
		$attachments = is_array( $args['attachments'] ) ? implode( ", ", $args['attachments'] ) : $args['attachments'];

		$source = 'WordPress';
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 );
		foreach ( $backtrace as $step ) {
			if ( isset( $step['class'] ) ) {
				if ( stripos( $step['class'], 'WC_' ) === 0 || stripos( $step['class'], 'WooCommerce' ) !== false ) {
					$source = 'WooCommerce';
					break;
				}
				if ( stripos( $step['class'], 'Dokan' ) !== false ) {
					$source = 'Dokan';
					break;
				}
			}
			if ( isset( $step['file'] ) ) {
				$file = str_replace( '\\', '/', $step['file'] );
				if ( stripos( $file, 'plugins/woocommerce' ) !== false ) {
					$source = 'WooCommerce';
					break;
				}
				if ( stripos( $file, 'plugins/dokan' ) !== false ) {
					$source = 'Dokan';
					break;
				}
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$this->table_name,
			array(
				'to_email'    => $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $headers,
				'attachments' => $attachments,
				'source'      => $source,
			)
		);

		$this->maybe_cleanup_logs();

		return $args;
	}

	private function maybe_cleanup_logs() {
		if ( false === get_transient( 'samrat_emily_mail_tracker_cleanup_lock' ) ) {
			$settings = $this->get_settings();
			$days = intval( $settings['retention_days'] );
			if ( $days > 0 ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ) );
			}
			set_transient( 'samrat_emily_mail_tracker_cleanup_lock', 'locked', DAY_IN_SECONDS );
		}
	}

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

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$samrat_emily_mail_tracker_settings = $this->get_settings();
		$samrat_emily_mail_tracker_option   = $this->option_name;
		
		include SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$samrat_emily_mail_tracker_page   = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$samrat_emily_mail_tracker_limit  = 20;
		$samrat_emily_mail_tracker_offset = ( $samrat_emily_mail_tracker_page - 1 ) * $samrat_emily_mail_tracker_limit;
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$samrat_emily_mail_tracker_results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->table_name ORDER BY created_at DESC LIMIT %d OFFSET %d", $samrat_emily_mail_tracker_limit, $samrat_emily_mail_tracker_offset ) );
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$samrat_emily_mail_tracker_total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $this->table_name" );
		$samrat_emily_mail_tracker_total_pages = ceil( $samrat_emily_mail_tracker_total_items / $samrat_emily_mail_tracker_limit );

		include SAMRAT_EMILY_MAIL_TRACKER_PLUGIN_DIR . 'templates/admin-logs.php';
	}
}
