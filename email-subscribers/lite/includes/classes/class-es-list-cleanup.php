<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The trial-specific functionality of the plugin.
 */
class ES_List_Cleanup {

	// class instance
	public static $instance;

	/**
	 * Initialize the class.
	 *
	 * @since 4.6.2
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'ig_es_list_cleanup_worker', array( $this, 'cleanup_lists' ) );
		add_action( 'ig_es_clean_emails', array( $this, 'clean_emails' ) );
		add_action( 'ig_es_before_bulk_contact_import', array( $this, 'store_last_contact_id' ) );
		add_action( 'ig_es_after_bulk_contact_import', array( $this, 'clean_imported_emails' ) );
	}

	public function cleanup_lists() {

		$es_services = ES()->get_es_services();
		if ( empty( $es_services ) || ! in_array( 'list_cleanup', $es_services, true ) ) {
			wp_clear_scheduled_hook( 'ig_es_list_cleanup_worker' );
			return;
		}

		$cleanup_started_at = time();

		$data = array(
			'context'            => 'existing_contacts',
			'cleanup_started_at' => $cleanup_started_at
		);

		$this->add_cleanup_task( $data );
	}

	public function get_contact_emails_for_cleanup( $data = array() ) {
		global $wpbd;
		
		$query = "SELECT email FROM `{$wpbd->prefix}ig_contacts`";
		
		$context = ! empty( $data['context'] ) ? $data['context'] : '';
		if ( 'imported_contacts' === $context ) {
			$last_contact_id    = isset( $data['last_contact_id'] ) ? $data['last_contact_id'] : 0;
			$cleanup_started_at = ! empty( $data['cleanup_started_at'] ) ? $data['cleanup_started_at'] : time();
			$last_cleanup_date  = gmdate( 'Y-m-d H:i:s', $cleanup_started_at );
			$query             .= $wpbd->prepare( ' WHERE id > %d AND ( updated_at < %s OR updated_at IS NULL )', $last_contact_id, $last_cleanup_date );
		} else {
			$cleanup_started_at = ! empty( $data['cleanup_started_at'] ) ? $data['cleanup_started_at'] : time();
			$last_cleanup_date  = gmdate( 'Y-m-d H:i:s', $cleanup_started_at );
			$query             .= $wpbd->prepare( ' WHERE updated_at < %s OR updated_at IS NULL', $last_cleanup_date );
		}

		$query .= ' LIMIT 1000';
		$emails = $wpbd->get_col( $query );

		return $emails;
	}

	public function add_cleanup_task( $data = array(), $schedule_time = 0 ) {
		if ( empty( $schedule_time ) ) {
			$schedule_time = time() + MINUTE_IN_SECONDS; // Schedule next task after 1 minute from current time.
		}
		IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_clean_emails', $data, false, false, $schedule_time );
	}

	public function clean_emails( $data = array() ) {
		$emails = $this->get_contact_emails_for_cleanup( $data );
		if ( ! empty( $emails ) ) {
			$es_clean_list = Email_Subscribers_Utils::es_list_cleanup( $emails );
			if ( ! empty( $es_clean_list['status'] ) && 'SUCCESS' === $es_clean_list['status'] && is_array( $es_clean_list['data'] ) ) {
				$this->update_contacts_status( $es_clean_list['data'] );
			} else {
				// Scheduling clean task after 12 Hours in case error occurs.
				$schedule_time = time() + 12 * HOUR_IN_SECONDS;
				$this->add_cleanup_task( $data, $schedule_time );
				return;
			}
		}

		$emails = $this->get_contact_emails_for_cleanup( $data );
		
		if ( ! empty( $emails ) ) {
			$this->add_cleanup_task( $data );
		} else {
			$last_cleanup_time = time();
			update_option( 'ig_es_last_cleanup_time', $last_cleanup_time );
			update_option('ig_list_cleanup_used', true);
		}
	}

	public function store_last_contact_id() {
		$last_contact_id = ES()->contacts_db->get_last_contact_id();
		if ( empty( $last_contact_id ) ) {
			$last_contact_id = 0;
		}
		set_transient( 'ig_es_last_contact_id', $last_contact_id );
	}

	public function clean_imported_emails() {
		$last_contact_id    = get_transient( 'ig_es_last_contact_id', 0 );
		$cleanup_started_at = time();
		$data               = array(
			'last_contact_id' => $last_contact_id,
			'context' => 'imported_contacts',
			'cleanup_started_at' => $cleanup_started_at
		);
		$this->add_cleanup_task( $data );
		delete_transient( 'ig_es_last_contact_id' );
	}

	public function update_contacts_status( $emails_data ) {
		global $wpbd;
		$result = false;
		if ( empty( $emails_data ) ) {
			return $result;
		}

		$is_rolebased   = array();
		$is_disposable  = array();
		$is_deliverable = array();
		$is_sendsafely  = array();
		$status         = array();
		$is_webmail     = array();
		$is_verified    = array();
		$updated_at     = array();
		$emails         = array();
		foreach ( $emails_data as $email ) {
			$emails[]    = sanitize_email( $email['_given'] );
			$role_based  = ( ! empty( $email['attributes']['roleBased'] ) ) ? $email['attributes']['roleBased'] : 0;
			$webmail     = ( ! empty( $email['attributes']['webmail'] ) ) ? $email['attributes']['webmail'] : 0;
			$disposable  = ( ! empty( $email['attributes']['disposable'] ) ) ? $email['attributes']['disposable'] : 0;
			$deliverable = ( ! empty( $email['attributes']['deliverable'] ) ) ? $email['attributes']['deliverable'] : 0;
			$sendSafely  = ( ! empty( $email['attributes']['sendSafely'] ) ) ? $email['attributes']['sendSafely'] : 0;
			// sql

			$is_rolebased []   = ' WHEN email = "' . $email['_given'] . '" THEN ' . $role_based;
			$is_disposable []  = ' WHEN email = "' . $email['_given'] . '" THEN ' . $disposable;
			$is_deliverable [] = ' WHEN email = "' . $email['_given'] . '" THEN ' . $deliverable;
			$is_sendsafely []  = ' WHEN email = "' . $email['_given'] . '" THEN ' . $sendSafely;

			$status[]       = ' WHEN email = "' . $email['_given'] . '" AND ( ' . $disposable . ' = 1 OR ' . $deliverable . ' = 0 )  THEN "spam"';
			$is_webmail []  = ' WHEN email = "' . $email['_given'] . '" THEN ' . $webmail;
			$is_verified [] = ' WHEN email = "' . $email['_given'] . '" THEN 1';
			$updated_at []  = ' WHEN email = "' . $email['_given'] . '" THEN "' . gmdate( 'Y-m-d H:i:s' ) . '"';
		}

		$update_sql = 'UPDATE ' . $wpbd->prefix . 'ig_contacts SET
        is_rolebased = (CASE ' . implode( ' ', $is_rolebased ) . ' ELSE is_rolebased END),
        is_disposable = (CASE ' . implode( ' ', $is_disposable ) . ' ELSE is_disposable  END),
        is_deliverable = (CASE ' . implode( ' ', $is_deliverable ) . ' ELSE is_deliverable  END),
        is_sendsafely = (CASE ' . implode( ' ', $is_sendsafely ) . ' ELSE is_sendsafely  END),
        status = (CASE ' . implode( ' ', $status ) . ' ELSE "verified"  END),
        is_webmail = (CASE ' . implode( ' ', $is_webmail ) . ' ELSE is_webmail  END),
        is_verified = (CASE ' . implode( ' ', $is_verified ) . ' ELSE is_verified  END),
        updated_at = (CASE ' . implode( ' ', $updated_at ) . ' ELSE updated_at  END) WHERE email IN( \'' . implode( '\',\'', $emails ) . '\')';

		$result = $wpbd->query( $update_sql );
		return $result;
	}

	public function can_do_list_cleanup() {

		$last_cleanup_time  = get_option( 'ig_es_last_cleanup_time', 0 );

		$es_services = ES()->get_es_services(); 
		
		$can_do_cleanup = false;
		if ( ! empty( $es_services ) && in_array( 'list_cleanup', $es_services, true ) ) {
			$can_do_cleanup = true;
		} else {
			$can_do_cleanup = empty( $last_cleanup_time );
		}

		return $can_do_cleanup;

	}
}
ES_List_Cleanup::get_instance();