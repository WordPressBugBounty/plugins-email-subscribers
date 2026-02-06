<?php

if ( ! class_exists( 'ES_Contact_Import_Controller' ) ) {

	/**
	 * Class to handle contact import operation
	 *
	 * @class ES_Contact_Import_Controller
	 */
	class ES_Contact_Import_Controller {

		/**
		 * Class instance
		 *
		 * @var ES_Contact_Import_Controller
		 */
		public static $instance;

		/**
		 * API instance
		 *
		 * @var mixed
		 */
		public static $api_instance = null;

		/**
		 * Class constructor
		 */
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
		 * Helper method to sanitize and normalize arguments
		 * 
		 * @param mixed $args Arguments to sanitize
		 * @return array Sanitized arguments array
		 */
		private static function sanitize_args( $args ) {
			// Handle JSON string parameters
			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				$args = is_array( $decoded ) ? $decoded : array();
			}
			
			return is_array( $args ) ? $args : array();
		}

		public static function import_subscribers_upload_handler( $args = array() ) {
			$response = array( 'success' => false );

			// Handle case where $args might be a JSON string
			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				$args = is_array( $decoded ) ? $decoded : array();
			}

			// Ensure $args is always an array
			if ( ! is_array( $args ) ) {
				$args = array();
			}
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
			
			$raw_data = '';
			$seperator = ',';
			$data_contain_headers = false;
			$headers = array();
	
			if ( 'csv' === $importing_from && ! empty( $args['file'] ) ) {
				$tmp_file = $args['file'];
				
				if ( ! file_exists( $tmp_file ) ) {
					return array( 'error' => __( 'Uploaded file not found.', 'email-subscribers' ) );
				}
				
				$original_filename = '';
				if ( isset( $_FILES['async-upload']['name'] ) ) {
					$original_filename = sanitize_text_field( $_FILES['async-upload']['name'] );
				}
				
				if ( ! empty( $original_filename ) ) {
					$file_extension = strtolower( pathinfo( $original_filename, PATHINFO_EXTENSION ) );
					if ( 'csv' !== $file_extension ) {
						return array( 'error' => __( 'Only CSV files are allowed.', 'email-subscribers' ) );
					}
				}
				
				$file_size = filesize( $tmp_file );
				$max_size = 2 * 1024 * 1024;
				if ( $file_size > $max_size ) {
					return array( 'error' => __( 'File size must be less than 2MB.', 'email-subscribers' ) );
				}
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
				$insert_result = ES()->temp_import_db->insert_temp_data( $new_value, $identifier );
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

			if ( empty( $identifier ) ) {
				return false;
			}

			$data = get_option( 'ig_es_bulk_import' );

			$entries = ES()->temp_import_db->get_import_metadata_by_identifier( $identifier );

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

			if ( empty( $identifier ) ) {
				delete_option( 'ig_es_bulk_import' );
				delete_option( 'ig_es_bulk_import_errors' );

				ES()->temp_import_db->truncate_table();
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
					if ( is_wp_error( $lists ) ) {
						wp_send_json_error( array( 'message' => $lists->get_error_message() ) );
					} else {
						wp_send_json_success( array( 'lists' => $lists ) );
					}
					break;
	
				case 'import_list':
					$response = self::handle_import_list( $args );
					wp_send_json_success( $response );
					break;
	
				case 'verify_api_key':
					$result = self::api()->ping();
					if ( is_wp_error( $result ) ) {
						wp_send_json_error( array( 'message' => $result->get_error_message() ) );
					} elseif ( $result && isset( $result->health_status ) ) {
						wp_send_json_success( array( 'message' => $result->health_status ) );
					} else {
						wp_send_json_error( array( 'message' => __( 'Invalid API key or unable to connect to MailChimp.', 'email-subscribers' ) ) );
					}
					break;
	
				default:
					wp_send_json_error( array( 'message' => 'Invalid endpoint' ) );
			}
		}

		public static function import_contact( $args = array()) {

			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				$args = is_array( $decoded ) ? $decoded : array();
			}

			if ( ! is_array( $args ) ) {
				$args = array();
			}

			$phpmailer = ES()->mailer->get_phpmailer();

			$memory_limit       = @ini_get( 'memory_limit' );
			$max_execution_time = @ini_get( 'max_execution_time' );

			@set_time_limit( 0 );

			if ( (int) $max_execution_time < 300 ) {
				@set_time_limit( 300 );
			}

			if ( (int) $memory_limit < 256 ) {
				add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

				wp_raise_memory_limit( 'ig_es' );

				remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
			}

			$return['success'] = false;

			$bulkdata = ( isset( $args['options'] ) && is_array( $args['options'] ) ) ? $args['options'] : array();

			$mapping_order_param = isset( $bulkdata['mapping_order'] ) ? $bulkdata['mapping_order'] : array();
			$list_id_param = isset( $bulkdata['list_id'] ) ? $bulkdata['list_id'] : array();
			$status_param = isset( $bulkdata['status'] ) ? $bulkdata['status'] : '';
			$send_optin_param = isset( $bulkdata['send_optin_emails'] ) ? $bulkdata['send_optin_emails'] : '';
			$update_param = isset( $bulkdata['update_subscribers_data'] ) ? $bulkdata['update_subscribers_data'] : '';
			$identifier_param = isset( $bulkdata['identifier'] ) ? $bulkdata['identifier'] : '';

			$bulkdata = wp_parse_args( $bulkdata, get_option( 'ig_es_bulk_import' ) );
			
			if ( !empty( $mapping_order_param ) ) {
				$bulkdata['mapping_order'] = $mapping_order_param;
			}
			if ( !empty( $list_id_param ) ) {
				$bulkdata['list_id'] = $list_id_param;
			}
			if ( !empty( $status_param ) ) {
				$bulkdata['status'] = $status_param;
			}
			if ( !empty( $send_optin_param ) ) {
				$bulkdata['send_optin_emails'] = $send_optin_param;
			}
			if ( !empty( $update_param ) ) {
				$bulkdata['update_subscribers_data'] = $update_param;
			}
			if ( !empty( $identifier_param ) ) {
				$bulkdata['identifier'] = $identifier_param;
			}

			$erroremails                     = get_option( 'ig_es_bulk_import_errors', array() );
			$order                           = isset( $bulkdata['mapping_order'] ) ? $bulkdata['mapping_order'] : array();
			$list_id                         = isset( $bulkdata['list_id'] ) ? $bulkdata['list_id'] : array();
			
			$parts_at_once                   = 10;
			$selected_status                 = isset( $bulkdata['status'] ) ? $bulkdata['status'] : 'subscribed';
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
				
				$raw_list_data = ES()->temp_import_db->get_import_data_by_identifier(
					$bulkdata['identifier'],
					$bulkdata['current'] * $parts_at_once,
					$parts_at_once
				);
				
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
						$raw_list = ig_es_maybe_unserialize( base64_decode( $raw_list ) );
						foreach ( $raw_list as $line ) {
							if ( ! trim( $line ) ) {
								$bulkdata['lines']--;
								continue;
							}
							$data       = str_getcsv( $line, $bulkdata['separator'], '"', '\\' );
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
							$existing_contacts = array_intersect_key( $contacts_data, $existing_contacts_email_id_map );
							
							// Filter out any custom field keys that don't exist as actual database columns
							// to prevent issues in bulk_update
							$valid_columns = array_keys( ES()->contacts_db->get_columns() );
							$filtered_existing_contacts = array();
							foreach ( $existing_contacts as $email => $contact_fields ) {
								$filtered_contact = array();
								foreach ( $contact_fields as $field_key => $field_value ) {
									if ( in_array( $field_key, $valid_columns, true ) ) {
										$filtered_contact[$field_key] = $field_value;
									}
								}
								$filtered_existing_contacts[$email] = $filtered_contact;
							}
							
							$updated_contacts = ES()->contacts_db->bulk_update( $filtered_existing_contacts, 100 );
							if ( ! empty( $updated_contacts ) ) {
								$bulkdata['updated_contacts'] += $updated_contacts; 
							}
						}
					}
				

					if ( ! empty( $existing_contacts_email_id_map ) ) {
						$contacts_data = array_diff_key( $contacts_data, $existing_contacts_email_id_map );
					}						if ( ! empty( $contacts_data ) ) {
							// Filter out any custom field keys that don't exist as actual database columns
							// to prevent placeholder count mismatch in bulk_insert
							$valid_columns = array_keys( ES()->contacts_db->get_columns() );
							$filtered_contacts_data = array();
							foreach ( $contacts_data as $email => $contact_fields ) {
								$filtered_contact = array();
								foreach ( $contact_fields as $field_key => $field_value ) {
									if ( in_array( $field_key, $valid_columns, true ) ) {
										$filtered_contact[$field_key] = $field_value;
									}
								}
								$filtered_contacts_data[$email] = $filtered_contact;
							}
							
							$insert_ids = ES()->contacts_db->bulk_insert( $filtered_contacts_data, 100, true );
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
					
					if ( $need_to_send_welcome_emails ) {
						self::handle_after_bulk_contact_import();
						
						// Trigger queue processing to send emails
						$request_args = array(
							'action' => 'ig_es_process_queue',
						);
						IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args, true );
					}
				} else {
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

		/**
		 * Get import data for column mapping (React frontend)
		 *
		 * @param array $args Arguments containing identifier
		 * @return array Response array with structured data
		 * @since 5.0.0
		 */
		public static function get_import_data_for_mapping( $args = array() ) {

			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				$args = is_array( $decoded ) ? $decoded : array();
			}

			if ( ! is_array( $args ) ) {
				$args = array();
			}

			$identifier = isset( $args['identifier'] ) ? sanitize_text_field( $args['identifier'] ) : '';

			if ( empty( $identifier ) ) {
				return false;
			}

			$metadata = self::get_import_metadata( $identifier );

			if ( ! $metadata ) {
				return false;
			}

			$entries = $metadata['entries'];
			$data = $metadata['data'];

		$first = ig_es_maybe_unserialize( base64_decode( $entries->first ) );
		$last  = ig_es_maybe_unserialize( base64_decode( $entries->last ) );

		$sample_data = str_getcsv( $first[0], $data['separator'], '"', '\\' );
		$cols_count = count( $sample_data );
		$contact_count = $data['lines'];			$fields = array(
				'email'      => __( 'Email', 'email-subscribers' ),
				'first_name' => __( 'First Name', 'email-subscribers' ),
				'last_name'  => __( 'Last Name', 'email-subscribers' ),
				'first_last' => __( '(First Name) (Last Name)', 'email-subscribers' ),
				'last_first' => __( '(Last Name) (First Name)', 'email-subscribers' ),
				'created_at' => __( 'Subscribed at', 'email-subscribers' ),
			);

			if ( ! empty( $data['importing_from'] ) && 'wordpress_users' !== $data['importing_from'] ) {
				$fields['list_name'] = __( 'List Name', 'email-subscribers' );
				$fields['status']    = __( 'Status', 'email-subscribers' );
			}

			$fields = apply_filters( 'es_import_show_more_fields_for_mapping', $fields );

			$status_options = array(
				'subscribed'   => __( 'Subscribed', 'email-subscribers' ),
				'unsubscribed' => __( 'Unsubscribed', 'email-subscribers' ),
				'unconfirmed'  => __( 'Unconfirmed', 'email-subscribers' ),
			);

			$headers = array();
			if ( ! empty( $data['headers'] ) ) {
				$headers = $data['headers'];
			}

		$sample_rows = array();
		$phpmailer = ES()->mailer->get_phpmailer();

		for ( $i = 0; $i < min( 3, $contact_count ); $i++ ) {
			$row_data = str_getcsv( $first[$i], $data['separator'], '"', '\\' );
			$sample_rows[] = $row_data;
		}

		if ( $contact_count > 3 ) {
			$last_row_data = str_getcsv( array_pop( $last ), $data['separator'], '"', '\\' );
			$sample_rows[] = $last_row_data;
		}			$suggested_mappings = array();
			for ( $i = 0; $i < $cols_count; $i++ ) {
				$col_data = trim( $sample_data[$i] );
				
				if ( is_callable( array( $phpmailer, 'punyencodeAddress' ) ) ) {
					$col_data = $phpmailer->punyencodeAddress( $col_data );
				}
				
				$is_email = is_email( trim( $col_data ) );
				
				if ( $is_email ) {
					$suggested_mappings[$i] = 'email';
				} elseif ( ! empty( $headers[$i] ) ) {
					$header_name = strip_tags( $headers[$i] );
					foreach ( $fields as $key => $value ) {
						if ( $header_name === $value ) {
							$suggested_mappings[$i] = $key;
							break;
						}
					}
				}
				
				if ( ! isset( $suggested_mappings[$i] ) ) {
					$suggested_mappings[$i] = '-1'; 
				}
			}

			$response = array(
				'identifier' => $identifier,
				'import_metadata' => $data,
				'total_contacts' => $contact_count,
				'columns_count' => $cols_count,
				'headers' => $headers,
				'sample_rows' => $sample_rows,
				'available_fields' => $fields,
				'available_status_options' => $status_options,
				'suggested_mappings' => $suggested_mappings,
				'hidden_contacts_count' => max( 0, $contact_count - 4 ),
			);

			return $response;
		}

		/**
		 * Add a single contact manually
		 *
		 * @param array $args Contact data
		 * @return array Response array
		 * @since 5.0.0
		 */
		public static function add_contact_manually( $args = array() ) {

			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				$args = is_array( $decoded ) ? $decoded : array();
			}

			if ( ! is_array( $args ) ) {
				$args = array();
			}

			$email      = isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '';
			$first_name = isset( $args['first_name'] ) ? sanitize_text_field( $args['first_name'] ) : '';
			$last_name  = isset( $args['last_name'] ) ? sanitize_text_field( $args['last_name'] ) : '';
			$list_ids   = isset( $args['list_ids'] ) ? array_map( 'intval', (array) $args['list_ids'] ) : array();
			$status     = isset( $args['status'] ) ? sanitize_text_field( $args['status'] ) : 'subscribed';
			$send_optin_emails = isset( $args['send_optin_emails'] ) ? sanitize_text_field( $args['send_optin_emails'] ) : 'no';
			$custom_fields = isset( $args['custom_fields'] ) ? (array) $args['custom_fields'] : array();

			if ( empty( $email ) ) {
				return array(
					'success' => false,
					'message' => __( 'Email address is required.', 'email-subscribers' )
				);
			}

			if ( ! is_email( $email ) ) {
				return array(
					'success' => false,
					'message' => __( 'Please enter a valid email address.', 'email-subscribers' )
				);
			}

			$existing_contact_id = ES()->contacts_db->get_contact_id_by_email( $email );
			
			if ( $existing_contact_id ) {
				return array(
					'success' => false,
					'message' => __( 'This email address already exists in your contacts.', 'email-subscribers' )
				);
			}

			$contact_data = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'email'      => $email,
				'source'     => 'manual',
				'status'     => 'verified',
				'hash'       => ES_Common::generate_guid(),
				'created_at' => ig_get_current_date_time(),
				'updated_at' => ig_get_current_date_time(),
			);

			// Add custom fields to contact data if provided (PRO feature only)
			if ( ! empty( $custom_fields ) && ES()->is_pro() ) {
				// Get available custom fields to validate
				$available_custom_fields = ES()->custom_fields_db->get_custom_fields();
				$valid_custom_field_slugs = array();
				
				foreach ( $available_custom_fields as $custom_field ) {
					$valid_custom_field_slugs[] = $custom_field['slug'];
				}

				// Only include custom fields that exist in the system
				foreach ( $custom_fields as $field_slug => $field_value ) {
					if ( in_array( $field_slug, $valid_custom_field_slugs ) ) {
						$contact_data[ $field_slug ] = sanitize_text_field( $field_value );
					}
				}
			}

			$contact_id = ES()->contacts_db->insert( $contact_data );

			if ( $contact_id ) {
			if ( ! empty( $list_ids ) ) {
				foreach ( $list_ids as $list_id ) {
					$list_contact_data = array(
						'list_id'    => $list_id,
						'contact_id' => $contact_id,
						'status'     => $status,
						'subscribed_at' => ig_get_current_date_time(),
						'optin_type' => 1,
					);
					ES()->lists_contacts_db->insert( $list_contact_data );
				}
				
				ES()->lists_contacts_db->clear_list_counts_cache( $list_ids );
					do_action( 'ig_es_contact_subscribe', $contact_id, $list_ids );
					
					$subscriber_options = array();
					$subscriber_options[ $contact_id ]['type'] = 'optin_welcome_email';
					
					$timestamp = time();
					ES()->queue->bulk_add(
						0,
						array( $contact_id ),
						$timestamp,
						20,
						false,
						1,
						false,
						$subscriber_options
					);

					$request_args = array(
						'action' => 'ig_es_process_queue',
					);
					IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args, true );
				}

				return array(
					'success'    => true,
					'contact_id' => $contact_id,
					'message'    => __( 'Contact added successfully.', 'email-subscribers' ),
				);
			} else {
				return array(
					'success' => false,
					'message' => __( 'Failed to add contact. Please try again.', 'email-subscribers' )
				);
			}
		}

		/**
		 * Verify Mailchimp API key
		 *
		 * @param array $args Arguments containing API key
		 * @return array Response array
		 * @since 5.0.0
		 */
		public static function mailchimp_verify_api_key( $args = array() ) {
			$args = self::sanitize_args( $args );
			
			$api_key = isset( $args['mailchimp_api_key'] ) ? sanitize_text_field( $args['mailchimp_api_key'] ) : '';
			
			if ( empty( $api_key ) ) {
				return array(
					'success' => false,
					'message' => __( 'Please provide a valid API key.', 'email-subscribers' )
				);
			}

			if ( ! preg_match( '/^[a-f0-9]{32}-[a-z]{2,4}\d*$/', $api_key ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid API key format. MailChimp API keys should be in format: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-usX', 'email-subscribers' )
				);
			}

			$dc = preg_replace( '/^([a-f0-9]+)-([a-z0-9]+)$/', '$2', $api_key );
			$ping_url = 'https://' . $dc . '.api.mailchimp.com/3.0/ping';
			
			$response = wp_remote_request(
				$ping_url,
				array(
					'method'  => 'GET',
					'headers' => array(
						'Authorization' => 'apikey ' . $api_key,
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return array(
					'success' => false,
					'message' => sprintf( 
						__( 'Unable to connect to MailChimp: %s', 'email-subscribers' ), 
						$response->get_error_message() 
					)
				);
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );

			if ( 200 == $code ) {
				$data = json_decode( $body );
				if ( $data && isset( $data->health_status ) ) {
					return array(
						'success' => true,
						'message' => $data->health_status
					);
				} else {
					return array(
						'success' => false,
						'message' => __( 'Unexpected response from MailChimp API.', 'email-subscribers' )
					);
				}
			} else {
				$error_data = json_decode( $body );
				$error_message = __( 'Invalid API key or unable to connect to MailChimp.', 'email-subscribers' );
				
				if ( $error_data ) {
					if ( isset( $error_data->detail ) ) {
						$error_message = $error_data->detail;
					} elseif ( isset( $error_data->title ) ) {
						$error_message = $error_data->title;
					} elseif ( isset( $error_data->error ) ) {
						$error_message = $error_data->error;
					}
				}

				return array(
					'success' => false,
					'message' => sprintf( 
						__( 'MailChimp API Error (HTTP %d): %s', 'email-subscribers' ), 
						$code, 
						$error_message 
					)
				);
			}
		}

		/**
		 * Get Mailchimp lists
		 *
		 * @param array $args Arguments containing API key
		 * @return array Response array
		 * @since 5.0.0
		 */
		public static function mailchimp_lists( $args = array() ) {
			$args = self::sanitize_args( $args );
			
			$api_key = isset( $args['mailchimp_api_key'] ) ? sanitize_text_field( $args['mailchimp_api_key'] ) : '';
			
			if ( empty( $api_key ) ) {
				return array(
					'success' => false,
					'message' => __( 'Please provide a valid API key.', 'email-subscribers' )
				);
			}

			$api_instance = new ES_Mailchimp_API( $api_key );
			
			$lists = $api_instance->lists();
			
			if ( is_wp_error( $lists ) ) {
				return array( 
					'success' => false, 
					'message' => $lists->get_error_message() 
				);
			}
			
			$formatted_lists = array();
			if ( is_array( $lists ) ) {
				foreach ( $lists as $list ) {
					$formatted_list = array(
						'id'           => isset( $list->id ) ? $list->id : '',
						'name'         => isset( $list->name ) ? $list->name : '',
						'member_count' => isset( $list->stats ) && isset( $list->stats->member_count ) ? (int) $list->stats->member_count : 0,
					);
					$formatted_lists[] = $formatted_list;
				}
			}
			
			return array( 
				'success' => true, 
				'lists' => $formatted_lists 
			);
		}

		/**
		 * Import from Mailchimp list
		 *
		 * @param array $args Arguments containing API key, list ID, and status
		 * @return array Response array
		 * @since 5.0.0
		 */
		public static function mailchimp_import_list( $args = array() ) {
			$args = self::sanitize_args( $args );
			
			$api_key = isset( $args['mailchimp_api_key'] ) ? sanitize_text_field( $args['mailchimp_api_key'] ) : '';
			$list_id = isset( $args['list_id'] ) ? sanitize_text_field( $args['list_id'] ) : '';
			
			if ( empty( $api_key ) || empty( $list_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'Please provide valid API key and list ID.', 'email-subscribers' )
				);
			}

			try {
				$api_instance = new ES_Mailchimp_API( $api_key );
				
				$members_response = $api_instance->members( $list_id );
				
				if ( ! is_array( $members_response ) ) {
					return array(
						'success' => false,
						'message' => __( 'Failed to retrieve members from MailChimp list.', 'email-subscribers' )
					);
				}

				$csv_data = array();
				$headers = array( 'Email', 'First Name', 'Last Name', 'Status' );
				
				foreach ( $members_response as $member ) {
					if ( ! isset( $member->email_address ) || empty( $member->email_address ) ) {
						continue;
					}
					
					$email = sanitize_email( $member->email_address );
					if ( ! is_email( $email ) ) {
						continue;
					}
					
					$first_name = '';
					$last_name = '';
					if ( isset( $member->merge_fields ) ) {
						$first_name = isset( $member->merge_fields->FNAME ) ? sanitize_text_field( $member->merge_fields->FNAME ) : '';
						$last_name = isset( $member->merge_fields->LNAME ) ? sanitize_text_field( $member->merge_fields->LNAME ) : '';
					}
					
					$status = isset( $member->status ) ? sanitize_text_field( $member->status ) : 'subscribed';
					
					// Add to CSV data array
					$csv_data[] = array( $email, $first_name, $last_name, $status );
				}
				
				if ( empty( $csv_data ) ) {
					return array(
						'success' => false,
						'message' => __( 'No valid contacts found in the MailChimp list.', 'email-subscribers' )
					);
				}
				
				$csv_content = implode( ',', $headers ) . "\n";
				foreach ( $csv_data as $row ) {
					$csv_content .= implode( ',', array_map( function( $field ) {
						$field = str_replace( '"', '""', $field );
						if ( strpos( $field, ',' ) !== false || strpos( $field, '"' ) !== false || strpos( $field, "\n" ) !== false ) {
							return '"' . $field . '"';
						}
						return $field;
					}, $row ) ) . "\n";
				}
				
				$response = self::insert_into_temp_table( 
					$csv_content, 
					',', 
					true, 
					$headers, 
					'', 
					'mailchimp' 
				);
				
				if ( $response['success'] ) {
					$response['message'] = sprintf( 
						__( 'MailChimp data prepared for mapping. %d contacts loaded from list.', 'email-subscribers' ), 
						count( $csv_data )
					);
				}
				
				return $response;
				
			} catch ( Exception $e ) {
				return array( 
					'success' => false, 
					'message' => $e->getMessage() 
				);
			}
		}

		/**
		 * Get available WordPress roles for import
		 *
		 * Returns all WordPress roles that can be used for user import filtering.
		 * This includes default WordPress roles and custom roles from plugins/themes.
		 *
		 * @return array Array of roles with slug and display name
		 * @since 5.9.7
		 */
		public static function get_wordpress_roles() {
			if ( ! function_exists( 'get_editable_roles' ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
			}
			
			$roles = get_editable_roles();
			$formatted_roles = array();
			
			foreach ( $roles as $role_slug => $role_data ) {
				$formatted_roles[] = array(
					'slug' => $role_slug,
					'name' => translate_user_role( $role_data['name'] )
				);
			}
			
			return $formatted_roles;
		}

	}

}

ES_Contact_Import_Controller::get_instance();
