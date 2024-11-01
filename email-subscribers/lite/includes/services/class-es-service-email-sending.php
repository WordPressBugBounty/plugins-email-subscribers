<?php

class ES_Service_Email_Sending extends ES_Services {

	/**
	 * Class instance.
	 *
	 * @var Onboarding instance
	 */
	protected static $instance = null;

	/**
	 * Added Logger Context
	 *
	 * @since 4.6.0
	 * @var array
	 */
	protected static $logger_context = array(
		'source' => 'ig_es_ess_onboarding',
	);

	/**
	 * API URL
	 *
	 * @since 4.6.0
	 * @var string
	 */
	public $api_url = 'https://api.igeml.com/';

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @since 4.6.1
	 */
	public $cmd = 'accounts/register';

	/**
	 * Variable to hold all onboarding tasks list.
	 * 
	 * UPDATE : Added ess cron scheduling in 5.6.11
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $all_onboarding_tasks = array(
		'configuration_tasks' => array(
			'create_ess_account',
			'set_sending_service_consent',
			'schedule_ess_cron',			
		),
		'email_delivery_check_tasks' => array(
			'dispatch_emails_from_server',
			'check_test_email_on_server',
		),
		'completion_tasks' => array(
			'complete_ess_onboarding',
		),
	);

	/**
	 * Option name for current task name.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_current_task_option = 'ig_es_ess_onboarding_current_task';

	/**
	 * Option name which holds common data between tasks.
	 *
	 * E.g. created subscription form id from create_default_subscription_form function so we can use it in add_widget_to_sidebar
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_tasks_data_option = 'ig_es_ess_onboarding_tasks_data';

	/**
	 * Option name which holds tasks which are done.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_tasks_done_option = 'ig_es_ess_onboarding_tasks_done';

	/**
	 * Option name which holds tasks which are failed.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_tasks_failed_option = 'ig_es_ess_onboarding_tasks_failed';

	/**
	 * Option name which holds tasks which are skipped due to dependency on other tasks.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	private static $onboarding_tasks_skipped_option = 'ig_es_ess_onboarding_tasks_skipped';

	/**
	 * Option name which store the step which has been completed.
	 *
	 * @since 4.6.0
	 * @var string
	 */
	private static $onboarding_step_option = 'ig_es_ess_onboarding_step';

	/**
	 * ES_Service_Email_Sending constructor.
	 *
	 * @since 4.6.1
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_ig_es_setup_email_sending_service', array( $this, 'setup_email_sending_service' ) );
		add_action( 'ig_es_ess_update_account', array( $this, 'send_plan_data_to_ess') );
		add_action( 'ig_es_message_sent', array( $this, 'update_sending_service_status' ) );
		// We are marking sending service status as failed only when we can't send a campaign after trying 3 times.
		// This will be helpful in avoiding temporary failure errors due to network calls/site load on ESS end.
		add_action( 'ig_es_campaign_failed', array( $this, 'update_sending_service_status' ) );
		add_action( 'admin_notices', array( $this, 'show_ess_promotion_notice' ) );

		add_action( 'admin_notices', array( $this, 'show_ess_fallback_removal_notice' ) );
		add_action( 'wp_ajax_ig_es_dismiss_ess_fallback_removal_notice', array( $this, 'dismiss_ess_fallback_removal_notice' ) );
		add_action( 'ig_es_before_settings_save', array( $this, 'maybe_update_ess_status' ) );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Register the JavaScript for ES gallery.
	 */
	public function enqueue_scripts() {

		$current_page = ig_es_get_request_data( 'page' );

		if ( in_array( $current_page, array( 'es_dashboard' ), true ) ) {
			wp_register_script( 'ig-es-sending-service-js', ES_PLUGIN_URL . 'lite/admin/js/sending-service.js', array( 'jquery' ), ES_PLUGIN_VERSION, true );
			wp_enqueue_script( 'ig-es-sending-service-js' );
			$onboarding_data                  = $this->get_onboarding_data();
			$onboarding_data['next_task']     = $this->get_next_onboarding_task();
			$onboarding_data['error_message'] = __( 'An error occured. Please try again later.', 'email-subscribers' );
			wp_localize_script( 'ig-es-sending-service-js', 'ig_es_ess_onboarding_data', $onboarding_data );
		}
	}

	/**
	 * Method to perform configuration and list, ES form, campaigns creation related operations in the onboarding
	 *
	 * @since 4.6.0
	 */
	public function ajax_perform_configuration_tasks() {

		$step = 2;
		$this->update_onboarding_step( $step );
		return $this->perform_onboarding_tasks( 'configuration_tasks' );
	}

	

	public function setup_email_sending_service() {
		$response = array(
			'status' => 'error',
		);

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$request = ig_es_get_request_data( 'request' );

		if ( ! empty( $request ) ) {
			$callback = 'ajax_' . $request;
			if ( is_callable( array( $this, $callback ) ) ) {
				$response = call_user_func( array( $this, $callback ) );
			}
		}

		wp_send_json( $response );
	}

