<?php

if ( ! class_exists( 'ES_Workflows_Controller' ) ) {

	/**
	 * Class to handle single form operation
	 * 
	 * @class ES_Workflows_Controller
	 */
	class ES_Workflows_Controller {

		// class instance
		public static $instance;

		/**
	 * ES_DB_Workflows object
	 *
	 * @since 4.4.1
	 * @var $db
	 */
	protected $db;


		// class constructor
		public function __construct() {
			$this->db = new ES_DB_Workflows();
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
	 * Retrieve lists data from the database
	 *
	 * @return mixed
	 */
		public static function get_lists( $args = array()) {
		
			$type       = isset( $args['type'] ) ? $args['type'] : '';
			$do_count_only = ! empty( $args['do_count_only'] );

			if ( '' !== $type ) {
				if ( 'system' === $type ) {
					$type = IG_ES_WORKFLOW_TYPE_SYSTEM;
				} elseif ( 'user' === $type ) {
					$type = IG_ES_WORKFLOW_TYPE_USER;
				}

				$args['type'] = $type;
			}

			$result = ES()->workflows_db->get_workflows( $args, ARRAY_A, $do_count_only );

			return $result;
		}
		

		public static function delete_workflows( $args = array()) {
			 $workflow_id = $args['workflow_id'];
			ES()->workflows_db->delete_workflows( $workflow_id );
			ES()->workflows_db->delete_workflows_campaign( $workflow_id );

			return true;
		}

		public static function update_status( $args = array()) {
			$workflow_ids= $args['workflow_ids'];
			$status      = $args['status'];

			// Update multiple Workflows.
			ES()->workflows_db->update_status( $workflow_ids, $status );
	   
		}

		public static function toggle_status( $args = array()) {
		  $workflow_id = $args['workflow_id'];
		  $new_state   = $args['new_state'];
		  $workflow  = ES_Workflow_Factory::get( $workflow_id );

			if ( ! $workflow || ! $new_state ) {
			  die;
			}

		$status_updated = $workflow->update_status( $new_state );

		}
	
		/**
		 * Method to save workflow
		 *
		 * @since 4.4.1
		 * @param int $workflow_id Workflow ID.
		 * @return mixed $workflow_id/false workflow id on success otherwise false
		 *
		 * @since 4.5.3 Removed sanitization for $posted being performed through ig_es_get_request_data function. Instead added individual sanitization based on workflow field.
		 */
		public static function save( $args = array() ) {
		
			$workflow_id = isset( $args['workflow_id'] ) ? $args['workflow_id'] : 0;
			$posted      = isset( $args['posted'] ) ? $args['posted'] : '';

			if ( empty( $posted ) || ! is_array( $posted ) ) {
				return false;
			}

			$workflow_title  = isset( $posted['title'] ) ? ig_es_clean( $posted['title'] ) : '';
			$workflow_name   = ! empty( $workflow_title ) ? sanitize_title( ES_Clean::string( $workflow_title ) ) : '';
			$trigger_name    = isset( $posted['trigger_name'] ) ? ig_es_clean( $posted['trigger_name'] ) : '';
			$trigger_options = isset( $posted['trigger_options'] ) ? ig_es_clean( $posted['trigger_options'] ) : array();
			$rules           = isset( $posted['rules'] ) ? self::filter_valid_rules_to_save( ig_es_clean( $posted['rules'] ) ) : array();
			$actions         = isset( $posted['actions'] ) ? $posted['actions'] : array(); // We can't sanitize actions data since some actions like Send email allows html in its field.
			$status          = isset( $posted['status'] ) ? ig_es_clean( $posted['status'] ) : 0;
			$type            = isset( $posted['type'] ) ? ig_es_clean( $posted['type'] ) : 0;
			$priority        = isset( $posted['priority'] ) ? ig_es_clean( $posted['priority'] ) : 0;

			$workflow_meta                = array();
			$workflow_meta['when_to_run'] = self::extract_string_option_value( 'when_to_run', $posted, 'immediately' );

			switch ( $workflow_meta['when_to_run'] ) {

				case 'delayed':
					$workflow_meta['run_delay_value'] = self::extract_string_option_value( 'run_delay_value', $posted );
					$workflow_meta['run_delay_unit']  = self::extract_string_option_value( 'run_delay_unit', $posted );
					break;

				case 'scheduled':
					$workflow_meta['run_delay_value'] = self::extract_string_option_value( 'run_delay_value', $posted );
					$workflow_meta['run_delay_unit']  = self::extract_string_option_value( 'run_delay_unit', $posted );
					$workflow_meta['scheduled_time']  = self::extract_string_option_value( 'scheduled_time', $posted );
					$workflow_meta['scheduled_day']   = self::extract_array_option_value( 'scheduled_day', $posted );
					break;

				case 'fixed':
					$workflow_meta['fixed_date'] = self::extract_string_option_value( 'fixed_date', $posted );
					$workflow_meta['fixed_time'] = self::extract_array_option_value( 'fixed_time', $posted );
					break;

				case 'datetime':
					$workflow_meta['queue_datetime'] = self::extract_string_option_value( 'queue_datetime', $posted );
					break;
			}

			if ( ! empty( $workflow_id ) ) {
				$run_workflow = ig_es_get_request_data( 'run_workflow', 'no' );
				if ( 'no' === $run_workflow ) {
					$existing_meta = ES()->workflows_db->get_column( 'meta', $workflow_id );
					$existing_meta = maybe_unserialize( $existing_meta );
					if ( ! empty( $existing_meta['last_ran_at'] ) ) {
						// Don't update the workflow last run time unless admin check the run workflow option.
						$workflow_meta['last_ran_at'] = $existing_meta['last_ran_at'];
					}
				}
			}

			$workflow_data = array(
			'name'            => $workflow_name,
			'title'           => $workflow_title,
			'trigger_name'    => $trigger_name,
			'trigger_options' => maybe_serialize( $trigger_options ),
			'rules'           => maybe_serialize( $rules ),
			'actions'         => maybe_serialize( $actions ),
			'meta'            => maybe_serialize( $workflow_meta ),
			'status'          => $status,
			'type'            => $type,
			'priority'        => $priority,
			);

			if ( empty( $workflow_id ) ) {
				$workflow_id = ES()->workflows_db->insert_workflow( $workflow_data );
			} else {
				$workflow = new ES_Workflow( $workflow_id );
				if ( $workflow->exists ) {
					$workflow_updated = ES()->workflows_db->update_workflow( $workflow_id, $workflow_data );
					if ( ! $workflow_updated ) {
						// Return false if update failed.
						return false;
					}
				}
			}

			return $workflow_id;
		}

	/**
	 * Returns option value from workflow option data string
	 *
	 * @since 4.4.1
	 *
	 * @param string $option Option name.
	 * @param array  $posted Posted data.
	 * @param string $default Default value.
	 *
	 * @return string
	 */
		public static function extract_string_option_value( $option, $posted, $default = '' ) {
			return isset( $posted['workflow_options'][ $option ] ) ? ES_Clean::string( $posted['workflow_options'][ $option ] ) : $default;
		}

	/**
	 * Returns option value array from workflow option data array
	 *
	 * @since 4.4.1
	 *
	 * @param string $option Option name.
	 * @param array  $posted Posted data.
	 * @param string $default Default value.
	 *
	 * @return array
	 */
		public static function extract_array_option_value( $option, $posted, $default = array() ) {
			return isset( $posted['workflow_options'][ $option ] ) ? ES_Clean::recursive( $posted['workflow_options'][ $option ] ) : $default;
		}

	/**
	 * Generate preview HTML for workflow email
	 *
	 * @param string $trigger
	 * @param array  $args
	 *
	 * @return string
	 */
		public static function generate_preview_html( $args ) {
			$trigger = $args['trigger']; 
			return ES_Workflow_Action_Preview::get_preview( $trigger, $args );
		}


		public static function send_test_email( $args ) {

			$subject = $args['ig-es-email-subject']; 
			$subject = sanitize_text_field( $args['ig-es-email-subject'] );
            $email   = sanitize_email( $args['ig-es-email'] );

			$content = self::generate_preview_html( $args );
			$response = ES()->mailer->send_test_email( $email, $subject, $content, array() );
			return $response;
		}

		public static function create_workflow_from_gallery_item( $args = array() ) {

			$item_name = $args['item_name'];
			$workflow_id = 0;

			$workflow_gallery = ES_Workflow_Gallery::get_workflow_gallery_items();
			if ( ! empty( $workflow_gallery[ $item_name ] ) ) {
				$item_data = $workflow_gallery[ $item_name ];

				$workflow_title  = isset( $item_data['title'] ) ? ig_es_clean( $item_data['title'] ) : '';
				$workflow_name   = ! empty( $workflow_title ) ? sanitize_title( ES_Clean::string( $workflow_title ) ) : '';
				$trigger_name    = isset( $item_data['trigger_name'] ) ? ig_es_clean( $item_data['trigger_name'] ) : '';
				$trigger_options = isset( $item_data['trigger_options'] ) ? ig_es_clean( $item_data['trigger_options'] ) : array();
				$rules           = isset( $item_data['rules'] ) ? ig_es_clean( $item_data['rules'] ) : array();
				$actions         = isset( $item_data['actions'] ) ? $item_data['actions'] : array(); // We can't sanitize actions data since some actions like Send email allows html in its field.
				$status          = isset( $item_data['status'] ) ? ig_es_clean( $item_data['status'] ) : 0;
				$type            = isset( $item_data['type'] ) ? ig_es_clean( $item_data['type'] ) : 0;
				$priority        = isset( $item_data['priority'] ) ? ig_es_clean( $item_data['priority'] ) : 0;
				$meta            = isset( $item_data['meta'] ) ? ig_es_clean( $item_data['meta'] ) : 0;

				$workflow_data = array(
				'name'            => $workflow_name,
				'title'           => $workflow_title,
				'trigger_name'    => $trigger_name,
				'trigger_options' => maybe_serialize( $trigger_options ),
				'rules'           => maybe_serialize( $rules ),
				'actions'         => maybe_serialize( $actions ),
				'meta'            => maybe_serialize( $meta ),
				'status'          => 0,
				'type'            => 0,
				'priority'        => 0,
				);

				$workflow_id = ES()->workflows_db->insert_workflow( $workflow_data );
			}

			return $workflow_id ; 
		}

			/**
	 * Filter the rules before saving it into DB
	 *
	 * @param $rules
	 *
	 * @return array
	 */
	public static function filter_valid_rules_to_save( $rules ) {
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return array();
		}
		$valid_rules = array();

		foreach ( $rules as $rule_group ) {
			if ( empty( $rule_group ) || ! is_array( $rule_group ) ) {
				continue;
			}
			$valid_rule_group = array();
			foreach ( $rule_group as $rule ) {
				if ( empty( $rule['name'] ) || empty( $rule['compare'] ) || empty( $rule['value'] ) ) {
					continue;
				}
				array_push( $valid_rule_group, $rule );
			}

			if ( ! empty( $valid_rule_group ) ) {
				array_push( $valid_rules, $valid_rule_group );
			}
		}

		return $valid_rules;
	}

	}

}

ES_Workflows_Controller::get_instance();
