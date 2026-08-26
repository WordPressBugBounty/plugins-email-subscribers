<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_DB_Queue class
 *
 * @since 4.2.1
 */
class ES_DB_Queue extends ES_DB {
	/**
	 * Table name
	 *
	 * @since 4.2.1
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Table DB version
	 *
	 * @since 4.2.1
	 * @var $version
	 */
	public $version;

	/**
	 * Table primary key column name
	 *
	 * @since 4.2.1
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_queue';

		$this->version = '1.0';
	}

	/**
	 * Delete from queue based on campaign_id & contact_id
	 *
	 * @param $campaign_id
	 * @param $contact_id
	 *
	 * @return bool|int
	 *
	 * @since 4.2.1
	 */
	public function delete_from_queue( $campaign_id, $contact_id ) {
		global $wpdb;
// phpcs:disable
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}ig_queue WHERE campaign_id = %d AND contact_id = %d",
				$campaign_id,
				$contact_id
			)
		);
// phpcs:enable
	}

	/**
	 * Get latest mailing queue entries for specific campaigns
	 *
	 * Efficiently fetches latest mailing queue record for each campaign
	 *
	 * @param array $campaign_ids Campaign IDs to fetch queue entries for
	 * @return array Organized by campaign_id with status, hash, and other details
	 * 
	 */
	public  function get_latest_mailing_queue_by_campaigns( $campaign_ids = array() ) {
		global $wpdb;
		
		if ( empty( $campaign_ids ) ) {
			return array();
		}
		
		$campaign_ids = array_map( 'intval', $campaign_ids );
		$campaign_ids = array_filter( $campaign_ids );
		
		if ( empty( $campaign_ids ) ) {
			return array();
		}
		
		$mq_table = $wpdb->prefix . 'ig_mailing_queue';
		$placeholders = implode( ',', array_fill( 0, count( $campaign_ids ), '%d' ) );
		
		// Fetch latest mailing queue record for each campaign in one query
		// phpcs:disable
		$mq_latest_records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT campaign_id, MAX(id) as latest_id FROM {$mq_table} 
				WHERE campaign_id IN ($placeholders) 
				GROUP BY campaign_id",
				$campaign_ids
			),
			ARRAY_A
		);
		// phpcs:enable
		
		$mq_by_campaign = array();
		
		if ( ! empty( $mq_latest_records ) ) {
			$latest_ids = array_column( $mq_latest_records, 'latest_id' );
			$latest_ids = array_filter( array_map( 'intval', $latest_ids ) );
			
			if ( ! empty( $latest_ids ) ) {
				$id_placeholders = implode( ',', array_fill( 0, count( $latest_ids ), '%d' ) );
				// phpcs:disable
				$mq_details = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, campaign_id, status, hash FROM {$mq_table} 
						WHERE id IN ($id_placeholders)",
						$latest_ids
					),
					ARRAY_A
				);
				// phpcs:enable
				
				foreach ( $mq_details as $mq ) {
					$mq_by_campaign[ (int) $mq['campaign_id'] ] = $mq;
				}
			}
		}
		
		return $mq_by_campaign;
	}
}
