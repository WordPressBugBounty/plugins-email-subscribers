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
			// Default arguments
			$defaults = array(
				'per_page'    => -1,
				'page_number' => 1,
				'order_by'    => 'created_at',
				'order'       => 'DESC',
				'search'      => '',
				'status'      => 'active'
			);

			$args = wp_parse_args( $args, $defaults );

			// Use the lists database to get lists
			$lists_db = ES()->lists_db;
			
			// Get all lists since we don't have status filtering in the basic method
			$all_lists = $lists_db->get_lists();
			
			// Filter by search if provided
			if ( ! empty( $args['search'] ) && 'none' !== $args['search'] ) {
				$all_lists = array_filter( $all_lists, function( $list ) use ( $args ) {
					return stripos( $list['name'], $args['search'] ) !== false;
				});
			}

			// Convert to the expected format
			$formatted_lists = array();
			foreach ( $all_lists as $list ) {
				$formatted_lists[] = array(
					'id'              => (int) $list['id'],
					'name'            => $list['name'],
					'description'     => ! empty( $list['desc'] ) ? $list['desc'] : '',
					'status'          => 'active', // Default status
					'created_at'      => $list['created_at'],
					'updated_at'      => $list['updated_at'],
					'subscriber_count' => ! empty( $list['total_contacts'] ) ? (int) $list['total_contacts'] : 0,
				);
			}

			// Apply pagination if not getting all
			if ( $args['per_page'] > 0 ) {
				$offset = ( $args['page_number'] - 1 ) * $args['per_page'];
				$formatted_lists = array_slice( $formatted_lists, $offset, $args['per_page'] );
			}

			return $formatted_lists;
		}

		/**
		 * Get a single list by ID
		 *
		 * @param array $args Arguments containing list_id
		 *
		 * @return array|false
		 */
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

			if ( empty( $args['name'] ) ) {
				$response['message'] = __( 'List name is required.', 'email-subscribers' );
				return $response;
			}

			$name = sanitize_text_field( $args['name'] );
			$desc = ! empty( $args['description'] ) ? sanitize_text_field( $args['description'] ) : '';

			// Check if list already exists
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

			// Check if another list with the same name exists
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
	}
}

ES_Lists_Controller::get_instance();
