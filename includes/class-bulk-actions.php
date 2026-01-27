<?php
/**
 * Bulk Actions class for Draft Drip Scheduler
 *
 * @package Draft_Drip_Scheduler
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DDS_Bulk_Actions class
 */
class DDS_Bulk_Actions {
	
	/**
	 * Instance of this class
	 *
	 * @var DDS_Bulk_Actions
	 */
	private static $instance = null;
	
	/**
	 * Scheduler instance
	 *
	 * @var DDS_Scheduler
	 */
	private $scheduler;
	
	/**
	 * Get instance of this class
	 *
	 * @return DDS_Bulk_Actions
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
		$this->scheduler = DDS_Scheduler::get_instance();
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Add bulk actions for all public post types
		add_action( 'admin_init', array( $this, 'register_bulk_actions' ) );
		
		// Handle bulk action execution - hook into admin_init to catch all post types
		add_action( 'admin_init', array( $this, 'process_bulk_action' ) );
		
		// Show admin notices
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		
		// Add scheduled date column to post lists
		add_action( 'admin_init', array( $this, 'register_post_list_columns' ) );
	}
	
	/**
	 * Register bulk actions for all public post types
	 */
	public function register_bulk_actions() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		
		foreach ( $post_types as $post_type ) {
			// Add filter for bulk actions dropdown
			add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'add_bulk_action' ) );
			
			// Add filter for handling bulk actions
			add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'handle_bulk_action' ), 10, 3 );
		}
	}
	
	/**
	 * Add bulk action to dropdown
	 *
	 * @param array $bulk_actions Existing bulk actions
	 * @return array Modified bulk actions
	 */
	public function add_bulk_action( $bulk_actions ) {
		$bulk_actions['dds_auto_schedule'] = __( 'Auto Schedule / Drip', 'draft-drip-scheduler' );
		return $bulk_actions;
	}
	
	/**
	 * Process bulk action if triggered (fallback method)
	 */
	public function process_bulk_action() {
		// Check if we're processing a bulk action
		if ( ! isset( $_REQUEST['action'] ) && ! isset( $_REQUEST['action2'] ) ) {
			return;
		}
		
		$action = isset( $_REQUEST['action'] ) && $_REQUEST['action'] !== '-1' 
			? $_REQUEST['action'] 
			: ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] !== '-1' ? $_REQUEST['action2'] : '' );
		
		if ( $action !== 'dds_auto_schedule' ) {
			return;
		}
		
		// Check nonce
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-posts' ) ) {
			return;
		}
		
		// Get post IDs
		if ( ! isset( $_REQUEST['post'] ) || ! is_array( $_REQUEST['post'] ) ) {
			return;
		}
		
		$post_ids = array_map( 'absint', $_REQUEST['post'] );
		
		// Get post type from screen
		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'edit' ) {
			return;
		}
		
		$post_type = $screen->post_type ? $screen->post_type : 'post';
		
		// Process the action
		$this->execute_bulk_action( $post_ids, $post_type );
	}
	
	/**
	 * Handle bulk action execution (WordPress filter hook)
	 *
	 * @param string $redirect_to Redirect URL
	 * @param string $action Action name
	 * @param array  $post_ids Selected post IDs
	 * @return string Modified redirect URL
	 */
	public function handle_bulk_action( $redirect_to, $action, $post_ids ) {
		// Check if this is our action
		if ( $action !== 'dds_auto_schedule' ) {
			return $redirect_to;
		}
		
		// Get post type from first post
		if ( empty( $post_ids ) ) {
			return $redirect_to;
		}
		
		$first_post = get_post( $post_ids[0] );
		if ( ! $first_post ) {
			return $redirect_to;
		}
		
		$post_type = $first_post->post_type;
		
		// Execute the bulk action
		$results = $this->execute_bulk_action( $post_ids, $post_type );
		
		// Add query args to redirect URL
		$redirect_to = add_query_arg(
			array(
				'dds_scheduled' => $results['scheduled_count'],
				'post_type'     => $post_type,
			),
			$redirect_to
		);
		
		return $redirect_to;
	}
	
	/**
	 * Execute bulk action scheduling
	 *
	 * @param array  $post_ids Array of post IDs to schedule
	 * @param string $post_type Post type
	 * @return array Results array with scheduled_count, failed_count, start_date, end_date
	 */
	private function execute_bulk_action( $post_ids, $post_type ) {
		// Check user permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'draft-drip-scheduler' ) );
		}
		
		// Get baseline date
		$baseline = $this->scheduler->get_baseline_date( $post_type );
		$start_date = $baseline;
		$end_date = null;
		$scheduled_count = 0;
		$failed_count = 0;
		
		// Loop through selected posts
		foreach ( $post_ids as $post_id ) {
			// Verify post exists and is a draft
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== $post_type ) {
				$failed_count++;
				continue;
			}
			
			// Only schedule drafts
			if ( $post->post_status !== 'draft' ) {
				$failed_count++;
				continue;
			}
			
			// Calculate next slot
			$next_slot = $this->scheduler->calculate_next_slot( $baseline );
			
			// Convert to GMT for WordPress
			$next_slot_gmt = $this->scheduler->local_to_gmt( $next_slot );
			
			// Validate and ensure date is in the future using settings
			$validated_dates = $this->scheduler->ensure_future_date( $next_slot, $next_slot_gmt );
			$next_slot = $validated_dates['local'];
			$next_slot_gmt = $validated_dates['gmt'];
			
			// Final validation: double-check that GMT date is in the future
			if ( ! $this->scheduler->is_future_date( $next_slot_gmt ) ) {
				// Force it to be at least 15 minutes in the future
				$current_gmt = time();
				$next_slot_gmt_timestamp = $current_gmt + 900; // 15 minutes
				$next_slot_gmt = gmdate( 'Y-m-d H:i:s', $next_slot_gmt_timestamp );
				$next_slot = get_date_from_gmt( $next_slot_gmt );
			}
			
			// Update post - WordPress will automatically schedule if post_date_gmt is in future
			$update_result = wp_update_post( array(
				'ID'            => $post_id,
				'post_status'   => 'future',
				'post_date'     => $next_slot,
				'post_date_gmt' => $next_slot_gmt,
			), true );
			
			if ( is_wp_error( $update_result ) ) {
				$failed_count++;
				continue;
			}
			
			$scheduled_count++;
			$end_date = $next_slot;
			
			// Update baseline for next iteration
			$baseline = $next_slot;
		}
		
		// Store results in transient for admin notice
		$results = array(
			'scheduled_count' => $scheduled_count,
			'failed_count'    => $failed_count,
			'start_date'      => $start_date,
			'end_date'        => $end_date,
		);
		
		set_transient( 'dds_bulk_action_results', $results, 30 );
		
		return $results;
	}
	
	/**
	 * Show admin notices after bulk action
	 */
	public function show_admin_notices() {
		// Check if we're on the edit screen
		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'edit' ) {
			return;
		}
		
		// Check if scheduled parameter exists
		if ( ! isset( $_GET['dds_scheduled'] ) ) {
			return;
		}
		
		// Get results from transient
		$results = get_transient( 'dds_bulk_action_results' );
		if ( ! $results ) {
			return;
		}
		
		// Delete transient
		delete_transient( 'dds_bulk_action_results' );
		
		$scheduled_count = absint( $results['scheduled_count'] );
		$failed_count = absint( $results['failed_count'] );
		$start_date = isset( $results['start_date'] ) ? $results['start_date'] : '';
		$end_date = isset( $results['end_date'] ) ? $results['end_date'] : '';
		
		// Format dates for display
		$start_formatted = '';
		$end_formatted = '';
		
		if ( $start_date ) {
			$start_timestamp = strtotime( $start_date );
			$start_formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start_timestamp );
		}
		
		if ( $end_date ) {
			$end_timestamp = strtotime( $end_date );
			$end_formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $end_timestamp );
		}
		
		// Show success notice
		if ( $scheduled_count > 0 ) {
			$message = sprintf(
				_n(
					'Successfully scheduled %d post',
					'Successfully scheduled %d posts',
					$scheduled_count,
					'draft-drip-scheduler'
				),
				$scheduled_count
			);
			
			if ( $start_formatted && $end_formatted ) {
				$message .= sprintf(
					' ' . __( 'from %s to %s.', 'draft-drip-scheduler' ),
					$start_formatted,
					$end_formatted
				);
			}
			
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
		
		// Show error notice if some failed
		if ( $failed_count > 0 ) {
			$error_message = sprintf(
				_n(
					'%d post could not be scheduled (may not be a draft or already scheduled).',
					'%d posts could not be scheduled (may not be drafts or already scheduled).',
					$failed_count,
					'draft-drip-scheduler'
				),
				$failed_count
			);
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php echo esc_html( $error_message ); ?></p>
			</div>
			<?php
		}
	}
	
	/**
	 * Register scheduled date column for all public post types
	 */
	public function register_post_list_columns() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		
		foreach ( $post_types as $post_type ) {
			// Add column header
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_scheduled_column' ) );
			
			// Add column content
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_scheduled_column' ), 10, 2 );
			
			// Make column sortable
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'make_scheduled_column_sortable' ) );
		}
		
		// Handle sorting
		add_action( 'pre_get_posts', array( $this, 'handle_scheduled_column_sorting' ) );
	}
	
	/**
	 * Add scheduled date column to post list
	 *
	 * @param array $columns Existing columns
	 * @return array Modified columns
	 */
	public function add_scheduled_column( $columns ) {
		// Insert after date column if it exists, otherwise add at the end
		$new_columns = array();
		$inserted = false;
		
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( $key === 'date' && ! $inserted ) {
				$new_columns['dds_scheduled'] = __( 'Scheduled Date', 'draft-drip-scheduler' );
				$inserted = true;
			}
		}
		
		// If date column doesn't exist, add at the end
		if ( ! $inserted ) {
			$new_columns['dds_scheduled'] = __( 'Scheduled Date', 'draft-drip-scheduler' );
		}
		
		return $new_columns;
	}
	
	/**
	 * Render scheduled date column content
	 *
	 * @param string $column_name Column name
	 * @param int    $post_id Post ID
	 */
	public function render_scheduled_column( $column_name, $post_id ) {
		if ( $column_name !== 'dds_scheduled' ) {
			return;
		}
		
		$post = get_post( $post_id );
		
		// Only show for scheduled posts
		if ( $post->post_status === 'future' ) {
			$scheduled_date = $post->post_date;
			$scheduled_timestamp = strtotime( $scheduled_date );
			
			$formatted_date = date_i18n( get_option( 'date_format' ), $scheduled_timestamp );
			$formatted_time = date_i18n( get_option( 'time_format' ), $scheduled_timestamp );
			
			echo '<strong>' . esc_html( $formatted_date ) . '</strong><br>';
			echo '<span style="color: #666;">' . esc_html( $formatted_time ) . '</span>';
			
			// Show relative time
			$time_diff = human_time_diff( $scheduled_timestamp, current_time( 'timestamp' ) );
			if ( $scheduled_timestamp > current_time( 'timestamp' ) ) {
				echo '<br><span style="color: #2271b1;">' . esc_html( sprintf( __( 'In %s', 'draft-drip-scheduler' ), $time_diff ) ) . '</span>';
			} else {
				echo '<br><span style="color: #d63638;">' . esc_html( sprintf( __( '%s ago', 'draft-drip-scheduler' ), $time_diff ) ) . '</span>';
			}
		} else {
			echo '<span style="color: #999;">—</span>';
		}
	}
	
	/**
	 * Make scheduled column sortable
	 *
	 * @param array $columns Sortable columns
	 * @return array Modified sortable columns
	 */
	public function make_scheduled_column_sortable( $columns ) {
		$columns['dds_scheduled'] = 'post_date';
		return $columns;
	}
	
	/**
	 * Handle sorting by scheduled date
	 *
	 * @param WP_Query $query WP_Query object
	 */
	public function handle_scheduled_column_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		
		$orderby = $query->get( 'orderby' );
		
		if ( $orderby === 'post_date' && isset( $_GET['post_type'] ) ) {
			$post_type = sanitize_text_field( $_GET['post_type'] );
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			
			if ( in_array( $post_type, $post_types, true ) ) {
				$query->set( 'orderby', 'post_date' );
			}
		}
	}
}
