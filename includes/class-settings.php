<?php
/**
 * Settings class for Draft Drip Scheduler
 *
 * @package Draft_Drip_Scheduler
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DDS_Settings class
 */
class DDS_Settings {
	
	/**
	 * Instance of this class
	 *
	 * @var DDS_Settings
	 */
	private static $instance = null;
	
	/**
	 * Option name for settings
	 *
	 * @var string
	 */
	private $option_name = 'dds_settings';
	
	/**
	 * Get instance of this class
	 *
	 * @return DDS_Settings
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Scheduler instance
	 *
	 * @var DDS_Scheduler
	 */
	private $scheduler;
	
	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_schedule_now' ) );
		add_action( 'admin_notices', array( $this, 'show_schedule_now_notices' ) );
	}
	
	/**
	 * Get scheduler instance (lazy loading to avoid circular dependency)
	 *
	 * @return DDS_Scheduler
	 */
	private function get_scheduler() {
		if ( null === $this->scheduler ) {
			$this->scheduler = DDS_Scheduler::get_instance();
		}
		return $this->scheduler;
	}
	
	/**
	 * Add settings page to WordPress admin menu
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Drip Scheduler', 'draft-drip-scheduler' ),
			__( 'Drip Scheduler', 'draft-drip-scheduler' ),
			'manage_options',
			'draft-drip-scheduler',
			array( $this, 'render_settings_page' )
		);
	}
	
	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'dds_settings_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
		
		add_settings_section(
			'dds_main_section',
			__( 'Scheduling Options', 'draft-drip-scheduler' ),
			array( $this, 'render_section_description' ),
			'draft-drip-scheduler'
		);
		
		// Default Start Time field
		add_settings_field(
			'default_start_time',
			__( 'Default Start Time', 'draft-drip-scheduler' ),
			array( $this, 'render_default_start_time_field' ),
			'draft-drip-scheduler',
			'dds_main_section'
		);
		
		// Interval field
		add_settings_field(
			'interval_hours',
			__( 'Interval (Hours)', 'draft-drip-scheduler' ),
			array( $this, 'render_interval_field' ),
			'draft-drip-scheduler',
			'dds_main_section'
		);
		
		// Skip Weekends checkbox
		add_settings_field(
			'skip_weekends',
			__( 'Skip Weekends', 'draft-drip-scheduler' ),
			array( $this, 'render_skip_weekends_field' ),
			'draft-drip-scheduler',
			'dds_main_section'
		);
		
		// Random Jitter field
		add_settings_field(
			'random_jitter',
			__( 'Random Jitter (Minutes)', 'draft-drip-scheduler' ),
			array( $this, 'render_jitter_field' ),
			'draft-drip-scheduler',
			'dds_main_section'
		);
		
		// Minimum Future Minutes field
		add_settings_field(
			'minimum_future_minutes',
			__( 'Minimum Future Minutes', 'draft-drip-scheduler' ),
			array( $this, 'render_minimum_future_field' ),
			'draft-drip-scheduler',
			'dds_main_section'
		);
		
		// Timezone Override field
		add_settings_field(
			'timezone_override',
			__( 'Timezone Override', 'draft-drip-scheduler' ),
			array( $this, 'render_timezone_field' ),
			'draft-drip-scheduler',
			'dds_main_section'
		);
	}
	
	/**
	 * Sanitize settings input
	 *
	 * @param array $input Raw input data
	 * @return array Sanitized settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();
		
		// Sanitize default start time (HH:MM format)
		if ( isset( $input['default_start_time'] ) ) {
			$time = sanitize_text_field( $input['default_start_time'] );
			// Validate time format HH:MM
			if ( preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time ) ) {
				$sanitized['default_start_time'] = $time;
			} else {
				$sanitized['default_start_time'] = '08:00'; // Default fallback
			}
		}
		
		// Sanitize interval hours (must be positive number)
		if ( isset( $input['interval_hours'] ) ) {
			$interval = absint( $input['interval_hours'] );
			$sanitized['interval_hours'] = $interval > 0 ? $interval : 24; // Default to 24 if 0 or invalid
		}
		
		// Sanitize skip weekends (checkbox)
		$sanitized['skip_weekends'] = isset( $input['skip_weekends'] ) ? 1 : 0;
		
		// Sanitize random jitter (must be non-negative integer)
		if ( isset( $input['random_jitter'] ) ) {
			$jitter = absint( $input['random_jitter'] );
			$sanitized['random_jitter'] = $jitter;
		}
		
		// Sanitize minimum future minutes (must be positive integer, default 60)
		if ( isset( $input['minimum_future_minutes'] ) ) {
			$minutes = absint( $input['minimum_future_minutes'] );
			$sanitized['minimum_future_minutes'] = $minutes > 0 ? $minutes : 60;
		}
		
		// Sanitize timezone override
		if ( isset( $input['timezone_override'] ) ) {
			$timezone = sanitize_text_field( $input['timezone_override'] );
			// Validate timezone string
			if ( ! empty( $timezone ) ) {
				try {
					$test_timezone = new DateTimeZone( $timezone );
					$sanitized['timezone_override'] = $timezone;
				} catch ( Exception $e ) {
					// Invalid timezone, don't save
					$sanitized['timezone_override'] = '';
				}
			} else {
				$sanitized['timezone_override'] = '';
			}
		}
		
		return $sanitized;
	}
	
	/**
	 * Render section description
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure how draft posts should be scheduled when using the bulk action.', 'draft-drip-scheduler' ) . '</p>';
	}
	
	/**
	 * Render default start time field
	 */
	public function render_default_start_time_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['default_start_time'] ) ? $settings['default_start_time'] : '08:00';
		?>
		<input type="time" 
		       id="default_start_time" 
		       name="<?php echo esc_attr( $this->option_name ); ?>[default_start_time]" 
		       value="<?php echo esc_attr( $value ); ?>" 
		       class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Time of day for the first post if no future posts exist (24-hour format).', 'draft-drip-scheduler' ); ?>
		</p>
		<?php
	}
	
	/**
	 * Render interval field
	 */
	public function render_interval_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['interval_hours'] ) ? absint( $settings['interval_hours'] ) : 24;
		?>
		<input type="number" 
		       id="interval_hours" 
		       name="<?php echo esc_attr( $this->option_name ); ?>[interval_hours]" 
		       value="<?php echo esc_attr( $value ); ?>" 
		       min="1" 
		       step="1" 
		       class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Hours between each scheduled post.', 'draft-drip-scheduler' ); ?>
		</p>
		<?php
	}
	
	/**
	 * Render skip weekends field
	 */
	public function render_skip_weekends_field() {
		$settings = $this->get_settings();
		$checked = isset( $settings['skip_weekends'] ) && $settings['skip_weekends'] ? 'checked="checked"' : '';
		?>
		<label>
			<input type="checkbox" 
			       id="skip_weekends" 
			       name="<?php echo esc_attr( $this->option_name ); ?>[skip_weekends]" 
			       value="1" 
			       <?php echo $checked; ?> />
			<?php esc_html_e( 'Do not schedule posts on Saturdays or Sundays', 'draft-drip-scheduler' ); ?>
		</label>
		<?php
	}
	
	/**
	 * Render jitter field
	 */
	public function render_jitter_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['random_jitter'] ) ? absint( $settings['random_jitter'] ) : 0;
		?>
		<input type="number" 
		       id="random_jitter" 
		       name="<?php echo esc_attr( $this->option_name ); ?>[random_jitter]" 
		       value="<?php echo esc_attr( $value ); ?>" 
		       min="0" 
		       step="1" 
		       class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Randomly adjust schedule time by ±X minutes to make scheduling look more organic. Set to 0 to disable.', 'draft-drip-scheduler' ); ?>
		</p>
		<?php
	}
	
	/**
	 * Render minimum future minutes field
	 */
	public function render_minimum_future_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['minimum_future_minutes'] ) ? absint( $settings['minimum_future_minutes'] ) : 60;
		?>
		<input type="number" 
		       id="minimum_future_minutes" 
		       name="<?php echo esc_attr( $this->option_name ); ?>[minimum_future_minutes]" 
		       value="<?php echo esc_attr( $value ); ?>" 
		       min="1" 
		       step="1" 
		       class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Minimum minutes in the future that posts must be scheduled. Prevents immediate publishing due to timezone issues. Recommended: 60 minutes.', 'draft-drip-scheduler' ); ?>
		</p>
		<?php
	}
	
	/**
	 * Render timezone override field
	 */
	public function render_timezone_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['timezone_override'] ) ? $settings['timezone_override'] : '';
		$wp_timezone = get_option( 'timezone_string' );
		if ( empty( $wp_timezone ) ) {
			$gmt_offset = get_option( 'gmt_offset' );
			$wp_timezone = sprintf( 'UTC%+d', $gmt_offset );
		}
		?>
		<input type="text" 
		       id="timezone_override" 
		       name="<?php echo esc_attr( $this->option_name ); ?>[timezone_override]" 
		       value="<?php echo esc_attr( $value ); ?>" 
		       placeholder="<?php echo esc_attr( $wp_timezone ); ?>"
		       class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Override WordPress timezone for scheduling calculations. Leave empty to use WordPress timezone setting. Examples: "America/New_York", "Europe/London", "UTC+5".', 'draft-drip-scheduler' ); ?>
			<br>
			<strong><?php esc_html_e( 'Current WordPress Timezone:', 'draft-drip-scheduler' ); ?></strong> <?php echo esc_html( $wp_timezone ); ?>
		</p>
		<?php
	}
	
	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Check if settings were saved
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'dds_messages',
				'dds_message',
				__( 'Settings saved successfully.', 'draft-drip-scheduler' ),
				'success'
			);
		}
		
		settings_errors( 'dds_messages' );
		
		// Get timezone info for display
		$wp_timezone_string = get_option( 'timezone_string' );
		if ( empty( $wp_timezone_string ) ) {
			$gmt_offset = get_option( 'gmt_offset' );
			$wp_timezone_string = sprintf( 'UTC%+d', $gmt_offset );
		}
		$settings = $this->get_settings();
		$timezone_override = isset( $settings['timezone_override'] ) ? $settings['timezone_override'] : '';
		$active_timezone = ! empty( $timezone_override ) ? $timezone_override : $wp_timezone_string;
		$current_time = current_time( 'Y-m-d H:i:s' );
		$current_gmt_time = gmdate( 'Y-m-d H:i:s' );
		$minimum_minutes = absint( $settings['minimum_future_minutes'] );
		$next_safe_time = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( $minimum_minutes * 60 ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<!-- Timezone & Date Info Section -->
			<div class="card" style="max-width: 800px; margin-bottom: 20px; background: #f0f6fc; border-left: 4px solid #2271b1;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Timezone & Date Information', 'draft-drip-scheduler' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'WordPress Timezone', 'draft-drip-scheduler' ); ?></th>
							<td><code><?php echo esc_html( $wp_timezone_string ); ?></code></td>
						</tr>
						<?php if ( ! empty( $timezone_override ) ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Plugin Timezone Override', 'draft-drip-scheduler' ); ?></th>
							<td><code><?php echo esc_html( $timezone_override ); ?></code> <span style="color: #2271b1;">✓</span></td>
						</tr>
						<?php endif; ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active Timezone', 'draft-drip-scheduler' ); ?></th>
							<td><strong><code><?php echo esc_html( $active_timezone ); ?></code></strong></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Current Local Time', 'draft-drip-scheduler' ); ?></th>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $current_time ) ) ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Current GMT Time', 'draft-drip-scheduler' ); ?></th>
							<td><code><?php echo esc_html( $current_gmt_time ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Minimum Future Minutes', 'draft-drip-scheduler' ); ?></th>
							<td><?php echo esc_html( $minimum_minutes ); ?> <?php esc_html_e( 'minutes', 'draft-drip-scheduler' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Earliest Safe Schedule Time', 'draft-drip-scheduler' ); ?></th>
							<td><strong><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $next_safe_time ) ) ); ?></strong></td>
						</tr>
					</tbody>
				</table>
				<p class="description" style="margin-top: 15px;">
					<?php esc_html_e( 'If posts are publishing immediately, check that the "Earliest Safe Schedule Time" is in the future. Increase "Minimum Future Minutes" if needed.', 'draft-drip-scheduler' ); ?>
				</p>
			</div>
			
			<!-- Schedule Now Section -->
			<div class="card" style="max-width: 800px; margin-bottom: 20px;">
				<h2><?php esc_html_e( 'Schedule Drafts', 'draft-drip-scheduler' ); ?></h2>
				<p><?php esc_html_e( 'Select which post types to schedule. Only post types with draft posts are shown.', 'draft-drip-scheduler' ); ?></p>
				
				<?php
				// Get draft counts per post type
				$post_types = get_post_types( array( 'public' => true ), 'names' );
				$draft_counts = array();
				$total_drafts = 0;
				
				foreach ( $post_types as $post_type ) {
					$count = wp_count_posts( $post_type );
					$draft_count = isset( $count->draft ) ? (int) $count->draft : 0;
					if ( $draft_count > 0 ) {
						$post_type_obj = get_post_type_object( $post_type );
						$draft_counts[ $post_type ] = array(
							'count' => $draft_count,
							'label' => $post_type_obj->labels->name,
						);
						$total_drafts += $draft_count;
					}
				}
				?>
				
				<?php if ( $total_drafts > 0 ) : ?>
					<form method="post" action="" style="margin-top: 20px;">
						<?php wp_nonce_field( 'dds_schedule_now', 'dds_schedule_now_nonce' ); ?>
						<input type="hidden" name="dds_action" value="schedule_now" />
						
						<fieldset style="margin: 20px 0;">
							<legend style="font-weight: bold; margin-bottom: 10px;"><?php esc_html_e( 'Select Post Types to Schedule:', 'draft-drip-scheduler' ); ?></legend>
							<div style="margin-left: 20px;">
								<?php foreach ( $draft_counts as $post_type => $data ) : ?>
									<label style="display: block; margin-bottom: 10px; padding: 8px; background: #f9f9f9; border-left: 3px solid #2271b1;">
										<input type="checkbox" 
										       name="dds_selected_post_types[]" 
										       value="<?php echo esc_attr( $post_type ); ?>" 
										       checked="checked"
										       style="margin-right: 8px;" />
										<strong><?php echo esc_html( $data['label'] ); ?></strong>
										<span style="color: #666; margin-left: 10px;">
											<?php echo esc_html( sprintf( _n( '(%d draft)', '(%d drafts)', $data['count'], 'draft-drip-scheduler' ), $data['count'] ) ); ?>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
						
						<p style="margin-top: 20px;">
							<strong><?php echo esc_html( sprintf( _n( 'Total drafts available: %d', 'Total drafts available: %d', $total_drafts, 'draft-drip-scheduler' ), $total_drafts ) ); ?></strong>
						</p>
						
						<?php submit_button( __( 'Schedule Selected Post Types', 'draft-drip-scheduler' ), 'primary large', 'submit', false ); ?>
					</form>
				<?php else : ?>
					<p><?php esc_html_e( 'No draft posts found.', 'draft-drip-scheduler' ); ?></p>
				<?php endif; ?>
			</div>
			
			<!-- Settings Form -->
			<form action="options.php" method="post">
				<?php
				settings_fields( 'dds_settings_group' );
				do_settings_sections( 'draft-drip-scheduler' );
				submit_button( __( 'Save Settings', 'draft-drip-scheduler' ) );
				?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Handle Schedule Now action
	 */
	public function handle_schedule_now() {
		// Check if this is our action
		if ( ! isset( $_POST['dds_action'] ) || $_POST['dds_action'] !== 'schedule_now' ) {
			return;
		}
		
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'draft-drip-scheduler' ) );
		}
		
		// Verify nonce
		if ( ! isset( $_POST['dds_schedule_now_nonce'] ) || ! wp_verify_nonce( $_POST['dds_schedule_now_nonce'], 'dds_schedule_now' ) ) {
			wp_die( __( 'Security check failed.', 'draft-drip-scheduler' ) );
		}
		
		// Get selected post types
		$selected_post_types = isset( $_POST['dds_selected_post_types'] ) && is_array( $_POST['dds_selected_post_types'] ) 
			? array_map( 'sanitize_text_field', $_POST['dds_selected_post_types'] ) 
			: array();
		
		// Validate selected post types
		if ( empty( $selected_post_types ) ) {
			wp_die( __( 'Please select at least one post type to schedule.', 'draft-drip-scheduler' ) );
		}
		
		// Get all public post types for validation
		$all_public_post_types = get_post_types( array( 'public' => true ), 'names' );
		
		// Filter to only include valid public post types
		$post_types = array_intersect( $selected_post_types, $all_public_post_types );
		
		if ( empty( $post_types ) ) {
			wp_die( __( 'Invalid post types selected.', 'draft-drip-scheduler' ) );
		}
		
		$total_scheduled = 0;
		$total_failed = 0;
		$results_by_type = array();
		
		foreach ( $post_types as $post_type ) {
			// Get all draft posts for this post type
			$draft_posts = get_posts( array(
				'post_type'      => $post_type,
				'post_status'    => 'draft',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );
			
			if ( empty( $draft_posts ) ) {
				continue;
			}
			
			// Get baseline date for this post type
			$baseline = $this->get_scheduler()->get_baseline_date( $post_type );
			$start_date = $baseline;
			$end_date = null;
			$scheduled_count = 0;
			$failed_count = 0;
			
			// Schedule each draft
			foreach ( $draft_posts as $post_id ) {
				// Calculate next slot
				$next_slot = $this->get_scheduler()->calculate_next_slot( $baseline );
				
				// Convert to GMT for WordPress
				$next_slot_gmt = $this->get_scheduler()->local_to_gmt( $next_slot );
				
				// Validate and ensure date is in the future using settings
				$validated_dates = $this->get_scheduler()->ensure_future_date( $next_slot, $next_slot_gmt );
				$next_slot = $validated_dates['local'];
				$next_slot_gmt = $validated_dates['gmt'];
				
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
			
			$total_scheduled += $scheduled_count;
			$total_failed += $failed_count;
			
			if ( $scheduled_count > 0 ) {
				$post_type_obj = get_post_type_object( $post_type );
				$results_by_type[ $post_type ] = array(
					'label'         => $post_type_obj->labels->name,
					'scheduled'     => $scheduled_count,
					'failed'        => $failed_count,
					'start_date'    => $start_date,
					'end_date'      => $end_date,
				);
			}
		}
		
		// Store results in transient
		set_transient( 'dds_schedule_now_results', array(
			'total_scheduled' => $total_scheduled,
			'total_failed'    => $total_failed,
			'results_by_type' => $results_by_type,
		), 30 );
		
		// Redirect to prevent resubmission
		wp_safe_redirect( add_query_arg( 'dds_scheduled_now', '1', admin_url( 'options-general.php?page=draft-drip-scheduler' ) ) );
		exit;
	}
	
	/**
	 * Show admin notices for Schedule Now action
	 */
	public function show_schedule_now_notices() {
		// Only show on our settings page
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'settings_page_draft-drip-scheduler' ) {
			return;
		}
		
		// Check if scheduled parameter exists
		if ( ! isset( $_GET['dds_scheduled_now'] ) ) {
			return;
		}
		
		// Get results from transient
		$results = get_transient( 'dds_schedule_now_results' );
		if ( ! $results ) {
			return;
		}
		
		// Delete transient
		delete_transient( 'dds_schedule_now_results' );
		
		$total_scheduled = absint( $results['total_scheduled'] );
		$total_failed = absint( $results['total_failed'] );
		$results_by_type = isset( $results['results_by_type'] ) ? $results['results_by_type'] : array();
		
		// Show success notice
		if ( $total_scheduled > 0 ) {
			$message = sprintf(
				_n(
					'Successfully scheduled %d post.',
					'Successfully scheduled %d posts.',
					$total_scheduled,
					'draft-drip-scheduler'
				),
				$total_scheduled
			);
			
			if ( ! empty( $results_by_type ) ) {
				$message .= '<br><br><strong>' . __( 'Details:', 'draft-drip-scheduler' ) . '</strong><ul style="margin-left: 20px;">';
				foreach ( $results_by_type as $post_type => $data ) {
					$start_formatted = '';
					$end_formatted = '';
					
					if ( $data['start_date'] ) {
						$start_timestamp = strtotime( $data['start_date'] );
						$start_formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start_timestamp );
					}
					
					if ( $data['end_date'] ) {
						$end_timestamp = strtotime( $data['end_date'] );
						$end_formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $end_timestamp );
					}
					
					$message .= '<li>' . esc_html( $data['label'] ) . ': ' . esc_html( $data['scheduled'] );
					if ( $start_formatted && $end_formatted ) {
						$message .= ' (' . esc_html( $start_formatted ) . ' - ' . esc_html( $end_formatted ) . ')';
					}
					$message .= '</li>';
				}
				$message .= '</ul>';
			}
			
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
			<?php
		}
		
		// Show error notice if some failed
		if ( $total_failed > 0 ) {
			$error_message = sprintf(
				_n(
					'%d post could not be scheduled.',
					'%d posts could not be scheduled.',
					$total_failed,
					'draft-drip-scheduler'
				),
				$total_failed
			);
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php echo esc_html( $error_message ); ?></p>
			</div>
			<?php
		}
	}
	
	/**
	 * Get settings with defaults
	 *
	 * @return array Settings array
	 */
	public function get_settings() {
		$defaults = array(
			'default_start_time'      => '08:00',
			'interval_hours'          => 24,
			'skip_weekends'           => 0,
			'random_jitter'           => 0,
			'minimum_future_minutes'  => 60,
			'timezone_override'       => '',
		);
		
		$settings = get_option( $this->option_name, array() );
		return wp_parse_args( $settings, $defaults );
	}
	
	/**
	 * Get a specific setting value
	 *
	 * @param string $key Setting key
	 * @param mixed  $default Default value if not set
	 * @return mixed Setting value
	 */
	public function get_setting( $key, $default = null ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}
}