	public function create_ess_account() {

		global $ig_es_tracker;

		$response = array(
			'status' => 'error',
		);

		if ( $ig_es_tracker::is_dev_environment() ) {
			$response['message'] = __( 'Email sending service is not supported on local or dev environments.', 'email-subscribers' );
			return $response;
		}

		$plan = $this->get_plan();
		$from_email = get_option( 'ig_es_from_email' );
		$home_url   = home_url();
		$parsed_url = parse_url( $home_url );
		$domain     = ! empty( $parsed_url['host'] ) ? $parsed_url['host'] : '';

		if ( empty( $domain ) ) {
			$response['message'] = __( 'Site url is not valid. Please check your site url.', 'email-subscribers' );
			return $response;
		}

		$email = ES_Common::get_admin_email();
		$limit = 3000;

		$from_name = ES()->mailer->get_from_name();

		$data = array(
			'limit'      => $limit,
			'domain'     => $domain,
			'email'      => $email,
			'from_email' => $from_email,
			'from_name'  => $from_name,
			'plan'		 => $plan,
		);

		$options = array(
			'timeout' => 50,
			'method'  => 'POST',
			'body'    => $data,
		);

		$request_response = $this->send_request( $options, 'POST', false );
		if ( ! is_wp_error( $request_response ) && ! empty( $request_response['account_id'] ) ) {
			$account_id      = $request_response['account_id'];
			$api_key         = $request_response['api_key'];
			$allocated_limit = $request_response['allocated_limit'];
			$internval       = $request_response['interval'];
			$from_email      = $request_response['from_email'];
			$plan			 = $request_response['plan'];

			$ess_data = array(
				'account_id'      => $account_id,
				'allocated_limit' => $allocated_limit,
				'interval'        => 'month',
				'api_key'         => $api_key,
				'from_email'      => $from_email,
				'plan'			  => $plan,
			);

			update_option( 'ig_es_ess_data', $ess_data );
			$response['status'] = 'success';
		} else {
			$response['message'] = ! empty( $request_response->get_error_message() ) ? $request_response->get_error_message() : __( 'An error has occured while creating your account. Please try again later', 'email-subscribers' );
		}

		return $response;
	}

	public function set_sending_service_consent() {

		$response = array(
			'status' => 'error',
		);

		update_option( 'ig_es_ess_opted_for_sending_service', 'yes', 'no' );
		update_option( 'ig_es_ess_status', 'success' );

		$response['status'] = 'success';
		
		return $response;
	}

	/**
	 * Method to perform give onboarding tasks types.
	 *
	 * @param string $task_group Tasks group
	 * @param string $task_name Specific task
	 *
	 * @since 4.6.0
	 */
	public function perform_onboarding_tasks( $task_group = '', $task_name = '' ) {

		$response = array(
			'status' => '',
			'tasks'  => array(),
		);

		$logger     = get_ig_logger();
		$task_group = ! empty( $task_group ) ? $task_group : 'configuration_tasks';

		$all_onboarding_tasks = self::$all_onboarding_tasks;

		$current_tasks = array();
		if ( ! empty( $all_onboarding_tasks[ $task_group ] ) ) {
			// Get specific task else all tasks in a group.
			if ( ! empty( $task_name ) ) {
				$task_index = array_search( $task_name, $all_onboarding_tasks[ $task_group ], true );
				if ( false !== $task_index ) {
					$current_tasks = array( $task_name );
				}
			} else {
				$current_tasks = $all_onboarding_tasks[ $task_group ];
			}
		}

		$onboarding_tasks_done = get_option( self::$onboarding_tasks_done_option, array() );
		$current_tasks_done    = ! empty( $onboarding_tasks_done[ $task_group ] ) ? $onboarding_tasks_done[ $task_group ] : array();

		$onboarding_tasks_failed = get_option( self::$onboarding_tasks_failed_option, array() );
		$current_tasks_failed    = ! empty( $onboarding_tasks_failed[ $task_group ] ) ? $onboarding_tasks_failed[ $task_group ] : array();

		$onboarding_tasks_skipped = get_option( self::$onboarding_tasks_skipped_option, array() );
		$current_tasks_skipped    = ! empty( $onboarding_tasks_skipped[ $task_group ] ) ? $onboarding_tasks_skipped[ $task_group ] : array();

		$onboarding_tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
		if ( ! empty( $current_tasks ) ) {
			foreach ( $current_tasks as $current_task ) {
				if ( ! in_array( $current_task, $current_tasks_done, true ) ) {

					if ( $this->is_required_tasks_completed( $current_task ) ) {
						if ( is_callable( array( $this, $current_task ) ) ) {
							$logger->info( 'Doing Task:' . $current_task, self::$logger_context );
	
							// Call callback function.
							$task_response = call_user_func( array( $this, $current_task ) );
							if ( 'success' === $task_response['status'] ) {
								if ( ! empty( $task_response['tasks_data'] ) ) {
									if ( ! isset( $onboarding_tasks_data[ $current_task ] ) ) {
										$onboarding_tasks_data[ $current_task ] = array();
									}
									$onboarding_tasks_data[ $current_task ] = array_merge( $onboarding_tasks_data[ $current_task ], $task_response['tasks_data'] );
								}
								$logger->info( 'Task Done:' . $current_task, self::$logger_context );
								// Set success status only if not already set else it can override error/skipped statuses set previously from other tasks.
								if ( empty( $response['status'] ) ) {
									$response['status'] = 'success';
								}
								$current_tasks_done[] = $current_task;
							} elseif ( 'skipped' === $task_response['status'] ) {
								$response['status']      = 'skipped';
								$current_tasks_skipped[] = $current_task;
							} else {
								$logger->info( 'Task Failed:' . $current_task, self::$logger_context );
								$response['status']     = 'error';
								$current_tasks_failed[] = $current_task;
							}
	
							$response['tasks'][ $current_task ] = $task_response;
	
							$onboarding_tasks_done[ $task_group ]    = $current_tasks_done;
							$onboarding_tasks_failed[ $task_group ]  = $current_tasks_failed;
							$onboarding_tasks_skipped[ $task_group ] = $current_tasks_skipped;
	
							update_option( self::$onboarding_tasks_done_option, $onboarding_tasks_done );
							update_option( self::$onboarding_tasks_failed_option, $onboarding_tasks_failed );
							update_option( self::$onboarding_tasks_skipped_option, $onboarding_tasks_skipped );
							update_option( self::$onboarding_tasks_data_option, $onboarding_tasks_data );
							update_option( self::$onboarding_current_task_option, $current_task );
						} else {
							$logger->info( 'Missing Task:' . $current_task, self::$logger_context );
						}
					} else {
						$response['status']      = 'skipped';
						$current_tasks_skipped[] = $current_task;
					}
				} else {
					$response['tasks'][ $current_task ] = array(
						'status' => 'success',
					);
					$logger->info( 'Task already done:' . $current_task, self::$logger_context );
				}
			}
		}

		return $response;
	}

