<?php

if ( ! class_exists( 'ES_Contacts_Controller' ) ) {

	/**
	 * Class to handle single form operation
	 * 
	 * @class ES_Contacts_Controller
	 */
	class ES_Contacts_Controller {

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

		
		// public static function load_import()
		// {
		// 	$import = new ES_Import_Subscribers();
		//     $import->import_subscribers_page();
		// }

		// public static function load_export()
		// {  
		// 	$export = new Export_Subscribers();
		//     $export->export_subscribers_page();
		// }

	/**
	 * Retrieve subscribers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
		public static function get_subscribers( $contact_args) {
			global $wpbd;
			$order_by     = isset( $contact_args['order_by'] ) ? esc_sql( $contact_args['order_by'] ) : 'created_at';
			$order        = isset( $contact_args['order'] ) ? strtoupper( $contact_args['order'] ) : 'DESC';
			$search       = isset( $contact_args['search'] ) ? $contact_args['search'] : '';
			$per_page     = isset( $contact_args['per_page'] ) ? (int) $contact_args['per_page'] : 5;
			$page_number  = isset( $contact_args['page_number'] ) ? (int) $contact_args['page_number'] : 1;
			$do_count_only = ! empty( $contact_args['do_count_only'] );
			$filter_by_list_id = isset( $contact_args['filter_by_list_id'] ) ? $contact_args['filter_by_list_id'] : '';
			$filter_by_status = isset( $contact_args['filter_by_status'] ) ? $contact_args['filter_by_status'] : '';
			$advanced_filter = isset( $contact_args['advanced_filter'] ) ? $contact_args['advanced_filter'] : '';

			$contacts_table       = IG_CONTACTS_TABLE;
			$lists_contacts_table = IG_LISTS_CONTACTS_TABLE;

			$add_where_clause = false;

			$args  = array();
			$query = array();

			if ( $do_count_only ) {
				$sql = "SELECT count(*) FROM {$contacts_table}";
			} else {
				$sql = "SELECT * FROM {$contacts_table}";
			}

			// Construct proper query conditions for advanced filtering
			if ( !empty ( $advanced_filter ) ) {

				$query_obj  = new IG_ES_Subscribers_Query();
				$query_args = array(
				'select'    => array( 'subscribers.id' ),
				'conditions'=> $advanced_filter,
				'return_sql'=> true,
				);

				$condition = $query_obj->run($query_args);

				array_push($query, 'id IN ( ' . $condition . ' )');
				$add_where_clause = true;
			}
			// Prepare filter by list query
			if ( ! empty( $filter_by_list_id ) || ! empty( $filter_by_status ) ) {
				$add_where_clause = true;

				$filter_sql = "SELECT contact_id FROM {$lists_contacts_table}";

				$list_filter_sql    = '';
				$where_clause_added = false;

				if ( ! empty( $filter_by_list_id ) ) {
					$list_filter_sql    = $wpbd->prepare( ' WHERE list_id = %d', $filter_by_list_id );
					$where_clause_added = true;
				}

				if ( ! empty( $filter_by_status ) ) {
					$list_filter_sql .= ( $where_clause_added ) ? ' AND ' : ' WHERE';
					if ( 'soft_bounced' === $filter_by_status ) {
						$list_filter_sql .= $wpbd->prepare( ' bounce_status = %s', 1 );
					} elseif ( 'hard_bounced' === $filter_by_status ) {
						$list_filter_sql .= $wpbd->prepare( ' bounce_status = %s', 2 );
					} else {
						$list_filter_sql .= $wpbd->prepare( ' status = %s', $filter_by_status );
					}
				}

				$filter_sql .= $list_filter_sql;
				$query[]     = "id IN ( $filter_sql )";
			}

			// Prepare search query
			if ( ! empty( $search ) ) {
				$query[] = ' ( first_name LIKE %s OR last_name LIKE %s OR email LIKE %s ) ';
				$args[]  = '%' . $wpbd->esc_like( $search ) . '%';
				$args[]  = '%' . $wpbd->esc_like( $search ) . '%';
				$args[]  = '%' . $wpbd->esc_like( $search ) . '%';
			}

			if ( $add_where_clause || count( $query ) > 0 ) {
				$sql .= ' WHERE ';

				if ( count( $query ) > 0 ) {
					$sql .= implode( ' AND ', $query );
					if ( ! empty( $args ) ) {
						$sql = $wpbd->prepare( $sql, $args );
					}
				}
			}

			if ( ! $do_count_only ) {

				// Prepare Order by clause
				$order                 = ! empty( $order ) ? strtolower( $order ) : 'desc';
				$expected_order_values = array( 'asc', 'desc' );
				if ( ! in_array( $order, $expected_order_values ) ) {
					$order = 'desc';
				}

				$offset = ( $page_number - 1 ) * $per_page;

				$expected_order_by_values = array( 'name', 'email', 'created_at', 'first_name' );
				if ( ! in_array( $order_by, $expected_order_by_values ) ) {
					$order_by = 'created_at';
				}

				$order_by = esc_sql( $order_by );

				$order_by_clause = " ORDER BY {$order_by} {$order}";

				$sql .= $order_by_clause;
				$sql .= " LIMIT {$offset}, {$per_page}";

				$result = $wpbd->get_results( $sql, 'ARRAY_A' );
			} else {
				$result = $wpbd->get_var( $sql );
			}
			return $result;
		}

	}

}

ES_Contacts_Controller::get_instance();
