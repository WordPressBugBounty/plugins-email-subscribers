<?php

if ( ! class_exists( 'ES_Lists_Controller' ) ) {

	/**
	 * Class to handle lists operations via API
	 * 
	 * @class ES_Lists_Controller
	 */
	class ES_Lists_Controller {

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

		/**
		 * Get lists for API requests
		 *
		 * @param array $args Arguments for fetching lists
		 *
		 * @return array
		 */
		public static function get_lists( $args = array() ) {
			if ( is_string( $args ) ) {
                $decoded = json_decode( $args, true );
                if ( $decoded ) {
                    $args = $decoded;
                }									
            }

            $order_by     = isset( $args['order_by'] ) ? $args['order_by'] : 'created_at';
            $order        = isset( $args['order'] ) ? strtoupper( $args['order'] ) : 'DESC';
            $search       = isset( $args['search'] ) ? $args['search'] : '';
            $per_page     = isset( $args['per_page'] ) ? (int) $args['per_page'] : 20;
            $page_number  = isset( $args['page_number'] ) ? (int) $args['page_number'] : 1;
            $do_count_only = ! empty( $args['do_count_only'] );

            $lists_db = ES()->lists_db;
            $lists_contacts_db = ES()->lists_contacts_db;

            $order = ! empty( $order ) ? strtolower( $order ) : 'desc';
            $expected_order_values = array( 'asc', 'desc' );
            if ( ! in_array( $order, $expected_order_values ) ) {
                $order = 'desc';
            }

            $expected_order_by_values = array( 'name', 'created_at', 'updated_at' );
            if ( ! in_array( $order_by, $expected_order_by_values ) ) {
                $order_by = 'created_at';
            }

            if ( $do_count_only ) {
			
			if ( ! empty( $search ) && 'none' !== $search ) {
				$where_condition = "name LIKE '%" . esc_sql( $search ) . "%'";
				$total_count = $lists_db->count( $where_condition );
			} else {
				$total_count = $lists_db->count();
			}
			
			return array(
                'total' => $total_count
            );
            }

            $es_args = array(
                'order_by'    => $order_by,
                'order'       => strtoupper( $order ),
                'per_page'    => $per_page,
                'page_number' => $page_number,
            );

            if ( ! empty( $search ) && 'none' !== $search ) {

				if ( method_exists( $lists_db, 'get_lists_by_conditions' ) ) {
					$lists = $lists_db->get_lists_by_conditions( array(
						'conditions' => array(
							array(
								'key'     => 'name',
								'value'   => '%' . $search . '%',
								'compare' => 'LIKE'
							)
						),
						'order_by'    => $order_by,
						'order'       => strtoupper( $order ),
						'per_page'    => $per_page,
						'page_number' => $page_number,
					) );
				}
				else if ( method_exists( $lists_db, 'get_lists_by_name_like' ) ) {
					$lists = $lists_db->get_lists_by_name_like( $search, array(
						'order_by'    => $order_by,
						'order'       => strtoupper( $order ),
						'per_page'    => $per_page,
						'page_number' => $page_number,
					) );
				}
				else {
					$all_lists_args = array(
						'order_by'    => $order_by,
						'order'       => strtoupper( $order ),
						'per_page'    => -1,  
						'page_number' => 1,
					);
					$all_lists = $lists_db->get_lists( $all_lists_args );
					
					$filtered_lists = array();
					if ( ! empty( $all_lists ) ) {
						foreach ( $all_lists as $list ) {
							if ( stripos( $list['name'], $search ) !== false ) {
								$filtered_lists[] = $list;
							}
						}
					}
					
					$offset = ( $page_number - 1 ) * $per_page;
					$lists = array_slice( $filtered_lists, $offset, $per_page );
				}
				
            } else {
			$lists = $lists_db->get_lists( $es_args );
		}

		if ( ! empty( $lists ) ) {
			$formatted_lists = array();

			$list_ids = array_column( $lists, 'id' );
			
			$skip_cache = ! empty( $args['_t'] );
			$all_counts = $lists_contacts_db->get_list_contact_counts_batch( $list_ids, $skip_cache );
                foreach ( $lists as $list ) {
                    $list_id = (int) $list['id'];
                    $counts = isset( $all_counts[ $list_id ] ) ? $all_counts[ $list_id ] : array(
                        'total' => 0,
                        'subscribed' => 0,
                        'unsubscribed' => 0,
                        'unconfirmed' => 0,
                    );

                    $formatted_lists[] = array(
                        'id'                 => $list_id,
                        'name'               => $list['name'],
                        'description'        => ! empty( $list['desc'] ) ? $list['desc'] : '',
                        'status'             => 'active',  
                        'created_at'         => $list['created_at'],
                        'updated_at'         => $list['updated_at'],
                        'subscriber_count'   => ! empty( $list['total_contacts'] ) ? (int) $list['total_contacts'] : 0,
                        'no_of_contacts'     => $counts['total'],
                        'subscribed_count'   => $counts['subscribed'],
                        'unsubscribed_count' => $counts['unsubscribed'],
                        'unconfirmed_count'  => $counts['unconfirmed'],
                    );
                }

            return $formatted_lists;
        }

        return array();
			
	}
		public static function get_list( $args = array() ) {
			if ( empty( $args['list_id'] ) ) {
				return false;
			}

			$list_id = intval( $args['list_id'] );
			$list = ES()->lists_db->get_list_by_id( $list_id );
			
			if ( empty( $list ) ) {
				return false;
			}

			return array(
				'id'              => (int) $list['id'],
				'name'            => $list['name'],
				'description'     => ! empty( $list['desc'] ) ? $list['desc'] : '',
				'status'          => 'active',
				'created_at'      => $list['created_at'],
				'updated_at'      => $list['updated_at'],
				'subscriber_count' => ! empty( $list['total_contacts'] ) ? (int) $list['total_contacts'] : 0,
			);
		}

		/**
		 * Create a new list
		 *
		 * @param array $args Arguments for creating list
		 *
		 * @return array
		 */
		public static function create_list( $args = array() ) {
			$response = array( 'status' => 'error', 'message' => '' );

			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				if ( $decoded ) {
					$args = $decoded;
				}
			}

			if ( empty( $args['name'] ) ) {
				$response['message'] = __( 'List name is required.', 'email-subscribers' );
				return $response;
			}

			$name = sanitize_text_field( $args['name'] );
			$desc = ! empty( $args['description'] ) ? sanitize_text_field( $args['description'] ) : '';

 			if ( ES()->lists_db->is_list_exists( $name ) ) {
				$response['message'] = __( 'List already exists. Please choose a different name.', 'email-subscribers' );
				return $response;
			}

			$list_data = array(
				'name' => $name,
				'desc' => $desc,
			);

			$list_id = ES()->lists_db->add_list( $list_data );

			if ( $list_id ) {
				$response['status'] = 'success';
				$response['message'] = __( 'List created successfully.', 'email-subscribers' );
				$response['list_id'] = $list_id;
			} else {
				$response['message'] = __( 'Failed to create list.', 'email-subscribers' );
			}

			return $response;
		}

		/**
		 * Update an existing list
		 *
		 * @param array $args Arguments for updating list
		 *
		 * @return array
		 */
		public static function update_list( $args = array() ) {
			$response = array( 'status' => 'error', 'message' => '' );

			if ( empty( $args['id'] ) ) {
				$response['message'] = __( 'List ID is required.', 'email-subscribers' );
				return $response;
			}

			if ( empty( $args['name'] ) ) {
				$response['message'] = __( 'List name is required.', 'email-subscribers' );
				return $response;
			}

			$list_id = intval( $args['id'] );
			$name = sanitize_text_field( $args['name'] );
			$desc = ! empty( $args['description'] ) ? sanitize_text_field( $args['description'] ) : '';

 			$existing_list = ES()->lists_db->get_list_by_name( $name );
			if ( $existing_list && $existing_list['id'] != $list_id ) {
				$response['message'] = __( 'List already exists. Please choose a different name.', 'email-subscribers' );
				return $response;
			}

			$list_data = array(
				'name' => $name,
				'desc' => $desc,
			);

			$result = ES()->lists_db->update_list( $list_id, $list_data );

			if ( $result ) {
				$response['status'] = 'success';
				$response['message'] = __( 'List updated successfully.', 'email-subscribers' );
			} else {
				$response['message'] = __( 'Failed to update list.', 'email-subscribers' );
			}

			return $response;
		}

		/**
		 * Delete a list
		 *
		 * @param array $args Arguments containing list_id
		 *
		 * @return array
		 */
		public static function delete_list( $args = array() ) {
			$response = array( 'status' => 'error', 'message' => '' );

			if ( empty( $args['list_id'] ) ) {
				$response['message'] = __( 'List ID is required.', 'email-subscribers' );
				return $response;
			}

			$list_id = intval( $args['list_id'] );
			$result = ES()->lists_db->delete_list( $list_id );

			if ( $result ) {
				$response['status'] = 'success';
				$response['message'] = __( 'List deleted successfully.', 'email-subscribers' );
			} else {
				$response['message'] = __( 'Failed to delete list.', 'email-subscribers' );
			}

			return $response;
		}

		/**
		 * Delete a list
		 *
		 * @param array $args Arguments containing list_id
		 *
		 * @return array
		 */
		public static function delete_lists( $args = array() ) {
			$response = array( 'status' => 'error', 'message' => '' );

			if ( is_string( $args ) ) {
				$args = json_decode( $args, true );
			}
			if ( ! is_array( $args ) ) {
				$args = array();
			}
			
			$list_ids = $args['list_ids'];
			if ( ! empty( $list_ids ) ) {
				ES()->lists_db->delete_lists( $list_ids );
				return true;
			}
			return false;
		}

		/**
		 * Get country statistics for contacts (legacy method)
		 *
		 * @param array $args Arguments containing optional filters
		 * @return array
		 */
		public static function get_legacy_country_stats( $args = array() ) {

			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				if ( $decoded ) {
					$args = $decoded;
				}
			}

			$args['countries_count'] = 5;

			$top_countries = ES()->contacts_db->get_top_countries_by_days_and_list( $args );

			$countries = ES_Geolocation::get_countries();

			$country_data_array = array();

			$max_count = max( $top_countries );

			$max_width = 500;
 
			foreach ( $top_countries as $country_code => $total_subscribers ) {

				if ( 'others' === $country_code ) {
					$country_name = __('Others', 'email-subscribers');
				} else {
					$country_name = ! empty( $countries[ $country_code ] ) ? $countries[ $country_code ] : '';
				}

				$calculated_width = 0;
				if ( $max_count > 0 ) {
					$calculated_width = intval( ( $total_subscribers / $max_count ) * $max_width );
				}
				
				$country_data_array[] = array(
					'country_code'      => $country_code,
					'country_name'      => $country_name,
					'total_subscribers' => (int) $total_subscribers,
					'width'				=> 'w-[' . $calculated_width . 'px]'
				);	
			}

			return $country_data_array; 
		}

		/**
		 * Get country statistics for audience insights
		 *
		 * @param array $args Arguments containing list_id and days
		 * @return array
		 */
		public static function get_country_stats( $args = array() ) {

		if ( is_string( $args ) ) {
			$decoded = json_decode( $args, true );
			if ( $decoded ) {
				$args = $decoded;
			}
		}

		$list_id = isset( $args['list_id'] ) ? $args['list_id'] : 'all';
		$days = isset( $args['days'] ) ? intval( $args['days'] ) : 7;
		
		$db_args = array(
			'list_id' => $list_id,
			'days' => $days,
			'countries_count' => 5  
		);
		
		try {
			$top_countries = ES_DB_Contacts::get_top_countries_by_days_and_list( $db_args );
				if ( empty( $top_countries ) ) {
					return array();
				}
				
				$countries_data = array();
				$country_names = ES_Geolocation::get_countries();

				$max_count = max( $top_countries );

				$max_width = 500;
				
				foreach ( $top_countries as $country_code => $total_subscribers ) {
					$country_code = strtoupper( $country_code );
					
					$country_name = isset( $country_names[ $country_code ] ) ? $country_names[ $country_code ] : $country_code;

					$calculated_width = 0;
					if ( $max_count > 0 ) {
						$calculated_width = intval( ( $total_subscribers / $max_count ) * $max_width );
					}
					
					$countries_data[] = array(
						'country_name'       => $country_name,
						'country_code'       => $country_code,
						'total_subscribers'  => intval( $total_subscribers ),
						'width'              => 'w-[' . $calculated_width . 'px]'
					);
				}
				
		return $countries_data;
		
	} catch ( Exception $e ) {

		return array();
	}
}

	public static function verify_emails() {

		$cleaner = ES_List_Cleanup::get_instance();
		$plan = ES()->get_plan();

			if ( 'pro' === strtolower( $plan ) ) {
 				
				$cleaner->clean_emails();

				return array(
					'status'  => 'success',
					'message' => __( 'List cleanup completed', 'email-subscribers' ),
				);
			}

			if( $cleaner->can_do_list_cleanup() ) {

				$cleaner->clean_emails();  

				return array( 'status' => 'success', 'message' => __( 'List cleanup completed!', 'email-subscribers' ) );
			}

			return array( 'status' => 'error', 'message' => __( 'List cleanup is available only one time.', 'email-subscribers' ) );
		}

		public static function check_list_cleanup_used() {
 
			$used = get_option('ig_list_cleanup_used', false);
  
			$plan = ES()->get_plan();

			if ( $plan && 'pro' === $plan ) {
				return array(
					'status'   => 'success',
					'has_used' => false,
					'message'  => __( 'Pro plan: unlimited list cleanup access.', 'email-subscribers' ),
				);
			}

 
			if( $used ) {

				return array( 'status' => 'success', 'has_used' => true, 'message' => __( 'List cleanup already used.', 'email-subscribers' ) );
			} 
			
			return array( 'status' => 'error', 'has_used' => false, 'message' => __( 'List cleanup not used yet. ', 'email-subscribers' ) );	
		}
	}
}

ES_Lists_Controller::get_instance();