	/**
	 * Method to get next task for onboarding.
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
	public function get_next_onboarding_task() {
		$all_onboarding_tasks = self::$all_onboarding_tasks;
		$current_task         = get_option( self::$onboarding_current_task_option, '' );

		// Variable to hold tasks list without any grouping.
		$onboarding_tasks = array();
		foreach ( $all_onboarding_tasks as $task_group => $grouped_tasks ) {
			foreach ( $grouped_tasks as $task ) {
				$onboarding_tasks[] = $task;
			}
		}

		$next_task = '';
		if ( ! empty( $current_task ) ) {
			$current_task_index = array_search( $current_task, $onboarding_tasks, true );
			if ( ! empty( $current_task_index ) ) {

				$next_task_index = $current_task_index + 1;
				$next_task       = ! empty( $onboarding_tasks[ $next_task_index ] ) ? $onboarding_tasks[ $next_task_index ] : '';

				// Check if previous required tasks are completed then only return next task else return blank task.
				if ( ! $this->is_required_tasks_completed( $next_task ) ) {
					$next_task = '';
				}
			}
		}

		return $next_task;
	}

	/**
	 * Method to get the onboarding data options used in onboarding process.
	 *
	 * @since 4.6.0
	 */
	public function get_onboarding_data_options() {

		$onboarding_options = array(
			self::$onboarding_tasks_done_option,
			self::$onboarding_tasks_failed_option,
			self::$onboarding_tasks_data_option,
			self::$onboarding_tasks_skipped_option,
			self::$onboarding_step_option,
			self::$onboarding_current_task_option,
		);

		return $onboarding_options;
	}

	/**
	 * Method to get saved onboarding data.
	 *
	 * @since 4.6.0
	 */
	public function get_onboarding_data() {

		$onboarding_data = array();

		$onboarding_options = $this->get_onboarding_data_options();

		foreach ( $onboarding_options as $option ) {
			$option_data                = get_option( $option );
			$onboarding_data[ $option ] = $option_data;
		}

		return $onboarding_data;
	}

	/**
	 * Method to get the current onboarding step
	 *
	 * @return int $onboarding_step Current onboarding step.
	 *
	 * @since 4.6.0
	 */
	public static function get_onboarding_step() {
		$onboarding_step = (int) get_option( self::$onboarding_step_option, 1 );
		return $onboarding_step;
	}

	/**
	 * Method to updatee the onboarding step
	 *
	 * @return bool
	 *
	 * @since 4.6.0
	 */
	public static function update_onboarding_step( $step = 1 ) {
		if ( ! empty( $step ) ) {
			update_option( self::$onboarding_step_option, $step );
			return true;
		}

		return false;
	}

