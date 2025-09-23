<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The onboarding-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      4.6.0
 *
 * @package    Email_Subscribers
 */

if ( ! class_exists( 'ES_Onboarding_Controller' ) ) {
	/**
	 * Class to handle onboarding operation
	 * 
	 * @class ES_Onboarding_Controller
	 */
	class ES_Onboarding_Controller {
		
		/**
		 * Class instance.
		 *
		 * @var Onboarding instance
		 */
		public static $instance;
		
		/**
		 * Added Logger Context
		 *
		 * @since 4.6.0
		 * @var array
		 */
		protected static $logger_context = array(
			'source' => 'es_onboarding_controller',
		);
		
		/**
		 * Variable to hold all onboarding tasks list
		 *
		 * @since 4.6.0
		 * @var array
		 */
		private static $configuration_tasks = array(
			'set_settings',
			'create_default_lists',
			'create_contacts_and_add_to_list',
			'add_default_workflows',
			'create_default_newsletter_broadcast',
			'create_default_post_notification',
			'create_default_subscription_form',
			'add_widget_to_sidebar',
		);
		
		/**
		 * Option name for current task name.
		 *
		 * @since 4.6.0
		 * @var array
		 */
		private static $onboarding_current_task_option = 'ig_es_onboarding_current_task';
		
		/**
		 * Option name which holds common data between tasks.
		 *
		 * E.g. created subscription form id from create_default_subscription_form function so we can use it in add_widget_to_sidebar
		 *
		 * @since 4.6.0
		 * @var array
		 */
		private static $onboarding_tasks_data_option = 'ig_es_onboarding_tasks_data';
		
		/**
		 * Option name which holds tasks which are done.
		 *
		 * @since 4.6.0
		 * @var array
		 */
		private static $onboarding_tasks_done_option = 'ig_es_onboarding_tasks_done';
		
		/**
		 * Option name which holds tasks which are failed.
		 *
		 * @since 4.6.0
		 * @var array
		 */
		private static $onboarding_tasks_failed_option = 'ig_es_onboarding_tasks_failed';
		
		/**
		 * Option name which holds tasks which are skipped due to dependency on other tasks.
		 *
		 * @since 4.6.0
		 * @var array
		 */
		private static $onboarding_tasks_skipped_option = 'ig_es_onboarding_tasks_skipped';
		
		/**
		 * Option name which store the step which has been completed.
		 *
		 * @since 4.6.0
		 * @var string
		 */
		private static $onboarding_step_option = 'ig_es_onboarding_step';

		/**
		 * Option name to track if onboarding is completed.
		 *
		 * @since 4.6.0
		 * @var string
		 */
		private static $onboarding_completed_option = 'ig_es_onboarding_complete';

		private static $onboarding_new_completed_option = 'ig_es_onboarding_new_complete';

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since 4.6.0
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Get class instance.
		 *
		 * @since 4.6.0
		 */
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
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Register the JavaScript for the onboarding process.
		 *
		 * @since 4.6.0
		 */
		public function enqueue_scripts() {

			if ( ! ES()->is_es_admin_screen() ) {
				return;
			}

			$current_page = ig_es_get_request_data( 'page' );

			if ( 'es_dashboard' === $current_page ) {
				// wp_enqueue_script( 'es-onboarding', ES_PLUGIN_URL . 'lite/admin/js/onboarding.js', array( 'jquery' ), ES_PLUGIN_VERSION, true );
				// wp_localize_script( 'es-onboarding', 'es_onboarding_data', array(
				// 	'ajax_url' => admin_url( 'admin-ajax.php' ),
				// 	'security' => wp_create_nonce( 'ig-es-admin-ajax-nonce' ),
				// ) );
			}
		}

		public static function submit_onboarding_form( $data = array() ) {
			
			if ( is_string( $data ) ) {
				$decoded_data = json_decode( $data, true );
				if ( $decoded_data ) {
					$data = $decoded_data;
				}
			}

			$form_data = isset( $data['data'] ) ? $data['data'] : $data;
			
			$sender_name = isset( $form_data['senderName'] ) ? sanitize_text_field( $form_data['senderName'] ) : '';
			$sender_email = isset( $form_data['senderEmail'] ) ? sanitize_email( $form_data['senderEmail'] ) : '';
			$test_emails = isset( $form_data['testEmails'] ) ? $form_data['testEmails'] : array();
			
			// Handle boolean conversion properly - check for string 'true' or actual boolean true
			$double_opt_in = false;
			if ( isset( $form_data['doubleOptIn'] ) ) {
				if ( is_bool( $form_data['doubleOptIn'] ) ) {
					$double_opt_in = $form_data['doubleOptIn'];
				} else {
					$double_opt_in = ( 'true' === strtolower( $form_data['doubleOptIn'] ) || '1' === $form_data['doubleOptIn'] );
				}
			}
			
			$gdpr_consent = false;
			if ( isset( $form_data['gdprConsent'] ) ) {
				if ( is_bool( $form_data['gdprConsent'] ) ) {
					$gdpr_consent = $form_data['gdprConsent'];
				} else {
					$gdpr_consent = ( 'true' === strtolower( $form_data['gdprConsent'] ) || '1' === $form_data['gdprConsent'] );
				}
			}

			if ( empty( $sender_name ) || empty( $sender_email ) ) {
				return array(
					'status' => 'error',
					'message' => __( 'Sender name and email are required.', 'email-subscribers' ),
				);
			}

			if ( ! is_email( $sender_email ) ) {
				return array(
					'status' => 'error',
					'message' => __( 'Please enter a valid email address.', 'email-subscribers' ),
				);
			}

			update_option( 'ig_es_from_name', $sender_name );
			update_option( 'ig_es_from_email', $sender_email );
			
			$optin_type = $double_opt_in ? 'double_opt_in' : 'single_opt_in';
			update_option( 'ig_es_optin_type', $optin_type );
			
			if ( $gdpr_consent ) {
				update_option( 'ig_es_allow_tracking', 'yes' );
			}

			if ( ! empty( $test_emails ) && is_array( $test_emails ) ) {
				$sanitized_emails = array();
				foreach ( $test_emails as $email ) {
					$clean_email = sanitize_email( $email );
					if ( is_email( $clean_email ) ) {
						$sanitized_emails[] = $clean_email;
					}
				}
				update_option( 'ig_es_test_emails', $sanitized_emails );
			}

			$autoload = false;
			$step = 2;
			update_option( self::$onboarding_step_option, $step, $autoload );

			$configuration_response = self::perform_configuration_tasks();
			
			// Check if onboarding is now completed
			$is_completed = self::is_onboarding_completed();
			$current_step = self::get_onboarding_step();

			return array(
				'status' => 'success',
				'message' => __( 'Onboarding form submitted successfully.', 'email-subscribers' ),
				'data' => array(
					'onboarding_completed' => $is_completed,
					'current_step' => $current_step,
					'configuration_tasks' => $configuration_response,
					'redirect_url' => $is_completed ? admin_url( 'admin.php?page=es_dashboard' ) : null,
				),
			);
		}

		/**
		 * Method to perform configuration and list, ES form, campaigns creation related operations in the onboarding
		 *
		 * @since 4.6.0
		 */
		public static function perform_configuration_tasks() {
			
			$tasks_done = get_option( self::$onboarding_tasks_done_option, array() );
			$tasks_failed = get_option( self::$onboarding_tasks_failed_option, array() );
			$tasks_data = get_option( self::$onboarding_tasks_data_option, array() );

			$response = array(
				'status' => 'success',
				'tasks_completed' => array(),
				'tasks_failed' => array(),
				'debug_info' => array(),
			);

			foreach ( self::$configuration_tasks as $task ) {
				
				if ( in_array( $task, $tasks_done, true ) ) {
					$response['tasks_completed'][] = $task;
					continue;
				}

				$instance = new self();
				
				if ( method_exists( $instance, $task ) ) {
					try {
						$task_response = $instance->$task();
						
						if ( isset( $task_response['status'] ) && 'success' === $task_response['status'] ) {
							$tasks_done[] = $task;
							$response['tasks_completed'][] = $task;
							$response['debug_info'][] = $task . ': SUCCESS';
							
							if ( isset( $task_response['data'] ) ) {
								$tasks_data[ $task ] = $task_response['data'];
								// Update task data immediately so subsequent tasks can use it
								update_option( self::$onboarding_tasks_data_option, $tasks_data );
							}
						} else {
							$tasks_failed[] = $task;
							$response['tasks_failed'][] = $task;
							$response['debug_info'][] = $task . ': FAILED - ' . ( isset( $task_response['message'] ) ? $task_response['message'] : 'Unknown error' );
						}
					} catch ( Exception $e ) {
						$tasks_failed[] = $task;
						$response['tasks_failed'][] = $task;
						$response['debug_info'][] = $task . ': EXCEPTION - ' . $e->getMessage();
					}
				} else {
					$tasks_failed[] = $task;
					$response['tasks_failed'][] = $task;
					$response['debug_info'][] = $task . ': METHOD_NOT_EXISTS';
				}
			}

			update_option( self::$onboarding_tasks_done_option, $tasks_done );
			update_option( self::$onboarding_tasks_failed_option, $tasks_failed );
			update_option( self::$onboarding_tasks_data_option, $tasks_data );

			if ( empty( $response['tasks_failed'] ) ) {
				update_option( self::$onboarding_step_option, 3 );
				update_option( self::$onboarding_completed_option, 'yes' );
				update_option( self::$onboarding_new_completed_option, 'yes' );
			}

			return $response;
		}

		/**
		 * Set settings required for ES settings.
		 *
		 * @since 4.6.0
		 */
		public function set_settings() {
			
			$from_name = get_option( 'ig_es_from_name', '' );
			$from_email = get_option( 'ig_es_from_email', '' );

			if ( ! empty( $from_name ) && ! empty( $from_email ) ) {
				return array( 'status' => 'success' );
			}

			return array( 'status' => 'error', 'message' => 'Missing from_name or from_email' );
		}

		/**
		 * Create default list contact
		 *
		 * @since 4.0.0
		 */
		public function create_default_lists() {
			
			$response = array(
				'status' => 'error',
			);

			// Check if ES() function and database objects are available
			if ( ! function_exists( 'ES' ) ) {
				return array( 'status' => 'error', 'message' => 'ES() function not available' );
			}

			if ( ! ES()->lists_db ) {
				return array( 'status' => 'error', 'message' => 'ES()->lists_db not available' );
			}

			// Check if constants are defined
			if ( ! defined( 'IG_MAIN_LIST' ) ) {
				return array( 'status' => 'error', 'message' => 'IG_MAIN_LIST constant not defined' );
			}

			$main_list = ES()->lists_db->get_list_by_name( IG_MAIN_LIST );
			
			if ( empty( $main_list['id'] ) ) {
				$main_list_id = ES()->lists_db->add_list( IG_MAIN_LIST );
			} else {
				$main_list_id = $main_list['id'];
			}

			if ( defined( 'IG_DEFAULT_LIST' ) ) {
				$default_list = ES()->lists_db->get_list_by_name( IG_DEFAULT_LIST );
				
				if ( empty( $default_list['id'] ) ) {
					$default_list_id = ES()->lists_db->add_list( IG_DEFAULT_LIST );
				} else {
					$default_list_id = $default_list['id'];
				}
			} else {
				$default_list_id = $main_list_id;
			}

			if ( $main_list_id > 0 ) {
				$response['status'] = 'success';
				$response['data'] = array( 
					'main_list_id' => $main_list_id,
					'default_list_id' => $default_list_id,
				);
			} else {
			}

			return $response;
		}

		public function create_contacts_and_add_to_list() {
			
			$test_emails = get_option( 'ig_es_test_emails', array() );
			$sender_email = get_option( 'ig_es_from_email', '' );
			$tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
			$main_list_id = isset( $tasks_data['create_default_lists']['main_list_id'] ) ? $tasks_data['create_default_lists']['main_list_id'] : 0;


			// If no test emails provided, use sender email as a default contact
			$emails_to_process = $test_emails;
			if ( empty( $emails_to_process ) && ! empty( $sender_email ) && is_email( $sender_email ) ) {
				$emails_to_process = array( $sender_email );
			}

			if ( empty( $emails_to_process ) || empty( $main_list_id ) ) {
				return array( 'status' => 'success' );
			}

			$contacts_added = 0;
			foreach ( $emails_to_process as $email ) {
				if ( is_email( $email ) ) {
					$existing_contact_id = ES()->contacts_db->get_contact_id_by_email( $email );
					
					if ( empty( $existing_contact_id ) ) {
						$contact_data = array(
							'first_name'   => ES_Common::get_name_from_email( $email ),
							'email'        => $email,
							'source'       => 'admin',
							'form_id'      => 0,
							'status'       => 'verified',
							'unsubscribed' => 0,
							'hash'         => ES_Common::generate_guid(),
							'created_at'   => ig_get_current_date_time(),
						);
						
						$contact_id = ES()->contacts_db->insert( $contact_data );
						if ( $contact_id ) {
							$list_contact_data = array(
								'contact_id'    => $contact_id,
								'status'        => 'subscribed',
								'optin_type'    => IG_SINGLE_OPTIN,
								'subscribed_at' => ig_get_current_date_time(),
								'subscribed_ip' => '',
							);
							$result = ES()->lists_contacts_db->add_contact_to_lists( $list_contact_data, $main_list_id );
							$contacts_added++;
						} else {
						}
					} else {
						$list_contact_data = array(
							'contact_id'    => $existing_contact_id,
							'status'        => 'subscribed',
							'optin_type'    => IG_SINGLE_OPTIN,
							'subscribed_at' => ig_get_current_date_time(),
							'subscribed_ip' => '',
						);
						$result = ES()->lists_contacts_db->add_contact_to_lists( $list_contact_data, $main_list_id );
						$contacts_added++;
					}
				} else {
				}
			}

			return array(
				'status' => 'success',
				'data' => array( 'contacts_added' => $contacts_added ),
			);
		}

		/**
		 * Add user registration workflow
		 *
		 * @return $response
		 *
		 * @since 4.6.0
		 */
		public function add_default_workflows() {
			
			if ( ! isset( ES()->workflows_db ) || ! method_exists( ES()->workflows_db, 'insert' ) ) {
				return array( 'status' => 'success' );
			}

			try {
				$workflow_data = array(
					'name' => __( 'Welcome Email', 'email-subscribers' ),
					'title' => __( 'Welcome Email', 'email-subscribers' ),
					'trigger_name' => 'user_registered',
					'trigger_options' => '',
					'rules' => '',
					'actions' => '',
					'meta' => '',
					'status' => 1,
					'type' => 1,
					'priority' => 0,
					'created_at' => ig_get_current_date_time(),
					'updated_at' => '',
				);

				$workflow_id = ES()->workflows_db->insert( $workflow_data );

				if ( $workflow_id ) {
					return array(
						'status' => 'success',
						'data' => array( 'workflow_id' => $workflow_id ),
					);
				}
			} catch ( Exception $e ) {
			}

			return array( 'status' => 'success' );
		}

		/**
		 * Create and send default broadcast while onboarding
		 *
		 * @return array|mixed|void
		 *
		 * @since 4.0.0
		 */
		public function create_default_newsletter_broadcast() {
			if ( ! isset( ES()->campaigns_db ) || ! method_exists( ES()->campaigns_db, 'insert' ) ) {
				return array( 'status' => 'success' );
			}

			$from_name = get_option( 'ig_es_from_name', '' );
			$from_email = get_option( 'ig_es_from_email', '' );

			$campaign_data = array(
				'name' => __( 'Sample Newsletter', 'email-subscribers' ),
				'subject' => __( 'Welcome to our newsletter!', 'email-subscribers' ),
				'body' => $this->get_default_newsletter_content(),
				'from_name' => $from_name,
				'from_email' => $from_email,
				'type' => 'newsletter',
				'status' => 'draft',
			);

			$campaign_id = ES()->campaigns_db->insert( $campaign_data );

			if ( $campaign_id ) {
				return array(
					'status' => 'success',
					'data' => array( 'campaign_id' => $campaign_id ),
				);
			}

			return array( 'status' => 'success' );
		}

		/**
		 * Create default Post notification while on boarding
		 *
		 * @return array|int|mixed|void|WP_Error
		 *
		 * @since 4.6.0
		 */
		public function create_default_post_notification() {
			if ( ! isset( ES()->campaigns_db ) || ! method_exists( ES()->campaigns_db, 'insert' ) ) {
				return array( 'status' => 'success' );
			}

			$from_name = get_option( 'ig_es_from_name', '' );
			$from_email = get_option( 'ig_es_from_email', '' );

			$notification_data = array(
				'name' => __( 'New Post Notification', 'email-subscribers' ),
				'subject' => __( 'New post: {{POSTTITLE}}', 'email-subscribers' ),
				'body' => $this->get_default_post_notification_content(),
				'from_name' => $from_name,
				'from_email' => $from_email,
				'type' => 'post_notification',
				'status' => 'active',
			);

			$notification_id = ES()->campaigns_db->insert( $notification_data );

			if ( $notification_id ) {
				return array(
					'status' => 'success',
					'data' => array( 'notification_id' => $notification_id ),
				);
			}

			return array( 'status' => 'success' );
		}

		/**
		 * Create default form
		 *
		 * @since 4.6.0
		 * 
		 * @modify 5.5.14
		 */
	public function create_default_subscription_form() {
		
		// Get the created list to assign to the form
		$tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
		$main_list_id = isset( $tasks_data['create_default_lists']['main_list_id'] ) ? $tasks_data['create_default_lists']['main_list_id'] : 0;
		
		// Check if GDPR consent was enabled during onboarding
		$gdpr_form_consent = get_option( 'ig_es_allow_tracking', 'no' );
		$gdpr_enabled = ( 'yes' === $gdpr_form_consent );
		
		// Use the new wysiwyg editor format like the React form wizard
		$form_body = array(
			array(
				'id' => 'email',
				'type' => 'email',
				'name' => 'Email address',
				'label' => 'Email address',
				'placeholder' => 'Enter your email address',
				'required' => true,
				'enabled' => true,
				'order' => 1,
				'options' => array(),
				'value' => ''
			),
			array(
				'id' => 'name',
				'type' => 'text',
				'name' => 'Name',
				'label' => 'Name',
				'placeholder' => 'Enter your name',
				'required' => false,
				'enabled' => true,
				'order' => 2,
				'options' => array(),
				'value' => ''
			)
		);

		// Add GDPR consent field if enabled during onboarding
		if ( $gdpr_enabled ) {
			$form_body[] = array(
				'id' => 'gdpr',
				'type' => 'checkbox',
				'name' => 'GDPR Consent',
				'label' => 'GDPR Consent',
				'placeholder' => '',
				'required' => true,
				'enabled' => true,
				'order' => 3,
				'options' => array(),
				'value' => ''
			);
		}

		$settings = array(
			'editor_type' => 'wysiwyg',
			'form_version' => '1.0',
			'lists' => $main_list_id ? array( $main_list_id ) : array(),
			'desc' => '',
			'show_in_popup' => 'no',
			'popup_headline' => '',
			'success_message' => __( 'Thank you for subscribing!', 'email-subscribers' ),
			'redirect_url' => '',
			'form_style' => 'inherit',
			'gdpr' => array(
				'consent' => $gdpr_enabled ? 'yes' : 'no',
				'consent_text' => __( 'I agree to receive emails and accept the terms and conditions.', 'email-subscribers' )
			),
			'captcha' => 'no',
			'action_after_submit' => 'show_success_message',
			'redirect_to_url' => 'no',
			'show_message' => 'no',
			'is_embed_form_enabled' => 'no',
			'embed_form_remote_urls' => array()
		);

		$styles = array(
			'form_bg_color' => '#ffffff',
			'form_width' => '600'
		);

		$form_data = array(
			'name' => __( 'Default Subscription Form', 'email-subscribers' ),
			'body' => wp_json_encode( $form_body ), // JSON encode the body array
			'settings' => serialize( $settings ), // PHP serialize the settings array
			'styles' => serialize( $styles ), // PHP serialize the styles array
			'lists' => $main_list_id ? array( $main_list_id ) : array(), // Add lists at top level too
			'created_at' => ig_get_current_date_time(),
			'updated_at' => ig_get_current_date_time(),
		);


		$result = ES_Form_Controller::save( $form_data );
		
		
		if ( 'success' === $result['status'] ) {
			// Get the form ID from the database
			global $wpdb;
			$form_id = $wpdb->insert_id;
			
			
			if ( $form_id ) {
				return array(
					'status' => 'success',
					'data' => array( 'form_id' => $form_id ),
				);
			} else {
			}
		} else {
		}

		return array( 'status' => 'error', 'message' => 'Failed to create form' );
	}		/**
		 * Add ES widget to active sidebar
		 *
		 * @since 4.6.0
		 */
		public function add_widget_to_sidebar() {
			
			$tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
			$form_id = isset( $tasks_data['create_default_subscription_form']['form_id'] ) ? $tasks_data['create_default_subscription_form']['form_id'] : 0;


			if ( empty( $form_id ) ) {
				return array( 'status' => 'error' );
			}

			$sidebars_widgets = get_option( 'sidebars_widgets' );
			$widget_data = get_option( 'widget_es_widget' );

			if ( ! is_array( $widget_data ) ) {
				$widget_data = array();
			}

			$widget_id = 'es_widget-' . ( count( $widget_data ) + 1 );
			$widget_data[ count( $widget_data ) + 1 ] = array(
				'title' => __( 'Subscribe To Our Newsletter', 'email-subscribers' ),
				'es_form' => $form_id,
			);

			update_option( 'widget_es_widget', $widget_data );

			if ( isset( $sidebars_widgets['sidebar-1'] ) ) {
				array_unshift( $sidebars_widgets['sidebar-1'], $widget_id );
				update_option( 'sidebars_widgets', $sidebars_widgets );
			} else {
			}

			return array( 'status' => 'success' );
		}

		private function get_default_form_body() {
		return array(
			array(
				'id' => 'email',
				'type' => 'email',
				'name' => __( 'Email address', 'email-subscribers' ),
				'label' => __( 'Email address', 'email-subscribers' ),
				'placeholder' => __( 'Enter your email address', 'email-subscribers' ),
				'required' => true,
				'enabled' => true,
				'order' => 1,
				'options' => array(),
				'value' => ''
			),
			array(
				'id' => 'name',
				'type' => 'text',
				'name' => __( 'Name', 'email-subscribers' ),
				'label' => __( 'Name', 'email-subscribers' ),
				'placeholder' => __( 'Enter your name', 'email-subscribers' ),
				'required' => false,
				'enabled' => true,
				'order' => 2,
				'options' => array(),
				'value' => ''
			)
		);
	}

	private function get_default_newsletter_content() {
			return '<h2>' . __( 'Welcome to our newsletter!', 'email-subscribers' ) . '</h2>' .
				   '<p>' . __( 'Thank you for subscribing to our newsletter. We will keep you updated with our latest news and offers.', 'email-subscribers' ) . '</p>';
		}

		private function get_default_post_notification_content() {
			return '<h2>{{POSTTITLE}}</h2>' .
				   '<p>{{POSTEXCERPT}}</p>' .
				   '<p><a href="{{POSTLINK}}">' . __( 'Read more', 'email-subscribers' ) . '</a></p>';
		}

		private function get_default_form_content() {
			return '<!-- wp:es/email-subscribers {"widgetId":"es_email_subscribers_form"} -->' .
				   '<div class="wp-block-es-email-subscribers">' .
				   '<div class="es-form-wrapper">' .
				   '<input type="email" name="esfpx_email" placeholder="' . __( 'Enter your email', 'email-subscribers' ) . '" required />' .
				   '<input type="submit" value="' . __( 'Subscribe', 'email-subscribers' ) . '" />' .
				   '</div>' .
				   '</div>' .
				   '<!-- /wp:es/email-subscribers -->';
		}

		/**
		 * Method to get the current onboarding step
		 *
		 * @return int $onboarding_step Current onboarding step.
		 *
		 * @since 4.6.0
		 */
		public static function get_onboarding_step() {
			return get_option( self::$onboarding_step_option, 1 );
		}

		/**
		 * Method to updatee the onboarding step
		 *
		 * @return bool
		 *
		 * @since 4.6.0
		 */
		public static function update_onboarding_step( $step = 1 ) {
			$autoload = false;
			return update_option( self::$onboarding_step_option, $step, $autoload );
		}

		/**
		 * Method to check if onboarding is completed
		 *
		 * @return bool
		 *
		 * @since 4.6.0
		 */
		public static function is_onboarding_completed() {
			$completed = get_option( self::$onboarding_completed_option, false );
			if ( ! $completed ) {
				$step = self::get_onboarding_step();
				$completed = $step >= 3;
				if ( $completed ) {
					update_option( self::$onboarding_completed_option, true );
					update_option( self::$onboarding_new_completed_option, true );
				}
			}
			return (bool) $completed;
		}



		/**
		 * Validate all configuration tasks are working properly
		 * 
		 * @return array Validation results for each task
		 */
		public static function validate_configuration_tasks() {
			$validation_results = array();
			$instance = new self();
			
			foreach ( self::$configuration_tasks as $task ) {
				$validation_results[ $task ] = array(
					'method_exists' => method_exists( $instance, $task ),
					'status' => 'not_tested'
				);
				
				if ( $validation_results[ $task ]['method_exists'] ) {
					try {
						// Test the method
						$result = $instance->$task();
						$validation_results[ $task ]['status'] = isset( $result['status'] ) ? $result['status'] : 'unknown';
						$validation_results[ $task ]['response'] = $result;
					} catch ( Exception $e ) {
						$validation_results[ $task ]['status'] = 'error';
						$validation_results[ $task ]['error'] = $e->getMessage();
					}
				}
			}
			
			return $validation_results;
		}

		/**
		 * Method to delete the all onboarding data used in onboarding process.
		 */
		public function delete_onboarding_data() {
			delete_option( self::$onboarding_current_task_option );
			delete_option( self::$onboarding_tasks_data_option );
			delete_option( self::$onboarding_tasks_done_option );
			delete_option( self::$onboarding_tasks_failed_option );
			delete_option( self::$onboarding_tasks_skipped_option );
			delete_option( self::$onboarding_step_option );
			delete_option( self::$onboarding_completed_option );
			delete_option( self::$onboarding_new_completed_option );
		}
	}
}

ES_Onboarding_Controller::get_instance();
