<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Temp_Import extends ES_DB {

	/**
	 * Table name
	 *
	 * @since 5.0.0
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Table DB version
	 *
	 * @since 5.0.0
	 * @var $version
	 */
	public $version;

	/**
	 * Table primary key column name
	 *
	 * @since 5.0.0
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB_Temp_Import constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_temp_import';

		$this->primary_key = 'ID';

		$this->version = '1.0';
	}

	/**
	 * Get the table columns
	 *
	 * @since 5.0.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'ID'         => '%d',
			'data'       => '%s',
			'identifier' => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since 5.0.0
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'data'       => '',
			'identifier' => '',
		);
	}

	/**
	 * Insert import data into temp table
	 *
	 * @param string $data Base64 encoded serialized data
	 * @param string $identifier Unique identifier for the import
	 * @return int|false Insert ID on success, false on failure
	 * @since 5.0.0
	 */
	public function insert_temp_data( $data, $identifier ) {
		global $wpdb;

		$result = $wpdb->query( 
			$wpdb->prepare( 
				"INSERT INTO {$this->table_name} (data, identifier) VALUES (%s, %s)", 
				$data, 
				$identifier 
			) 
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get import metadata (first and last entries) by identifier
	 *
	 * @param string $identifier Unique identifier for the import
	 * @return object|null Database row object or null if not found
	 * @since 5.0.0
	 */
	public function get_import_metadata_by_identifier( $identifier ) {
		global $wpdb;

		if ( empty( $identifier ) ) {
			return null;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					(SELECT data FROM {$this->table_name} WHERE identifier = %s ORDER BY ID ASC LIMIT 1) AS first,
					(SELECT data FROM {$this->table_name} WHERE identifier = %s ORDER BY ID DESC LIMIT 1) AS last",
				$identifier,
				$identifier
			)
		);
	}

	/**
	 * Get import data by identifier with pagination
	 *
	 * @param string $identifier Unique identifier for the import
	 * @param int    $offset Offset for pagination
	 * @param int    $limit Number of records to retrieve
	 * @return array Array of data records
	 * @since 5.0.0
	 */
	public function get_import_data_by_identifier( $identifier, $offset = 0, $limit = 10 ) {
		global $wpdb;

		if ( empty( $identifier ) ) {
			return array();
		}

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT data FROM {$this->table_name} 
				WHERE identifier = %s ORDER BY ID ASC LIMIT %d, %d",
				$identifier,
				$offset,
				$limit
			)
		);
	}

	/**
	 * Truncate the temp import table
	 *
	 * @return bool True on success, false on failure
	 * @since 5.0.0
	 */
	public function truncate_table() {
		global $wpdb;

		$result = $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );

		return $result !== false;
	}

	/**
	 * Delete import data by identifier
	 *
	 * @param string $identifier Unique identifier for the import
	 * @return int|false Number of rows deleted, false on error
	 * @since 5.0.0
	 */
	public function delete_by_identifier( $identifier ) {
		global $wpdb;

		if ( empty( $identifier ) ) {
			return false;
		}

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE identifier = %s",
				$identifier
			)
		);
	}

	/**
	 * Get count of records by identifier
	 *
	 * @param string $identifier Unique identifier for the import
	 * @return int Number of records
	 * @since 5.0.0
	 */
	public function count_by_identifier( $identifier ) {
		global $wpdb;

		if ( empty( $identifier ) ) {
			return 0;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE identifier = %s",
				$identifier
			)
		);
	}
}