	/**
	 * Method to check if onboarding is completed
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
	public static function ajax_complete_ess_onboarding() {
		$response       = array();
		$option_updated = update_option( 'ig_es_ess_onboarding_complete', 'yes', false );
		if ( $option_updated ) {
			$response['html']   = self::get_account_overview_html();
			$response['status'] = 'success';
		}
		return $response;
	}

	public static function get_account_overview_html() {
		$current_month       = ig_es_get_current_month();
		$service_status      = self::get_sending_service_status();
		$ess_data            = get_option( 'ig_es_ess_data', array() );
		$used_limit          = isset( $ess_data['used_limit'][$current_month] ) ? $ess_data['used_limit'][$current_month]: 0;
		$allocated_limit     = isset( $ess_data['allocated_limit'] ) ? $ess_data['allocated_limit']                    : 0;
		$interval            = isset( $ess_data['interval'] ) ? $ess_data['interval']                                  : '';
		$current_mailer_name = ES()->mailer->get_current_mailer_name();
		$settings_url        = admin_url( 'admin.php?page=es_settings' );

		ob_start();
		ES_Admin::get_view(
			'dashboard/ess-account-overview',
			array(
				'service_status'      => $service_status,
				'allocated_limit'     => $allocated_limit,
				'used_limit'          => $used_limit,
				'interval'            => $interval,
				'current_mailer_name' => $current_mailer_name,
				'settings_url'        => $settings_url,
			)
		);
		$account_overview_html = ob_get_clean();
		return $account_overview_html;
	}

	/**
	 * Method to check if onboarding is completed
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
	public static function is_onboarding_completed() {

		$onboarding_complete = get_option( 'ig_es_ess_onboarding_complete', 'no' );

		if ( 'yes' === $onboarding_complete ) {
			return true;
		}

		return false;
	}

	/**
	 * Method to check if all required task has been completed.
	 *
	 * @param string $task_name Task name.
	 *
	 * @return bool
	 *
	 * @since 4.6.0
	 */
	public function is_required_tasks_completed( $task_name = '' ) {

		if ( empty( $task_name ) ) {
			return false;
		}

		$required_tasks = $this->get_required_tasks( $task_name );

		// If there are not any required tasks which means this task can run without any dependency.
		if ( empty( $required_tasks ) ) {
			return true;
		}

		$done_tasks = get_option( self::$onboarding_tasks_done_option, array() );

		// Variable to hold list of all done tasks without any grouping.
		$all_done_tasks         = array();
		$is_required_tasks_done = false;
		if ( ! empty( $done_tasks ) ) {
			foreach ( $done_tasks as $task_group => $grouped_tasks ) {
				foreach ( $grouped_tasks as $task ) {
					$all_done_tasks[] = $task;
				}
			}
		}

		$remaining_required_tasks = array_diff( $required_tasks, $all_done_tasks );

		// Check if there are not any required tasks remaining.
		if ( empty( $remaining_required_tasks ) ) {
			$is_required_tasks_done = true;
		}

		return $is_required_tasks_done;
	}

	/**
	 * Method to get lists of required tasks which should be completed successfully for this task.
	 *
	 * @return array $required_tasks List of required tasks.
	 */
	public function get_required_tasks( $task_name = '' ) {

		if ( empty( $task_name ) ) {
			return array();
		}

		$required_tasks_mapping = array(
			'set_sending_service_consent' => array(
				'create_ess_account',
			),
			'schedule_ess_cron' => array(
				'create_ess_account',
			),
			'dispatch_emails_from_server' => array(
				'set_sending_service_consent',
			),
			'check_test_email_on_server' => array(
				'dispatch_emails_from_server',
			),
		);

		$required_tasks = ! empty( $required_tasks_mapping[ $task_name ] ) ? $required_tasks_mapping[ $task_name ] : array();

		return $required_tasks;
	}

	/**
	 * Method to perform email delivery tasks.
	 *
	 * @since 4.6.0
	 */
	public function ajax_dispatch_emails_from_server() {
		return $this->perform_onboarding_tasks( 'email_delivery_check_tasks', 'dispatch_emails_from_server' );
	}

	/**
	 * Method to perform email delivery tasks.
	 *
	 * @since 4.6.0
	 */
	public function ajax_check_test_email_on_server() {

		return $this->perform_onboarding_tasks( 'email_delivery_check_tasks', 'check_test_email_on_server' );
	}

	/**
	 * Method to send default broadcast campaign.
	 *
	 * @since 4.6.0
	 */
	public function dispatch_emails_from_server() {

		$response = array(
			'status' => 'error',
		);

		$service = new ES_Send_Test_Email();
		$result  = $service->send_test_email();
		if ( ! empty( $result['status'] ) && 'SUCCESS' === $result['status'] ) {
			$response['status'] = 'success';
		}
		
		return $response;
	}

	/**
	 * Method to check if test email is received on Icegram servers.
	 *
	 * @since 4.6.0
	 */
	public function check_test_email_on_server() {

		$response = array(
			'status' => 'error',
		);

		$onboarding_tasks_failed           = get_option( self::$onboarding_tasks_failed_option, array() );
		$email_delivery_check_tasks_failed = ! empty( $onboarding_tasks_failed['email_delivery_check_tasks'] ) ? $onboarding_tasks_failed['email_delivery_check_tasks'] : array();

		$task_failed = in_array( 'dispatch_emails_from_server', $email_delivery_check_tasks_failed, true );

		// Peform test email checking if dispatch_emails_from_server task hasn't failed.
		if ( ! $task_failed ) {
			$service  = new ES_Email_Delivery_Check();
			$response = $service->test_email_delivery();
		} else {
			$response['status'] = 'failed';
		}

		return $response;
	}

