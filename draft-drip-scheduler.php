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
 *
 * @package Draft_Drip_Scheduler
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'DDS_VERSION', '1.1.2' );
define( 'DDS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DDS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'DDS_PLUGIN_FILE', __FILE__ );

require_once DDS_PLUGIN_DIR . 'includes/class-draft-drip-scheduler.php';

/**
 * Initialize the plugin.
 *
 * @return Draft_Drip_Scheduler
 */
function draft_drip_scheduler_init() {
	return Draft_Drip_Scheduler::get_instance();
}

// Start the plugin.
draft_drip_scheduler_init();
