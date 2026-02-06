<?php

if ( ! class_exists( 'ES_Settings_Controller' ) ) {

	/**
	 * Class to handle dashboard operation
	 * 
	 * @class ES_Settings_Controller
	 */
	class ES_Settings_Controller {

		// class instance
		public static $instance;

		/**
		 * API URL
		 *
		 * @var string
		 */
		public $api_url = 'https://api.icegram.com';

		// class constructor
		public function __construct() {
			$this->init();
		}

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function init() {
			$this->register_hooks();
		}

		public function register_hooks() {
		}

		/**
		 * Get the return path
		 *
		 * @return string
		 */
		public function get_return_path( $username_only = false ) {
			$mailbox_user = get_option( 'ig_es_bounce_mailbox_user', '' );
			if ( empty( $mailbox_user ) ) {
				$mailbox_user = self::get_bounce_mailbox_username();
				update_option( 'ig_es_bounce_mailbox_user', $mailbox_user );
			}
			if ( $username_only ) {
				$return_path = $mailbox_user;
			} else {
				$return_path = $mailbox_user . '@box.icegram.com';
			}

			return apply_filters( 'ig_es_bounce_handling_get_return_path', $return_path, $this );
		}

		/**
		 * Generate the unique mailbox username
		 *
		 * @return mixed|void
		 */
		public static function get_bounce_mailbox_username() {
			$site_url  = get_site_url();
			$site_url  = preg_replace( '(^https?://)', '', $site_url );
			$prefix    = ES_Common::generate_random_string( 4 );
			$suffix    = ES_Common::generate_random_string( 4 );
			$unique_id = uniqid();
			$username  = "bounce_{$site_url}_{$prefix}_{$unique_id}_$suffix";
			$username  = str_replace( array( ':', '/', '-', '\\', '.' ), '_', $username );

			return apply_filters( 'ig_es_get_bounce_mailbox_username', $username );
		}

		/**
		 * Get the WebHook URL
		 *
		 * @param $esp
		 *
		 * @return string
		 */
		public function get_webhook_url( $esp ) {
			$api_url = rtrim( $this->api_url, '/' );

			return "$api_url/email/bounce/$esp/{$this->get_return_path(true)}";
		}



		/**
		 * Display Mailjet webhook details
		 *
		 * @since 5.3.2
		 */
		public function display_mailjet_webhook_url() {
			$this->get_webhook_url( 'mailjet' );
		}

		/**
		 * Display Sendinblue webhook details
		 *
		 * @since 5.3.2
		 */
		public function display_sendinblue_webhook_url() {
			$this->get_webhook_url( 'sendinblue' );
		}

		/**
		 * Display Amazon SES webhook details
		 */
		public function display_amazon_ses_webhook_url() {
			$this->get_webhook_url( 'amazon_ses' );
		}

		/**
		 * Display PostMark webhook details
		 */
		public function display_postmark_webhook_url() {
			$this->get_webhook_url( 'postmark' );
		}

		/**
		 * Display SparkPost webhook details
		 */
		public function display_sparkpost_webhook_url() {
			$this->get_webhook_url( 'sparkpost' );
		}

		/**
		 * Display MailGun webhook details
		 */
		public function display_mailgun_webhook_url() {
			$this->get_webhook_url( 'mailgun' );
		}

		/**
		 * Display SendGrid webhook details
		 */
		public function display_sendgrid_webhook_url() {
			$this->get_webhook_url( 'sendgrid' );
		}

		/**
		 * Display PepiPost webhook details
		 */
		public function display_pepipost_webhook_url() {
			$this->get_webhook_url( 'pepipost' );
		}

		/**
		 * Get cron info data
		 *
		 * @return array
		 */
		public static function get_cron_info() {
			$site_crons = get_option( 'cron' );

			if ( empty( $site_crons ) ) {
				return array();
			}

			$es_crons_data  = array();
			$es_cron_events = array(
				'ig_es_cron',
				'ig_es_cron_worker',
				'ig_es_cron_auto_responder',
				'ig_es_summary_automation',
			);

			$cron_schedules = wp_get_schedules();
			$time_offset    = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			$date_format    = get_option( 'date_format' );
			$time_format    = get_option( 'time_format' );

			foreach ( $site_crons as $next_scheduled_time => $scheduled_crons ) {
				if ( ! empty( $scheduled_crons ) && is_array( $scheduled_crons ) ) {
					foreach ( $scheduled_crons as $cron_event => $cron_data ) {
						if ( ! in_array( $cron_event, $es_cron_events, true ) ) {
							continue;
						}
						foreach ( $cron_data as $cron_info ) {
							if ( ! empty( $cron_info['schedule'] ) ) {
								$cron_schedule                = $cron_info['schedule'];
								$cron_interval                = ! empty( $cron_schedules[ $cron_schedule ]['interval'] ) ? $cron_schedules[ $cron_schedule ]['interval'] : 0;
								$es_crons_data[ $cron_event ] = array(
									'cron_interval'       => $cron_interval,
									'next_scheduled_time' => $next_scheduled_time,
								);
							}
						}
					}
				}
			}

			$cron_info_array = array();
			if ( ! empty( $es_crons_data ) ) {
				foreach ( $es_cron_events as $cron_event ) {
					$cron_interval       = '';
					$next_scheduled_time = '';
					if ( ! empty( $es_crons_data[ $cron_event ] ) ) {
						$es_cron_data        = $es_crons_data[ $cron_event ];
						$cron_interval       = $es_cron_data['cron_interval'];
						$next_scheduled_time = $es_cron_data['next_scheduled_time'];
					} else {
						if ( 'ig_es_cron_auto_responder' === $cron_event ) {
							wp_schedule_event( floor( time() / 300 ) * 300 - 120, 'ig_es_cron_interval', 'ig_es_cron_auto_responder' );
						} elseif ( 'ig_es_cron_worker' === $cron_event ) {
							wp_schedule_event( floor( time() / 300 ) * 300, 'ig_es_cron_interval', 'ig_es_cron_worker' );
						} elseif ( 'ig_es_cron' === $cron_event ) {
							wp_schedule_event( strtotime( 'midnight' ) - 300, 'hourly', 'ig_es_cron' );
						}
						$next_scheduled_time = wp_next_scheduled( $cron_event );
						if ( 'ig_es_cron' === $cron_event ) {
							$cron_interval = 3600; // Hourly interval for ig_es_cron.
						} else {
							$cron_interval = ES()->cron->get_cron_interval();
						}
					}
					if ( empty( $cron_interval ) || empty( $next_scheduled_time ) ) {
						continue;
					}

					$cron_info_array[] = array(
						'event'                => $cron_event,
						'interval'             => ig_es_get_human_interval( $cron_interval ),
						'next_execution'       => sprintf( __( 'In %s', 'email-subscribers' ), human_time_diff( time(), $next_scheduled_time ) ),
						'next_execution_date'  => date_i18n( $date_format . ' ' . $time_format, $next_scheduled_time + $time_offset ),
						'next_execution_utc'   => date_i18n( $date_format . ' ' . $time_format, $next_scheduled_time ),
					);
				}
			}

			return $cron_info_array;
		}

		/**
		 * Check if Gmail client credentials are valid
		 *
		 * @return bool
		 * @since 5.8.0
		 */
		private static function get_gmail_valid_credentials() {
			if ( ! class_exists( 'ES_Gmail_Oauth_Handler' ) ) {
				return false;
			}
			$es_gmail_oauth_handler = ES_Gmail_Oauth_Handler::get_instance();
			return $es_gmail_oauth_handler->is_valid_client_credentials();
		}

		/**
		 * Check if Gmail OAuth tokens are valid
		 *
		 * @return bool
		 * @since 5.8.0
		 */
		private static function get_gmail_valid_tokens() {
			if ( ! class_exists( 'ES_Gmail_Oauth_Handler' ) ) {
				return false;
			}
			$es_gmail_oauth_handler = ES_Gmail_Oauth_Handler::get_instance();
			return $es_gmail_oauth_handler->is_valid_tokens();
		}

		public static function get_ess_plan_info() {
			$es_ess_data     = ES_Service_Email_Sending::get_ess_data();
			$current_month   = ig_es_get_current_month();
			$interval        = isset( $es_ess_data['interval'] ) ? $es_ess_data['interval']: '';
			$next_reset      = isset( $es_ess_data['next_reset'] ) ? $es_ess_data['next_reset']: '';
			$allocated_limit = isset( $es_ess_data['allocated_limit'] ) ? $es_ess_data['allocated_limit']: 0;
			$used_limit      = isset( $es_ess_data['used_limit'][$current_month] ) ? $es_ess_data['used_limit'][$current_month] : 0;
			$remaining_limit = $allocated_limit - $used_limit;
			if ( $allocated_limit > 0 ) {
				$remaining_limit_percentage = number_format_i18n( ( ( $remaining_limit * 100 ) / $allocated_limit ), 2 );
				$used_limit_percentage = number_format_i18n( ( ( $used_limit * 100 ) / $allocated_limit ), 2 );
			} else {
				$remaining_limit_percentage = 0;
				$used_limit_percentage = 0;
			}
			$remaining_percentage_limit = 10;   //Set email remaining percentage limit, so upsell notice box will visible.
			$plan                       = ES_Service_Email_Sending::get_plan();
			$premium_plans              = array( 'pro', 'max' );
			$is_premium_plan            = in_array( $plan, $premium_plans, true );
			$is_ess_branding_enabled    = ES_Service_Email_Sending::is_ess_branding_enabled();
			if ( ! empty( $next_reset ) ) {
				$next_reset = ig_es_format_date_time( $next_reset );
			}
			
			return array(
				'success' => true,
				'data' => array(
					'allocated_limit' => $allocated_limit,
					'used_limit' => $used_limit,
					'remaining_limit' => $remaining_limit,
					'used_limit_percentage' => floatval( $used_limit_percentage ),
					'remaining_limit_percentage' => floatval( $remaining_limit_percentage ),
					'next_reset' => $next_reset,
					'plan' => $plan,
					'is_premium_plan' => $is_premium_plan,
					'is_ess_branding_enabled' => $is_ess_branding_enabled,
				),
				'message' => __( 'ESS plan info retrieved successfully.', 'email-subscribers' ),
			);
		}

		public static function send_test_email_request( $data = array() ) {
			// Data comes as JSON string, decode it to array
			if ( is_string( $data ) && ! empty( $data ) ) {
				$decoded_data = json_decode( $data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_data ) ) {
					$data = $decoded_data;
				}
			}

			// Ensure data is an array
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$email = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
			
			if ( empty( $email ) || ! is_email( $email ) ) {
				return array(
					'success' => false,
					'message' => __( 'Please provide a valid email address.', 'email-subscribers' ),
				);
			}
			
			$template_id = 0;
			$campaign_id = 0;
		
			// Process template body
			$content = ES_Common::es_process_template_body( $content, $template_id, $campaign_id );			$merge_tags = array();
			
			// Send test email using the mailer (same method used by ES_Tools)
			$response = ES()->mailer->send_test_email( $email, $subject, $content, $merge_tags );
			
			if ( $response && 'SUCCESS' === $response['status'] ) {
				return array(
					'success' => true,
					'message' => __( 'Test email has been sent successfully. Please check your inbox.', 'email-subscribers' ),
				);
			} else {
				$error_message = is_array( $response['message'] ) ? implode( ' ', $response['message'] ) : $response['message'];
				return array(
					'success' => false,
					'message' => $error_message,
				);
			}
		}

		public static function verify_email_authentication( $data = array() ) {
			// First, send test email to verification mailbox
			$mailbox = ES_Common::get_email_verify_test_email();
			
			if ( empty( $mailbox ) ) {
				return array(
					'success' => false,
					'message' => __( 'Verification mailbox not configured.', 'email-subscribers' ),
				);
			}

			// Send test email
			$test_email = new ES_Send_Test_Email();
			$params     = array( 'email' => $mailbox );
			$send_response = $test_email->send_test_email( $params );

			if ( 'error' === $send_response['status'] ) {
				return array(
					'success' => false,
					'message' => isset( $send_response['error_message'] ) ? $send_response['error_message'] : __( 'Failed to send verification email.', 'email-subscribers' ),
				);
			}

			// Wait a moment for email to be received and processed
			sleep( 3 );

			// Get authentication headers
			$header_check = new ES_Service_Auth_Header_Check();
			$headers_response = $header_check->get_email_authentication_headers();

			if ( 'error' === $headers_response['status'] || empty( $headers_response['data'] ) ) {
				return array(
					'success' => false,
					'message' => isset( $headers_response['error_message'] ) ? $headers_response['error_message'] : __( 'Failed to retrieve authentication headers.', 'email-subscribers' ),
				);
			}

			// Parse and save the headers
			$email_auth_headers = json_decode( $headers_response['data'], true );
			update_option( 'ig_es_email_auth_headers', $email_auth_headers );

			// Format the response for frontend using the same method used for settings
			$formatted_headers = self::format_email_auth_headers( $email_auth_headers );

			return array(
				'success' => true,
				'message' => __( 'Email authentication verified successfully.', 'email-subscribers' ),
				'data' => $formatted_headers,
			);
		}	

		/**
		 * Format cron last hit timestamp for frontend consumption
		 * 
		 * @param array|int $cron_last_hit Cron last hit data (array with timestamp or Unix timestamp)
		 * @return string Formatted date time string
		 */
		public static function format_cron_last_hit( $cron_last_hit ) {
			if ( empty( $cron_last_hit ) ) {
				return 'Never';
			}

			$timestamp = '';
			if ( is_array( $cron_last_hit ) && isset( $cron_last_hit['timestamp'] ) ) {
				$timestamp = $cron_last_hit['timestamp'];
			} elseif ( is_numeric( $cron_last_hit ) ) {
				$timestamp = $cron_last_hit;
			}

			if ( empty( $timestamp ) ) {
				return 'Never';
			}

			// Convert Unix timestamp to MySQL datetime format
			$date_time = gmdate( 'Y-m-d H:i:s', $timestamp );
			return ig_es_format_date_time( $date_time );
		}

		/**
		 * Format email auth headers for frontend consumption
		 * 
		 * @param array $raw_headers Raw headers from database
		 * @return array Formatted headers
		 */
		public static function format_email_auth_headers( $raw_headers ) {
			$formatted_headers = array();
			
			if ( ! is_array( $raw_headers ) || empty( $raw_headers ) ) {
				return $formatted_headers;
			}

			foreach ( $raw_headers as $header ) {
				if ( ! is_array( $header ) ) {
					continue;
				}

				// Handle both old and new format
				$auth_header_name = '';
				$result = '';
				$value = '';

				// New format (from verify_email_authentication)
				if ( isset( $header['authHeaderName'] ) ) {
					$auth_header_name = $header['authHeaderName'];
					$result = isset( $header['result'] ) ? $header['result'] : 'N/A';
					$value = isset( $header['value'] ) ? $header['value'] : 'N/A';
				}
				// Old format (from stored option)
				elseif ( isset( $header['header'] ) ) {
					$auth_header_name = strtoupper( $header['header'] );
					$result = isset( $header['test'] ) ? $header['test'] : 'N/A';
					$value = isset( $header['record'] ) ? $header['record'] : 'N/A';
				}

				if ( ! empty( $auth_header_name ) ) {
					$formatted_headers[] = array(
						'authHeaderName' => $auth_header_name,
						'result' => $result,
						'value' => $value,
					);
				}
			}

			return $formatted_headers;
		}

		public static function es_settings_callback( $data = array() ) {
			// Data comes as JSON string, decode it to array
			if ( is_string( $data ) && ! empty( $data ) ) {
				$decoded_data = json_decode( $data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_data ) ) {
					$data = $decoded_data;
				}
			}

			// Ensure data is an array
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			// Call save_settings with the array data
			$result = self::save_settings( $data );

			return $result;
		}

		public static function get_pages( $data = array() ) {
			// Get all published pages
			$pages = get_pages(array(
				'sort_column' => 'menu_order, post_title',
				'post_status' => 'publish'
			));

			$pages_data = array();
			foreach ( $pages as $page ) {
				$pages_data[] = array(
					'id' => $page->ID,
					'title' => $page->post_title,
					'url' => get_permalink( $page->ID )
				);
			}

			return array(
				'success' => true,
				'data' => $pages_data,
				'message' => __( 'Pages loaded successfully.', 'email-subscribers' ),
			);
		}

		public static function get_users( $data = array() ) {
			// Get all Users
			$admin_users = get_users(array(
			'role'   => 'administrator',
			'fields' => array('ID', 'user_email', 'user_login'),
			));

			$users_data = array();
			foreach ($admin_users as $user) {
				$users_data[] = array(
					'id' => $user->ID,
					'email' => $user->user_email,
					'username' => $user->user_login,
				);
			}

			return array(
				'success' => true,
				'data' => $users_data,
				'message' => __( 'Users loaded successfully.', 'email-subscribers' ),
			);
		}

		public static function generate_rest_api_key_request( $data = array() ) {
			// Data comes as JSON string, decode it to array
			if ( is_string( $data ) && ! empty( $data ) ) {
				$decoded_data = json_decode( $data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_data ) ) {
					$data = $decoded_data;
				}
			}

			// Ensure data is an array
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$user_id = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : 0;			
			if ( empty( $user_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'Please select a user.', 'email-subscribers' ),
				);
			}

			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return array(
					'success' => false,
					'message' => __( 'Selected user doesn\'t exists. Please select a different user.', 'email-subscribers' ),
				);
			}

			$generated_api_key = ES_Rest_API_Admin::generate_rest_api_key( $user_id );
			if ( $generated_api_key ) {
				$rest_api_keys   = get_user_meta( $user_id, 'ig_es_rest_api_keys', true );
				$rest_api_keys   = ! empty( $rest_api_keys ) ? $rest_api_keys : array();
				$rest_api_keys[] = $generated_api_key;
				update_user_meta( $user_id, 'ig_es_rest_api_keys', $rest_api_keys );
				
				// Get updated list of API keys
				$api_keys_data = self::format_api_keys( $user_id );
				
				return array(
					'success' => true,
					'message' => sprintf( __( 'API key generated successfully: %s', 'email-subscribers' ), $generated_api_key ),
					'apiKey' => $generated_api_key,
					'apiKeys' => $api_keys_data,
				);
			}

			return array(
				'success' => false,
				'message' => __( 'Failed to generate API key.', 'email-subscribers' ),
			);
		}

		public static function get_rest_api_keys( $data = array() ) {
			// Data comes as JSON string, decode it to array
			if ( is_string( $data ) && ! empty( $data ) ) {
				$decoded_data = json_decode( $data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_data ) ) {
					$data = $decoded_data;
				}
			}

			// Ensure data is an array
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$user_id = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : 0;
			
			if ( empty( $user_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'User ID is required.', 'email-subscribers' ),
				);
			}

			$api_keys_data = self::format_api_keys( $user_id );

			return array(
				'success' => true,
				'data' => $api_keys_data,
				'message' => __( 'API keys loaded successfully.', 'email-subscribers' ),
			);
		}

		public static function delete_rest_api_key_request( $data = array() ) {
			// Data comes as JSON string, decode it to array
			if ( is_string( $data ) && ! empty( $data ) ) {
				$decoded_data = json_decode( $data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_data ) ) {
					$data = $decoded_data;
				}
			}

			// Ensure data is an array
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$user_id = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : 0;
			$api_index = isset( $data['api_index'] ) ? absint( $data['api_index'] ) : 0;			
			if ( empty( $user_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'User ID is required.', 'email-subscribers' ),
				);
			}

			$api_key_deleted = ES_Rest_API_Admin::delete_rest_api_key( $user_id, $api_index );
			if ( $api_key_deleted ) {
				return array(
					'success' => true,
					'message' => __( 'API key deleted successfully.', 'email-subscribers' ),
				);
			}

			return array(
				'success' => false,
				'message' => __( 'Failed to delete API key.', 'email-subscribers' ),
			);
		}

		private static function format_api_keys( $user_id ) {
			$rest_api_keys = get_user_meta( $user_id, 'ig_es_rest_api_keys', true );
			$rest_api_keys = ! empty( $rest_api_keys ) ? $rest_api_keys : array();
			
			$user = get_user_by( 'id', $user_id );
			$username = $user ? $user->user_login : '';
			
			$api_keys_data = array();
			foreach ( $rest_api_keys as $index => $api_key ) {
				// Handle both old format (string) and new format (array)
				if ( is_string( $api_key ) ) {
					$api_keys_data[] = array(
						'key' => $api_key,
						'username' => $username,
						'created_at' => __( 'Unknown', 'email-subscribers' ),
						'index' => $index,
					);
				} else if ( is_array( $api_key ) && isset( $api_key['key'] ) ) {
					$api_keys_data[] = array(
						'key' => $api_key['key'],
						'username' => $username,
						'created_at' => isset( $api_key['created_at'] ) ? $api_key['created_at'] : __( 'Unknown', 'email-subscribers' ),
						'index' => $index,
					);
				}
			}
			
			return $api_keys_data;
		}

	public static function get_settings( $data = array() ) {
		$mailer_settings = get_option( 'ig_es_mailer_settings', array() );
		$ess_email       = ! empty( $mailer_settings['icegram']['email'] ) ? $mailer_settings['icegram']['email'] : ES_Common::get_admin_email();

		// Generate webhook URLs
		$settings_controller = self::get_instance();
		$webhook_urls = array(
			'pepipost' => $settings_controller->get_webhook_url( 'pepipost' ),
			'mailjet' => $settings_controller->get_webhook_url( 'mailjet' ),
			'sendinblue' => $settings_controller->get_webhook_url( 'sendinblue' ),
			'amazon_ses' => $settings_controller->get_webhook_url( 'amazon_ses' ),
			'postmark' => $settings_controller->get_webhook_url( 'postmark' ),
			'sparkpost' => $settings_controller->get_webhook_url( 'sparkpost' ),
			'mailgun' => $settings_controller->get_webhook_url( 'mailgun' ),
			'sendgrid' => $settings_controller->get_webhook_url( 'sendgrid' ),
		);

		// Get all the settings from WordPress options
		$settings_data = array(
			'ig_es_from_name' => get_option( 'ig_es_from_name', '' ),
			'ig_es_from_email' => get_option( 'ig_es_from_email', '' ),
			'ig_es_admin_emails' => get_option( 'ig_es_admin_emails', '' ),
			'ig_es_post_image_size' => get_option( 'ig_es_post_image_size', 'Thumbnail' ),
			'ig_es_enable_summary_automation' => get_option( 'ig_es_enable_summary_automation', 'no' ),
			'ig_es_run_cron_on' => get_option( 'ig_es_run_cron_on', 'monday' ),
			'ig_es_run_cron_time' => get_option( 'ig_es_run_cron_time', '4pm' ),
			'ig_es_powered_by' => get_option( 'ig_es_powered_by', 'no' ),
			'ig_es_house_keeping_enabled' => get_option( 'ig_es_house_keeping_enabled', 'no' ),
			'ig_es_house_keeping_enabled_campaign_types' => get_option( 'ig_es_house_keeping_enabled_campaign_types', array() ),
			'ig_es_delete_unconfirmed_contacts' => get_option( 'ig_es_delete_unconfirmed_contacts', 'no' ),
			'ig_es_delete_plugin_data' => get_option( 'ig_es_delete_plugin_data', 'no' ),
			// Tracking Settings
			'ig_es_track_email_opens' => get_option( 'ig_es_track_email_opens', 'no' ),
			'ig_es_track_link_click' => get_option( 'ig_es_track_link_click', 'no' ),
			'ig_es_track_utm' => get_option( 'ig_es_track_utm', 'no' ),
			'ig_es_send_time_optimizer_enabled' => get_option( 'ig_es_send_time_optimizer_enabled', 'no' ),
			'ig_es_send_time_optimization_method' => get_option( 'ig_es_send_time_optimization_method', 'subscriber-timezone' ),
			// Subscription Form Settings
			'ig_es_enable_ajax_form_submission' => get_option( 'ig_es_enable_ajax_form_submission', 'no' ),
			'ig_es_intermediate_unsubscribe_page' => get_option( 'ig_es_intermediate_unsubscribe_page', 'no' ),
			'ig_es_show_opt_in_consent' => get_option( 'ig_es_show_opt_in_consent', 'no' ),
			'ig_es_opt_in_consent_text' => get_option( 'ig_es_opt_in_consent_text', '' ),
			// Opt-in Settings
			'ig_es_optin_type' => get_option( 'ig_es_optin_type', 'double_opt_in' ),
			'ig_es_optin_page' => get_option( 'ig_es_optin_page', 'default' ),
			'ig_es_subscription_success_message' => get_option( 'ig_es_subscription_success_message', '' ),
			'ig_es_subscription_error_messsage' => get_option( 'ig_es_subscription_error_messsage', '' ),
			'ig_es_form_submission_success_message' => get_option( 'ig_es_form_submission_success_message', '' ),
			// Unsubscribe Settings
			'ig_es_unsubscribe_page' => get_option( 'ig_es_unsubscribe_page', 'default' ),
			'ig_es_unsubscribe_success_message' => get_option( 'ig_es_unsubscribe_success_message', '' ),
			'ig_es_unsubscribe_error_message' => get_option( 'ig_es_unsubscribe_error_message', '' ),
			'ig_es_unsubscribe_link_content' => get_option( 'ig_es_unsubscribe_link_content', '' ),
			// Security Settings
			'ig_es_blocked_domains' => get_option( 'ig_es_blocked_domains', '' ),
			'ig_es_track_ip_address' => get_option( 'ig_es_track_ip_address', 'yes' ),
			'ig_es_enable_known_attackers_domains' => get_option( 'ig_es_enable_known_attackers_domains', 'yes' ),
			'ig_es_enable_disposable_domains' => get_option( 'ig_es_enable_disposable_domains', 'yes' ),
			'ig_es_enable_captcha' => get_option( 'ig_es_enable_captcha', 'no' ),
			// access control settings
			'ig_es_user_roles' => get_option( 'ig_es_user_roles', array() ),
			// API settings
			'ig_es_allow_api' => get_option( 'ig_es_allow_api', 'no' ),
			// cron settings
			'ig_es_disable_wp_cron' => get_option( 'ig_es_disable_wp_cron', 'no' ),
			'ig_es_cronurl' => get_option( 'ig_es_cronurl', '' ),
			'ig_es_cron_last_hit' => self::format_cron_last_hit( get_option( 'ig_es_cron_last_hit', array() ) ),
			// email sending settings
			'ig_es_cron_interval' => get_option( 'ig_es_cron_interval', ''),
			'ig_es_hourly_email_send_limit' => get_option( 'ig_es_hourly_email_send_limit', ''),
			'ig_es_max_email_send_at_once' => get_option( 'ig_es_max_email_send_at_once', ''),
			'ig_es_mailer_settings' => get_option( 'ig_es_mailer_settings', array() ),
			'ig_es_ess_email' => $ess_email,
			'ig_es_ess_branding_enabled' => get_option( 'ig_es_ess_branding_enabled', 'no' ),
			'ig_es_email_auth_headers' => self::format_email_auth_headers( get_option( 'ig_es_email_auth_headers', array() ) ),
			// Gmail OAuth validation
			'ig_es_gmail_valid_credentials' => self::get_gmail_valid_credentials(),
			'ig_es_gmail_valid_tokens' => self::get_gmail_valid_tokens(),
			// Webhook URLs for email services
			'ig_es_webhook_urls' => $webhook_urls,
			// Cron info
			'ig_es_cron_info' => self::get_cron_info(),
		);			
			// Get pages data as well
			$pages_result = self::get_pages();
			$pages_data = $pages_result['success'] ? $pages_result['data'] : array();

			$users_data = self::get_users();
			$users_data = $users_data['success'] ? $users_data['data'] : array();

			return array(
				'success' => true,
				'data' => $settings_data,
				'pages' => $pages_data,
				'users' => $users_data,
				'message' => __( 'Settings loaded successfully.', 'email-subscribers' ),
			);
		}

		public static function save_settings( $options = array() ) {
			$options = apply_filters( 'ig_es_before_save_settings', $options );
		
			$options = self::set_default_settings( $options );
			$options = self::set_trial_based_settings( $options );
			
			do_action( 'ig_es_before_settings_save', $options );
		
			self::sanitize_and_save_options( $options );
		
			do_action( 'ig_es_after_settings_save', $options );

			return array(
				'success' => true,
				'message' => __( 'Settings saved successfully.', 'email-subscribers' ),
			);
		}
		
		private static function set_default_settings( $options ) {
			$defaults = array(
				'ig_es_disable_wp_cron'             => 'no',
				'ig_es_ess_branding_enabled'        => 'no',
				'ig_es_track_email_opens'           => 'no',
				'ig_es_enable_ajax_form_submission' => 'no',
				'ig_es_enable_welcome_email'        => 'no',
				'ig_es_notify_admin'                => 'no',
				'ig_es_enable_cron_admin_email'     => 'no',
				'ig_es_delete_plugin_data'          => 'no',
			);			
			foreach ( $defaults as $key => $default ) {
				// Only set default if the option is not already set in the options array
				// AND the option doesn't exist in the database yet (to preserve existing values)
				if ( ! isset( $options[ $key ] ) && false === get_option( $key, false ) ) {
					$options[ $key ] = $default;
				}
			}
		
			return $options;
		}

		private static function set_trial_based_settings( $options ) {
			if ( ! ES()->is_premium() && ! ES()->trial->is_trial_valid() ) {
				$options['ig_es_allow_tracking'] = isset( $options['ig_es_allow_tracking'] ) ? $options['ig_es_allow_tracking'] : 'no';
			}
			return $options;
		}
		
		private static function sanitize_and_save_options( $options ) {
			$text_fields = array(
				'ig_es_from_name',
				'ig_es_admin_emails',
				'ig_es_email_type',
				'ig_es_post_image_size',
				'ig_es_track_email_opens',
				'ig_es_enable_ajax_form_submission',
				'ig_es_enable_welcome_email',
				'ig_es_welcome_email_subject',
				'ig_es_confirmation_mail_subject',
				'ig_es_notify_admin',
				'ig_es_admin_new_contact_email_subject',
				'ig_es_enable_cron_admin_email',
				'ig_es_cron_admin_email_subject',
				'ig_es_cronurl',
				'ig_es_hourly_email_send_limit',
				'ig_es_disable_wp_cron',
				'ig_es_allow_api',
			);
			$textarea_fields = array(
				'ig_es_unsubscribe_link_content',
				'ig_es_subscription_success_message',
				'ig_es_subscription_error_messsage',
				'ig_es_unsubscribe_success_message',
				'ig_es_unsubscribe_error_message',
				'ig_es_welcome_email_content',
				'ig_es_confirmation_mail_content',
				'ig_es_admin_new_contact_email_content',
				'ig_es_cron_admin_email',
				'ig_es_blocked_domains',
				'ig_es_form_submission_success_message',
			);
		
			$email_fields = array(
				'ig_es_from_email',
			);
		
			foreach ( $options as $key => $value ) {
				if ( strpos( $key, 'ig_es_' ) !== 0 ) {
					continue;
				}
		
				$value = stripslashes_deep( $value );
		
				if ( in_array( $key, $text_fields, true ) ) {
					$value = sanitize_text_field( $value );
				} elseif ( in_array( $key, $textarea_fields, true ) ) {
					$value = wp_kses_post( $value );
				} elseif ( in_array( $key, $email_fields, true ) ) {
					$value = sanitize_email( $value );
				}
		
				update_option( $key, wp_unslash( $value ), false );
			}
		}
		
		public static function get_registered_settings() {

			$from_email_description  = __( 'The "from" email address for all emails.', 'email-subscribers' );
	
			$from_email              = get_option( 'ig_es_from_email' );
			$from_email_description .= '<br/>' . self::get_from_email_notice( $from_email );
			$general_settings = array(
	
				'sender_information'                    => array(
					'id'         => 'sender_information',
					'name'       => __( 'Sender', 'email-subscribers' ),
					'sub_fields' => array(
						'from_name'  => array(
							'id'          => 'ig_es_from_name',
							'name'        => __( 'Name', 'email-subscribers' ),
							'desc'        => __( 'The "from" name people will see when they receive emails.', 'email-subscribers' ),
							'type'        => 'text',
							'placeholder' => __( 'Name', 'email-subscribers' ),
							'default'     => '',
						),
	
						'from_email' => array(
							'id'          => 'ig_es_from_email',
							'name'        => __( 'Email', 'email-subscribers' ),
							'desc'        => $from_email_description,
							'type'        => 'text',
							'placeholder' => __( 'Email Address', 'email-subscribers' ),
							'default'     => '',
						),
					),
				),
	
				'admin_email'                           => array(
					'id'      => 'ig_es_admin_emails',
					'name'    => __( 'Admin emails', 'email-subscribers' ),
					'info'    => __( 'Who should be notified about system events like "someone subscribed", "campaign sent" etc?', 'email-subscribers' ),
					'type'    => 'text',
					'desc'    => __( 'You can enter multiple email addresses - separate them with comma', 'email-subscribers' ),
					'default' => '',
				),
	
				'ig_es_optin_type'                      => array(
					'id'      => 'ig_es_optin_type',
					'name'    => __( 'Opt-in type', 'email-subscribers' ),
					'info'    => '',
					'desc'    => __( 'Single = confirm subscribers as they subscribe.<br> Double = send a confirmation email and require clicking on a link to confirm subscription.', 'email-subscribers' ),
					'type'    => 'select',
					'options' => ES_Common::get_optin_types(),
					'default' => '',
				),
	
				// Start-IG-Code.
				'ig_es_post_image_size'                 => array(
					'id'      => 'ig_es_post_image_size',
					'name'    => __( 'Image size', 'email-subscribers' ),
					'info'    => __( 'Image to use in Post Notification emails' ),
					'type'    => 'select',
					'options' => ES_Common::get_registered_image_sizes(),
					/* translators: %s: Keyword */
					'desc'    => sprintf( __( '%s keyword will use this image size. Use full size only if your template design needs it. Thumbnail should work well otherwise.', 'email-subscribers' ), '{{POSTIMAGE}}' ),
					'default' => 'full',
				),
				// End-IG-Code.
	
				'ig_es_enable_ajax_form_submission'     => array(
					'id'      => 'ig_es_enable_ajax_form_submission',
					'name'    => __( 'Enable AJAX subscription form submission', 'email-subscribers' ),
					'info'    => __( 'Enabling this will let users to submit their subscription form without page reload using AJAX call.', 'email-subscribers' ),
					'type'    => 'checkbox',
					'default' => 'yes',
				),
	
				'ig_es_track_email_opens'               => array(
					'id'      => 'ig_es_track_email_opens',
					'name'    => __( 'Track opens', 'email-subscribers' ),
					'info'    => __( 'Do you want to track when people view your emails? (We recommend keeping it enabled)', 'email-subscribers' ),
					'type'    => 'checkbox',
					'default' => 'yes',
				),
	
				'ig_es_form_submission_success_message' => array(
					'type'         => 'textarea',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_form_submission_success_message',
					'name'         => __( 'Subscription success message', 'email-subscribers' ),
					'info'         => __( 'This message will show when a visitor successfully subscribes using the form.', 'email-subscribers' ),
					'desc'         => '',
				),
	
				'ig_es_unsubscribe_link_content'        => array(
					'type'         => 'textarea',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_unsubscribe_link_content',
					'name'         => __( 'Unsubscribe text in email footer:', 'email-subscribers' ),
					'info'         => __( 'All emails will include this text in the footer so people can unsubscribe if they want.', 'email-subscribers' ),
					/* translators: %s: List of Keywords */
					'desc'         => sprintf( __( 'Use %s keyword to add unsubscribe link.', 'email-subscribers' ), '{{UNSUBSCRIBE-LINK}}' ),
				),
	
				'subscription_messages'                 => array(
					'id'         => 'subscription_messages',
					'name'       => __( 'Double opt-in subscription messages:', 'email-subscribers' ),
					'info'       => __( 'Page and messages to show when people click on the link in a subscription confirmation email.', 'email-subscribers' ),
					'sub_fields' => array(
						'ig_es_subscription_success_message' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'You have been subscribed successfully!', 'email-subscribers' ),
							'id'           => 'ig_es_subscription_success_message',
							'name'         => __( 'Message on successful subscription', 'email-subscribers' ),
							'desc'         => __( 'Show this message if contact is successfully subscribed from double opt-in (confirmation) email', 'email-subscribers' ),
						),
	
						'ig_es_subscription_error_messsage'  => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Oops.. Your request couldn\'t be completed. This email address seems to be already subscribed / blocked.', 'email-subscribers' ),
							'id'           => 'ig_es_subscription_error_messsage',
							'name'         => __( 'Message when subscription fails', 'email-subscribers' ),
							'desc'         => __( 'Show this message if any error occured after clicking confirmation link from double opt-in (confirmation) email.', 'email-subscribers' ),
						),
	
					),
				),
	
				'unsubscription_messages'               => array(
					'id'         => 'unsubscription_messages',
					'name'       => __( 'Unsubscribe messages', 'email-subscribers' ),
					'info'       => __( 'Page and messages to show when people click on the unsubscribe link in an email\'s footer.', 'email-subscribers' ),
					'sub_fields' => array(
	
						'ig_es_unsubscribe_success_message' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Thank You, You have been successfully unsubscribed. You will no longer hear from us.', 'email-subscribers' ),
							'id'           => 'ig_es_unsubscribe_success_message',
							'name'         => __( 'Message on unsubscribe success', 'email-subscribers' ),
							'desc'         => __( 'Once contact clicks on unsubscribe link, he/she will be redirected to a page where this message will be shown.', 'email-subscribers' ),
						),
	
						'ig_es_unsubscribe_error_message'   => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Oops.. There was some technical error. Please try again later or contact us.', 'email-subscribers' ),
							'id'           => 'ig_es_unsubscribe_error_message',
							'name'         => __( 'Message when unsubscribe fails', 'email-subscribers' ),
							'desc'         => __( 'Show this message if any error occured after clicking on unsubscribe link.', 'email-subscribers' ),
						),
					),
				),
	
				// Start-IG-Code.
				'ig_es_powered_by'                      => array(
					'id'      => 'ig_es_powered_by',
					'name'    => __( 'Share Icegram', 'email-subscribers' ),
					'info'    => __( 'Show "Powered By" link in the unsubscription form' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				// End-IG-Code.
	
				'ig_es_delete_plugin_data'              => array(
					'id'      => 'ig_es_delete_plugin_data',
					'name'    => __( 'Delete plugin data on uninstall', 'email-subscribers' ),
					'info'    => __( 'Be careful with this! When enabled, it will remove all lists, campaigns and other data if you uninstall the plugin.', 'email-subscribers' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				
			);
	
			$general_settings = apply_filters( 'ig_es_registered_general_settings', $general_settings );
	
			$signup_confirmation_settings = array(
	
				'worflow_migration_notice' => array(
					'id'   => 'worflow_migration_notice',
					'type' => 'html',
					'html' => self::get_workflow_migration_notice_html(),
				),
			);
	
			$signup_confirmation_settings = apply_filters( 'ig_es_registered_signup_confirmation_settings', $signup_confirmation_settings );
	
			if ( ES()->trial->is_trial_valid() || ES()->is_premium() ) {
				$gmt_offset  = ig_es_get_gmt_offset( true );
				$icegram_cron_last_hit_timestamp = get_option( 'ig_es_cron_last_hit' );
				$icegram_cron_last_hit_message = '';
				if ( !empty( $icegram_cron_last_hit_timestamp['icegram_timestamp'] ) ) {
					$icegram_timestamp_with_gmt_offset = $icegram_cron_last_hit_timestamp['icegram_timestamp'] + $gmt_offset;
					$icegram_cron_last_hit_date_and_time = ES_Common::convert_timestamp_to_date( $icegram_timestamp_with_gmt_offset );
					$icegram_cron_last_hit_message = __( '<br><span class="ml-6">Cron last hit time : <b>' . $icegram_cron_last_hit_date_and_time . '</b></span>', 'email-subscribers' );
				}
			}
	
			$cron_url_setting_desc = '';
	
			if ( ES()->trial->is_trial_valid() || ES()->is_premium() ) {
				$cron_url_setting_desc = __( '<span class="es-send-success es-icon"></span> We will take care of it. You don\'t need to visit this URL manually.' . $icegram_cron_last_hit_message, 'email-subscribers' );
			} else {
				/* translators: %s: Link to Icegram documentation */
				$cron_url_setting_desc = sprintf( __( "You need to visit this URL to send email notifications. Know <a href='%s' target='_blank'>how to run this in background</a>", 'email-subscribers' ), 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page' );
			}
	
			$cron_url_setting_desc .= '<div class="mt-2.5 ml-1"><a class="hover:underline text-sm font-medium text-indigo-600" href=" ' . esc_url( 'https://www.icegram.com/documentation/how-to-configure-email-sending-in-email-subscribers?utm_source=in_app&utm_medium=setup_email_sending&utm_campaign=es_doc' ) . '" target="_blank">' . esc_html__( 'How to configure Email Sending', 'email-subscribers' ) . '→</a></div>';
	
			$pepipost_api_key_defined = ES()->is_const_defined( 'pepipost', 'api_key' );
	
			$test_email = ES_Common::get_admin_email();
	
			$total_emails_sent = ES_Common::count_sent_emails();
			$account_url       = ES()->mailer->get_current_mailer_account_url();
	
			$email_sending_settings = array(
				'ig_es_cronurl'                 => array(
					'type'         => 'text',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'readonly'     => 'readonly',
					'id'           => 'ig_es_cronurl',
					'name'         => __( 'Cron URL', 'email-subscribers' ),
					'desc'         => $cron_url_setting_desc,
				),
				'ig_es_disable_wp_cron'         => array(
					'type'         => 'checkbox',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => 'no',
					'id'           => 'ig_es_disable_wp_cron',
					'name'         => __( 'Disable Wordpress Cron', 'email-subscribers' ),
					'info'         => __( 'Enable this option if you do not want Icegram Express to use WP Cron to send emails.', 'email-subscribers' ),
				),
				'ig_es_cron_interval'           => array(
					'id'      => 'ig_es_cron_interval',
					'name'    => __( 'Send emails at most every', 'email-subscribers' ),
					'type'    => 'select',
					'options' => ES()->cron->cron_intervals(),
					'desc'    => __( 'Optional if a real cron service is used', 'email-subscribers' ),
					'default' => IG_ES_CRON_INTERVAL,
				),
	
				'ig_es_hourly_email_send_limit' => array(
					'type'         => 'number',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => 50,
					'id'           => 'ig_es_hourly_email_send_limit',
					'name'         => __( 'Maximum emails to send in an hour', 'email-subscribers' ),
					/* translators: 1. Break tag 2. ESP Account url with anchor tag 3. ESP name 4. Closing anchor tag */
					'desc'         => __( 'Total emails sent in current hour: <b>' . $total_emails_sent . '</b>' , 'email-subscribers' ) . ( $account_url ? sprintf( __( '%1$sCheck sending limit from your %2$s%3$s\'s account%4$s.', 'email-subscribers' ), '<br/>', '<a href="' . esc_url( $account_url ) . '" target="_blank">', ES()->mailer->get_current_mailer_name(), '</a>' ) : '' ),
				),
	
				'ig_es_max_email_send_at_once'  => array(
					'type'         => 'number',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => IG_ES_MAX_EMAIL_SEND_AT_ONCE,
					'id'           => 'ig_es_max_email_send_at_once',
					'name'         => __( 'Maximum emails to send at once', 'email-subscribers' ),
					'desc'         => __( 'Maximum emails you want to send on every cron request.', 'email-subscribers' ),
				),
	
				'ig_es_test_send_email'         => array(
					'type'         => 'html',
					'html'         => self::get_test_send_email_html( $test_email ),
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_test_send_email',
					'name'         => __( 'Send test email', 'email-subscribers' ),
					'desc'         => __( 'Enter email address to send test email.', 'email-subscribers' ),
				),
	
				'ig_es_mailer_settings'         => array(
					'type'         => 'html',
					'sub_fields'   => array(
						'mailer'                  => array(
							'id'   => 'ig_es_mailer_settings[mailer]',
							'name' => __( 'Select Mailer', 'email-subscribers' ),
							'type' => 'html',
							'html' => self::mailers_html(),
							'desc' => '',
						),
						'ig_es_pepipost_api_key'  => array(
							'type'         => $pepipost_api_key_defined ? 'text' : 'password',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => '',
							'id'           => 'ig_es_mailer_settings[pepipost][api_key]',
							'name'         => __( 'Pepipost API key', 'email-subscribers' ),
							'desc'         => $pepipost_api_key_defined ? ES()->get_const_set_message( 'pepipost', 'api_key' ) : '',
							'class'        => 'pepipost',
							'disabled'     => $pepipost_api_key_defined ? 'disabled' : '',
							'value'        => $pepipost_api_key_defined ? '******************' : '',
						),
						'ig_es_pepipost_docblock' => array(
							'type' => 'html',
							'html' => self::pepipost_doc_block(),
							'id'   => 'ig_es_pepipost_docblock',
							'name' => '',
						),
					),
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_mailer_settings',
					'name'         => __( 'Email Sender', 'email-subscribers' ),
					'info'         => '',
				),
			);
	
			$email_sending_settings = apply_filters( 'ig_es_registered_email_sending_settings', $email_sending_settings );
	
			$security_settings = array(
				'blocked_domains' => array(
					'id'      => 'ig_es_blocked_domains',
					'name'    => __( 'Blocked domain(s)', 'email-subscribers' ),
					'type'    => 'textarea',
					'info'    => __( 'Seeing spam signups from particular domains? Enter domains names (one per line) that you want to block here.', 'email-subscribers' ),
					'default' => '',
					'rows'    => 3,
				),
			);
	
			$security_settings = apply_filters( 'ig_es_registered_security_settings', $security_settings );
	
			$es_settings = array(
				'general'             => $general_settings,
				'signup_confirmation' => $signup_confirmation_settings,
				'email_sending'       => $email_sending_settings,
				'security_settings'   => $security_settings,
			);
	
			if ( ES_Common::is_rest_api_supported() ) {
	
				$rest_api_endpoint = get_rest_url( null, 'email-subscribers/v1/subscribers' );
				$rest_api_settings = array(
					'allow_api' => array(
						'id'	=> 'ig_es_allow_api',
						'name'  => __( 'Enable API', 'email-subscribers' ),
						'info'    => __( 'Enable API to add/edit/delete subscribers through third-party sites or apps.', 'email-subscribers' ),
						'type'    => 'checkbox',
						'default' => 'no',
						/* translators: REST API endpoint */
						'desc' => sprintf( __( 'URL endpoint: %s', 'email-subscribers'), '<code class="es-code">' . $rest_api_endpoint . '</code>' )
					),
					'api_key_access_section' => array(
						'id'   => 'ig_es_api_keys_section',
						'name' => __( 'API Keys', 'email-subscribers' ),
						'type' => 'html',
						'html' => self::render_rest_api_keys_section(),
					),
				);
		
				$es_settings['rest_api_settings'] = $rest_api_settings;
			}
	
			return apply_filters( 'ig_es_registered_settings', $es_settings );
		}

		public static function get_from_email_notice( $from_email ) {
			$from_email_notice = '';
	
			$from_email              = get_option( 'ig_es_from_email' );
			$is_popular_domain	     = ES_Common::is_popular_domain( $from_email );
			$from_email_notice_class = $is_popular_domain ? '' : 'hidden';
			$from_email_notice      .= '<span id="ig-es-from-email-notice" class="text-red-600 ' . $from_email_notice_class . '">' . __( 'Your emails might land in spam if you use above email address..', 'email-subscribers' );
			$site_url				 = site_url();
			$site_domain             = ES_Common::get_domain_from_url( $site_url );
			/* translators: %s: Site domain */
			$from_email_notice      .= '<br/>' . sprintf( __( 'Consider using email address matching your site domain like %s', 'email-subscribers' ), 'info@' . $site_domain ) . '</span>';
			return $from_email_notice;
		}

		/**
		 * Get HTML for workflow migration
		 *
		 * @return string
		 */
		public static function get_workflow_migration_notice_html() {
			ob_start();
			$workflow_url = admin_url( 'admin.php?page=es_workflows' );
			?>
			<style>
				#tabs-signup_confirmation .es-settings-submit-btn {
					display: none;
				}
			</style>
			<p class="pb-2 text-sm font-normal text-gray-500">
				<?php echo esc_html__( 'Now you can control all your notifications through workflows.', 'email-subscribers' ); ?>
				<?php
					/* translators: 1. Anchor start tag 2. Anchor end tag */
					echo sprintf( esc_html__( 'Click %1$shere%2$s to go to workflows.', 'email-subscribers' ), '<a href="' . esc_url( $workflow_url ) . '" class="text-indigo-600" target="_blank">', '</a>' );
				?>
			</p>
			<?php
			$html = ob_get_clean();
			return $html;
		}

		public static function get_test_send_email_html( $test_email ) {

			/* translators: %s: Spinner image path */
			$html = sprintf( '<div class="send-email-div flex"><input id="es-test-email" type="email" value=%s class="form-input"/><button type="submit" name="submit" id="es-send-test" class="primary">Send Email</button><span class="es_spinner_image_admin" id="spinner-image" style="display:none"><img src="%s" alt="Loading..."/></span></div>', $test_email, ES_PLUGIN_URL . 'lite/public/images/spinner.gif' );
			return $html;
		}

		/**
		 * Prepare Mailers Setting
		 *
		 * @return string
		 *
		 * @modify 4.3.12
		 */
		public static function mailers_html() {
			$html                     = '';
			$es_email_type            = get_option( 'ig_es_email_type', '' );
			$selected_mailer_settings = get_option( 'ig_es_mailer_settings', array() );

			$selected_mailer = '';
			if ( ! empty( $selected_mailer_settings ) && ! empty( $selected_mailer_settings['mailer'] ) ) {
				$selected_mailer = $selected_mailer_settings['mailer'];
			} else {
				$php_email_type_values = array(
				'php_html_mail',
				'php_plaintext_mail',
				'phpmail',
				);

				if ( in_array( $es_email_type, $php_email_type_values, true ) ) {
					$selected_mailer = 'phpmail';
				}
			}

			$pepipost_doc_block = '';

			$mailers = array(
			'wpmail'   => array(
				'name' => 'WP Mail',
				'logo' => ES_PLUGIN_URL . 'lite/admin/images/wpmail.png',
			),
			'phpmail'  => array(
				'name' => 'PHP mail',
				'logo' => ES_PLUGIN_URL . 'lite/admin/images/phpmail.png',
			),
			'pepipost' => array(
				'name'     => 'Pepipost',
				'logo'     => ES_PLUGIN_URL . 'lite/admin/images/pepipost.png',
				'docblock' => $pepipost_doc_block,
			),
			);

			$mailers = apply_filters( 'ig_es_mailers', $mailers );

			$selected_mailer = ( array_key_exists( $selected_mailer, $mailers ) ) ? $selected_mailer : 'wpmail';

			foreach ( $mailers as $key => $mailer ) {
				$html .= '<label class="es-mailer-label inline-flex items-center cursor-pointer" data-mailer="' . esc_attr( $key ) . '">';
				$html .= '<input type="radio" class="absolute w-0 h-0 opacity-0 es_mailer" name="ig_es_mailer_settings[mailer]" value="' . $key . '" ' . checked( $selected_mailer, $key, false ) . '></input>';

				if ( ! empty( $mailer['url'] ) ) {
					$html .= '<a href="' . $mailer['url'] . '" target="_blank">';
				}

				$html .= '<div class="mt-3 mr-4 border border-gray-200 rounded-lg shadow-md es-mailer-logo">
			<div class="border-0 es-logo-wrapper">
			<img src="' . $mailer['logo'] . '" alt="Default (none)">
			</div><p class="mb-2 inline-block">'
				. $mailer['name'] . '</p>';

				if ( ! empty( $mailer['is_premium'] ) ) {
					$plan  = isset( $mailer['plan'] ) ? $mailer['plan'] : '';
					$html .= '<span class="premium-icon ' . $plan . '"></span>';
				} elseif ( ! empty( $mailer['is_recommended'] ) ) {
					$html .= '<span class="ig-es-recommended-icon text-indigo-600 uppercase">' . __( 'Recommended', 'email-subscribers' ) . '</span>';
				}

				$html .= '</div>';

				if ( ! empty( $mailer['is_premium'] ) ) {
					$html .= '</a>';
				}

				$html .= '</label>';
			}

			return $html;
		}
		public static function pepipost_doc_block() {
			$html = '';

			$url = ES_Common::get_utm_tracking_url(
			array(
				'url'        => 'https://www.icegram.com/email-subscribers-integrates-with-pepipost',
				'utm_medium' => 'pepipost_doc',
			)
			);

			ob_start();
			do_action('ig_es_before_get_pepipost_doc_block');
			?>
		<div class="es_sub_headline ig_es_docblock ig_es_pepipost_div_wrapper pepipost">
			<ul>
				<li><a class="" href="https://app.pepipost.com/index.php/signup/icegram?fpr=icegram" target="_blank"><?php esc_html_e( 'Signup for Pepipost', 'email-subscribers' ); ?></a></li>
				<li><?php esc_html_e( 'How to find', 'email-subscribers' ); ?> <a href="https://developers.pepipost.com/api/getstarted/overview?utm_source=icegram&utm_medium=es_inapp&utm_campaign=pepipost" target="_blank"> <?php esc_html_e( 'Pepipost API key', 'email-subscribers' ); ?></a></li>
				<li><a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php esc_html_e( 'Why to choose Pepipost', 'email-subscribers' ); ?></a></li>
			</ul>
		</div>

		<?php
			do_action('ig_es_after_get_pepipost_doc_block');
			$html = ob_get_clean();

			return $html;
		}

		public static function render_rest_api_keys_section() {
			ob_start();
			$rest_api_keys = get_option('ig_es_rest_api_keys', array());
	
		
			$admin_users = get_users(array(
			'role'   => 'administrator',
			'fields' => array('ID', 'user_email', 'user_login'),
			));
			?>
		<div id="ig-es-rest-api-section">
			<table class="min-w-full rounded-lg">
				<thead>
				<tr class="bg-blue-50 text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
					<th class="px-5 py-4"><?php echo esc_html__('Key', 'email-subscribers'); ?></th>
					<th class="px-2 py-4 text-center"><?php echo esc_html__('Username', 'email-subscribers'); ?></th>
					<th class="px-2 py-4 text-center"><?php echo esc_html__('Actions', 'email-subscribers'); ?></th>
				</tr>
				</thead>
				<tbody class="bg-blue-50">
				<?php
				if (!empty($admin_users)) {
					foreach ($admin_users as $user) {
						$rest_api_keys = get_user_meta($user->ID, 'ig_es_rest_api_keys', true);
						if (!empty($rest_api_keys)) {
							foreach ($rest_api_keys as $index => $rest_api_key) {
								$key_start = substr($rest_api_key, 0, 4);
								$key_end = substr($rest_api_key, -4);
								?>
								<tr class="ig-es-rest-api-row border-b border-gray-200 text-xs leading-4 font-medium"
									data-user-id="<?php echo esc_attr($user->ID); ?>"
									data-api-index="<?php echo esc_attr($index); ?>">
									<td class="px-5 py-4 text-center"><?php echo esc_html($key_start); ?>***********<?php echo esc_html($key_end); ?></td>
									<td class="px-2 py-4 text-center"><?php echo esc_html($user->user_login); ?></td>
									<td class="px-2 py-4 text-center">
										<a class="ig-es-delete-rest-api-key inline-block" href="#">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
												 stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
												<path stroke-linecap="round" stroke-linejoin="round"
													  d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
											</svg>
										</a>
									</td>
								</tr>
								<?php
							}
						}
					}
				}
				?>
				<tr id="ig-es-no-api-keys-message" class="border-b border-gray-200 text-xs leading-4 font-medium">
					<td colspan="3" class="px-5 py-4 text-center">
						<?php echo esc_html__('No API keys found.', 'email-subscribers'); ?>
					</td>
				</tr>
				</tbody>
			</table>
			<div id="ig-es-create-new-rest-api-container" class="mt-2">
				<select id="ig-es-rest-api-user-id">
					<option value=""><?php echo esc_html__('Select user', 'email-subscribers'); ?></option>
					<?php
					foreach ($admin_users as $user) {
						?>
						<option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->user_email); ?></option>
						<?php
					}
					?>
				</select>
				<button type="button" id="ig-es-generate-rest-api-key" class="ig-es-title-button ml-2 align-middle ig-es-inline-loader secondary">
					<span>
						<?php echo esc_html__('Generate API key', 'email-subscribers'); ?>
					</span>
					<svg class="es-btn-loader animate-spin h-4 w-4 text-indigo"
						 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
								stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor"
							  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
				</button>
				<div id="response-messages" class="p-2 mt-2 hidden">
					<div class="message"></div>
				</div>
			</div>
		</div>
			<?php
			$html = ob_get_clean();
			return $html;
		}

		public static function render_settings_fields( $fields ) {
			$html  = '<table>';
			$html .= '<tbody>';
			foreach ( $fields as $key => $field ) {
				if ( ! empty( $field['name'] ) ) {
					$html .= "<tr id='" . $field['id'] . "-field-row'><th scope='row'><span>";
					$html .= $field['name'];

					if ( ! empty( $field['is_premium'] ) ) {
						$premium_plan = isset( $field['plan'] ) ? $field['plan'] : '';
						$html .= '</span><a class="ml-1" href="' . $field['link'] . '" target="_blank"><span class="premium-icon ' . $premium_plan . '"></span></a>';
					}

					// If there is help text
					if ( ! empty( $field['info'] ) ) {
						$helper = $field['info'];
						$html  .= '<br />' . sprintf( '<p>%s</p>', $helper ); // Show it
					}
					$button_html = '<tr>';

					$html .= '</th>';
				}

				$html .= '<td>';

				if ( ! empty( $field['upgrade_desc'] ) ) {
					$html .= "<div class='flex settings_upsell_div'><div class='flex-none w-2/5 upsell_switcher'>";
				}

				if ( ! empty( $field['sub_fields'] ) ) {
					$option_key = '';
					foreach ( $field['sub_fields'] as $field_key => $sub_field ) {
						if ( strpos( $sub_field['id'], '[' ) ) {
							$parts = explode( '[', $sub_field['id'] );
							if ( $option_key !== $parts[0] ) {
								$option_value = get_option( $parts[0] );
								$option_key   = $parts[0];
							}
							$sub_field['option_value'] = is_array( $option_value ) ? $option_value : '';
						}
						$class = ( ! empty( $sub_field['class'] ) ) ? $sub_field['class'] : '';
						$html .= ( reset( $field['sub_fields'] ) !== $sub_field ) ? '<p class="pt-1></p>' : '';
						$html .= '<div class="es_sub_headline ' . $class . ' pt-4" ><strong>' . $sub_field['name'] . '</strong>';
						if ( ! empty( $sub_field['tooltip_text'] ) ) {
							$tooltip_html = ES_Common::get_tooltip_html( $sub_field['tooltip_text'] );
							$html        .= $tooltip_html;
						}
						$html .= '</div>';
						$html .= self::field_callback( $sub_field, $field_key );
					}
				} else {
					$html .= self::field_callback( $field );
				}

				if ( ! empty( $field['upgrade_desc'] ) ) {
					$upsell_info = array(
					'upgrade_title'  => $field['upgrade_title'],
					'pricing_url'    => $field['link'],
					'upsell_message' => $field['upgrade_desc'],
					'cta_html'       => false,
					);
					$html       .= '</div> <div class="w-3/5 upsell_box">';
					$html       .= ES_Common::upsell_description_message_box( $upsell_info, false );
					$html       .= '</div>';
				}

				$html .= '</td></tr>';
			}

			$button_html = empty( $button_html ) ? '<tr>' : $button_html;

			$nonce_field = wp_nonce_field( 'update-settings', 'update-settings', true, false );
			$html       .= $button_html . "<td class='es-settings-submit-btn'>";
			$html       .= '<input type="hidden" name="submitted" value="submitted" />';
			$html       .= '<input type="hidden" name="submit_action" value="ig-es-save-admin-settings" />';
			$html       .= $nonce_field;
			$html       .= '<button type="submit" name="submit" class="primary">' . __( 'Save Settings', 'email-subscribers' ) . '</button>';
			$html       .= '</td></tr>';
			$html       .= '</tbody>';
			$html       .= '</table>';

			$allowedtags = ig_es_allowed_html_tags_in_esc();
			add_filter( 'safe_style_css', 'ig_es_allowed_css_style' );
			echo wp_kses( $html, $allowedtags );
		}

		public static function field_callback( $arguments, $id_key = '' ) {
			$field_html = '';
			if ( 'ig_es_cronurl' === $arguments['id'] ) {
				$value = ES()->cron->url();
			} else {
				if ( ! empty( $arguments['option_value'] ) ) {
					preg_match( '(\[.*$)', $arguments['id'], $m );
					$n     = explode( '][', $m[0] );
					$n     = str_replace( '[', '', $n );
					$n     = str_replace( ']', '', $n );
					$count = count( $n );
					$id    = '';
					foreach ( $n as $key => $val ) {
						if ( '' == $id ) {
							$id = ! empty( $arguments['option_value'][ $val ] ) ? $arguments['option_value'][ $val ] : '';
						} else {
							$id = ! empty( $id[ $val ] ) ? $id[ $val ] : '';
						}
					}
					$value = $id;
				} else {
					$value = get_option( $arguments['id'] ); // Get the current value, if there is one
				}
			}

			if ( ! $value ) { // If no value exists
				$value = ! empty( $arguments['default'] ) ? $arguments['default'] : ''; // Set to our default
			}

			$uid         = ! empty( $arguments['id'] ) ? $arguments['id'] : '';
			$type        = ! empty( $arguments['type'] ) ? $arguments['type'] : '';
			$placeholder = ! empty( $arguments['placeholder'] ) ? $arguments['placeholder'] : '';
			$readonly    = ! empty( $arguments['readonly'] ) ? $arguments['readonly'] : '';
			$html        = ! empty( $arguments['html'] ) ? $arguments['html'] : '';
			$id_key      = ! empty( $id_key ) ? $id_key : $uid;
			$class       = ! empty( $arguments['class'] ) ? $arguments['class'] : '';
			$rows        = ! empty( $arguments['rows'] ) ? $arguments['rows'] : 8;
			$disabled    = ! empty( $arguments['disabled'] ) ? 'disabled="' . $arguments['disabled'] . '"' : '';
			$value       = ! empty( $arguments['value'] ) ? $arguments['value'] : $value;

			// Check which type of field we want
			switch ( $arguments['type'] ) {
				case 'text': // If it is a text field
					$field_html = sprintf( '<input name="%1$s" id="%2$s" placeholder="%4$s" value="%5$s" %6$s class="%7$s form-input h-9 mt-2 mb-1 text-sm border-gray-400 w-3/5" %8$s/>', $uid, $id_key, $type, $placeholder, $value, $readonly, $class, $disabled );
					break;
				case 'password': // If it is a text field
					$field_html = sprintf( '<input name="%1$s" id="%2$s" type="%3$s" placeholder="%4$s" value="%5$s" %6$s class="form-input h-9 mt-2 mb-1 text-sm border-gray-400 w-3/5 %7$s" %8$s/>', $uid, $id_key, $type, $placeholder, $value, $readonly, $class, $disabled );
					break;

				case 'number': // If it is a number field
					$field_html = sprintf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" %5$s min="0" class="w-2/5 mt-2 mb-1 text-sm border-gray-400 h-9 " %6$s/>', $uid, $type, $placeholder, $value, $readonly, $disabled );
					break;

				case 'email':
					$field_html = sprintf( '<input name="%1$s" id="%2$s" type="%3$s" placeholder="%4$s" value="%5$s" class="%6$s form-input w-2/3 mt-2 mb-1 h-9 text-sm border-gray-400 w-3/5" %7$s/>', $uid, $id_key, $type, $placeholder, $value, $class, $disabled );
					break;

				case 'textarea':
					$field_html = sprintf( '<textarea name="%1$s" id="%2$s" placeholder="%3$s" size="100" rows="%6$s" cols="58" class="%5$s form-textarea text-sm w-2/3 mt-3 mb-1 border-gray-400 w-3/5" %7$s>%4$s</textarea>', $uid, $id_key, $placeholder, $value, $class, $rows, $disabled );
					break;

				case 'file':
					$field_html = '<input type="text" id="logo_url" name="' . $uid . '" value="' . $value . '" class="w-2/3 w-3/5 mt-2 mb-1 text-sm border-gray-400 form-input h-9' . $class . '"/> <input id="upload_logo_button" type="button" class="button" value="Upload Logo" />';
					break;

				case 'checkbox':
					$field_html = '<label for="' . $id_key . '" class="inline-flex items-center mt-3 mb-1 cursor-pointer">
			<span class="relative">';

					if ( ! $disabled ) {
						$field_html .= '<input id="' . $id_key . '"  type="checkbox" name="' . $uid . '"  value="yes" ' . checked( $value, 'yes', false ) . ' class="sr-only peer absolute w-0 h-0 mt-6 opacity-0 es-check-toggle ' . $class . '" />';
					}

					$field_html .= $placeholder . '</input>
				<div class="w-11 h-6 bg-gray-200 rounded-full peer  dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
			</span>
			</label>';
					break;

				case 'select':
					if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
						$options_markup = '';
						foreach ( $arguments['options'] as $key => $label ) {
							$options_markup .= sprintf(
							'<option value="%s" %s>%s</option>',
							$key,
							selected( $value, $key, false ),
							$label
							);
						}
						$field_html = sprintf( '<select name="%1$s" id="%2$s" class="%4$s form-select rounded-lg w-48 h-9 mt-2 mb-1 border-gray-400" %5$s>%3$s</select>', $uid, $id_key, $options_markup, $class, $disabled );
					}
					break;

				case 'html':
				default:
					$field_html = $html;
					break;
			}

			// If there is help text
			if ( ! empty( $arguments['desc'] ) ) {
				$helper      = $arguments['desc'];
				$field_html .= sprintf( '<p class="field-desciption helper %s"> %s</p>', $class, $helper ); // Show it
			}

			return $field_html;
		}

	}

}

ES_Settings_Controller::get_instance();