	public function schedule_ess_cron() {
		$response = array(
			'status' => 'error',
		);

		if ( ! wp_next_scheduled( 'ig_es_ess_update_account') ) {
			wp_schedule_event( time(), 'daily', 'ig_es_ess_update_account' );
		}

		$response['status'] = 'success';
		return $response;
	}

	public function clear_ess_cron() {
		wp_clear_scheduled_hook('ig_es_ess_update_account');
	}

	public function send_plan_data_to_ess() {

		$response = array(
			'status' => 'error',
		);

		if ( !$this->opted_for_sending_service() ) {
			$this -> clear_ess_cron();
			return;
		}

		$ess_data = get_option( 'ig_es_ess_data', array() );
		$api_key  = $ess_data['api_key'];
		$current_plan = $this -> get_plan();
		
		// Update account if plan is not registered or it has changed
		if ( !empty( $ess_data['plan'] ) && $ess_data['plan'] == $current_plan ) {
			return;
		}

		$data = array(
			'plan'   => $current_plan,
		);

		$options = array(
			'timeout' => 50,
			'method'  => 'POST',
			'body'    => json_encode($data),
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,// Keep it like bearer when we send email
				'Content-Type'  => 'application/json',
			),
		);

		$api_url = 'https://api.igeml.com/accounts/update/';

		$response = wp_remote_post( $api_url, $options );

		if ( ! is_wp_error( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = ( array ) json_decode( $response_body );
			if ( 'success' === $response_data['status'] ) {
				$ess_data['plan'] = $this->get_plan();
				update_option( 'ig_es_ess_data', $ess_data );
			}
		}
	}

	public static function update_used_limit( $sent_count = 0 ) {
		$ess_data      = get_option( 'ig_es_ess_data', array() );
		$current_month = ig_es_get_current_month();
		$used_limit    = ! empty( $ess_data['used_limit'][$current_month] ) ? $ess_data['used_limit'][$current_month] : 0;
		$used_limit   += $sent_count;
		if ( ! isset( $ess_data['used_limit'] ) || ! is_array( $ess_data['used_limit'] ) ) {
			$ess_data['used_limit'] = array();
		}
		$ess_data['used_limit'][$current_month] = $used_limit;
		update_option( 'ig_es_ess_data', $ess_data );
	}

	public static function get_remaining_limit() {
	
		self::fetch_and_update_ess_limit();
		$ess_data        = get_option( 'ig_es_ess_data', array() );
		$current_month   = ig_es_get_current_month();
		$allocated_limit = ! empty( $ess_data['allocated_limit'] ) ? $ess_data['allocated_limit'] : 0;
		$used_limit      = ! empty( $ess_data['used_limit'][$current_month] ) ? $ess_data['used_limit'][$current_month] : 0;
		$remaining_limit = $allocated_limit - $used_limit;
		return $remaining_limit;
	}

	public static function fetch_and_update_ess_limit() {
		$admin_email = ES_Common::get_admin_email();
		$data = array(
			'admin_email'   => $admin_email,
		);
		$ess_data = get_option( 'ig_es_ess_data', array() );
		$api_key  = $ess_data['api_key'];
		$options = array(
			'method'  => 'POST',
			'body'    => json_encode($data),
			'timeout' => 15,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,// Keep it like bearer when we send email
			),
		);

		$api_url = 'https://api.igeml.com/limit/check/';

		$response = wp_remote_post( $api_url, $options );

		if ( ! is_wp_error( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = ( array ) json_decode( $response_body );
			if ( 'success' === $response_data['status'] ) {
				if ( ! empty( $response_data['account'] ) ) {
					$current_month               = ig_es_get_current_month();
					$account                     = (array) $response_data['account'];
					$ess_data                    = get_option( 'ig_es_ess_data', array() );
					$ess_data['allocated_limit'] = $account['allocated_limit'];
					$ess_data['used_limit'][$current_month]      = $account['used_limit'];
					update_option( 'ig_es_ess_data', $ess_data );
				}
			}
		}
	}

	public static function use_icegram_mailer() {
		$use_icegram_mailer = false;
		if ( self::opted_for_sending_service() ) {
			$use_icegram_mailer = true;
		}
		return $use_icegram_mailer;
	}

	public static function opted_for_sending_service() {
		$opted_for_sending_service = get_option( 'ig_es_ess_opted_for_sending_service', 'no' );
		return 'yes' === $opted_for_sending_service;
	}

	public static function using_icegram_mailer() {
		return 'icegram' === ES()->mailer->mailer->slug;
	}

	public static function get_ess_from_email() {
		$ess_data       = get_option( 'ig_es_ess_data', array() );
		$ess_from_email = ! empty( $ess_data['from_email'] ) ? $ess_data['from_email'] : '';
		return $ess_from_email;
	}

	public static function can_show_ess_optin() {

		global $ig_es_tracker;

		if ( $ig_es_tracker::is_dev_environment() ) {
			return false;
		}

		return true;
	}

	public static function is_installed_on_same_month_day() {
		$installation_date = ES_Common::get_plugin_installation_date();
		if ( ! empty( $installation_date ) ) {
			$installation_day = gmdate( 'd', strtotime( $installation_date ) );
			$current_day      = gmdate( 'd', time() );
			if ( $current_day === $installation_day ) {
				return true;
			}
		}
		return false;
	}

