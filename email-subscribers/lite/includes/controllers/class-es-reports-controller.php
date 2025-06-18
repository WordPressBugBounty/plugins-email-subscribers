<?php

if ( ! class_exists( 'ES_Reports_Controller' ) ) {

	/**
	 * Class to handle reports 
	 * 
	 * @class ES_Reports_Controller
	 */
	class ES_Reports_Controller {

		// class instance
		public static $instance;

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


		public static function get_notifications( $args = array() ) {
			global $wpdb, $wpbd;
			
			$order_by    = isset( $args['order_by'] ) ? $args['order_by'] : '';
			$order       = isset( $args['order'] ) ? $args['order'] : '';
			$campaign_id = isset( $args['campaign_id'] ) ? $args['campaign_id'] : '';
			$per_page = isset( $args['per_page'] ) ? $args['per_page'] : 5;
			$page_number = isset( $args['page_number'] ) ? $args['page_number'] : 1;
			$do_count_only = isset( $args['do_count_only'] ) ? $args['do_count_only'] : false;
			$search = isset( $args['search'] ) ? $args['search'] : '';
			$filter_reports_by_campaign_status = isset( $args['filter_reports_by_campaign_status'] ) ? $args['filter_reports_by_campaign_status'] : '';
			$filter_reports_by_campaign_type = isset( $args['filter_reports_by_campaign_type'] ) ? $args['filter_reports_by_campaign_type'] : '';
			$filter_reports_by_month_year = isset( $args['filter_reports_by_month_year'] ) ? $args['filter_reports_by_month_year'] : '';
				
			$ig_mailing_queue_table = IG_MAILING_QUEUE_TABLE;
	
			if ( $do_count_only ) {
				$sql = "SELECT count(*) as total FROM {$ig_mailing_queue_table}";
			} else {
				$sql = "SELECT * FROM {$ig_mailing_queue_table}";
			}
	
			$where_columns    = array();
			$where_args       = array();
			$add_where_clause = true;
	
			if ( ! empty( $campaign_id ) && is_numeric( $campaign_id ) ) {
				$where_columns[] = 'campaign_id = %d';
				$where_args[]    = $campaign_id;
			}
	
			if ( ! empty( $filter_reports_by_month_year ) ) {
	
				if ( preg_match('/^[0-9]{6}$/', $filter_reports_by_month_year) ) {
	
					$year_val  = substr($filter_reports_by_month_year, 0, 4);
					$month_val = substr($filter_reports_by_month_year, 4 );
	
					$date_string = $year_val . '-' . $month_val;
					$date        = new DateTime($date_string);
	
					$start_date = $date->format('Y-m-01 H:i:s') ;
					$end_date   = $date->format('Y-m-t H:i:s');
	
					array_push( $where_columns, 'start_at >= %s', 'start_at <= %s' );
					array_push($where_args, $start_date, $end_date);
				}
			}
			
			$where_query = '';
			if ( ! empty( $where_columns ) ) {
				$where_query = implode( ' AND ', $where_columns );
				$where_query = $wpbd->prepare( $where_query, $where_args );
			}
	
			if ( ! empty( $where_query ) ) {
				$sql             .= ' WHERE ' . $where_query;
				$add_where_clause = false;
			}
	
			if ( ! empty( $filter_reports_by_campaign_status ) || ( '0' === $filter_reports_by_campaign_status ) ) {
				if ( ! $add_where_clause ) {
					$sql .= $wpdb->prepare( ' AND status = %s', $filter_reports_by_campaign_status );
				} else {
					$sql             .= $wpdb->prepare( ' WHERE status = %s', $filter_reports_by_campaign_status );
					$add_where_clause = false;
				}
			}
	
			if ( ! empty( $filter_reports_by_campaign_type ) ) {
				if ( ! $add_where_clause ) {
					$sql .= $wpdb->prepare( ' AND meta LIKE %s', '%' . $wpdb->esc_like( $filter_reports_by_campaign_type ) . '%' );
				} else {
					$sql .= $wpdb->prepare( ' WHERE meta LIKE %s', '%' . $wpdb->esc_like( $filter_reports_by_campaign_type ) . '%' );
				}
			}
	
			if ( ! $do_count_only ) {
	
				// Prepare Order by clause
				$order = ! empty( $order ) ? strtolower( $order ) : 'desc';
	
				$expected_order_values = array( 'asc', 'desc' );
				if ( ! in_array( $order, $expected_order_values ) ) {
					$order = 'desc';
				}
	
				$default_order_by = esc_sql( 'created_at' );
	
				$expected_order_by_values = array( 'subject', 'type', 'status', 'start_at', 'count', 'created_at' );
	
				if ( ! in_array( $order_by, $expected_order_by_values ) ) {
					$order_by_clause = " ORDER BY {$default_order_by} DESC";
				} else {
					$order_by        = esc_sql( $order_by );
					$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
				}
	
				$sql   .= $order_by_clause;
				$sql   .= " LIMIT $per_page";
				$sql   .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
				$result = $wpbd->get_results( $sql, 'ARRAY_A' );
	
			} else {
				$result = $wpbd->get_var( $sql );
			}
	
			return $result;
		}

		public static function delete_notification( $args = array() ) {
			$notification_ids = isset( $args['notification_ids'] ) ? (array) $args['notification_ids'] : array();
		
			if ( ! empty( $notification_ids ) ) {
				ES_DB_Mailing_Queue::delete_notifications( $notification_ids );
				ES_DB_Sending_Queue::delete_by_mailing_queue_id( $notification_ids );
			}
			return true;
		}


		/**
	 * Get view activity table data
	 */
		public static function get_activity_table_data( $args = array() ) {

			global $wpbd;

			$hash = isset($args['hash']) ? $args['hash'] : '';
			$campaign_id = isset($args['campaign_id']) ? $args['campaign_id'] : '';
			$filter_by_status = isset($args['filter_by_status']) ? $args['filter_by_status'] : '';
			$filter_by_country = isset($args['filter_by_country']) ? $args['filter_by_country'] : '';
			$search = isset($args['search']) ? $args['search'] : '';
			$orderby = isset($args['orderby']) ? $args['orderby'] : '';
			$order = isset($args['order']) ? $args['order'] : 'DESC';  // default DESC
			$page_number = isset($args['page_number']) ? $args['page_number'] : 1;  // default 1
			$return_count = isset($args['return_count']) ? $args['return_count'] : false;

			$message_id            = 0;
			$view_activity_data    = array();
			$delivery_table_exists = false;
			$selects               = array();

			if ( ! empty( $hash ) ) {
				$notification_data_from_hash = ES_DB_Mailing_Queue::get_notification_by_hash( $hash );
				$campaign_id                 = $notification_data_from_hash['campaign_id'];
				$message_id                  = $notification_data_from_hash['id'];
				$delivery_table_exists       = ES()->campaigns_db->table_exists( $wpbd->prefix . 'es_deliverreport' );

				// We are assigning NULL values to sent_at and opened_at columns as actions tables have NULL values for these columns when no data is present in the column.
				// Assigning NULL ensures sorting works as expected when both the tables are combined.
				$queue_query = "SELECT queue.contact_id AS `contact_id`, queue.email AS `email`, 0 AS `type`, NULL AS `sent_at`, NULL AS `opened_at`, queue.status, '' AS `country`, '' AS `device`, '' AS `email_client`, '' AS `os`
			FROM {$wpbd->prefix}ig_sending_queue AS queue
			WHERE `mailing_queue_id` = %d AND `contact_id` NOT IN ( SELECT `contact_id` FROM {$wpbd->prefix}ig_actions WHERE campaign_id = %d AND message_id = %d )";

				$delivery_query = $wpbd->prepare(
				"SELECT
				es_deliver_emailid AS `contact_id`,
				es_deliver_emailmail AS `email`,
				0 AS `type`,
				UNIX_TIMESTAMP(es_deliver_sentdate) AS `sent_at`,
				UNIX_TIMESTAMP(es_deliver_viewdate) AS `opened_at`,
				es_deliver_sentstatus AS `status`,
				'' AS `country`,
				'' AS `device`,
				'' AS `email_client`,
				'' AS `os`
				FROM {$wpbd->prefix}es_deliverreport WHERE es_deliver_sentguid = %s",
				array( $hash )
				);

				$selects[] = $wpbd->prepare( $queue_query, $message_id, $campaign_id, $message_id );
			}

			$action_query = "SELECT
		MAX(contacts.id) AS `contact_id`,
		contacts.email AS `email`,
		MAX(actions.type) AS `type`,
		MAX(CASE WHEN actions.type = %d THEN actions.created_at END) AS `sent_at`,
		MAX(CASE WHEN actions.type = %d THEN actions.created_at END) AS `opened_at`,
		CASE WHEN MAX(actions.type) = %d THEN 'Sent' WHEN MAX(actions.type) = %d THEN 'Opened' END AS `status`,
		MAX(actions.country) AS `country`,
		MAX(actions.device) AS `device`,
		MAX(actions.email_client) AS `email_client`,
		MAX(actions.os) AS `os`
		FROM {$wpbd->prefix}ig_actions AS actions
		LEFT JOIN {$wpbd->prefix}ig_contacts AS contacts ON actions.contact_id = contacts.id
		WHERE actions.campaign_id = %d AND actions.message_id = %d AND actions.type IN (%d, %d)
		GROUP BY email";

			$query_args = array(
			IG_MESSAGE_SENT,
			IG_MESSAGE_OPEN,
			IG_MESSAGE_SENT,
			IG_MESSAGE_OPEN,
			$campaign_id,
			$message_id,
			IG_MESSAGE_SENT,
			IG_MESSAGE_OPEN,
			);

			$selects[] = $wpbd->prepare( $action_query, $query_args );

			if ( $return_count ) {
				$notification_query = 'SELECT count(*) FROM ( ';
			} else {
				$notification_query = 'SELECT * FROM ( ';
			}
			$notification_query .= implode( ' UNION ALL ', $selects );
			$notification_query .= ') AS `activity`';

			$notification       = ES()->campaigns_db->get( $campaign_id );
			$total_email_sent   = ES()->actions_db->get_count_based_on_id_type( $notification['id'], $message_id, IG_MESSAGE_SENT );
			$email_viewed_count = ES()->actions_db->get_count_based_on_id_type( $notification['id'], $message_id, IG_MESSAGE_OPEN );

			$notification_query .= ' WHERE 1';

			$search_query = '';
			if ( ! empty( $search ) ) {
				$search_query = $wpbd->prepare( ' AND email LIKE %s', '%' . $wpbd->esc_like( $search ) . '%' );
			}

			$status_query = '';
			if ( ! empty( $filter_by_status ) ) {
				$status       = 'not_opened' === $filter_by_status ? 'Sent' : 'Opened';
				$status_query = $wpbd->prepare( ' AND `status` = %s', $status );
			}

			$country_query = '';
			if ( ! empty( $filter_by_country ) ) {
				$country_query = $wpbd->prepare( ' AND `country` = %s', $filter_by_country );
			}

			$order_by_query = '';
			$offset         = 0;

			if ( ! $return_count ) {

				if ( empty( $orderby ) ) {
					// By default sort by opened_at and sent_at columns.
					$orderby = "`opened_at` {$order}, `sent_at` {$order}";
				} else {
					$orderby = "{$orderby} {$order}";
				}
				$orderby = sanitize_sql_orderby( $orderby );
				if ( $orderby ) {
					$per_page = 100;
					$offset   = $page_number > 1 ? ( $page_number - 1 ) * $per_page : 0;

					$order_by_query = " ORDER BY {$orderby} LIMIT {$offset}, {$per_page}";
				}

			}

			$notification_query .= $search_query . $status_query . $country_query . $order_by_query;
			if ( $return_count ) {
				$count = $wpbd->get_var( $notification_query );
				if ( empty( $count ) && $delivery_table_exists ) {
					$count_query  = 'SELECT count(*) FROM ( ' . $delivery_query . ' ) AS delivery_report WHERE 1';
					$count_query .= $search_query . $status_query . $country_query . $order_by_query;

					// If no results exists then check data into es_deliverreport table as earlier version were using this table.
					$count = $wpbd->get_var(
					$count_query
					);
				}
				return $count;
			} else {
				$results = $wpbd->get_results( $notification_query, ARRAY_A );

				// If no results exists then check data into es_deliverreport table as earlier version were using this table.
				if ( empty( $results ) && $delivery_table_exists ) {

					$delivery_query  = 'SELECT * FROM ( ' . $delivery_query . ' ) AS delivery_report WHERE 1';
					$delivery_query .= $search_query . $status_query . $country_query . $order_by_query;

					$results = $wpbd->get_results(
					$delivery_query,
					ARRAY_A
					);
				}

				$sr_no = $offset + 1;
				if ( ! empty( $results ) ) {
					$date_format = get_option( 'date_format' );
					$time_format = get_option( 'time_format' );
					$gmt_offset  = ig_es_get_gmt_offset( true );
					$format      = $date_format . ' ' . $time_format;
					foreach ( $results as $notification_action ) {

						$contact_id = $notification_action['contact_id'];
						$sent_at 	= '';
						if ( ! empty( $notification_action['sent_at'] ) ) {
							$sent_timestamp  = (int) $notification_action['sent_at'];
							$sent_timestamp += $gmt_offset;
							$sent_at         = ES_Common::convert_timestamp_to_date( $sent_timestamp, $format );
						}

						$opened_at = '';
						if ( ! empty( $notification_action['opened_at'] ) ) {
							$opened_timestamp  = (int) $notification_action['opened_at'];
							$opened_timestamp += $gmt_offset;
							$opened_at         = ES_Common::convert_timestamp_to_date( $opened_timestamp, $format );
						}

						$view_activity_data[ $contact_id ] = array(
						'sr_no'        => $sr_no++,
						'email'        => $notification_action['email'],
						'opened_at'    => $opened_at,
						'sent_at'      => $sent_at,
						'status'       => $notification_action['status'],
						'country_flag' => '',
						'device'       => '',
						'email_client' => '',
						'os'           => '',
						);

						$view_activity_data = apply_filters( 'additional_es_report_activity_data', $view_activity_data, $contact_id, $notification_action );
					}
				}
			}

			if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$insight  = ig_es_get_request_data( 'insight', '' );
				$_wpnonce = ig_es_get_request_data( '_wpnonce', '' );

				if ( ( ES()->is_pro() || $insight ) && 0 !== $message_id ) {
					do_action( 'ig_es_view_report_data', $hash );
				}
				?>

			<div>
				<?php if ( ! ES()->is_pro() && ! $insight ) { ?>
					<?php do_action( 'ig_es_view_report_data_lite', $hash ); ?>
					<a href="?page=es_reports&action=view&list=<?php echo esc_attr( $hash ); ?>&_wpnonce=<?php echo esc_attr( $_wpnonce ); ?>&insight=true" class="campaign-analitics-btn"><button type="button" class="primary"><?php esc_html_e( 'Campaign Analytics', 'email-subscribers' ); ?></button></a>
				<?php } ?>
			</div>
			<div class="mt-2 mb-2 inline-block relative es-activity-viewed-count">
				<span class="pt-3 pb-4 leading-5 tracking-wide text-gray-600"><?php echo esc_html( '(Viewed ' . number_format( $email_viewed_count ) . '/' . number_format( $total_email_sent ) . ')' ); ?>
				</span>
			</div>
			<?php
			}

			return $view_activity_data;
		}

	/**
	 * Get individual reports data
	 *
	 * @version 5.4.2
	 */
		public static function view_report_data_lite( $id ) {
			global $wpdb;

			$notification             = ES_DB_Mailing_Queue::get_notification_by_hash( $id );
			$report_id                = $notification['id'];
			$notification_campaign_id = $notification['campaign_id'];
			$total_email_sent         = ES()->actions_db->get_count_based_on_id_type( $notification_campaign_id, $report_id, IG_MESSAGE_SENT );
			$email_viewed_count       = ES()->actions_db->get_count_based_on_id_type( $notification_campaign_id, $report_id, IG_MESSAGE_OPEN );
			//--->
			$email_unsubscribed_count = ES()->actions_db->get_count_based_on_id_type( $notification_campaign_id, $report_id, IG_CONTACT_UNSUBSCRIBE );
			$avg_unsubscribed_rate    =	!empty($total_email_sent) ? number_format_i18n(( ( $email_unsubscribed_count/$total_email_sent ) * 100 ), 2) : 0;
			//--->
			$avg_open_rate            = ! empty( $total_email_sent) ? number_format_i18n( ( ( $email_viewed_count * 100 ) / $total_email_sent ), 2 ) : 0;
			$email_click_count        = ! empty( $notification_campaign_id ) ? ES()->actions_db->get_count_based_on_id_type( $notification_campaign_id, $report_id, IG_LINK_CLICK ) : 0;
			$avg_click_rate           = ! empty( $total_email_sent ) ? number_format_i18n( ( ( $email_click_count * 100 ) / $total_email_sent ), 2 ) : 0;

			if ( empty( $notification['campaign_id'] ) ) {
				$notification_type = __( 'Post Notification', 'email-subscribers' );
			} else {
				$notification_type = ES()->campaigns_db->get_campaign_type_by_id( $notification['campaign_id'] );
				$notification_type = strtolower( $notification_type );
				$notification_type = ( 'newsletter' === $notification_type ) ? __( 'Broadcast', 'email-subscribers' ) : $notification_type;
			}

			$report_kpi_statistics = array(
				'total_email_sent'			=> number_format_i18n( $total_email_sent ),
				'email_viewed_count'		=> number_format_i18n( $email_viewed_count ),
				'email_unsubscribed_count'	=> number_format_i18n( $email_unsubscribed_count ),
				'email_click_count'			=> number_format_i18n( $email_click_count ),
				'avg_open_rate'				=> $avg_open_rate,
				'avg_click_rate'			=> $avg_click_rate,
				'avg_unsubscribed_rate'		=> $avg_unsubscribed_rate,
			);

			$notification['type']    		 = ucwords( str_replace( '_', ' ', $notification_type ) );
			$notification_subject    		 = $notification['subject'];
			$notification_campaign   	 	 = ES()->campaigns_db->get_campaign_by_id( $notification_campaign_id, - 1 );
			$notification['from_email'] 	 = ! empty( $notification_campaign['from_email'] ) ? $notification_campaign['from_email'] : '';
			$notification_lists_ids 	 	 = ! empty( $notification_campaign['list_ids'] ) ? explode( ',', $notification_campaign['list_ids'] ) : array();
			$notification['list_name'] 		 = ES_Common::prepare_list_name_by_ids( $notification_lists_ids );
			$total_contacts          		 = $notification['count'];
			$notification_status     		 = $notification['status'];
			$campaign_meta					 = ! empty( $notification_campaign['meta'] ) ? unserialize( $notification_campaign['meta']) : '';
			$notification['list_conditions'] = ! empty( $campaign_meta['list_conditions'] ) ? $campaign_meta['list_conditions'] : '';

			$where                = $wpdb->prepare( 'message_id = %d ORDER BY updated_at DESC', $report_id );
			$notification_actions = ES()->actions_db->get_by_conditions( $where );

			$links_where        = $wpdb->prepare( 'message_id = %d', $report_id );
			$notification_links = ES()->links_db->get_by_conditions( $links_where );

			$activity_data = array();
			$time_offset   = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			$date_format   = get_option( 'date_format' );

			if ( ! empty( $notification_actions ) ) {
				foreach ( $notification_actions as $notification_action ) {
					$action_type = (int) $notification_action['type'];
					if ( in_array( $action_type, array( IG_MESSAGE_OPEN, IG_LINK_CLICK ), true ) ) {
						$created_timestamp = $notification_action['created_at'];
						//$created_date      = date_i18n( $date_format, $created_timestamp + $time_offset );
						$created_date = gmdate( 'Y-m-d', $created_timestamp + $time_offset );
						if ( ! isset( $activity_data[ $created_date ] ) ) {
							$activity_data[ $created_date ] = array(
								'opened'  => 0,
								'clicked' => 0,
							);
						}
						if ( IG_MESSAGE_OPEN === $action_type ) {
							$activity_data[ $created_date ]['opened'] ++;
						} elseif ( IG_LINK_CLICK === $action_type ) {
							$activity_data[ $created_date ]['clicked'] ++;
						}
					}
				}
			}
			if ( ! empty( $activity_data ) ) {
				ksort( $activity_data );
			}

			// To display report header information and KPI values
			do_action( 'ig_es_view_report_description_lite', $notification, $report_kpi_statistics );

		}

		/**
		 * Method to preview email
		 */
		public static function preview_email_in_report( $args = array() ) {

			$response      = array();
			$email_body    = '';
			$report_id     = isset( $args['report_id'] ) ? $args['report_id'] : '';
			$campaign_type = isset( $args['campaign_type'] ) ? $args['campaign_type'] : '';

			if ( ! empty( $report_id ) ) {

				if ( ! empty( $campaign_type ) && in_array( $campaign_type, array( 'sequence_message', 'workflow_email' ), true ) ) {
					$email_body = ES()->campaigns_db->get_campaign_by_id( $report_id );
				} else {
					$email_body = ES_DB_Mailing_Queue::get_mailing_queue_by_id( $report_id );
				}
				$es_email_type             = get_option( 'ig_es_email_type' );    // Not the ideal way. Email type can differ while previewing sent email.
				$response['template_html'] = ES_Common::es_process_template_body( $email_body['body'] );

				return $response;
			}
		}

	}

}

ES_Reports_Controller::get_instance();
