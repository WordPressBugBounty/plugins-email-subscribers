<?php

if ( ! class_exists( 'ES_Contact_Export_Controller' ) ) {

	/**
	 * Class to handle contact export operation
	 * 
	 * @class ES_Contact_Export_Controller
	 */
	class ES_Contact_Export_Controller {

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

		public static function process_list_export( $args = array() ) {
			$status = trim( $args['status'] );
			$selected_list_id = $args['selected_list_id'];
		
			$csv       = self::generate_csv( $status, $selected_list_id );
			$file_name = sprintf( '%s-contacts.csv', strtolower( $status ) );
			self::output_CSV( $csv, $file_name );
		}

		public static function process_campaign_link_export( $args = array() ) {
			$campaign_id = $args['campaign_id'];
			$link_id     = $args['link_id'];
		
			$subscribers = ES()->actions_db->get_link_cliked_subscribers( $campaign_id, $link_id );
		
			if ( count( $subscribers ) > 0 ) {
				$sub_headers = [
					__( 'First Name', 'email-subscribers' ),
					__( 'Last Name', 'email-subscribers' ),
					__( 'Email', 'email-subscribers' ),
				];
		
				$csv = implode( ',', $sub_headers ) . "\n";
		
				foreach ( $subscribers as $subscriber ) {
					$data = [
						'first_name' => self::escape_and_trim_data( $subscriber['first_name'] ),
						'last_name'  => self::escape_and_trim_data( $subscriber['last_name'] ),
						'email'      => self::escape_and_trim_data( $subscriber['email'] ),
					];
					$csv .= '"' . implode( '","', $data ) . "\"\n";
				}
			} else {
				self::show_error_message( __( 'No data available', 'email-subscribers' ) );
			}
		
			self::output_CSV( $csv, 'subscriber-contacts.csv' );
		}

		private static function show_error_message( $message) {
			ES_Common::show_message($message, 'error');
			exit();
		}

		private static function output_CSV( $csv_content, $file_name) {
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private', false);
			header('Content-Type: application/octet-stream');
			header("Content-Disposition: attachment; filename=$file_name");
			header('Content-Transfer-Encoding: binary');
		
			echo wp_kses_post($csv_content);
			exit();
		}
				
		/**
		 * Generate CSV
		 * first_name, last_name, email, status, list, subscribed_at, unsubscribed_at
		 *
		 * @param string $status
		 * @param string $list_id
		 *
		 * @return string
		 */
		public static function generate_csv( $status = 'all', $list_id = 0 ) {

			global $wpbd;

			// Add filter to increase memory limit
			add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

			wp_raise_memory_limit( 'ig_es' );

			// Remove the added filter function so that it won't be called again if wp_raise_memory_limit called later on.
			remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

			set_time_limit( IG_SET_TIME_LIMIT );

			$results = array();
			if ( 'all' === $status ) {
				$results = ES()->lists_contacts_db->get_all_contacts();
			} elseif ( 'subscribed' === $status ) {
				$results = ES()->lists_contacts_db->get_all_subscribed_contacts();
			} elseif ( 'unsubscribed' === $status ) {
				$results = ES()->lists_contacts_db->get_all_unsubscribed_contacts();
			} elseif ( 'confirmed' === $status ) {
				$results = ES()->lists_contacts_db->get_all_confirmed_contacts();
			} elseif ( 'unconfirmed' === $status ) {
				$results = ES()->lists_contacts_db->get_all_unconfirmed_contacts();
			} elseif ( 'select_list' === $status ) {
				$list_id = absint( $list_id );
				$results = ES()->lists_contacts_db->get_all_contacts_from_list( $list_id );
			}

			$subscribers = array();

			if ( count( $results ) > 0 ) {
				$contact_list_map = array();
				$contact_ids      = array();
				foreach ( $results as $result ) {

					if ( ! in_array( $result['contact_id'], $contact_ids, true ) ) {
						$contact_ids[] = $result['contact_id'];
					}

					$contact_list_map[ $result['contact_id'] ][] = array(
						'status'     => $result['status'],
						'list_id'    => $result['list_id'],
						'optin_type' => $result['optin_type'],
					);
				}

				$contact_ids_str = implode( ',', $contact_ids );

				$select_columns = array(
					'id',
					'first_name',
					'last_name',
					'email',
					'created_at',
				);

				$custom_fields = ES()->custom_fields_db->get_custom_fields();
				if ( ! empty( $custom_fields ) ) {
					foreach ( $custom_fields as $field ) {
						$select_columns[] = $field['slug'];
					}
				}

				$query = 'SELECT ' . implode( ',', $select_columns ) . " FROM {$wpbd->prefix}ig_contacts WHERE id IN ({$contact_ids_str})";

				$subscribers = $wpbd->get_results( $query, ARRAY_A );
			}

			$csv_output = '';
			if ( count( $subscribers ) > 0 ) {

				$headers = array(
					__( 'First Name', 'email-subscribers' ),
					__( 'Last Name', 'email-subscribers' ),
					__( 'Email', 'email-subscribers' ),
					__( 'List', 'email-subscribers' ),
					__( 'Status', 'email-subscribers' ),
					__( 'Opt-In Type', 'email-subscribers' ),
					__( 'Created On', 'email-subscribers' ),
				);

				if ( ! empty( $custom_fields ) ) {
					foreach ( $custom_fields as $field ) {
						$headers[] = $field['label'];
					}
				}

				$lists_id_name_map = ES()->lists_db->get_list_id_name_map();
				$csv_output       .= implode( ',', $headers );

				foreach ( $subscribers as $key => $subscriber ) {

					$data 				= array();
					$data['first_name'] = trim( str_replace( '"', '""', self::escape_data( $subscriber['first_name'] ) ) );
					$data['last_name']  = trim( str_replace( '"', '""', self::escape_data( $subscriber['last_name'] ) ) );
					$data['email']      = trim( str_replace( '"', '""', self::escape_data( $subscriber['email'] ) ) );

					$contact_id = $subscriber['id'];
					if ( ! empty( $contact_list_map[ $contact_id ] ) ) {
						foreach ( $contact_list_map[ $contact_id ] as $list_details ) {
							$data['list']       = $lists_id_name_map[ $list_details['list_id'] ];
							$data['status']     = ucfirst( $list_details['status'] );
							$data['optin_type'] = ( 1 == $list_details['optin_type'] ) ? 'Single Opt-In' : 'Double Opt-In';
							$data['created_at'] = $subscriber['created_at'];
							if ( ! empty( $custom_fields ) ) {
								foreach ( $custom_fields as $field ) {
									$column_name = $field['slug'];
									$data[ $column_name ] = $subscriber[ $column_name ];
								}
							}
							$csv_output        .= "\n";
							$csv_output        .= '"' . implode( '","', $data ) . '"';
						}
					}
				}
			}

			return $csv_output;
		}

		/**
		 * Count total subscribers
		 *
		 * @param string $status
		 *
		 * @return string|null
		 */
		public static function count_subscribers( $status = 'all' ) {

			global $wpdb;

			switch ( $status ) {
				case 'all':
					return ES()->lists_contacts_db->get_all_contacts_count( 0, false );
				break;

				case 'subscribed':
					return ES()->lists_contacts_db->get_subscribed_contacts_count( 0, false );
				break;

				case 'unsubscribed':
					return ES()->lists_contacts_db->get_unsubscribed_contacts_count( 0, false );
				break;

				case 'confirmed':
					return ES()->lists_contacts_db->get_confirmed_contacts_count( 0, false );
				break;

				case 'unconfirmed':
					return ES()->lists_contacts_db->get_unconfirmed_contacts_count( 0, false );
				break;

				case 'select_list':
				default:
					return '-';
				break;
			}

		}
		/**
		 * Escape a string to be used in a CSV context
		 *
		 * Malicious input can inject formulas into CSV files, opening up the possibility
		 * for phishing attacks and disclosure of sensitive information.
		 *
		 * Additionally, Excel exposes the ability to launch arbitrary commands through
		 * the DDE protocol.
		 *
		 * @see http://www.contextis.com/resources/blog/comma-separated-vulnerabilities/
		 * @see https://hackerone.com/reports/72785
		 *
		 * @since 5.5.3
		 * @param string $data CSV field to escape.
		 * @return string
		 */
		private static function escape_data( $data ) {
			$active_content_triggers = array( '=', '+', '-', '@' );

			if ( in_array( mb_substr( $data, 0, 1 ), $active_content_triggers, true ) ) {
				$data = "'" . $data;
			}

			return $data;
		}

		private static function escape_and_trim_data( $data) {
			return trim(str_replace('"', '""', self::escape_data($data)));
		}
		
	}

}

ES_Contact_Export_Controller::get_instance();