	public static function is_shown_previously() {
		$ess_optin_shown = get_option( 'ig_es_ess_optin_shown', 'no' );
		return 'yes' === $ess_optin_shown;
	}

	public static function set_ess_optin_shown_flag() {
		update_option( 'ig_es_ess_optin_shown', 'yes', false );
	}

	public function update_sending_service_status() {
		if ( self::using_icegram_mailer() ) {
			$status = 'ig_es_message_sent' === current_action() ? 'success' : 'error';
			update_option( 'ig_es_ess_status', $status, false );
		}
	}

	public static function get_sending_service_status() {
		$service_status = get_option( 'ig_es_ess_status' );
		return $service_status;
	}

	public static function get_plan() {
		
		$es_services  = ES()->get_es_services();
		$service_plan = 'lite';
		if ( empty( $es_services ) ) {
			return $service_plan;
		}

		if ( in_array( 'bounce_handling', $es_services, true ) ) {
			$service_plan = 'max';
		} else {
			$service_plan = 'pro';
		}

		return $service_plan;
	}

	public static function is_ess_branding_enabled() {
		$ess_branding_enabled = get_option( 'ig_es_ess_branding_enabled', 'yes' );
		return 'yes' === $ess_branding_enabled; 
	}

	public static function can_promote_ess() {
		if ( get_option( 'ig_es_ess_opted_for_sending_service', '' ) === '' && ! self::is_ess_promotion_disabled() ) {
			return true;
		}
		return false;
	}

	public static function is_ess_promotion_disabled() {
		$is_ess_promotion_disabled = 'yes' === get_option( 'ig_es_promotion_disabled', 'no' );
		return $is_ess_promotion_disabled;
	}

	public static function get_ess_promotion_message_html() {
		ob_start();
		$optin_url      = admin_url( '?page=es_dashboard&ess_optin=yes' );
		$learn_more_url = 'https://www.icegram.com/email-sending-service-in-icegram-express/';
		?>
		<div id="ig_es_ess_promotion_message" class="text-gray-700 not-italic">
			<p>
				<?php echo esc_html__( 'Please fix above sending error to continue sending emails', 'email-subscribers' ); ?>
				
			</p>
			<p>
				<?php echo esc_html__( 'OR', 'email-subscribers' ); ?>
			</p>
			<p>
				<?php echo esc_html__( 'Use our Icegram email sending service for a hassle-free email sending experience.', 'email-subscribers' ); ?>
			</p>
			<a href="<?php echo esc_url( $optin_url ); ?>" target="_blank" id="ig-es-ess-optin-promo">
			<button class="primary">	<?php echo esc_html__('Signup to ESS', 'email-subscribers'); ?>
			</button>
			</a>
			<a href="<?php echo esc_url( $learn_more_url ); ?>" class="ml-2" target="_blank" >
			<button class="secondary">	<?php echo esc_html__('Learn more', 'email-subscribers'); ?>
			</button>
			</a>
		</div>
		<?php
		$message_html = ob_get_clean();
		return $message_html;
	}

	public function send_used_limit_data_to_ess() {
		$response = array(
			'status' => 'error',
		);

		$ess_option_exists = get_option( 'ig_es_ess_opted_for_sending_service', '' ) !== '';
		if ( ! $ess_option_exists ) {
			return;
		}

		$ess_data = get_option( 'ig_es_ess_data', array() );
		$current_month = ig_es_get_current_month();
		$allocated_limit = ! empty( $ess_data['allocated_limit'] ) ? (int) $ess_data['allocated_limit'] : 0;
		if ( $allocated_limit !== 3000 ) {
			return;
		}
		$used_limit      = ! empty( $ess_data['used_limit'][$current_month] ) ? $ess_data['used_limit'][$current_month] : 0;
		$api_key  = $ess_data['api_key'];

		$data = array(
			'used_limit'   => (int) $used_limit,
		);

		$options = array(
			'timeout' => 50,
			'method'  => 'POST',
			'body'    => json_encode($data),
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,// Keep it like bearer when we send email
				'Content-Type'  => 'application/json',
			),
		);

		$api_url = 'https://api.igeml.com/accounts/update/';

		$response = wp_remote_post( $api_url, $options );

		if ( ! is_wp_error( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = ( array ) json_decode( $response_body );
			if ( 'success' === $response_data['status'] ) {
				$ess_data['plan'] = $this->get_plan();
				update_option( 'ig_es_ess_data', $ess_data );
			}
		}
	}

