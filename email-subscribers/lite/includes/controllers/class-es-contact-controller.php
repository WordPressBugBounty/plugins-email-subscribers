<?php

if ( ! class_exists( 'ES_Contact_Controller' ) ) {

	/**
	 * Class to handle single contact operation
	 * 
	 * @class ES_Contact_Controller
	 */
	class ES_Contact_Controller {

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

		public static function get_existing_contact_data( $id = 0 ) {
			$contact_db = new ES_DB_Contacts();
			$contact = $contact_db->get( $id );
		
			if ( empty( $contact ) ) {
				return array();
			}
		
			$first_name       = $contact['first_name'];
			$last_name        = $contact['last_name'];
			$email            = sanitize_email( $contact['email'] );
			$guid             = $contact['hash'];
			$contact_cf_data  = apply_filters( 'es_prepare_cf_data_for_contact_array', $contact );
			$list_ids         = ES()->lists_contacts_db->get_list_ids_by_contact( $id );
		
			$data = array(
				'id'               => $id,
				'first_name'       => $first_name,
				'last_name'        => $last_name,
				'email'            => $email,
				'guid'             => $guid,
				'list_ids'         => $list_ids,
			);
		
			// Merge custom fields if available
			if ( isset( $contact_cf_data['custom_fields'] ) ) {
				$data = array_merge( $data, $contact_cf_data );
			}
		
			return $data;
		}

		public static function validate_and_sanitize_contact_data( $contact_data = array() ) {
			$defaults = array(
				'id'         => 0,
				'email'      => '',
				'first_name' => '',
				'last_name'  => '',
				'lists'      => array(),
			);
			$contact_data = wp_parse_args( $contact_data, $defaults );
		
			$errors = array();
		
			// Sanitize and validate email.
			$email = sanitize_email( $contact_data['email'] );
			if ( empty( $email ) || ! is_email( $email ) ) {
				$errors[] = esc_html__( 'Please enter a valid email address', 'email-subscribers' );
			}
		
			// Validate list selection.
			$lists = is_array( $contact_data['lists'] ) ? $contact_data['lists'] : array();
			if ( empty( $lists ) ) {
				$errors[] = esc_html__( 'Please select a list', 'email-subscribers' );
			}
		
			// Check for duplicate contact.
			if ( ! empty( $email ) ) {
				$existing_contact_id = ES()->contacts_db->get_contact_id_by_email( $email );
				if ( $existing_contact_id && ( (int) $existing_contact_id !== (int) $contact_data['id'] ) ) {
					$errors[] = esc_html__( 'Contact already exists.', 'email-subscribers' );
				}
			}
		
			// Return result.
			return array(
				'errors'  => $errors,
				'email'   => $email,
				'lists'   => $lists,
				'contact' => array(
					'first_name' => sanitize_text_field( $contact_data['first_name'] ),
					'last_name'  => sanitize_text_field( $contact_data['last_name'] ),
					'email'      => $email,
					'status'     => 'verified',
				),
			);
		}
		

		public static function maybe_send_welcome_email( $contact_data, $contact, $list_ids ) {
			if ( ! empty( $contact_data['send_welcome_email'] ) ) {
				$list_name = ES_Common::prepare_list_name_by_ids( $list_ids );
				$name      = ES_Common::prepare_name_from_first_name_last_name( $contact['first_name'], $contact['last_name'] );
		
				$template_data = array(
					'email'      => $contact['email'],
					'contact_id' => $contact['id'],
					'name'       => $name,
					'first_name' => $contact['first_name'],
					'last_name'  => $contact['last_name'],
					'guid'       => $contact['hash'],
					'list_name'  => $list_name,
				);
		
				ES()->mailer->send_welcome_email( $contact['email'], $template_data );
			}
		}

		public static function update_contact_lists( $id, $lists, $is_new ) {
			$existing_lists = ES()->lists_contacts_db->get_list_ids_by_contact( $id, 'subscribed' );
			ES()->lists_contacts_db->update_contact_lists( $id, $lists );
			$updated_lists = ES()->lists_contacts_db->get_list_ids_by_contact( $id, 'subscribed' );
		
			$changed_lists = array_diff( $existing_lists, $updated_lists );
		
			if ( ! $is_new && ! empty( $changed_lists ) ) {
				do_action( 'ig_es_admin_contact_unsubscribe', $id, 0, 0, $changed_lists );
			}
		}

		public static function process_contact_save( $contact_data = array() ) {
			$id = isset( $contact_data['id'] ) ? absint( $contact_data['id']) : 0;
		
			//Validate and sanitize
			$result = self::validate_and_sanitize_contact_data( $contact_data );
		
			if ( ! empty( $result['errors'] ) ) {
				return array( 'errors' => $result['errors'] );
			}
		
			$contact = $result['contact'];
			$lists   = $result['lists'];
			$is_new  = empty( $id );
		
			//Prepare contact before save
			$contact = apply_filters( 'es_set_additional_contact_data', $contact, $contact_data );
			$contact_cf_data = apply_filters( 'es_prepare_cf_data_for_contact_array', $contact_data, true );
		
			//Save or update contact
			if ( $is_new ) {
				$contact['source']     = 'admin';
				$contact['status']     = ! empty( $contact['status'] ) ? $contact['status'] : 'verified';
				$contact['hash']       = ES_Common::generate_guid();
				$contact['created_at'] = ig_get_current_date_time();
				$id                    = self::save_contact( $contact );
				$contact['id']         = $id;

				// Send welcome email
				self::maybe_send_welcome_email( $contact_data, $contact, $lists );

			} else {
				$contact['id'] = $id;
				self::update_contact( $contact );
			}
		
			//Update contact list 
			self::update_contact_lists( $id, $lists, $is_new );
		
			// Return result
			return array(
				'id'              => $id,
				'is_new'          => $is_new,
				'contact'         => $contact,
				'contact_cf_data' => $contact_cf_data,
			);
		}
		
		public static function update_contact( $contact) {
			if ( ! empty( $contact['id'] ) ) {
			  $id = $contact['id'];
			  ES()->contacts_db->update_contact( $id, $contact );
			}
		}
		public static function save_contact( $contact) {
			return ES()->contacts_db->insert( $contact );
		}
	}

}

ES_Contact_Controller::get_instance();
