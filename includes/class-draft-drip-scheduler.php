<?php
/**
 * Main plugin class for Draft Drip Scheduler.
 *
 * @package Draft_Drip_Scheduler
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Draft_Drip_Scheduler {

	/**
	 * Instance of this class.
	 *
	 * @var Draft_Drip_Scheduler
	 */
	private static $instance = null;

	/**
	 * Get instance of this class.
	 *
	 * @return Draft_Drip_Scheduler
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		require_once DDS_PLUGIN_DIR . 'includes/class-dds-settings.php';
		require_once DDS_PLUGIN_DIR . 'includes/class-dds-scheduler.php';
		require_once DDS_PLUGIN_DIR . 'includes/class-dds-bulk-actions.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Initialize classes.
		add_action( 'plugins_loaded', array( $this, 'init_classes' ) );

		// Load text domain for translations.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Purge debug post meta left behind by older versions (runs once).
		add_action( 'admin_init', array( $this, 'maybe_cleanup_legacy_debug_meta' ) );
	}

	/**
	 * Remove the write-only `_dds_debug_*` post meta that earlier versions wrote
	 * unconditionally on every scheduled post. Runs a single time per site.
	 *
	 * @return void
	 */
	public function maybe_cleanup_legacy_debug_meta() {
		if ( get_option( 'dds_debug_meta_purged' ) ) {
			return;
		}

		$debug_meta_keys = array(
			'_dds_debug_local_date',
			'_dds_debug_gmt_date',
			'_dds_debug_gmt_timestamp',
			'_dds_debug_current_gmt',
			'_dds_debug_result',
			'_dds_debug_final_status',
		);

		foreach ( $debug_meta_keys as $meta_key ) {
			delete_post_meta_by_key( $meta_key );
		}

		update_option( 'dds_debug_meta_purged', 1 );
	}

	/**
	 * Initialize plugin classes.
	 */
	public function init_classes() {
		// Initialize settings.
		DDS_Settings::get_instance();

		// Initialize scheduler.
		DDS_Scheduler::get_instance();

		// Initialize bulk actions.
		DDS_Bulk_Actions::get_instance();
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'draft-drip-scheduler',
			false,
			dirname( DDS_PLUGIN_BASENAME ) . '/languages'
		);
	}
}
