<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Actions extends ES_DB {
	/**
	 * Table Name
	 *
	 * @since 4.2.1
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Version
	 *
	 * @since 4.2.1
	 * @var $version
	 */
	public $version;

	/**
	 * Primary Key
	 *
	 * @since 4.2.1
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB_Lists constructor.
	 *
	 * @since 4.2.1
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'ig_actions';

		$this->version = '1.0';

	}

	/**
	 * Get table columns
	 *
	 * @return array
	 *
	 * @since 4.2.1
	 */
	public function get_columns() {
		return array(
			'contact_id'   => '%d',
			'message_id'   => '%d',
			'campaign_id'  => '%d',
			'type'         => '%d',
			'count'        => '%d',
			'link_id'      => '%d',
			'list_id'      => '%d',
			'ip'           => '%s',
			'country'      => '%s',
			'device'       => '%s',
			'browser'      => '%s',
			'email_client' => '%s',
			'os'           => '%s',
			'created_at'   => '%d',
			'updated_at'   => '%d',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since  4.2.1
	 */
	public function get_column_defaults() {
		return array(
			'contact_id'   => null,
			'message_id'   => null,
			'campaign_id'  => null,
			'type'         => 0,
			'count'        => 0,
			'link_id'      => 0,
			'list_id'      => 0,
			'ip'           => '',
			'country'      => '',
			'device'       => '',
			'browser'      => '',
			'email_client' => '',
			'os'           => '',
			'created_at'   => ig_es_get_current_gmt_timestamp(),
			'updated_at'   => ig_es_get_current_gmt_timestamp(),
		);
	}

	/**
	 * Track action
	 *
	 * @param $args
	 * @param bool $explicit
	 *
	 * @return bool
	 *
	 * @since 4.2.4
	 */
	public function add( $args, $explicit = true ) {

		global $wpbd;

		$ig_actions_table = IG_ACTIONS_TABLE;

		$args_keys = array_keys( $args );

		$args_keys_str = implode( ', ', $args_keys );

		$sql = "INSERT INTO $ig_actions_table ($args_keys_str)";

		$args_values_array = array();
		if ( is_array( $args['contact_id'] ) ) {
			$contact_ids = $args['contact_id'];
			foreach ( $contact_ids as $contact_id ) {
				$args['contact_id'] = $contact_id;

				$args_values = array_values( $args );
				$args_values = esc_sql( $args_values );

				$args_values_array[] = $this->prepare_for_in_query( $args_values );
			}
		} else {
			$args_values = array_values( $args );
			$args_values = esc_sql( $args_values );

			$args_values_array[] = $this->prepare_for_in_query( $args_values );
		}

		$sql .= ' VALUES ( ' . implode( '), (', $args_values_array ) . ' )';
		$sql .= ' ON DUPLICATE KEY UPDATE';
		$sql .= ( $explicit ) ? $wpbd->prepare( ' created_at = created_at, count = count+1, updated_at = %d, ip = %s, country = %s, browser = %s, device = %s, os = %s, email_client = %s', ig_es_get_current_gmt_timestamp(), $args['ip'], $args['country'], $args['browser'], $args['device'], $args['os'], $args['email_client'] ) : ' count = values(count)';

		$result = $wpbd->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false !== $result ) {
			return true;
		}

		return false;
	}

	/**
	 * Insert data into bulk
	 *
	 * @param array $values
	 * @param int   $length
	 * @param bool  $return_insert_ids
	 *
	 * @since 4.2.1
	 *
	 * @since 4.3.5 Fixed issues and started using it.
	 */
	public function bulk_add( $values = array(), $length = 100, $explicit = true ) {
		global $wpbd;

		if ( ! is_array( $values ) ) {
			return false;
		}

		// Get the first value from an array to check data structure
		$first_value = array_slice( $values, 0, 1 );

		$data = array_shift( $first_value );

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Remove primary key as we don't require while inserting data
		unset( $column_formats[ $this->primary_key ] );

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		$data_keys = array_keys( $data );

		$fields = array_keys( array_merge( array_flip( $data_keys ), $column_formats ) );

		// Convert Batches into smaller chunk
		$batches = array_chunk( $values, $length );

		$success = false;

		// Holds first and last row ids of each batch insert
		$bulk_rows_start_end_ids = [];

		foreach ( $batches as $key => $batch ) {

			$place_holders = array();
			$final_values  = array();
			$fields_str    = '';

			foreach ( $batch as $value ) {

				$formats = array();
				foreach ( $column_formats as $column => $format ) {
					$final_values[] = isset( $value[ $column ] ) ? $value[ $column ] : $data[ $column ]; // set default if we don't have
					$formats[]      = $format;
				}

				$place_holders[] = '( ' . implode( ', ', $formats ) . ' )';
				$fields_str      = '`' . implode( '`, `', $fields ) . '`';
			}

			$query  = "INSERT INTO $this->table_name ({$fields_str}) VALUES ";
			$query .= implode( ', ', $place_holders );
			$query .= ' ON DUPLICATE KEY UPDATE';
			$query .= ' count = count + 1';
			$sql    = $wpbd->prepare( $query, $final_values );

			if ( $wpbd->query( $sql ) ) {
				$success = true;
			}
		}

		return $success;
	}

	/**
	 * Get contacts count based on type, list id, days args
	 *
	 * @param int $args
	 *
	 * @return int $count
	 *
	 * @since 5.5.5
	 */
	public function get_count( $args = array(), $distinct = true ) {
		global $wpbd;

		// Generate cache key from arguments
		$cache_key = ES_Cache::generate_key( 'ig_es_actions_get_count_' . md5( serialize( array( $args, $distinct ) ) ) );
		
		// Check cache first (5 minute TTL)
		if ( ES_Cache::is_exists( $cache_key, 'query' ) ) {
			return ES_Cache::get( $cache_key, 'query' );
		}

		$contacts_count = 0;

		$query = 'SELECT';
		if ( $distinct ) {
			$query .= ' COUNT( DISTINCT ( contact_id ) )';
		} else {
			$query .= ' COUNT( contact_id )';
		}

		$query .= " FROM {$wpbd->prefix}ig_actions";

		$where = array();
		if ( ! empty( $args['type'] ) ) {
			$where[] = $wpbd->prepare( 'type = %d', esc_sql( $args['type'] ) );
		}

		if ( ! empty( $args['list_id'] ) ) {
			$where[] = $wpbd->prepare( 'list_id = %d', esc_sql( $args['list_id'] ) );
		}

		if ( ! empty( $args['days'] ) ) {
			$where[] = $wpbd->prepare( 'created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))', esc_sql( $args['days'] ) );
		}

		if ( ! empty( $where ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where );
		}

		$contacts_count = $wpbd->get_var(
			$query
		);

		// Cache results for 5 minutes
		ES_Cache::set( $cache_key, $contacts_count, 'query', 300 );

		return $contacts_count;
	}

	public function get_actions( $args = array() ) {
		global $wpbd;

		$cache_key = ES_Cache::generate_key( 'ig_es_actions_get_actions_' . md5( serialize( $args ) ) );
		
		if ( ES_Cache::is_exists( $cache_key, 'query' ) ) {
			return ES_Cache::get( $cache_key, 'query' );
		}

		$where = array();

		if ( ! empty( $args['contact_id'] ) && intval($args['contact_id']) > 0 ) {
			$where[] = $wpbd->prepare( 'contact_id = %d', intval($args['contact_id']) );
		}
		
		if ( ! empty( $args['type'] ) ) {
			if ( is_array( $args['type'] ) ) {
				$types_count        = count( $args['type'] );
				$types_placeholders = array_fill( 0, $types_count, '%d' );
				$where[] = $wpbd->prepare( 'type IN( ' . implode( ',', $types_placeholders ) . ' )', esc_sql( $args['type'] ) );
			} else {
				$where[] = $wpbd->prepare( 'type = %d', esc_sql( $args['type'] ) );
			}
		}

		if ( ! empty( $where ) ) {
			$where = 'WHERE (' . implode( ') AND (', $where ) . ')';
		} else {
			$where = '';
		}

		$limit = '';
		if ( ! empty( $args['limit'] ) ) {
			$limit = $wpbd->prepare( 'LIMIT %d', esc_sql( $args['limit'] ) );
		}

		$order_by = '';
		if ( ! empty( $args['order_by'] ) ) {
			$order_by_column = esc_sql( $args['order_by'] );
			$order           = ! empty( $args['order'] ) ? esc_sql( $args['order'] ) : 'ASC';
			$order_by        = "ORDER BY {$order_by_column} {$order}";
		}

		$query = "SELECT * FROM {$wpbd->prefix}ig_actions {$where} {$order_by} {$limit}" ;

		$actions = $wpbd->get_results(
			$query,
			ARRAY_A
		);

		// Cache results for 5 minutes
		ES_Cache::set( $cache_key, $actions, 'query', 300 );

		return $actions;
	}

	/**
	 * Get actions count by type
	 * @param array $args
	 * @return array
	 * 
	 * @since 4.2.1
	 */
	public function get_actions_count( $args = array() ) {
		global $wpbd;
		
		$cache_key = ES_Cache::generate_key( 'ig_es_actions_count_' . md5( serialize( $args ) ) );
		
		if ( ES_Cache::is_exists( $cache_key, 'query' ) ) {
			return ES_Cache::get( $cache_key, 'query' );
		}
		
		$results = array();
		
		if ( empty( $args['types'] ) ) {
			$query = "SELECT COUNT(*) as total FROM {$wpbd->prefix}ig_actions";
			$where = $this->build_where_clause( $args );
			if ( ! empty( $where ) ) {
				$query .= ' WHERE ' . implode( ' AND ', $where );
			}
			$results = array( 'total' => $wpbd->get_var( $query ) );
			ES_Cache::set( $cache_key, $results, 'query', 300 ); 
			return $results;
		}
		
		$where = $this->build_where_clause( $args );
		$where_conditions = ! empty( $where ) ? $where : array();
		
		$types = $args['types'];
		$type_placeholders = implode( ',', array_fill( 0, count( $types ), '%d' ) );
		$where_conditions[] = $wpbd->prepare( "type IN ($type_placeholders)", $types );
		
		$where_sql = ' WHERE ' . implode( ' AND ', $where_conditions );
		
		$group_by_campaign = ! empty( $args['campaign_ids'] ) && is_array( $args['campaign_ids'] );
		
		if ( $group_by_campaign ) {
		// Use SQL aggregation to avoid loading thousands of rows into PHP memory
		$query = "SELECT 
			campaign_id,
			type,
			CASE 
				WHEN type IN (" . IG_MESSAGE_OPEN . ", " . IG_LINK_CLICK . ", " . IG_CONTACT_UNSUBSCRIBE . ") 
				THEN COUNT(DISTINCT contact_id)
				ELSE COUNT(*)
			END as action_count
			FROM {$wpbd->prefix}ig_actions" . $where_sql . "
			GROUP BY campaign_id, type";
		
		$rows = $wpbd->get_results( $query );
		
		$campaign_results = array();
		foreach ( $args['campaign_ids'] as $cid ) {
			$campaign_results[ $cid ] = array(
				'subscribed'   => 0,
				'sent'         => 0,
				'opened'       => 0,
				'clicked'      => 0,
				'unsubscribed' => 0,
				'soft_bounced' => 0,
				'hard_bounced' => 0,
			);
		}
		
		// Fill in the aggregated counts from MySQL
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$campaign_id = (int) $row->campaign_id;
				$type = (int) $row->type;
				$count = (int) $row->action_count;
				
				if ( ! isset( $campaign_results[ $campaign_id ] ) ) {
					continue;
				}
				
				switch ( $type ) {
					case IG_CONTACT_SUBSCRIBE:
						$campaign_results[ $campaign_id ]['subscribed'] = $count;
						break;
					case IG_MESSAGE_SENT:
						$campaign_results[ $campaign_id ]['sent'] = $count;
						break;
					case IG_MESSAGE_OPEN:
						$campaign_results[ $campaign_id ]['opened'] = $count;
						break;
					case IG_LINK_CLICK:
						$campaign_results[ $campaign_id ]['clicked'] = $count;
						break;
					case IG_CONTACT_UNSUBSCRIBE:
						$campaign_results[ $campaign_id ]['unsubscribed'] = $count;
						break;
					case IG_MESSAGE_SOFT_BOUNCE:
					$campaign_results[ $campaign_id ]['soft_bounced'] = $count;
					break;
				case IG_MESSAGE_HARD_BOUNCE:
					$campaign_results[ $campaign_id ]['hard_bounced'] = $count;
					break;
			}
		}
	}
	
	ES_Cache::set( $cache_key, $campaign_results, 'query', 300 );
	return $campaign_results;
}

// For single campaign or backward compatibility
$query = "SELECT type, contact_id FROM {$wpbd->prefix}ig_actions" . $where_sql;
$rows = $wpbd->get_results( $query );

$sent = 0;
$opened = array();
$clicked = array();
$unsubscribed = array();
$hard_bounced = 0;
$soft_bounced = 0;
$subscribed = 0;
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$type = (int) $row->type;
				$contact_id = (int) $row->contact_id;
				
				switch ( $type ) {
					case IG_CONTACT_SUBSCRIBE:
						$subscribed++;
						break;
					
					case IG_MESSAGE_SENT:
						$sent++;
						break;
					
					case IG_MESSAGE_OPEN:
						$opened[ $contact_id ] = true;
						break;
					
					case IG_LINK_CLICK:
						$clicked[ $contact_id ] = true;
						break;
					
					case IG_CONTACT_UNSUBSCRIBE:
						$unsubscribed[ $contact_id ] = true;
						break;
					
					case IG_MESSAGE_SOFT_BOUNCE:
						$soft_bounced++;
						break;
					
					case IG_MESSAGE_HARD_BOUNCE:
						$hard_bounced++;
						break;
				}
			}
		}
		
		$results = array(
			'subscribed'   => $subscribed,
			'sent'         => $sent,
			'opened'       => count( $opened ),
			'clicked'      => count( $clicked ),
			'unsubscribed' => count( $unsubscribed ),
			'soft_bounced' => $soft_bounced,
			'hard_bounced' => $hard_bounced,
		);
		
		ES_Cache::set( $cache_key, $results, 'query', 300 );
		
		return $results;
	}
	
	/**
	 * Build WHERE clause for actions queries
	 * 
	 * @param array $args
	 * @return array
	 * 
	 * @since 5.7.25
	 */
	private function build_where_clause( $args = array() ) {
		global $wpbd;
		
		$where = array();
		
		if ( ! empty( $args['list_id'] ) ) {
			$where[] = $wpbd->prepare( "EXISTS(SELECT 1 FROM `{$wpbd->prefix}ig_lists_contacts` lc WHERE lc.contact_id = {$wpbd->prefix}ig_actions.contact_id AND lc.list_id = %d )", esc_sql( $args['list_id'] ) );
		}
		
		if ( isset( $args['campaign_id'] ) ) {
			$where[] = $wpbd->prepare( 'campaign_id = %d', esc_sql( $args['campaign_id'] ) );
		}
		
		if ( ! empty( $args['campaign_ids'] ) && is_array( $args['campaign_ids'] ) ) {
			$campaign_ids_placeholders = implode( ',', array_fill( 0, count( $args['campaign_ids'] ), '%d' ) );
			$where[] = $wpbd->prepare( "campaign_id IN ($campaign_ids_placeholders)", $args['campaign_ids'] );
		}
		
		if ( ! empty( $args['message_id'] ) || ( isset( $args['message_id'] ) && 0 === $args['message_id'] ) ) {
			$where[] = $wpbd->prepare( 'message_id = %d', esc_sql( $args['message_id'] ) );
		}
		
		if ( ! empty( $args['days'] ) ) {
			$where[] = $wpbd->prepare( 'created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))', esc_sql( $args['days'] ) );
		}
		
		if ( ! empty( $args['start_date'] ) ) {
			$where[] = $wpbd->prepare( 'created_at >= UNIX_TIMESTAMP(%s)', esc_sql( $args['start_date'] ) );
		}
		
		if ( ! empty( $args['end_date'] ) ) {
			$where[] = $wpbd->prepare( 'created_at <= UNIX_TIMESTAMP(%s)', esc_sql( $args['end_date'] ) );
		}
		
		return $where;
	}
	
	/**
	 * Get column name by action type
	 * 
	 * @param int $type
	 * @return string
	 * 
	 * @since 5.7.25
	 */
	private function get_column_name_by_type( $type ) {
		switch ( $type ) {
			case IG_CONTACT_SUBSCRIBE:
				return 'subscribed';
			case IG_MESSAGE_SENT:
				return 'sent';
			case IG_MESSAGE_OPEN:
				return 'opened';
			case IG_LINK_CLICK:
				return 'clicked';
			case IG_CONTACT_UNSUBSCRIBE:
				return 'unsubscribed';
			case IG_MESSAGE_SOFT_BOUNCE:
				return 'soft_bounced';
			case IG_MESSAGE_HARD_BOUNCE:
				return 'hard_bounced';
			default:
				return '';
		}
	}

	/**
	 * Get total contacts who have clicked links in last $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.3.2
	 */
	public function get_total_contacts_clicks_links( $days = 0, $distinct = true ) {
		global $wpdb;

		$args = array(
			IG_LINK_CLICK,
		);

		$total_contacts_clicked = 0;
		if ( $distinct ) {
			if ( 0 != $days ) {
				$days                   = esc_sql( $days );
				$args[]                 = $days;
				// phpcs:disable
				$total_contacts_clicked = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
				// phpcs:enable
			} else {
				// phpcs:disable
				$total_contacts_clicked = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
				// phpcs:enable
			}
		} else {
			if ( 0 != $days ) {
				$days                   = esc_sql( $days );
				$args[]                 = $days;
				// phpcs:disable
				$total_contacts_clicked = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
				// phpcs:enable
			} else {
				// phpcs:disable
				$total_contacts_clicked = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
				// phpcs:enable
			}
		}

		return $total_contacts_clicked;
	}

	/**
	 * Get total contacts who have unsubscribed in last $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 */
	public function get_total_contact_unsubscribed( $days = 0, $distinct = true ) {
		global $wpdb;

		$args = array(
			IG_CONTACT_UNSUBSCRIBE,
		);

		$total_emails_unsubscribed = 0;
		// phpcs:disable
		if ( $distinct ) {
			if ( 0 != $days ) {
				$days                      = esc_sql( $days );
				$args[]                    = $days;
				$total_emails_unsubscribed = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_unsubscribed = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		} else {
			if ( 0 != $days ) {
				$days                      = esc_sql( $days );
				$args[]                    = $days;
				$total_emails_unsubscribed = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_unsubscribed = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		}
// phpcs:enable
		return $total_emails_unsubscribed;
	}



	/**
	 * Get total contacts who have opened message in last $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.4.0
	 */
	public function get_total_contacts_opened_message( $days = 0, $distinct = true ) {
		global $wpdb;

		$args = array(
			IG_MESSAGE_OPEN,
		);

		$total_emails_opened = 0;
		// phpcs:disable
		if ( $distinct ) {
			if ( 0 != $days ) {
				$days                = esc_sql( $days );
				$args[]              = $days;
				$total_emails_opened = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_opened = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		} else {
			if ( 0 != $days ) {
				$days                = esc_sql( $days );
				$args[]              = $days;
				$total_emails_opened = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_opened = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		}
// phpcs:enable
		return $total_emails_opened;
	}

	/**
	 * Get total emails sent in last $days
	 *
	 * @param int $days
	 *
	 * @return string|null
	 *
	 * @since 4.4.0
	 */
	public function get_total_emails_sent( $days = 0, $distinct = true ) {
		global $wpdb;

		$args = array(
			IG_MESSAGE_SENT,
		);

		$total_emails_sent = 0;
		// phpcs:disable
		if ( $distinct ) {
			if ( 0 != $days ) {
				$days              = esc_sql( $days );
				$args[]            = $days;
				$total_emails_sent = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_sent = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT(`contact_id`)) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		} else {
			if ( 0 != $days ) {
				$days              = esc_sql( $days );
				$args[]            = $days;
				$total_emails_sent = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d AND created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))",
						$args
					)
				);
			} else {
				$total_emails_sent = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`contact_id`) FROM {$wpdb->prefix}ig_actions WHERE `type` = %d",
						$args
					)
				);
			}
		}
