<?php
/**
 * Plugin Name: Draft Drip Scheduler
 * Plugin URI: http://frontrowsales.com
 * Description: Bulk schedule draft posts to publish sequentially in the future (drip feed) with weekend skipping and time jitter options.
 * Version: 1.1.2
 * Author: James Ross
 * Author URI: http://frontrowsales.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: draft-drip-scheduler
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'DDS_VERSION', '1.1.2' );
define( 'DDS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DDS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'DDS_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class
 */
class Draft_Drip_Scheduler {
	
	/**
	 * Instance of this class
	 *
	 * @var Draft_Drip_Scheduler
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
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
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}
	
	/**
	 * Load required files
	 */
	private function load_dependencies() {
		require_once DDS_PLUGIN_DIR . 'includes/class-settings.php';
		require_once DDS_PLUGIN_DIR . 'includes/class-scheduler.php';
		require_once DDS_PLUGIN_DIR . 'includes/class-bulk-actions.php';
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Initialize classes
		add_action( 'plugins_loaded', array( $this, 'init_classes' ) );
		
		// Load text domain for translations
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}
	
	/**
	 * Initialize plugin classes
	 */
	public function init_classes() {
		// Initialize settings
		DDS_Settings::get_instance();
		
		// Initialize scheduler
		DDS_Scheduler::get_instance();
		
		// Initialize bulk actions
		DDS_Bulk_Actions::get_instance();
	}
	
	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'draft-drip-scheduler',
			false,
			dirname( DDS_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

/**
 * Initialize the plugin
 */
function draft_drip_scheduler_init() {
	return Draft_Drip_Scheduler::get_instance();
}

// Start the plugin
draft_drip_scheduler_init();