	// ESS promotion notice for WP/PHP mailer
	public static function get_ess_promotion_message_mailer_html( $time_message, $total_contacts) {
		ob_start();
		$optin_url      = admin_url('?page=es_dashboard&ess_optin=yes#sending-service-onboarding-tasks-list');
		$learn_more_url = 'https://www.icegram.com/email-sending-service/?utm_source=in_app&utm_medium=ess_wp_php_mailer_notice&utm_campaign=ess_upsell';
	
		$heading = esc_html__('Increase Your Email Campaign Efficiency with Our Email Sending Service!', 'email-subscribers');
		$allowedtags     = ig_es_allowed_html_tags_in_esc();
		$tooltip_text = sprintf(
			'Calculation based on your sending speed of %s and %s subscribers.',
			esc_html($time_message),
			esc_html($total_contacts)
		);
		//$tooltip_html = ES_Common::get_tooltip_html($tooltip_text);
		
		?>
		<div id="ig_es_ess_promotion_mailer_message" class="text-gray-700 not-italic p-4 leading-relaxed">
			<h2 class="text-xl font-bold mb-2"><?php echo $heading; ?></h2>
			<div class="mb-4">
				<?php
				printf(
					'Your current sending speeds can take up to %s ',
					'<strong>' . esc_html($time_message) . '</strong>'
				);
				// echo wp_kses( $tooltip_html, $allowedtags ); 
				?>
				
				<?php
				esc_html_e('for sending important updates to your subscribers. This delay could result in missed opportunities and time-sensitive information not being delivered promptly.', 'email-subscribers');
				?>
			</div>
			<p class="font-bold mt-4 mb-2">
				<?php
				printf(
					esc_html__('Upgrade to our%1$s (ESS) and experience:', 'email-subscribers'),
					'<a href="' . esc_url($learn_more_url) . '" class="ml-2" target="_blank">' . esc_html__('Email Sending Service', 'email-subscribers') . '</a>'
				);
				?>
			</p>
			<ul class="list-disc ml-6 mt-2 space-y-1" style="list-style-type:initial">
				<li><span class="font-bold"><?php esc_html_e('Lightning-Fast Sending Speeds:', 'email-subscribers'); ?></span> <?php esc_html_e('Send your entire campaign in minutes, not hours.', 'email-subscribers'); ?></li>
				<li><span class="font-bold"><?php esc_html_e('Enhanced Deliverability:', 'email-subscribers'); ?></span> <?php esc_html_e('Reach your audience\'s inboxes with higher reliability and avoid being flagged as spam.', 'email-subscribers'); ?></li>
				<li><span class="font-bold"><?php esc_html_e('Hassle-Free Experience:', 'email-subscribers'); ?></span> <?php esc_html_e('Focus on your content while we handle the technicalities of efficient email delivery.', 'email-subscribers'); ?></li>
			</ul>
	
			<div class="flex flex-row sm:flex-row sm:space-x-2 mt-2">
				<a href="<?php echo esc_url($optin_url); ?>" target="_blank" id="ig-es-ess-optin-promo" class="sm:mr-2 mb-2 sm:mb-0">
					<button class="primary bg-blue-500 text-white py-2 px-4 rounded w-full sm:w-auto">
						<?php esc_html_e('Signup to ESS', 'email-subscribers'); ?>
					</button>
				</a>
			</div>
		</div>
		<?php
		$message_html = ob_get_clean();
		return $message_html;
	}
	
   
	public function show_ess_promotion_notice() {
		
		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}
	
		$current_page = ig_es_get_request_data('page');
		if ( 'es_dashboard' === $current_page || 
			 'es_workflows' === $current_page || 
			 'es_logs'      === $current_page ) {
			return;
		}
	
		$current_mailer_slug = ES()->mailer->get_current_mailer_slug();
		if ( empty( $current_mailer_slug ) ) {
			return;
		}
	
		if ( 'wpmail' !== $current_mailer_slug && 'phpmail' !== $current_mailer_slug ) {
			return;
		}

		$ig_es_ess_promotion_mailer_notice_shown = get_option( 'ig_es_ess_promotion_mailer_notice', 'no' );

		if ( 'yes' === $ig_es_ess_promotion_mailer_notice_shown ) {
			return;
		}
	
		$can_promote_ess = self::can_promote_ess();
		if ( ! $can_promote_ess ) {
			return;
		}

		$total_contacts = ES()->contacts_db->get_total_contacts();
		if ( $total_contacts < 50 ) {
		 return;
		}

		if ( $total_contacts < 3000 ) {
			$total_contacts=3000;
		}

		$time_interval          = ES()->cron->get_cron_interval();
		$max_email_send_at_once = ES()->mailer->get_max_email_send_at_once_count();
		$intervals_needed       = ceil( $total_contacts / $max_email_send_at_once );
		$total_time_seconds     = $intervals_needed * $time_interval;
		
		// Calculate human-readable time difference
		$total_time_seconds += time(); 
		$time_message        = human_time_diff( time(), $total_time_seconds );
		
