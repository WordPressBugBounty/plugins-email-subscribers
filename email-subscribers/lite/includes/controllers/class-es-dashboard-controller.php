<?php

if ( ! class_exists( 'ES_Dashboard_Controller' ) ) {

	/**
	 * Class to handle dashboard operation
	 * 
	 * @class ES_Dashboard_Controller
	 */
	class ES_Dashboard_Controller {

		public static $instance;
		public static $api_instance = null;

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

		public static function get_subscribers_stats( $data = array() ) {
			
			if ( is_string( $data ) ) {
				$decoded_data = json_decode( $data, true );
				if ( $decoded_data ) {
					$data = $decoded_data;
				}
			}
			
			$page           = '';
			$days           = '';
			$list_id        = '';
			$override_cache = true;
			
			if ( isset( $data['page'] ) || isset( $data['days'] ) || isset( $data['list_id'] ) ) {
				$page           = isset( $data['page'] ) ? $data['page'] : 'es_dashboard';
				$days           = isset( $data['days'] ) ? $data['days'] : '';
				$list_id        = isset( $data['list_id'] ) ? $data['list_id'] : '';
				$override_cache = isset( $data['override_cache'] ) ? $data['override_cache'] : true;
			} 
			
			if ( empty( $days ) || ! is_numeric( $days ) ) {
				$days = 7;
			} else {
				$days = intval( $days );
			}
			
			if ( ! empty( $list_id ) && ! is_numeric( $list_id ) ) {
				$list_id = '';
			}

			$reports_args = array(
				'list_id' => $list_id,
				'days'    => $days,
			);
			
			
			$reports_data = ES_Reports_Data::get_dashboard_reports_data( $page, $override_cache, $reports_args );
			return $reports_data;
		}

		public static function get_dashboard_data( $args ) {
			$dashboard_kpi = ES_Reports_Data::get_dashboard_reports_data( 'es_dashboard', true, $args );
			
			$campaign_args = array(
				'status'          => array(
					IG_ES_CAMPAIGN_STATUS_IN_ACTIVE,
					IG_ES_CAMPAIGN_STATUS_ACTIVE,
				),
				'order_by_column' => 'ID',
				'limit'           => '5',
				'order'           => 'DESC',
			);
			$campaigns = ES()->campaigns_db->get_campaigns( $campaign_args );
			
			$audience_activity = self::get_audience_activities();
		
			$forms_args = array(
				'order_by_column' => 'ID',
				'limit'           => '5',
				'order'           => 'DESC',
			);
			$forms = ES()->forms_db->get_forms( $forms_args );

			if ( ! empty( $forms ) ) {
				foreach ( $forms as &$form ) {
				$form_id = ! empty( $form['id'] ) ? intval( $form['id'] ) : 0;

				$form['subscriber_count'] = ES()->contacts_db->get_total_contacts_by_form_id( $form_id, 0 );

				$settings = ! empty( $form['settings'] ) ? maybe_unserialize( $form['settings'] ) : [];

				$list_ids = [];
					if ( ! empty( $settings['lists'] ) ) {
						$list_ids = is_array( $settings['lists'] ) ? $settings['lists'] : [ intval( $settings['lists'] ) ];
					}

				$list_names = [];
					if ( ! empty( $list_ids ) ) {
						foreach ( $list_ids as $list_id ) {
							$list_names[] = ES()->lists_db->get_list_name_by_id( $list_id );
						}
					}

				$form['list_names'] = ! empty( $list_names ) ? implode( ', ', $list_names ) : '';
				}

			}

			$lists = array_slice( array_reverse( ES()->lists_db->get_lists() ), 0, 2 );
			$workflows = ES()->workflows_db->get_workflows();
			
			$icegram_plugins = self::get_icegram_plugins_info();
			
			$plan = ES()->get_plan();
			return array(	
				'campaigns'         => $campaigns,
				'audience_activity' => $audience_activity,
				'forms'             => $forms,
				'lists'             => $lists,
				'dashboard_kpi'     => $dashboard_kpi,
				'plan'              => $plan,
				'icegram_plugins'   => $icegram_plugins,
			);
		}
		
		public static function create_dashboard_workflow( $data = array() ) {		
			// Get data from request if not passed directly
			if ( empty( $data ) ) {
				$data = ig_es_get_request_data( 'data', array(), false );
			}
			
			// Handle if data is JSON string
			if ( is_string( $data ) ) {
				$decoded = json_decode( $data, true );
				if ( $decoded ) {
					$data = $decoded;
				}
			}
			
			$workflow_type = isset( $data['workflow_type'] ) ? sanitize_text_field( $data['workflow_type'] ) : '';
			
			
			// Check for abandoned cart email - requires pro plan
			if ('abandoned-cart-email' === $workflow_type  ||  'abandoned-cart' === $workflow_type ) {
				$is_pro = ES()->is_pro();
				$plan = ES()->get_plan();
				
				if ( ! $is_pro ) {
					return array(
						'success' => false,
						'message' => __( 'Abandoned cart email workflow requires a Pro plan. Please upgrade to access this feature.', 'email-subscribers' ),
						'action' => 'redirect_to_pricing',
						'requires_upgrade' => true,
						'current_plan' => $plan,
						'pricing_url' => admin_url( 'admin.php?page=es_pricing' ),
						'workflow_type' => $workflow_type
					);
				}
			}
			
			$workflow_gallery_map = array(
				'welcome-email' => 'welcome-email',
				'confirmation-email' => 'confirmation-email',
				'unsubscribe-email' => 'unsubscribe-email',
				'abandoned-cart-email' => 'abandoned-cart-basic-email',
				'abandoned-cart' => 'abandoned-cart-basic-email',
			);
			
			if ( empty( $workflow_type ) || ! isset( $workflow_gallery_map[ $workflow_type ] ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid workflow type.', 'email-subscribers' )
				);
			}
			
			$gallery_item_name = $workflow_gallery_map[ $workflow_type ];
			
			// Create workflow
			$args = array( 'item_name' => $gallery_item_name );
			$workflow_id = ES_Workflows_Controller::create_workflow_from_gallery_item( $args );
			
			if ( ! $workflow_id ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to create workflow. Please try again.', 'email-subscribers' )
				);
			}
			
			// Convert to integer and verify
			$workflow_id = intval( $workflow_id );
			
			// Verify workflow was created
			$workflow_from_db = ES()->workflows_db->get_workflow( $workflow_id );
			
			if ( ! $workflow_from_db ) {
				return array(
					'success' => false,
					'message' => __( 'Workflow was not created properly.', 'email-subscribers' )
				);
			}
			
			// Ensure we have the correct ID from database
			$db_workflow_id = isset( $workflow_from_db['id'] ) ? intval( $workflow_from_db['id'] ) : $workflow_id;
			if ( $db_workflow_id !== $workflow_id ) {
				$workflow_id = $db_workflow_id;
			}
			
			$edit_url = admin_url( "admin.php?page=es_workflows&action=edit&id={$workflow_id}" );
			
			// Get workflow display name
			$workflow_names = array(
				'welcome-email' => __( 'Welcome Email', 'email-subscribers' ),
				'confirmation-email' => __( 'Confirmation Email', 'email-subscribers' ),
				'unsubscribe-email' => __( 'Unsubscribe Email', 'email-subscribers' ),
				'abandoned-cart-email' => __( 'Abandoned Cart Email', 'email-subscribers' ),
				'abandoned-cart' => __( 'Abandoned Cart Email', 'email-subscribers' ),
			);
			$workflow_display_name = isset( $workflow_names[ $workflow_type ] ) ? $workflow_names[ $workflow_type ] : __( 'Workflow', 'email-subscribers' );
			
			// Return success response
			$success_response = array(
				'success' => true,
				'workflow_id' => intval( $workflow_id ),
				'edit_url' => $edit_url,
				/* translators: %s: workflow display name */
				'message' => sprintf( __( '%s workflow created successfully!', 'email-subscribers' ), $workflow_display_name )
			);
			return $success_response;
		}
		
		public static function get_icegram_plugins_info() {
			global $ig_es_tracker;
			
			// Get the admin URL base
			$admin_url_base = admin_url();
			
			$icegram_plugins = array(
				'icegram-mailer/icegram-mailer.php' => array(
					'name' => __( 'Icegram Mailer', 'email-subscribers' ),
					'description' => __( 'Use our built-in service, Mailer, for stress-free email delivery. No SMTP, no tech setup required. Send emails that actually reach inboxes.', 'email-subscribers' ),
					'plugin_url' => 'https://wordpress.org/plugins/icegram-mailer/',
					'install_url' => $admin_url_base . 'plugin-install.php?s=icegram%2520mailer&tab=search&type=term',
					'activate_url' => wp_nonce_url( $admin_url_base . 'plugins.php?action=activate&plugin=icegram-mailer/icegram-mailer.php', 'activate-plugin_icegram-mailer/icegram-mailer.php' ),
				),
				'icegram/icegram.php' => array(
					'name' => __( 'Icegram Engage', 'email-subscribers' ),
					'description' => __( 'The best WP popup plugin that creates a popup. Customize popup, target popups to show offers, email signups, social buttons, etc and increase conversions on your website.', 'email-subscribers' ),
					'plugin_url' => 'https://wordpress.org/plugins/icegram/',
					'install_url' => $admin_url_base . 'plugin-install.php?s=icegram&tab=search&type=term',
					'activate_url' => wp_nonce_url( $admin_url_base . 'plugins.php?action=activate&plugin=icegram/icegram.php', 'activate-plugin_icegram/icegram.php' ),
				),
				'icegram-rainmaker/icegram-rainmaker.php' => array(
					'name' => __( 'Icegram Collect', 'email-subscribers' ),
					'description' => __( 'Get readymade contact forms, email subscription forms and custom forms for your website. Choose from beautiful templates and get started within seconds', 'email-subscribers' ),
					'plugin_url' => 'https://wordpress.org/plugins/icegram-rainmaker/',
					'install_url' => $admin_url_base . 'plugin-install.php?s=rainmaker&tab=search&type=term',
					'activate_url' => wp_nonce_url( $admin_url_base . 'plugins.php?action=activate&plugin=icegram-rainmaker/icegram-rainmaker.php', 'activate-plugin_icegram-rainmaker/icegram-rainmaker.php' ),
				),
			);
			
			$active_plugins = $ig_es_tracker::get_active_plugins();
			$all_plugins = $ig_es_tracker::get_plugins( 'all', true );
			
			$plugins_info = array();
			
			foreach ( $icegram_plugins as $plugin_slug => $plugin_data ) {
				$is_installed = $ig_es_tracker::is_plugin_installed( $plugin_slug );
				$is_active = $ig_es_tracker::is_plugin_activated( $plugin_slug );
				
				$plugins_info[] = array(
					'slug' => $plugin_slug,
					'name' => $plugin_data['name'],
					'description' => $plugin_data['description'],
					'plugin_url' => $plugin_data['plugin_url'],
					'is_installed' => $is_installed,
					'is_active' => $is_active,
					'install_url' => $plugin_data['install_url'],
					'activate_url' => $plugin_data['activate_url'],
					'status_text' => $is_active ? __( 'Active', 'email-subscribers' ) : ( $is_installed ? __( 'Installed', 'email-subscribers' ) : __( 'Not Installed', 'email-subscribers' ) ),
					'action_text' => $is_active ? __( 'Active', 'email-subscribers' ) : ( $is_installed ? __( 'Activate', 'email-subscribers' ) : __( 'Install', 'email-subscribers' ) ),
					'action_url' => $is_active ? '' : ( $is_installed ? $plugin_data['activate_url'] : $plugin_data['install_url'] ),
				);
			}
			
			return $plugins_info;
		}
		
		public static function get_audience_activities() {
			$recent_activities_args = array(
				'limit'    => 5,
				'order_by' => 'updated_at',
				'order'    => 'DESC',
				'type' => array(
					IG_CONTACT_SUBSCRIBE,
					IG_CONTACT_UNSUBSCRIBE
				)
			);
			$recent_actions    = ES()->actions_db->get_actions( $recent_activities_args );
			$recent_activities = self::prepare_activities_from_actions( $recent_actions );
			
			return $recent_activities;
		}

		public static function prepare_activities_from_actions( $actions ) {
			$activities = array();
			if ( $actions ) {
				$contact_ids      = array_column( $actions, 'contact_id' );
				$contact_ids      = array_filter( $contact_ids, array( 'ES_Common', 'is_positive_number' ) );
				$contacts_details = array();
				if ( ! empty( $contact_ids ) ) {
					$contact_ids      = array_map( 'intval', $contact_ids );
					$contacts_details = ES()->contacts_db->get_details_by_ids( $contact_ids );
				}
				$list_ids   = array_column( $actions, 'list_id' );
				$list_ids   = array_filter( $list_ids, array( 'ES_Common', 'is_positive_number' ) );
				$lists_name = array();
				if ( ! empty( $list_ids ) ) {
					$list_ids   = array_map( 'intval', $list_ids );
					$lists_name = ES()->lists_db->get_list_name_by_ids( $list_ids );
				}
			
				foreach ( $actions as $action ) {
					$action_type   = $action['type'];
					$contact_id    = $action['contact_id'];
					$contact_email = ! empty( $contacts_details[ $contact_id ]['email'] ) ? $contacts_details[ $contact_id ]['email'] : '';
					if ( empty( $contact_email ) ) {
						continue;
					}
					$contact_first_name = ! empty( $contacts_details[ $contact_id ]['first_name'] ) ? $contacts_details[ $contact_id ]['first_name'] : '';
					if ( ! empty( $contact_first_name ) ) {
						$contact_info_text = $contact_first_name;
						if ( !  empty( $contacts_details[ $contact_id ]['last_name'] ) ) {
							$contact_info_text .= ' ' . $contacts_details[ $contact_id ]['last_name'];
						}
					} else {
						$contact_info_text = $contact_email;
					}
					
					$contact_info_text = '<a href="?page=es_subscribers&action=edit&subscriber=' . $contact_id . '" class="text-indigo-600" target="_blank">' . $contact_info_text . '</a>';
					$action_verb       = ES()->actions->get_action_verb( $action_type );
					$action_created_at = $action['created_at'];
					$activity_time     = human_time_diff( time(), $action_created_at ) . ' ' . __( 'ago', 'email-subscribers' );
					
					$list_id         = ! empty( $action['list_id'] ) ? $action['list_id'] : 0;
					$list_name       = ! empty( $lists_name[ $list_id ] ) ? $lists_name[ $list_id ] : '';
					$action_obj_name = '<a href="?page=es_lists&action=edit&list=' . $list_id . '" target="_blank">' . $list_name . '</a> ' . __( 'list', 'email-subscribers' );
					$activity_text = $contact_info_text . ' ' . $action_verb . ' ' . $action_obj_name;
					$activities[]  = array(
						'time' => $activity_time,
						'text' => $activity_text,
					);
				}
			}

			return $activities;
		}

		public static function get_recent_campaigns_kpis( $campaign_id ) {
			$args = array(
				'campaign_id' => $campaign_id,
				'types' => array(
					IG_MESSAGE_SENT,
					IG_MESSAGE_OPEN,
					IG_LINK_CLICK
				)
			);
			$actions_count       = ES()->actions_db->get_actions_count( $args );
			$total_email_sent    = $actions_count['sent'];
			$total_email_opened  = $actions_count['opened'];
			$total_email_clicked = $actions_count['clicked'];
			$open_rate  = ! empty( $total_email_sent ) ? number_format_i18n( ( ( $total_email_opened * 100 ) / $total_email_sent ), 2 ) : 0 ;
			$click_rate = ! empty( $total_email_sent ) ? number_format_i18n( ( ( $total_email_clicked * 100 ) / $total_email_sent ), 2 ) : 0;
			$campaign['open_rate']  = $open_rate;
			$campaign['click_rate'] = $click_rate;
			$campaign['total_email_sent'] = $total_email_sent;

			return $campaign;
		}
	
	}

}

ES_Dashboard_Controller::get_instance();
