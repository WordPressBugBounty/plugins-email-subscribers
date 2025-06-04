<?php

if ( ! class_exists( 'ES_Dashboard_Controller' ) ) {

	/**
	 * Class to handle dashboard operation
	 * 
	 * @class ES_Dashboard_Controller
	 */
	class ES_Dashboard_Controller {

		// class instance
		public static $instance;
		public static $api_instance = null;

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

		public static function get_subscribers_stats( $args = array() ) {
			
			$page           = isset( $args['page'] ) ? $args['page'] : 'es_dashboard';
			$days           = isset( $args['days'] ) ? $args['days'] : '';
			$list_id        = isset( $args['list_id'] ) ? $args['list_id'] : '';
			$override_cache = isset( $args['override_cache'] ) ? $args['override_cache'] : true;

			$reports_args = array(
				'list_id' => $list_id,
				'days'    => $days,
			);
			
			$reports_data   = ES_Reports_Data::get_dashboard_reports_data( $page, $override_cache, $reports_args  );
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
		
			// Forms
			$forms_args = array(
				'order_by_column' => 'ID',
				'limit'           => '2',
				'order'           => 'DESC',
			);
			$forms = ES()->forms_db->get_forms( $forms_args );
		
			// Lists
			$lists = array_slice( array_reverse( ES()->lists_db->get_lists() ), 0, 2 );
		
			return array(
				'campaigns'         => $campaigns,
				'audience_activity' => $audience_activity,
				'forms'             => $forms,
				'lists'             => $lists,
				'dashboard_kpi'     => $dashboard_kpi,
			);
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
