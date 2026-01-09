<?php
/**
 * Scheduler class for Draft Drip Scheduler
 *
 * @package Draft_Drip_Scheduler
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DDS_Scheduler class
 */
class DDS_Scheduler {
	
	/**
	 * Instance of this class
	 *
	 * @var DDS_Scheduler
	 */
	private static $instance = null;
	
	/**
	 * Settings instance
	 *
	 * @var DDS_Settings
	 */
	private $settings;
	
	/**
	 * Get instance of this class
	 *
	 * @return DDS_Scheduler
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
		$this->settings = DDS_Settings::get_instance();
	}
	
	/**
	 * Get WordPress timezone as DateTimeZone object
	 *
	 * @return DateTimeZone WordPress timezone
	 */
	private function get_wp_timezone() {
		// Check for timezone override setting
		$timezone_override = $this->settings->get_setting( 'timezone_override', '' );
		if ( ! empty( $timezone_override ) ) {
			try {
				return new DateTimeZone( $timezone_override );
			} catch ( Exception $e ) {
				// Invalid override, fall through to WordPress timezone
			}
		}
		
		// Use WordPress function if available (WordPress 5.3+)
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}
		
		// Fallback for older WordPress versions
		$timezone_string = get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) ) {
			try {
				return new DateTimeZone( $timezone_string );
			} catch ( Exception $e ) {
				// Fall through to GMT offset
			}
		}
		
		// Use GMT offset
		$gmt_offset = get_option( 'gmt_offset' );
		$hours = (int) $gmt_offset;
		$minutes = abs( ( $gmt_offset - $hours ) * 60 );
		$sign = $gmt_offset >= 0 ? '+' : '-';
		$timezone_string = sprintf( '%s%02d:%02d', $sign, abs( $hours ), $minutes );
		
		try {
			return new DateTimeZone( $timezone_string );
		} catch ( Exception $e ) {
			// Ultimate fallback to UTC
			return new DateTimeZone( 'UTC' );
		}
	}
	
	/**
	 * Validate and ensure date is in the future
	 *
	 * @param string $date_string Date string to validate
	 * @param string $gmt_date_string GMT date string to validate
	 * @return array Array with validated 'local' and 'gmt' date strings
	 */
	public function ensure_future_date( $date_string, $gmt_date_string ) {
		$minimum_minutes = absint( $this->settings->get_setting( 'minimum_future_minutes', 60 ) );
		$current_gmt_time = time();
		$gmt_timestamp = strtotime( $gmt_date_string . ' GMT' );
		
		// Calculate minimum future timestamp
		$minimum_future_timestamp = $current_gmt_time + ( $minimum_minutes * 60 );
		
		// If the date is not far enough in the future, adjust it
		if ( $gmt_timestamp <= $minimum_future_timestamp ) {
			$gmt_timestamp = $minimum_future_timestamp;
			$gmt_date_string = gmdate( 'Y-m-d H:i:s', $gmt_timestamp );
			// Recalculate local time from GMT
			$date_string = get_date_from_gmt( $gmt_date_string );
		}
		
		return array(
			'local' => $date_string,
			'gmt'   => $gmt_date_string,
		);
	}
	
	/**
	 * Calculate the next available scheduling slot
	 *
	 * @param string $baseline_date Baseline datetime string (Y-m-d H:i:s format)
	 * @return string New datetime string (Y-m-d H:i:s format)
	 */
	public function calculate_next_slot( $baseline_date ) {
		// Get settings
		$interval_hours = absint( $this->settings->get_setting( 'interval_hours', 24 ) );
		$jitter_minutes = absint( $this->settings->get_setting( 'random_jitter', 0 ) );
		$skip_weekends = $this->settings->get_setting( 'skip_weekends', 0 );
		
		// Convert baseline to DateTime object
		// Use WordPress timezone
		$timezone = $this->get_wp_timezone();
		
		try {
			$baseline = new DateTime( $baseline_date, $timezone );
		} catch ( Exception $e ) {
			// Fallback to current time if invalid date
			$baseline = new DateTime( current_time( 'Y-m-d H:i:s' ), $timezone );
		}
		
		// Add interval hours
		$baseline->modify( "+{$interval_hours} hours" );
		
		// Add random jitter if enabled
		if ( $jitter_minutes > 0 ) {
			// Generate random jitter between -jitter_minutes and +jitter_minutes
			$jitter = wp_rand( -$jitter_minutes, $jitter_minutes );
			$baseline->modify( "{$jitter} minutes" );
		}
		
		// Skip weekends if enabled
		if ( $skip_weekends ) {
			$max_iterations = 14; // Prevent infinite loop (max 2 weeks)
			$iterations = 0;
			
			while ( $iterations < $max_iterations ) {
				$day_of_week = (int) $baseline->format( 'w' ); // 0 = Sunday, 6 = Saturday
				
				// If Saturday (6) or Sunday (0), push forward by 24 hours
				if ( $day_of_week === 0 || $day_of_week === 6 ) {
					$baseline->modify( '+24 hours' );
					$iterations++;
				} else {
					// Not a weekend, break out of loop
					break;
				}
			}
		}
		
		// Return formatted datetime string
		return $baseline->format( 'Y-m-d H:i:s' );
	}
	
	/**
	 * Get the baseline date for scheduling
	 * This finds the latest future post or uses tomorrow at default start time
	 *
	 * @param string $post_type Post type to check
	 * @return string Baseline datetime string (Y-m-d H:i:s format)
	 */
	public function get_baseline_date( $post_type = 'post' ) {
		global $wpdb;
		
		// Query for the latest future post of this post type
		$latest_future = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_date 
			FROM {$wpdb->posts} 
			WHERE post_type = %s 
			AND post_status = 'future' 
			ORDER BY post_date DESC 
			LIMIT 1",
			$post_type
		) );
		
		if ( $latest_future ) {
			// Use the latest future post's date as baseline
			return $latest_future;
		}
		
		// No future posts found, use tomorrow at default start time
		$default_start_time = $this->settings->get_setting( 'default_start_time', '08:00' );
		
		// Get WordPress timezone
		$timezone = $this->get_wp_timezone();
		
		try {
			$tomorrow = new DateTime( 'tomorrow', $timezone );
			
			// Set the time to default start time
			list( $hour, $minute ) = explode( ':', $default_start_time );
			$tomorrow->setTime( (int) $hour, (int) $minute, 0 );
			
			return $tomorrow->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			// Fallback to current time + 24 hours
			return date( 'Y-m-d H:i:s', strtotime( '+24 hours', current_time( 'timestamp' ) ) );
		}
	}
	
	/**
	 * Convert local datetime to GMT for WordPress
	 *
	 * @param string $local_date Local datetime string
	 * @return string GMT datetime string
	 */
	public function local_to_gmt( $local_date ) {
		// Use WordPress function if available (WordPress 5.3+)
		if ( function_exists( 'wp_timezone' ) ) {
			try {
				$timezone = wp_timezone();
				$gmt_timezone = new DateTimeZone( 'UTC' );
				
				$local_datetime = new DateTime( $local_date, $timezone );
				$local_datetime->setTimezone( $gmt_timezone );
				
				return $local_datetime->format( 'Y-m-d H:i:s' );
			} catch ( Exception $e ) {
				// Fallback to WordPress function
				return get_gmt_from_date( $local_date );
			}
		}
		
		// Fallback for older WordPress versions
		return get_gmt_from_date( $local_date );
	}
}
