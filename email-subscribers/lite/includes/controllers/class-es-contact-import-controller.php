<?php

if ( ! class_exists( 'ES_Contact_Import_Controller' ) ) {

	/**
	 * Class to handle contact import operation
	 * 
	 * @class ES_Contact_Import_Controller
	 */
	class ES_Contact_Import_Controller {

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

		public static function import_subscribers_upload_handler( $args = array() ) {
			global $wpdb;
			$response = array( 'success' => false );
	
			@set_time_limit( 0 );
			if ( (int) @ini_get( 'max_execution_time' ) < 300 ) {
				@set_time_limit( 300 );
			}
			if ( (int) @ini_get( 'memory_limit' ) < 256 ) {
				add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
				wp_raise_memory_limit( 'ig_es' );
				remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
			}
	
			$importing_from = isset( $args['importing_from'] ) ? $args['importing_from'] : '';
	
			if ( 'csv' === $importing_from && ! empty( $args['file'] ) ) {
				$tmp_file  = $args['file'] ;
				$raw_data  = file_get_contents( $tmp_file );
				$seperator = self::get_delimiter( $tmp_file );
	
				$handle  = fopen( $tmp_file, 'r' );
				$headers = array_map( 'trim', fgetcsv( $handle, 0, $seperator ) );
	
				if ( isset( $headers[0] ) ) {
					$headers[0] = ig_es_remove_utf8_bom( $headers[0] );
				}
	
				$data_contain_headers = true;
				$phpmailer            = ES()->mailer->get_phpmailer();
				foreach ( $headers as $header ) {
					if ( ! empty( $header ) ) {
						if ( is_callable( array( $phpmailer, 'punyencodeAddress' ) ) ) {
							$header = $phpmailer->punyencodeAddress( $header );
						}
						if ( is_email( $header ) ) {
							$data_contain_headers = false;
							break;
						}
					}
				}
				fclose( $handle );
	
				if ( ! $data_contain_headers ) {
					$headers = array();
				}
	
				if ( function_exists( 'mb_convert_encoding' ) ) {
					$raw_data = mb_convert_encoding( $raw_data, 'UTF-8', mb_detect_encoding( $raw_data, 'UTF-8, ISO-8859-1', true ) );
				}
			} elseif ( 'wordpress_users' === $importing_from ) {
				$roles = isset( $args['selected_roles'] ) ? $args['selected_roles'] : [];
	
				$users = $wpdb->get_results(
					"SELECT u.user_email, IF(meta_role.meta_value = 'a:0:{}',NULL,meta_role.meta_value) AS '_role', meta_firstname.meta_value AS 'firstname', meta_lastname.meta_value AS 'lastname', u.display_name, u.user_nicename
					FROM {$wpdb->users} AS u
					LEFT JOIN {$wpdb->usermeta} AS meta_role ON meta_role.user_id = u.id AND meta_role.meta_key = '{$wpdb->prefix}capabilities'
					LEFT JOIN {$wpdb->usermeta} AS meta_firstname ON meta_firstname.user_id = u.id AND meta_firstname.meta_key = 'first_name'
					LEFT JOIN {$wpdb->usermeta} AS meta_lastname ON meta_lastname.user_id = u.id AND meta_lastname.meta_key = 'last_name'
					WHERE meta_role.user_id IS NOT NULL"
				);
	
				if ( ! empty( $users ) ) {
					$raw_data             = '';
					$seperator            = ';';
					$data_contain_headers = false;
	
					$headers = array(
						__( 'Email', 'email-subscribers' ),
						__( 'First Name', 'email-subscribers' ),
						__( 'Last Name', 'email-subscribers' ),
						__( 'Nick Name', 'email-subscribers' ),
						__( 'Display Name', 'email-subscribers' ),
					);
	
					foreach ( $users as $user ) {
						if ( ! $user->_role ) {
							continue;
						}
						if ( ! empty( $roles ) && ! array_intersect( array_keys( unserialize( $user->_role ) ), $roles ) ) {
							continue;
						}
	
						$user_data = [];
						foreach ( $user as $key => $data ) {
							if ( '_role' === $key ) {
continue;
							}
							if ( 'firstname' === $key && ! $data ) {
$data = $user->display_name;
							}
							$user_data[] = $data;
						}
						$raw_data .= implode( ';', $user_data ) . "\n";
					}
				}
	
				if ( empty( $raw_data ) ) {
					$response['message'] = __( 'We can\'t find any matching users. Please update your preferences and try again.', 'email-subscribers' );
					return $response;
				}
			}
	
			if ( empty( $raw_data ) ) {
				return $response;
			}
	
			$response                = self::insert_into_temp_table( $raw_data, $seperator, $data_contain_headers, $headers, '', $importing_from );
			$response['success']     = true;
			$response['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
	
			return $response;
		}

		/**
		 * Get CSV file delimiter
		 *
		 * @param $file
		 * @param int  $check_lines
		 *
		 * @return mixed
		 *
		 * @since 4.3.1
		 */
		public static function  get_delimiter( $file, $check_lines = 2 ) {

			$file = new SplFileObject( $file );

			$delimiters = array( ',', '\t', ';', '|', ':' );
			$results    = array();
			$i          = 0;
			while ( $file->valid() && $i <= $check_lines ) {
				$line = $file->fgets();
				foreach ( $delimiters as $delimiter ) {
					$regExp = '/[' . $delimiter . ']/';
					$fields = preg_split( $regExp, $line );
					if ( count( $fields ) > 1 ) {
						if ( ! empty( $results[ $delimiter ] ) ) {
							$results[ $delimiter ] ++;
						} else {
							$results[ $delimiter ] = 1;
						}
					}
				}
				$i ++;
			}

			if ( count( $results ) > 0 ) {

				$results = array_keys( $results, max( $results ) );

				return $results[0];
			}

			return ',';

		}
		public static function insert_into_temp_table( $raw_data, $seperator = ',', $data_contain_headers = false, $headers = array(), $identifier = '', $importing_from = 'csv' ) {
			global $wpdb;
			$raw_data = ( trim( str_replace( array( "\r", "\r\n", "\n\n" ), "\n", $raw_data ) ) );
	
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$encoding = mb_detect_encoding( $raw_data, 'auto' );
			} else {
				$encoding = 'UTF-8';
			}
	
			$lines = explode( "\n", $raw_data );
	
			// If data itself contains headers(in case of CSV), then remove it.
			if ( $data_contain_headers ) {
				array_shift( $lines );
			}
	
			$lines_count = count( $lines );
	
			$batch_size = min( 500, max( 200, round( count( $lines ) / 200 ) ) ); // Each entry in temporary import table will have this much of subscribers data
			$parts      = array_chunk( $lines, $batch_size );
			$partcount  = count( $parts );
	
			do_action( 'ig_es_remove_import_data', $identifier );
	
			$identifier             = empty( $identifier ) ? uniqid() : $identifier;
			$response['identifier'] = $identifier;
	
			for ( $i = 0; $i < $partcount; $i++ ) {
	
				$part = $parts[ $i ];
				$new_value = base64_encode( serialize( $part ) );
			 // phpcs:disable
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}ig_temp_import (data, identifier) VALUES (%s, %s)", $new_value, $identifier ) );
			 // phpcs:enable
			}
	
			$bulk_import_data = get_option( 'ig_es_bulk_import', array() );
			if ( ! empty( $bulk_import_data ) ) {
				$partcount   += $bulk_import_data['parts'];
				$lines_count += $bulk_import_data['lines'];
			}
	
			$bulkimport = array(
				'imported'               => 0,
				'errors'                 => 0,
				'duplicate_emails_count' => 0,
				'existing_contacts'		 => 0,
				'updated_contacts'		 => 0,
				'encoding'               => $encoding,
				'parts'                  => $partcount,
				'lines'                  => $lines_count,
				'separator'              => $seperator,
				'importing_from'         => $importing_from,
				'data_contain_headers'   => $data_contain_headers,
				'headers'                => $headers,
			);
	
			$response['success']     = true;
			$response['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
			update_option( 'ig_es_bulk_import', $bulkimport, 'no' );
	
			return $response;
		}

		/**
		 * Get import metadata (identifier, data, entries) from the temp table.
		 *
		 * @param string $identifier Unique identifier for the import .
		 * @return array|false Metadata array on success, false on failure.
		 */
		public static function get_import_metadata( $identifier ) {
			global $wpdb;

			if ( empty( $identifier ) ) {
				return false;
			}

			$data = get_option( 'ig_es_bulk_import' );

			$entries = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						(SELECT data FROM {$wpdb->prefix}ig_temp_import WHERE identifier = %s ORDER BY ID ASC LIMIT 1) AS first,
						(SELECT data FROM {$wpdb->prefix}ig_temp_import WHERE identifier = %s ORDER BY ID DESC LIMIT 1) AS last",
					$identifier,
					$identifier
				)
			);

			if ( empty( $entries ) ) {
				return false;
			}

			return array(
				'identifier' => $identifier,
				'data'       => $data,
				'entries'    => $entries,
			);
		}
		
		/**
	 * Handle adding contact id to excluded contact list
	 *
	 * @param $contact_id
	 */
		public static function handle_new_contact_inserted( $contact_id = 0 ) {
			$import_status = get_transient( 'ig_es_contact_import_is_running' );
			if ( ! empty( $import_status ) && 'yes' == $import_status && ! empty( $contact_id ) ) {
				$old_excluded_contact_ids =self::get_excluded_contact_id_on_import();
				array_push( $old_excluded_contact_ids, $contact_id );
				self::set_excluded_contact_id_on_import($old_excluded_contact_ids);
			}
		}

	/**
	 * Get the excluded contact ID's list
	 *
	 * @return array|mixed
	 */
		public static function get_excluded_contact_id_on_import() {
			$old_excluded_contact_ids = get_transient( 'ig_es_excluded_contact_ids_on_import' );
			if ( empty( $old_excluded_contact_ids ) || ! is_array( $old_excluded_contact_ids ) ) {
				$old_excluded_contact_ids = array();
			}

			return $old_excluded_contact_ids;
		}

	/**
	 * Set the excluded contact ID's list in transient
	 */
		public static function set_excluded_contact_id_on_import( $list ) {
			if ( ! is_array( $list ) ) {
				return false;
			}
			if ( empty( $list ) ) {
				delete_transient( 'ig_es_excluded_contact_ids_on_import' );
			} else {
				set_transient( 'ig_es_excluded_contact_ids_on_import', $list, 24 * HOUR_IN_SECONDS );
			}

			return true;
		}

		/**
	 * Handle sending bulk welcome and confirmation email to customers using cron job
	 */
		public static function handle_after_bulk_contact_import() {
			global $wpbd;
			$imported_contact_details = get_transient( 'ig_es_imported_contact_ids_range' );
			if ( ! empty( $imported_contact_details ) && isset( $imported_contact_details['rows'] )) {
				$imported_row_details   = is_array( $imported_contact_details['rows'] ) ? $imported_contact_details['rows'] : array();
				if (2 == count( $imported_row_details ) ) {
					$first_row  = intval( $imported_row_details[0] );
					$last_row   = intval( $imported_row_details[1] );
					$total_rows = ( $last_row - $first_row ) + 1;
					if ( 0 < $total_rows ) {
						$per_batch                     = 100;
						$total_batches                 = ceil( $total_rows / $per_batch );
						$excluded_contact_ids          = self::get_excluded_contact_id_on_import();
						$excluded_contact_ids_in_range = ig_es_get_values_in_range( $excluded_contact_ids, $first_row, $first_row + $per_batch );

						$sql = "SELECT contacts.id, lists_contacts.list_id, lists_contacts.status FROM {$wpbd->prefix}ig_contacts AS contacts";
						$sql .= " LEFT JOIN {$wpbd->prefix}ig_lists_contacts AS lists_contacts ON contacts.id = lists_contacts.contact_id";
						$sql .= " LEFT JOIN {$wpbd->prefix}ig_queue AS queue ON contacts.id = queue.contact_id AND queue.campaign_id = 0";
						$sql .= ' WHERE 1=1';
						$sql .= ' AND queue.contact_id IS NULL';
						$sql .= ' AND contacts.id >= %d AND contacts.id <= %d ';
						if ( ! empty( $excluded_contact_ids_in_range ) ) {
							$excluded_ids_for_next_batch = array_diff( $excluded_contact_ids, $excluded_contact_ids_in_range );
							self::set_excluded_contact_id_on_import( $excluded_ids_for_next_batch );
							$excluded_contact_ids_in_range = array_map( 'esc_sql', $excluded_contact_ids_in_range );
							$sql                           .= ' AND contacts.id NOT IN (' . implode( ',', $excluded_contact_ids_in_range ) . ')';
						}
						$sql     .= ' GROUP BY contacts.id LIMIT %d';
						$query   = $wpbd->prepare( $sql, [ $first_row, $first_row + $per_batch, $per_batch ] );
						$entries = $wpbd->get_results( $query );
						if ( 0 < count( $entries ) ) {
							$subscriber_ids     = array();
							$subscriber_options = array();
							foreach ( $entries as $entry ) {
								if ( in_array( $entry->status, array( 'subscribed', 'unconfirmed' ) ) ) {
									$subscriber_id                                = $entry->id;
									$subscriber_ids[]                             = $subscriber_id;
									$subscriber_options[ $subscriber_id ]['type'] = 'unconfirmed' === $entry->status ? 'optin_confirmation' : 'optin_welcome_email';
								}
							}
							if ( ! empty( $subscriber_ids ) ) {
								$timestamp = time();
								ES()->queue->bulk_add(
								0,
								$subscriber_ids,
								$timestamp,
								20,
								false,
								1,
								false,
								$subscriber_options
								);
							}
						}
						if ( 1 == $total_batches ) {
							delete_transient( 'ig_es_imported_contact_ids_range' );
						} else {
							$imported_contact_details = get_transient( 'ig_es_imported_contact_ids_range' );
							$insert_ids = array( $first_row + $per_batch, $last_row );
							$imported_contact_details['rows'] = $insert_ids;
							set_transient( 'ig_es_imported_contact_ids_range', $imported_contact_details );
							$next_task_time = time() + ( 1 * MINUTE_IN_SECONDS ); // Schedule next task after 1 minute from current time.
							IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_after_bulk_contact_import', array(), false, false, $next_task_time );
							//Process queued Welcome and Confirmation emails immidetly
							$request_args = array(
							'action' => 'ig_es_process_queue',
							);
							// Send an asynchronous request to trigger sending of confirmation emails.
							IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args, true );
						}
					}
				}
			}
		}

	/**
	 * Method to truncate temp import table and options used during import process
	 *
	 * @param string import identifier
	 *
	 * @since 4.6.6
	 *
	 * @since 4.7.5 Renamed the function, converted to static method
	 */
		public static function remove_import_data( $identifier = '' ) {

			global $wpdb;

			// If identifier is empty that means, there isn't any importer running. We can safely delete the import data.
			if ( empty( $identifier ) ) {
				// Delete options used during import.
				delete_option( 'ig_es_bulk_import' );
				delete_option( 'ig_es_bulk_import_errors' );

				// We are trancating table so that primary key is reset to 1 otherwise ID column's value will increase on every insert and at some point ID column's data type may not be able to accomodate its value resulting in insert to fail.
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}ig_temp_import" );
			}

		}

		public static function handle_import_list( $args = array() ) {
			$limit             = isset( $args['limit'] ) ? (int) $args['limit'] : 1000;
			$offset            = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
			$status            = ! empty( $args['status'] ) ? (array) $args['status'] : array( 'subscribed' );
			$identifier        = isset( $args['identifier'] ) ? $args['identifier'] : '';
			$list_id           = isset( $args['id'] ) ? $args['id'] : '';
			$list_name         = isset( $args['list_name'] ) ? $args['list_name'] : 'Test';
	
			if ( ! $list_id ) {
				wp_send_json_error( array( 'message' => 'no list' ) );
			}
	
			$subscribers = self::api()->members( $list_id, array(
			'count'  => $limit,
			'offset' => $offset,
			'status' => $status,
			) );
	
			$headers = array(
			__( 'Email', 'email-subscribers' ),
			__( 'First Name', 'email-subscribers' ),
			__( 'Last Name', 'email-subscribers' ),
			__( 'Status', 'email-subscribers' ),
			__( 'List Name', 'email-subscribers' ),
			);
	
			$es_mailchimp_status_mapping = array(
			'subscribed'   => __( 'Subscribed', 'email-subscribers' ),
			'unsubscribed' => __( 'Unsubscribed', 'email-subscribers' ),
			'pending'      => __( 'Unconfirmed', 'email-subscribers' ),
			'cleaned'      => __( 'Hard Bounced', 'email-subscribers' ),
			);
	
			$raw_data             = '';
			$seperator            = ';';
			$data_contain_headers = false;
	
			foreach ( $subscribers as $subscriber ) {
				if ( empty( $subscriber->email_address ) ) {
					continue;
				}
				$status = isset( $subscriber->status ) ? $subscriber->status : 'subscribed';
				$status = isset( $es_mailchimp_status_mapping[ $status ] ) ? $es_mailchimp_status_mapping[ $status ] : $status;
	
				$user_data = array(
				$subscriber->email_address,
				$subscriber->merge_fields->FNAME,
				$subscriber->merge_fields->LNAME,
				$status,
				$list_name,
				);
	
				$raw_data .= implode( $seperator, $user_data ) . "\n";
			}
	
			if ( ! empty( $raw_data ) ) {
				$result     = self::insert_into_temp_table( $raw_data, $seperator, $data_contain_headers, $headers, $identifier, 'mailchimp-api' );
				$identifier = $result['identifier'];
			}
	
			return array(
			'total'       => self::api()->get_total_items(),
			'added'       => count( $subscribers ),
			'subscribers' => count( $subscribers ),
			'identifier'  => $identifier,
			);
		}
	
		public static function api() {
			$mailchimp_apikey = ig_es_get_request_data( 'mailchimp_api_key' );
	
			if ( is_null( self::$api_instance ) ) {
				self::$api_instance = new ES_Mailchimp_API( $mailchimp_apikey );
			}
	
			return self::$api_instance;
		}
	

		public static function api_request_data( $args = array() ) {

			$endpoint = isset( $args['endpoint'] ) ? $args['endpoint'] : '';
			$valid_endpoints = array( 'lists', 'import_list', 'verify_api_key' );
			if ( ! in_array( $endpoint, $valid_endpoints, true ) ) {
				wp_send_json_error( array( 'message' => 'Invalid endpoint' ) );
			}

			switch ( $endpoint ) {
				case 'lists':
					$lists = self::api()->lists();
					wp_send_json_success( array( 'lists' => $lists ) );
					break;
	
				case 'import_list':
					$response = self::handle_import_list( $args );
					wp_send_json_success( $response );
					break;
	
				case 'verify_api_key':
					$result = self::api()->ping();
					if ( $result ) {
						wp_send_json_success( array( 'message' => $result->health_status ) );
					}
					break;
	
				default:
					wp_send_json_error( array( 'message' => 'Invalid endpoint' ) );
			}
		}

	//This html should be handled via new frontend.
		public static function import_contact( $args = array()) {
			global $wpdb;

			$phpmailer = ES()->mailer->get_phpmailer();

			$memory_limit       = @ini_get( 'memory_limit' );
			$max_execution_time = @ini_get( 'max_execution_time' );

			@set_time_limit( 0 );

			if ( (int) $max_execution_time < 300 ) {
				@set_time_limit( 300 );
			}

			if ( (int) $memory_limit < 256 ) {
				// Add filter to increase memory limit
				add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

				wp_raise_memory_limit( 'ig_es' );

				// Remove the added filter function so that it won't be called again if wp_raise_memory_limit called later on.
				remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
			}

			$return['success'] = false;

			$bulkdata = ( isset( $args['options'] ) && is_array( $args['options'] ) ) ? $args['options'] : array();

			$bulkdata                        = wp_parse_args( $bulkdata, get_option( 'ig_es_bulk_import' ) );
			$erroremails                     = get_option( 'ig_es_bulk_import_errors', array() );
			$order                           = isset( $bulkdata['mapping_order'] ) ? $bulkdata['mapping_order'] : array();
			$list_id                         = isset( $bulkdata['list_id'] ) ? $bulkdata['list_id'] : array();
			$parts_at_once                   = 10;
			$selected_status                 = $bulkdata['status'];
			$send_optin_emails               = isset( $bulkdata['send_optin_emails'] ) ? $bulkdata['send_optin_emails'] : 'no';
			$need_to_send_welcome_emails     = ( 'yes' === $send_optin_emails );
			$update_subscribers_data         = isset( $bulkdata['update_subscribers_data'] ) ? $bulkdata['update_subscribers_data'] : 'no';
			$need_to_update_subscribers_data = ( 'yes' === $update_subscribers_data );

			$error_codes = array(
			'invalid' => __( 'Email address is invalid.', 'email-subscribers' ),
			'empty'   => __( 'Email address is empty.', 'email-subscribers' ),
			);

			if ( ! empty( $list_id ) && ! is_array( $list_id ) ) {
				$list_id = array( $list_id );
			}

			if ( isset( $args['id'] ) ) {
				set_transient( 'ig_es_contact_import_is_running', 'yes' );
				$batch_id            = (int) sanitize_text_field( $args['id'] );
				$bulkdata['current'] = $batch_id;
				// phpcs:disable
				$raw_list_data       = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT data FROM {$wpdb->prefix}ig_temp_import 
					WHERE identifier = %s ORDER BY ID ASC LIMIT %d, %d",
					$bulkdata['identifier'],
					$bulkdata['current'] * $parts_at_once,
					$parts_at_once
				)
				);
				// phpcs:enable
				if ( $raw_list_data ) {

					$contacts_data        = array();
					$gmt_offset           = ig_es_get_gmt_offset( true );
					$current_date_time    =ig_get_current_date_time();

					$current_batch_emails = array();
					$processed_emails     = ! empty( $bulkdata['processed_emails'] ) ? $bulkdata['processed_emails'] : array();
					$list_contact_data    = array();
					$es_status_mapping    = array(
						__( 'Subscribed', 'email-subscribers' )   => 'subscribed',
						__( 'Unsubscribed', 'email-subscribers' ) => 'unsubscribed',
						__( 'Unconfirmed', 'email-subscribers' )  => 'unconfirmed',
						__( 'Hard Bounced', 'email-subscribers' ) => 'hard_bounced',
					);

					$is_starting_import = 0 === $batch_id;
					if ( $is_starting_import ) {
						do_action( 'ig_es_before_bulk_contact_import' );
					}
					foreach ( $raw_list_data as $raw_list ) {
						$raw_list = unserialize( base64_decode( $raw_list ) );
						// each entry
						foreach ( $raw_list as $line ) {
							if ( ! trim( $line ) ) {
								$bulkdata['lines']--;
								continue;
							}
							$data       = str_getcsv( $line, $bulkdata['separator'], '"' );
							$cols_count = count( $data );
							$insert     = array();
							for ( $col = 0; $col < $cols_count; $col++ ) {
								$d = trim( $data[ $col ] );
								if ( ! isset( $order[ $col ] ) ) {
									continue;
								}
								switch ( $order[ $col ] ) {
									case 'email':
										$insert['email'] = $d;
										// Convert special characters in the email domain name to ascii.
										if ( is_callable( array( $phpmailer, 'punyencodeAddress' ) ) ) {
											$encoded_email = $phpmailer->punyencodeAddress( $insert['email'] );
											if ( ! empty( $encoded_email ) ) {
												$insert['email'] = $encoded_email;
											}
										}
										break;
									case 'first_last':
										$name = explode( ' ', $d );
										if ( ! empty( $name[0] ) ) {
											$insert['first_name'] = $name[0];
										}
										if ( ! empty( $name[1] ) ) {
											$insert['last_name'] = $name[1];
										}
										break;
									case 'last_first':
										$name = explode( ' ', $d );
										if ( ! empty( $name[1] ) ) {
											$insert['first_name'] = $name[1];
										}
										if ( ! empty( $name[0] ) ) {
											$insert['last_name'] = $name[0];
										}
										break;
									case 'created_at':
										if ( ! is_numeric( $d ) && ! empty( $d ) ) {
											$d                    = sanitize_text_field( $d );
											$insert['created_at'] = gmdate( 'Y-m-d H:i:s', strtotime( $d ) - $gmt_offset );
										}
										break;
									case '-1':
										// ignored column
										break;
									default:
										$insert[ $order[ $col ] ] = $d;
								}
							}

							if ( empty( $insert['email'] ) || ! is_email( $insert['email'] ) ) {
								$error_data = array();
								if ( empty( $insert['email'] ) ) {
									$error_data['cd'] = 'empty';
								} elseif ( ! is_email( $insert['email'] ) ) {
									$error_data['cd']    = 'invalid';
									$error_data['email'] = $insert['email'];
								}
								if ( ! empty( $insert['first_name'] ) ) {
									$error_data['fn'] = $insert['first_name'];
								}
								if ( ! empty( $insert['last_name'] ) ) {
									$error_data['ln'] = $insert['last_name'];
								}
								$bulkdata['errors']++;
								$erroremails[] = $error_data;
								continue;
							}

							$email = sanitize_email( strtolower( $insert['email'] ) );
							if ( ! in_array( $email, $current_batch_emails, true ) && ! in_array( $email, $processed_emails, true ) ) {
								$first_name = isset( $insert['first_name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $insert['first_name'] ) ) ) : '';
								$last_name  = isset( $insert['last_name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $insert['last_name'] ) ) ) : '';
								$created_at = isset( $insert['created_at'] ) ? $insert['created_at'] : $current_date_time;

								$guid = ES_Common::generate_guid();

								$contact_data['first_name'] = $first_name;
								$contact_data['last_name']  = $last_name;
								$contact_data['email']      = $email;
								$contact_data['source']     = 'import';
								$contact_data['status']     = 'verified';
								$contact_data['hash']       = $guid;
								$contact_data['created_at'] = $created_at;

								$additional_contacts_data = apply_filters( 'es_prepare_additional_contacts_data_for_import', array(), $insert );

								$contacts_data[$email] = array_merge( $contact_data, $additional_contacts_data );
								$bulkdata['imported']++;
							} else {
								$bulkdata['duplicate_emails_count']++;
							}

							$list_names = isset( $insert['list_name'] ) ? sanitize_text_field( trim( $insert['list_name'] ) ) : '';
							if ( empty( $insert['list_name'] ) ) {
								$list_names_arr = ES()->lists_db->get_lists_by_id( $list_id );
								$list_names     = implode( ',', array_column( $list_names_arr, 'name' ) );
							}

							$status     = 'unconfirmed';
							$list_names = array_map( 'trim', explode( ',', $list_names ) );

							if ( isset( $insert['status'] ) ) {
								$map_status = strtolower( str_replace( ' ', '_', $insert['status'] ) );
							}

							if ( isset( $insert['status'] ) && in_array( $map_status, $es_status_mapping ) ) {
								$status = sanitize_text_field( trim( $map_status ) );
							} elseif ( ! empty( $selected_status ) ) {
								$status = $selected_status;
							}

							if ( ! empty( $es_status_mapping[ $status ] ) ) {
								$status = $es_status_mapping[ $status ];
							}

							foreach ( $list_names as $key => $list_name ) {
								if ( ! empty( $list_name ) ) {
									$list_contact_data[ $list_name ][ $status ][] = $email;
								}
							}

							$current_batch_emails[] = $email;
						}
					}

					if ( count( $current_batch_emails ) > 0 ) {

						$current_batch_emails = array_unique( $current_batch_emails );
					
						$existing_contacts_email_id_map = ES()->contacts_db->get_email_id_map( $current_batch_emails );

						if ( $need_to_update_subscribers_data ) {
							if ( ! empty( $existing_contacts_email_id_map ) ) {
								$existing_contacts 	   = array_intersect_key( $contacts_data, $existing_contacts_email_id_map );
								$updated_contacts  = ES()->contacts_db->bulk_update( $existing_contacts, 100 );		
								if ( ! empty( $updated_contacts ) ) {
									$bulkdata['updated_contacts'] += $updated_contacts; 
								}
							}
						}
					

						if ( ! empty( $existing_contacts_email_id_map ) ) {
							$contacts_data = array_diff_key( $contacts_data, $existing_contacts_email_id_map );
						}

						if ( ! empty( $contacts_data ) ) {
							$insert_ids = ES()->contacts_db->bulk_insert( $contacts_data, 100, true );
							if ( ! empty( $insert_ids ) && $need_to_send_welcome_emails ) {
								$imported_contacts_transient = get_transient( 'ig_es_imported_contact_ids_range' );
								if ( ! empty( $imported_contacts_transient ) && is_array( $imported_contacts_transient ) && isset( $imported_contacts_transient['rows'] ) ) {
									$old_rows   = is_array( $imported_contacts_transient['rows'] ) ? $imported_contacts_transient['rows'] : array();
									$all_data   = array_merge( $old_rows, $insert_ids );
									$insert_ids = array( min( $all_data ), max( $all_data ) );
								}
								$imported_contact_details = array(
								'rows'  => $insert_ids,
								'lists' => $list_id
								);
								set_transient( 'ig_es_imported_contact_ids_range', $imported_contact_details );
							}
						}

						if ( ! empty( $list_contact_data ) ) {
							foreach ( $list_contact_data as $list_name => $list_data ) {
								$list = ES()->lists_db->get_list_by_name( $list_name );

								if ( ! empty( $list ) ) {
									$list_id = $list['id'];
								} else {
									$list_id = ES()->lists_db->add_list( $list_name );
								}

								foreach ( $list_data as $status => $contact_emails ) {
									$contact_id_date = ES()->contacts_db->get_contact_ids_created_at_date_by_emails( $contact_emails );
									$contact_ids     = array_keys( $contact_id_date );
									if ( count( $contact_ids ) > 0 ) {
										ES()->lists_contacts_db->remove_contacts_from_lists( $contact_ids, $list_id );
										ES()->lists_contacts_db->do_import_contacts_into_list( $list_id, $contact_id_date, $status, 1 );
									}
								}
							}
						}
					}
				}

				$return['memoryusage']            = size_format( memory_get_peak_usage( true ), 2 );
				$return['errors']                 = isset( $bulkdata['errors'] ) ? $bulkdata['errors'] : 0;
				$return['duplicate_emails_count'] = isset( $bulkdata['duplicate_emails_count'] ) ? $bulkdata['duplicate_emails_count'] : 0;
				$return['existing_contacts']      = isset( $bulkdata['existing_contacts'] ) ? $bulkdata['existing_contacts'] : 0;
				$return['updated_contacts']       = isset( $bulkdata['updated_contacts'] ) ? $bulkdata['updated_contacts'] : 0;
				$return['imported']               = ( $bulkdata['imported'] );
				$return['total']                  = ( $bulkdata['lines'] );
				$return['f_errors']               = number_format_i18n( $bulkdata['errors'] );
				$return['f_imported']             = number_format_i18n( $bulkdata['imported'] );
				$return['f_total']                = number_format_i18n( $bulkdata['lines'] );
				$return['f_duplicate_emails']     = number_format_i18n( $bulkdata['duplicate_emails_count'] );

				$return['html'] = '';

				if ( ( $bulkdata['imported'] + $bulkdata['errors'] + $bulkdata['duplicate_emails_count'] ) >= $bulkdata['lines'] ) {
					$return['html'] = '<p class="text-base text-gray-600 pt-2 pb-1.5">';
				
					$total_imported_contacts = $bulkdata['imported'] - $bulkdata['updated_contacts'];
					if ( $total_imported_contacts > 0 ) {
						/* translators: 1. Total imported contacts */
						$return['html'] .= sprintf( esc_html__( '%1$s contacts imported.', 'email-subscribers' ) . ' ', '<span class="font-medium">' . number_format_i18n( $total_imported_contacts ) . '</span>' );
					}

					if ( $bulkdata['updated_contacts'] > 0 ) {
						/* translators: 1. Total updated contacts */
						$return['html'] .= sprintf( esc_html__( '%1$s contacts updated.', 'email-subscribers' ), '<span class="font-medium">' . number_format_i18n( $bulkdata['updated_contacts'] ) . '</span>' );
					}				

					if ( $bulkdata['duplicate_emails_count'] ) {
						$duplicate_email_string = _n( 'email', 'emails', $bulkdata['duplicate_emails_count'], 'email-subscribers' );
						/* translators: 1. Duplicate emails count. 2. Email or emails string based on duplicate email count. */
						$return['html'] .= sprintf( esc_html__( '%1$s duplicate %2$s found.', 'email-subscribers' ), '<span class="font-medium">' . number_format_i18n( $bulkdata['duplicate_emails_count'] ) . '</span>', $duplicate_email_string );
					}
					$return['html'] .= '</p>';
					if ( $bulkdata['errors'] ) {
						$i                      = 0;
						$skipped_contact_string = _n( 'contact was', 'contacts were', $bulkdata['errors'], 'email-subscribers' );

						/* translators: %d Skipped emails count %s Skipped contacts string */
						$table  = '<p class="text-sm text-gray-600 pt-2 pb-1.5">' . __( sprintf( 'The following %d %s skipped', $bulkdata['errors'], $skipped_contact_string ), 'email-subscribers' ) . ':</p>';
						$table .= '<table class="w-full bg-white rounded-lg shadow overflow-hidden mt-1.5">';
						$table .= '<thead class="rounded-md"><tr class="border-b border-gray-200 bg-gray-50 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider"><th class="pl-4 py-4" width="5%">#</th>';

						$first_name_column_choosen = in_array( 'first_name', $order, true );
						if ( $first_name_column_choosen ) {
							$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'First Name', 'email-subscribers' ) . '</th>';
						}

						$last_name_column_choosen = in_array( 'last_name', $order, true );
						if ( $last_name_column_choosen ) {
							$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'Last Name', 'email-subscribers' ) . '</th>';
						}

						$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'Email', 'email-subscribers' ) . '</th>';
						$table .= '<th class="pl-3 pr-1 py-3 font-medium">' . esc_html__( 'Reason', 'email-subscribers' ) . '</th>';
						$table .= '</tr></thead><tbody>';
						foreach ( $erroremails as $error_data ) {
							$table .= '<tr class="border-b border-gray-200 text-left leading-4 text-gray-800 tracking-wide">';
							$table .= '<td class="pl-4">' . ( ++$i ) . '</td>';
							$email  = ! empty( $error_data['email'] ) ? $error_data['email'] : '-';
							if ( $first_name_column_choosen ) {
								$first_name = ! empty( $error_data['fn'] ) ? $error_data['fn'] : '-';
								$table     .= '<td class="pl-3 py-3">' . esc_html( $first_name ) . '</td>';
							}
							if ( $last_name_column_choosen ) {
								$last_name = ! empty( $error_data['ln'] ) ? $error_data['ln'] : '-';
								$table    .= '<td class="pl-3 py-3">' . esc_html( $last_name ) . '</td>';
							}
							$error_code = ! empty( $error_data['cd'] ) ? $error_data['cd'] : '-';
							$reason     = ! empty( $error_codes[ $error_code ] ) ? $error_codes[ $error_code ] : '-';
							$table     .= '<td class="pl-3 py-3">' . esc_html( $email ) . '</td><td class="pl-3 py-3">' . esc_html( $reason ) . '</td></tr>';
						}
						$table          .= '</tbody></table>';
						$return['html'] .= $table;
					}
					do_action( 'ig_es_remove_import_data' );
					$next_task_time = time() + ( 1 * MINUTE_IN_SECONDS ); // Schedule next task after 1 minute from current time.
					IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_after_bulk_contact_import', array(), false, false, $next_task_time );
				} else {
					// Add current batch emails into the processed email list
					$processed_emails             = array_merge( $processed_emails, $current_batch_emails );
					$bulkdata['processed_emails'] = $processed_emails;

					update_option( 'ig_es_bulk_import', $bulkdata );
					update_option( 'ig_es_bulk_import_errors', $erroremails );
				}
				$return['success'] = true;
				delete_transient( 'ig_es_contact_import_is_running');
			}

			return $return;
		
		}

	}

}

ES_Contact_Import_Controller::get_instance();