// phpcs:enable
		return $total_emails_sent;
	}

	/**
	 * Get contact count based on campaign_id and type
	 *
	 * @return string|null
	 *
	 * @since 4.5.2
	 */
	public function get_count_based_on_id_type( $campaign_id, $message_id, $type, $distinct = true ) {
		global $wpbd;

		$args = array();

		$args[] = $campaign_id;
		$args[] = $message_id;
		$args[] = $type;

		$count = 0;
		// phpcs:disable
		if ( $distinct ) {
			$query = $wpbd->prepare(
				"SELECT COUNT(DISTINCT(`contact_id`)) as count FROM {$wpbd->prefix}ig_actions WHERE `campaign_id`= %d AND `message_id`= %d AND `type` = %d",
				$args
			);
		} else {
			$query = $wpbd->prepare(
				"SELECT  COUNT(`contact_id`) as count FROM {$wpbd->prefix}ig_actions WHERE `campaign_id`= %d  AND `message_id`= %d AND `type` = %d",
				$args
			);
		}
// phpcs:enable
		$cache_key       = ES_Cache::generate_key( $query );
		$exists_in_cache = ES_Cache::is_exists( $cache_key, 'query' );
		if ( ! $exists_in_cache ) {
			$count = $wpbd->get_var(
				$query
			);
			ES_Cache::set( $cache_key, $count, 'query' );
		} else {
			$count = ES_Cache::get( $cache_key, 'query' );
		}

		return $count;
	}

	/**
	 * Get Last opened at based on contact_ids
	 *
	 * @param array $contact_ids
	 *
	 * @return array
	 *
	 * @since 4.6.5
	 */
	public function get_last_opened_of_contact_ids( $contact_ids = '', $filter = false ) {

		global $wpbd;

		if ( empty( $contact_ids ) ) {
			return array();
		}

		$contact_ids_str = implode( ',', $contact_ids );
// phpcs:disable
		$result = $wpbd->get_results( $wpbd->prepare( "SELECT contact_id, MAX(created_at) as last_opened_at FROM {$wpbd->prefix}ig_actions WHERE contact_id IN ({$contact_ids_str}) AND type = %d  GROUP BY contact_id", IG_MESSAGE_OPEN ), ARRAY_A );
// phpcs:enable
		if ( $filter ) {
			$last_opened_at = array_column( $result, 'last_opened_at', 'contact_id' );
			foreach ( $last_opened_at as $contact_id => $timestamp ) {
				$convert_date_format           = get_option( 'date_format' );
				$convert_time_format           = get_option( 'time_format' );
				$last_opened_at[ $contact_id ] = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), $convert_date_format . ' ' . $convert_time_format );
			}
			return $last_opened_at;
		}
		return $result;
	}

	public function delete_by_mailing_queue_id( $mailing_queue_ids ) {
		global $wpbd;

		if ( ! empty( $mailing_queue_ids ) ) {
			$mailing_queue_ids = esc_sql( $mailing_queue_ids );
			$mailing_queue_ids = implode( ',', array_map( 'absint', $mailing_queue_ids ) );
	// phpcs:disable
			$wpbd->query(
				"DELETE FROM {$wpbd->prefix}ig_actions WHERE message_id IN ($mailing_queue_ids)"
			);
			// phpcs:enable
		}
	}

	public function get_link_cliked_subscribers( $campaign_id = '', $link_id = '' ) {
		global $wpdb;
		$result = array();
		if (!empty($campaign_id) && !empty($link_id) && is_numeric($campaign_id) && is_numeric($link_id)) {
		// phpcs:disable
			$result = $wpdb->get_results($wpdb->prepare(
				"SELECT c.email,c.first_name,c.last_name
				FROM {$wpdb->prefix}ig_contacts AS c
				INNER JOIN {$wpdb->prefix}ig_actions AS a ON c.id = a.contact_id
				WHERE a.campaign_id = %d AND a.link_id = %d",
				$campaign_id,
				$link_id
			), ARRAY_A);
			// phpcs:enable
		}
		   return $result;
	}

	/**
	 * Get device open counts grouped by device type
	 *
	 * Uses SQL GROUP BY for efficient aggregation instead of PHP loops
	 *
	 * @param int $days Number of days to look back (0 for all time)
	 * @return array Array of device => count pairs
	 * 
	 * @since 5.9.14
	 */
	public function get_device_open_counts( $days = 0 ) {
		global $wpdb;
		
		$device_counts = array();
		
		if ( $days > 0 ) {
			$start_date = time() - ( $days * DAY_IN_SECONDS );
			
			// phpcs:disable
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT LOWER(device) as device, COUNT(*) as count 
					FROM {$this->table_name} 
					WHERE created_at >= %d AND type = %d AND device IS NOT NULL AND device != ''
					GROUP BY LOWER(device)
					ORDER BY count DESC",
					$start_date,
					IG_MESSAGE_OPEN
				),
				ARRAY_A
			);
			// phpcs:enable
		} else {
			// No date filter - get all time stats
			// phpcs:disable
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT LOWER(device) as device, COUNT(*) as count 
					FROM {$this->table_name} 
					WHERE type = %d AND device IS NOT NULL AND device != ''
					GROUP BY LOWER(device)
					ORDER BY count DESC",
					IG_MESSAGE_OPEN
				),
				ARRAY_A
			);
			// phpcs:enable
		}
		
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$device_counts[ $row['device'] ] = (int) $row['count'];
			}
		}
		
		return $device_counts;
	}

	/**
	 * Get country open counts grouped by country
	 *
	 * Uses SQL GROUP BY for efficient aggregation instead of PHP loops
	 *
	 * @param int $days Number of days to look back (0 for all time)
	 * @param int $limit Maximum number of countries to return (default 10)
	 * @return array Array of country code => count pairs
	 * 
	 * @since 5.9.14
	 */
	public function get_country_open_counts( $days = 0, $limit = 10 ) {
		global $wpdb;
		
		$country_counts = array();
		
		if ( $days > 0 ) {
			$start_date = time() - ( $days * DAY_IN_SECONDS );
			
			// phpcs:disable
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT country, COUNT(*) as count 
					FROM {$this->table_name} 
					WHERE created_at >= %d AND type = %d AND country IS NOT NULL AND country != ''
					GROUP BY country
					ORDER BY count DESC
					LIMIT %d",
					$start_date,
					IG_MESSAGE_OPEN,
					$limit
				),
				ARRAY_A
			);
			// phpcs:enable
		} else {
			// No date filter - get all time stats
			// phpcs:disable
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT country, COUNT(*) as count 
					FROM {$this->table_name} 
					WHERE type = %d AND country IS NOT NULL AND country != ''
					GROUP BY country
					ORDER BY count DESC
					LIMIT %d",
					IG_MESSAGE_OPEN,
					$limit
				),
				ARRAY_A
			);
			// phpcs:enable
		}
		
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$country_counts[ $row['country'] ] = (int) $row['count'];
			}
		}
		
		return $country_counts;
	}

}
