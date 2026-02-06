<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for import utility database operations
 *
 * This class handles database queries related to import operations
 * that don't belong to specific entity classes like contacts, lists, etc.
 *
 * @since 5.9.4
 */
class ES_DB_Import_Utils extends ES_DB {

	/**
	 * ES_DB_Import_Utils constructor.
	 *
	 * @since 5.9.4
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get WooCommerce order IDs by product IDs
	 *
	 * Retrieves order IDs that contain specific products by querying
	 * WooCommerce order items and meta tables.
	 *
	 * @param array $product_ids Array of product IDs to search for
	 * @return array Array of order IDs that contain the specified products
	 *
	 * @since 5.9.4 (moved from ES_DB_Contacts)
	 * @since 4.9.0 (original implementation)
	 */
	public function get_woocommerce_order_ids_by_product_ids( $product_ids = array() ) {
		global $wpdb;

		if ( empty( $product_ids ) || ! is_array( $product_ids ) ) {
			return array();
		}

		$product_ids_count        = count( $product_ids );
		$product_ids_placeholders = array_fill( 0, $product_ids_count, '%d' );

		$order_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT order_id
				FROM {$wpdb->prefix}woocommerce_order_items
				WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value IN( " . implode( ',', $product_ids_placeholders ) . " ) )
				AND order_item_type = 'line_item'",
				$product_ids
			)
		);

		// Force wc_get_orders return empty if we don't found any order.
		return ! empty( $order_ids ) ? $order_ids : array( 0 );
	}

	/**
	 * Get WooCommerce placeholder order IDs that have items
	 *
	 * Retrieves placeholder order IDs that contain line items.
	 * Placeholder orders are used by WooCommerce for draft/incomplete orders.
	 *
	 * @return array Array of placeholder order IDs that contain line items
	 *
	 * @since 5.9.4 (moved from ES_DB_Contacts)
	 * @since 4.9.0 (original implementation)
	 */
	public function get_woocommerce_placeholder_order_ids_with_items() {
		global $wpdb;

		$placeholder_orders = $wpdb->get_col( "
			SELECT DISTINCT p.ID 
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
			WHERE p.post_type = 'shop_order_placehold'
			AND oi.order_item_type = 'line_item'
		" );

		return ! empty( $placeholder_orders ) ? $placeholder_orders : array();
	}

	/**
	 * Get list contacts data for export operations
	 *
	 * Retrieves contact data from the lists_contacts table with flexible filtering.
	 * This method provides a consistent way to query list-contact relationships
	 * for both import and export operations.
	 *
	 * @param string $where_clause The WHERE clause conditions (without WHERE keyword)
	 * @param array $params Parameters for the prepared statement
	 * @return array Array of contact-list relationship data
	 *
	 * @since 5.9.4
	 */
	public function get_list_contacts_data( $where_clause, $params = array() ) {
		global $wpdb;

		if ( empty( $where_clause ) ) {
			return array();
		}

		$query = "SELECT 
			lc.contact_id, 
			lc.list_id, 
			lc.status, 
			lc.optin_type, 
			lc.subscribed_at, 
			lc.subscribed_ip, 
			lc.unsubscribed_at, 
			lc.unsubscribed_ip
			FROM {$wpdb->prefix}ig_lists_contacts lc 
			WHERE " . $where_clause . "
			ORDER BY lc.contact_id ASC";

		if ( ! empty( $params ) ) {
			$results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );
		} else {
			$results = $wpdb->get_results( $query, ARRAY_A );
		}

		return ! empty( $results ) ? $results : array();
	}

	/**
	 * Get contacts data by IDs
	 *
	 * Retrieves contact details for specific contact IDs including custom fields.
	 * Used by export operations to get full contact information.
	 *
	 * @param array $contact_ids Array of contact IDs to retrieve
	 * @param bool $include_custom_fields Whether to include custom fields in the query
	 * @return array Array of contact data
	 *
	 * @since 5.9.4
	 */
	public function get_contacts_by_ids( $contact_ids = array(), $include_custom_fields = false ) {
		global $wpdb;

		if ( empty( $contact_ids ) || ! is_array( $contact_ids ) ) {
			return array();
		}

		// Sanitize contact IDs
		$contact_ids = array_filter( array_map( 'absint', $contact_ids ), function( $id ) {
			return $id > 0;
		} );

		if ( empty( $contact_ids ) ) {
			return array();
		}

		$select_columns = array(
			'id',
			'first_name',
			'last_name',
			'email',
			'created_at',
		);

		// Add custom fields if requested
		if ( $include_custom_fields ) {
			$custom_fields = ES()->custom_fields_db->get_custom_fields();
			if ( ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $field ) {
					$select_columns[] = $field['slug'];
				}
			}
		}

		$contact_ids_str = implode( ',', $contact_ids );
		$query = 'SELECT ' . implode( ',', $select_columns ) . " FROM {$wpdb->prefix}ig_contacts WHERE id IN ({$contact_ids_str})";
		
		$results = $wpdb->get_results( $query, ARRAY_A );
		return ! empty( $results ) ? $results : array();
	}

	/**
	 * Get WordPress users with their meta data for import
	 *
	 * Retrieves WordPress users along with their capabilities and metadata
	 * for importing into the contact system. This is an import utility function.
	 *
	 * @param array $selected_roles Array of user roles to filter by
	 * @return array Array of user objects with email, roles, and meta data
	 *
	 * @since 5.9.4 (moved from ES_DB_Contacts)
	 * @since 5.0.0 (original implementation)
	 */
	public function get_wordpress_users_for_import( $selected_roles = array() ) {
		global $wpdb;

		$users = $wpdb->get_results(
			"SELECT u.user_email, IF(meta_role.meta_value = 'a:0:{}',NULL,meta_role.meta_value) AS '_role', meta_firstname.meta_value AS 'firstname', meta_lastname.meta_value AS 'lastname', u.display_name, u.user_nicename
			FROM {$wpdb->users} AS u
			LEFT JOIN {$wpdb->usermeta} AS meta_role ON meta_role.user_id = u.ID AND meta_role.meta_key = '{$wpdb->prefix}capabilities'
			LEFT JOIN {$wpdb->usermeta} AS meta_firstname ON meta_firstname.user_id = u.ID AND meta_firstname.meta_key = 'first_name'
			LEFT JOIN {$wpdb->usermeta} AS meta_lastname ON meta_lastname.user_id = u.ID AND meta_lastname.meta_key = 'last_name'
			WHERE meta_role.user_id IS NOT NULL"
		);

		// Filter users by selected roles if provided
		if ( ! empty( $selected_roles ) && ! empty( $users ) ) {
			$filtered_users = array();
			foreach ( $users as $user ) {
				if ( ! $user->_role ) {
					continue;
				}
				$user_roles = ig_es_maybe_unserialize( $user->_role );
				if ( is_array( $user_roles ) && array_intersect( array_keys( $user_roles ), $selected_roles ) ) {
					$filtered_users[] = $user;
				}
			}
			return $filtered_users;
		}

		return $users ? $users : array();
	}
}