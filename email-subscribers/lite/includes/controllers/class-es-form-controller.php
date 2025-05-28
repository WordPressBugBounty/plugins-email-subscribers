<?php

if ( ! class_exists( 'ES_Form_Controller' ) ) {

	/**
	 * Class to handle single form operation
	 * 
	 * @class ES_Form_Controller
	 */
	class ES_Form_Controller {

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

		public static function validate_data( $form_data ) {

			$form_name   = ! empty( $form_data['name'] ) ? $form_data['name'] : '';
			$editor_type = ! empty( $form_data['settings']['editor_type'] ) ? $form_data['settings']['editor_type'] : '';
			
			$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
	
			$lists     = $form_data['lists'];
	
			$status  = 'error';
			$error   = false;
			$message = '';

			if ( empty( $form_name ) ) {
				$message = __( 'Please add form name.', 'email-subscribers' );
				$error   = true;
			} elseif ( ! $is_dnd_editor ) {
				if ( empty( $lists ) ) {
					$message = __( 'Please select list(s) in which contact will be subscribed.', 'email-subscribers' );
					$error   = true;
				}
			}
	
			if ( ! $error ) {
				$status = 'success';
			}
	
			$response = array(
				'status'  => $status,
				'message' => $message,
			);
	
			return $response;
	
		}

		public static function save( $form_data ) {
			$response = array();

			$form_id   = ! empty( $form_data['id'] ) ? $form_data['id'] : 0;
			$form_data = self::sanitize_data( $form_data );
			$response  = self::validate_data( $form_data );
			if ( 'error' === $response['status'] ) {
				return $response;
			}
			$form_data = self::prepare_form_data( $form_data );

			$result = false;
			if ( ! empty( $form_id ) ) {
				$form_data['updated_at'] = ig_get_current_date_time();

				// We don't want to change the created_at date for update
				unset( $form_data['created_at'] );
				// phpcs:disable
				//$return = $wpdb->update( IG_FORMS_TABLE, $form_data, array( 'form_id' => $form_id ) );
				$result = ES()->forms_db->update( $form_id, $form_data );
			} else {
				//$return = $wpdb->insert( IG_FORMS_TABLE, $form_data );
				$result = ES()->forms_db->insert( $form_data );
			}
			$response['status'] = $result ? 'success' : 'error';

			return $response;
		}

		public static function prepare_form_data( $data ) {
		
			$form_data     = array();
			$name          = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
			$editor_type   = ! empty( $data['settings']['editor_type'] ) ? sanitize_text_field( $data['settings']['editor_type'] ) : '';
			$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
	
			$es_form_popup         = ! empty( $data['show_in_popup'] ) ? 'yes' : 'no';
			$es_popup_headline     = ! empty( $data['popup_headline'] ) ? sanitize_text_field( $data['popup_headline'] ) : '';
			
			if ( ! $is_dnd_editor ) {
				$desc               = ! empty( $data['desc'] ) ? wp_kses_post( trim( wp_unslash( $data['desc'] ) ) ) : '';
				$email_label        = ! empty( $data['email_label'] ) ? sanitize_text_field( $data['email_label'] ) : '';
				$email_place_holder = ! empty( $data['email_place_holder'] ) ? sanitize_text_field( $data['email_place_holder'] ) : '';
				$name_label         = ! empty( $data['name_label'] ) ? sanitize_text_field( $data['name_label'] ) : '';
				$name_place_holder  = ! empty( $data['name_place_holder'] ) ? sanitize_text_field( $data['name_place_holder'] ) : '';
				$button_label       = ! empty( $data['button_label'] ) ? sanitize_text_field( $data['button_label'] ) : '';
				$name_visible       = ( ! empty( $data['name_visible'] ) && 'yes' === $data['name_visible'] ) ? true : false;
				$name_required      = ( ! empty( $data['name_required'] ) && 'yes' === $data['name_required'] ) ? true : false;
				$list_label         = ! empty( $data['list_label'] ) ? sanitize_text_field( $data['list_label'] ) : '';
				$list_visible       = ( ! empty( $data['list_visible'] ) && 'yes' === $data['list_visible'] ) ? true : false;
				$list_required      = true;
				$list_ids           = ! empty( $data['lists'] ) ? $data['lists'] : array();
				
				$gdpr_consent       = ! empty( $data['gdpr_consent'] ) ? sanitize_text_field( $data['gdpr_consent'] ) : 'no';
				$gdpr_consent_text  = ! empty( $data['gdpr_consent_text'] ) ? wp_kses_post( $data['gdpr_consent_text'] ) : '';
				$captcha            = ! empty( $data['captcha'] ) ? ES_Common::get_captcha_setting( null, $data ) : 'no';
	
				$body = array(
					array(
						'type'     => 'text',
						'name'     => 'Name',
						'id'       => 'name',
						'params'   => array(
							'label'        => $name_label,
							'place_holder' => $name_place_holder,
							'show'         => $name_visible,
							'required'     => $name_required,
						),
		
						'position' => 1,
					),
		
					array(
						'type'     => 'text',
						'name'     => 'Email',
						'id'       => 'email',
						'params'   => array(
							'label'        => $email_label,
							'place_holder' => $email_place_holder,
							'show'         => true,
							'required'     => true,
						),
		
						'position' => 2,
					),
		
					array(
						'type'     => 'checkbox',
						'name'     => 'Lists',
						'id'       => 'lists',
						'params'   => array(
							'label'    => $list_label,
							'show'     => $list_visible,
							'required' => $list_required,
							'values'   => $list_ids,
						),
		
						'position' => 3,
					),
				);
		
				$form_body = apply_filters( 'es_add_custom_fields_data_in_form_body', $body, $data );
		
				$submit_button_position = count( $form_body ) + 1;
				$submit_data            = array(
					array(
						'type'     => 'submit',
						'name'     => 'submit',
						'id'       => 'submit',
						'params'   => array(
							'label'    => $button_label,
							'show'     => true,
							'required' => true,
						),
		
						'position' => $submit_button_position,
					),
				);
		
				$body = array_merge( $form_body, $submit_data );
	
				$settings = array(
					'lists'        => $list_ids,
					'desc'         => $desc,
					'form_version' => ES()->forms_db->version,
					'captcha'      => $captcha,
					'gdpr'         => array(
						'consent'      => $gdpr_consent,
						'consent_text' => $gdpr_consent_text,
					),
					'es_form_popup'  => array(
						'show_in_popup'  => $es_form_popup,
						'popup_headline' => $es_popup_headline,
					),						
				);
		
				$settings = apply_filters( 'ig_es_form_settings', $settings, $data );
	
				$form_data['body'] = maybe_serialize( $body );
			} else {
				
				$form_data['body'] = self::process_form_body($data['body']);
				$settings          = $data['settings'];
			}
	
			$af_id = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;		
	
			$form_data['name']       = $name;
			$form_data['settings']   = maybe_serialize( $settings );
			$form_data['styles']     = null;
			$form_data['created_at'] = ig_get_current_date_time();
			$form_data['updated_at'] = null;
			$form_data['deleted_at'] = null;
			$form_data['af_id']      = $af_id;
	
			return $form_data;
		}

		public static function process_form_body( $content) {
			if (!empty($content)) {
				// Define the replacements as an associative array
				$replacements = array(
					'{{TOTAL-CONTACTS}}' => ES()->contacts_db->count_active_contacts_by_list_id(),
					'{{site.total_contacts}}' => ES()->contacts_db->count_active_contacts_by_list_id(),
					'{{SITENAME}}' => get_option('blogname'),
					'{{site.name}}' => get_option('blogname'),
					'{{SITEURL}}' => home_url('/'),
					'{{site.url}}' => home_url('/'),
				);
		
				// Perform the replacements
				$content = str_replace(array_keys($replacements), array_values($replacements), $content);
			}
		
			return $content;
		}

		public static function get_form_data_from_body( $data ) {

			$name          = ! empty( $data['name'] ) ? $data['name'] : '';
			$id            = ! empty( $data['id'] ) ? $data['id'] : '';
			$af_id         = ! empty( $data['af_id'] ) ? $data['af_id'] : '';
			$body_data     = maybe_unserialize( $data['body'] );
			$settings_data = maybe_unserialize( $data['settings'] );
	
			$desc          = ! empty( $settings_data['desc'] ) ? $settings_data['desc'] : '';
			$form_version  = ! empty( $settings_data['form_version'] ) ? $settings_data['form_version'] : '0.1';
			$editor_type   = ! empty( $settings_data['editor_type'] ) ? $settings_data['editor_type'] : '';
			$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
	
			if ( ! $is_dnd_editor ) {
				$gdpr_consent      	  = 'no';
				$gdpr_consent_text 	  = '';
				$es_form_popup     	  = ! empty( $settings_data['es_form_popup']['show_in_popup'] ) ? $settings_data['es_form_popup']['show_in_popup'] : 'no';
				$es_popup_headline 	  = ! empty( $settings_data['es_form_popup']['popup_headline'] ) ? $settings_data['es_form_popup']['popup_headline'] : '';
		
				$captcha = ES_Common::get_captcha_setting( $id, $settings_data );
		
				if ( ! empty( $settings_data['gdpr'] ) ) {
					$gdpr_consent      = ! empty( $settings_data['gdpr']['consent'] ) ? $settings_data['gdpr']['consent'] : 'no';
					$gdpr_consent_text = ! empty( $settings_data['gdpr']['consent_text'] ) ? $settings_data['gdpr']['consent_text'] : '';
				}
		
				$form_data = array(
					'form_id'              => $id,
					'name'                 => $name,
					'af_id'                => $af_id,
					'desc'                 => $desc,
					'form_version'         => $form_version,
					'gdpr_consent'         => $gdpr_consent,
					'gdpr_consent_text'    => $gdpr_consent_text,
					'captcha'              => $captcha,
					'show_in_popup'        => $es_form_popup,
					'popup_headline'       => $es_popup_headline,
					'editor_type'          => $editor_type,
				);
		
				foreach ( $body_data as $d ) {
					if ( 'name' === $d['id'] ) {
						$form_data['name_visible']      = ( true === $d['params']['show'] ) ? 'yes' : '';
						$form_data['name_required']     = ( true === $d['params']['required'] ) ? 'yes' : '';
						$form_data['name_label']        = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
						$form_data['name_place_holder'] = ! empty( $d['params']['place_holder'] ) ? $d['params']['place_holder'] : '';
					} elseif ( 'lists' === $d['id'] ) {
						$form_data['list_label']  	= ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
						$form_data['list_visible']  = ( true === $d['params']['show'] ) ? 'yes' : '';
						$form_data['list_required'] = ( true === $d['params']['required'] ) ? 'yes' : '';
						$form_data['lists']         = ! empty( $d['params']['values'] ) ? $d['params']['values'] : array();
					} elseif ( 'email' === $d['id'] ) {
						$form_data['email_label']        = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
						$form_data['email_place_holder'] = ! empty( $d['params']['place_holder'] ) ? $d['params']['place_holder'] : '';
					} elseif ( 'submit' === $d['id'] ) {
						$form_data['button_label'] = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
					} elseif ( $d['is_custom_field'] ) {
						$form_data['custom_fields'][] = $d;
					}
				}
				$form_data = apply_filters('ig_es_form_fields_data', $form_data, $settings_data, $body_data);
			} else {
				$form_data = array(
					'form_id'           => $id,
					'body'				=> $body_data,
					'name'              => $name,
					'af_id'             => $af_id,
					'form_version'      => $form_version,
					'settings'			=> $settings_data,
				);
			}
	
			return $form_data;
		}

		public static function sanitize_data( $form_data ) {

			if ( isset( $form_data['settings']['dnd_editor_css'] ) ) {
				$form_data['settings']['dnd_editor_css'] = wp_strip_all_tags( $form_data['settings']['dnd_editor_css'] );
			}
		
			$allowedtags = ig_es_allowed_html_tags_in_esc();
		
			if ( isset( $form_data['body'] ) ) {
				$form_data['body'] = wp_kses( $form_data['body'], $allowedtags );
			}
		
			if ( ! empty( $form_data['settings']['success_message'] ) ) {
				$form_data['settings']['success_message'] = wp_kses( $form_data['settings']['success_message'], $allowedtags );
			}
		
			$dnd_editor_data = isset( $form_data['settings']['dnd_editor_data'] ) 
				? json_decode( $form_data['settings']['dnd_editor_data'], true ) 
				: [];
		
			if ( is_array( $dnd_editor_data ) ) {
				array_walk_recursive( $dnd_editor_data, function ( &$value ) use ( $allowedtags ) {
					if ( is_string( $value ) ) {
						$value = wp_kses( $value, $allowedtags );
					}
				});
			}
		
			$form_data['settings']['dnd_editor_data'] = wp_json_encode( $dnd_editor_data );
		
			return $form_data;
		}

		public static function get_form_preview_data( $form_data) {
			if ( isset( $form_data ) ) {
				$form_data = self::sanitize_data( $form_data );
			}
			$template_data            = array();
			$template_data['content'] = ! empty( $form_data['body'] ) ? $form_data['body'] : '';
			$template_data['form_id'] = ! empty( $form_data['id'] ) ? $form_data['id'] : 0;
			$editor_css 	          = ! empty( $form_data['settings']['dnd_editor_css'] ) ? $form_data['settings']['dnd_editor_css'] : '';
			
			$form_body                = ! empty( $form_data['body'] ) ? do_shortcode( $form_data['body'] ) : '';
			$preview_html             = '<style>' . $editor_css . '</style>' . $form_body;
			$response['preview_html'] = $preview_html;
			$response = self::process_form_body( $response);
			return $response;
		}
	}

}

ES_Form_Controller::get_instance();