		?>
		<div class="notice notice-info is-dismissible">
			<?php
			$promotion_message_html = self::get_ess_promotion_message_mailer_html( $time_message, $total_contacts );
			$allowed_tags           = ig_es_allowed_html_tags_in_esc();
			echo wp_kses( $promotion_message_html, $allowed_tags );
			?>
		</div>
		<?php
		update_option( 'ig_es_ess_promotion_mailer_notice', 'yes', false );
	}
	
	public function show_ess_fallback_removal_notice() {

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$can_access_settings = ES_Common::ig_es_can_access( 'settings' );
		if ( ! $can_access_settings ) {
			return 0;
		}

		$current_page = ig_es_get_request_data( 'page' );

		if ( 'es_dashboard' === $current_page ) {
			return;
		}

		if ( ! self::opted_for_sending_service() || ES()->mailer->is_using_site_mailer() ) {
			return;
		}

		$ess_data = get_option( 'ig_es_ess_data', array() );
		$allocated_limit = ! empty( $ess_data['allocated_limit'] ) ? (int) $ess_data['allocated_limit'] : 0;

		$fallback_notice_dismissed = 'yes' === get_option( 'ig_es_ess_fallback_removal_notice_dismissed', 'no' );
		if ( ! $fallback_notice_dismissed ) {
			$ess_pricing_url = 'https://www.icegram.com/email-sending-service/?utm_source=in_app&utm_medium=ess_setting&utm_campaign=ess_fallback_removal_notice';
			$ess_setting_url = admin_url( 'admin.php?page=es_settings&section=ess#tabs-email_sending' );
			?>
			<div id="ig_es_ess_fallback_removal_notice" class="notice notice-error is-dismissible">
				<div id="" class="text-gray-700 not-italic">
					<p>
						<strong>[<?php echo esc_html__( 'Important Notice', 'email-subscribers' ); ?>]:</strong>
						<?php
							/* translators: 1. Starting strong tag 2. Closing strong tag */
							echo sprintf( esc_html__( 'Change in %1$sIcegram Email Sending Service%2$s', 'email-subscribers' ), '<strong>', '</strong>' );
						?>
					</p>
					<p class="mb-2">
						<?php
						/* translators: 1. Starting strong tag 2. Closing strong tag */
						echo sprintf( esc_html__( 'Starting %1$sNovember 4%2$s, 2024, once your Icegram email sending service\'s monthly limit is reached, %3$semail sending will be paused until the limit resets%4$s.'), '<strong>', '</strong>', '<strong>', '</strong>' );
						if ( $allocated_limit < 30000 ) {
							echo ' ' . esc_html__( 'You can upgrade to higher limit to avoid interruptions.', 'email-subscribers' );
						}
						echo ' ' . esc_html__( 'Alternatively disable our service to switch to your configured provider, though email delivery may be affected.', 'email-subscribers' ); 
						?>
					</p>
					<p>
						<?php
						if ( $allocated_limit < 30000 ) {
							?>
						<a href="<?php echo esc_url( $ess_pricing_url ); ?>" target="_blank" id="ig-es-ess-optin-promo">
							<button class="primary"><?php echo esc_html__('Upgrade', 'email-subscribers'); ?></button>
						</a>
						<?php
						}
						?>
						<a href="<?php echo esc_url( $ess_setting_url ); ?>" target="_blank" id="ig-es-ess-optin-promo">
							<button href="#" class="secondary"><?php echo esc_html__('Check your limit', 'email-subscribers'); ?></button>
						</a>
					</p>
				</div>
			</div>
			<script>
				jQuery(document).ready(function($) {
					$('#ig_es_ess_fallback_removal_notice').on('click', '.notice-dismiss', function() {
						$.ajax({
							method: 'POST',
							url: ajaxurl,
							dataType: 'json',
							data: {
								action: 'ig_es_dismiss_ess_fallback_removal_notice',
								security: ig_es_js_data.security
							}
						}).done(function(response){
							console.log( 'response: ', response );
						});
					});
				});

			</script>
			<?php
		}
	}

	public function dismiss_ess_fallback_removal_notice() {
		$response = array(
			'status' => 'success',
		);

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$can_access_settings = ES_Common::ig_es_can_access( 'settings' );
		if ( ! $can_access_settings ) {
			return 0;
		}

		update_option( 'ig_es_ess_fallback_removal_notice_dismissed', 'yes', false );

		wp_send_json( $response );
	}

	public function maybe_update_ess_status( $options ) {
		if ( ! empty( $options['ig_es_ess_opted_for_sending_service'] ) ) {
			$new_status = $options['ig_es_ess_opted_for_sending_service'];
			$old_status = get_option( 'ig_es_ess_opted_for_sending_service', 'no' );
			if ( $new_status !== $old_status ) {
				$ess_status = 'yes' === $new_status ? 'active' : 'paused';
				$this->update_ess_status( $ess_status );
			}
		}
	}

	public function update_ess_status( $ess_status ) {
		$response = array(
			'status' => 'error',
		);

		$ess_option_exists = get_option( 'ig_es_ess_opted_for_sending_service', '' ) !== '';
		if ( ! $ess_option_exists ) {
			return false;
		}

		$ess_data = get_option( 'ig_es_ess_data', array() );
		$api_key  = $ess_data['api_key'];

		$data = array(
			'status'   => $ess_status,
		);

		$options = array(
			'timeout' => 50,
			'method'  => 'POST',
			'body'    => json_encode($data),
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,// Keep it like bearer when we send email
				'Content-Type'  => 'application/json',
			),
		);

		$api_url = 'https://api.igeml.com/accounts/update/';

		$response = wp_remote_post( $api_url, $options );
		
		if ( ! is_wp_error( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = ( array ) json_decode( $response_body );
			if ( 'success' === $response_data['status'] ) {
				return true;
			}
		}

		return false;
	}
}

new ES_Service_Email_Sending();
