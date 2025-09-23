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

		/**
		 * API method to handle form save requests
		 *
		 * @param array $args Form data arguments
		 *
		 * @return array
		 */
		public static function save_api( $args ) {
			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				if ( $decoded ) {
					$args = $decoded;
				}
			}

			$response = self::save( $args );
			
			// If successful and it's a new form, get the form ID
			if ( 'success' === $response['status'] && empty( $args['id'] ) ) {
				// Get the last inserted form ID
				global $wpdb;
				$form_id = $wpdb->insert_id;
				if ( $form_id ) {
					$response['form_id'] = $form_id;
				}
			}

			return $response;
		}

		/**
		 * Get form data for editing
		 *
		 * @param array $args Arguments containing form_id
		 *
		 * @return array|false
		 */
		public static function get_form( $args ) {
			if ( is_string( $args ) ) {
				$decoded = json_decode( $args, true );
				if ( $decoded ) {
					$args = $decoded;
				}
			}

			if ( empty( $args['form_id'] ) ) {
				return false;
			}

		$form_id = intval( $args['form_id'] );
		
		$form = ES()->forms_db->get_form_by_id( $form_id );
		
			if ( empty( $form ) ) {
				return false;
			}

		// Ensure styles and settings are properly unserialized before processing
			if ( ! empty( $form['styles'] ) && is_string( $form['styles'] ) ) {
				$form['styles'] = maybe_unserialize( $form['styles'] );
			}
		
			if ( ! empty( $form['settings'] ) && is_string( $form['settings'] ) ) {
				$form['settings'] = maybe_unserialize( $form['settings'] );
			}

		// Get form data in the format expected by the frontend
		$form_data = self::get_form_data_from_body( $form );
return $form_data;
		}

		public static function validate_data( $form_data ) {

			$form_name   = ! empty( $form_data['name'] ) ? $form_data['name'] : '';
			$editor_type = ! empty( $form_data['settings']['editor_type'] ) ? $form_data['settings']['editor_type'] : '';
			
			$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
			$is_wysiwyg_editor = 'wysiwyg' === $editor_type;
	
			$lists = array();
			if ( ! empty( $form_data['lists'] ) ) {
				$lists = $form_data['lists'];
			} elseif ( ! empty( $form_data['settings']['lists'] ) ) {
				$lists = $form_data['settings']['lists'];
			}
	
			$status  = 'error';
			$error   = false;
			$message = '';

			if ( empty( $form_name ) ) {
				$message = __( 'Please add form name.', 'email-subscribers' );
				$error   = true;
			} elseif ( ! $is_dnd_editor && ! $is_wysiwyg_editor ) {
				if ( empty( $lists ) ) {
					$message = __( 'Please select list(s) in which contact will be subscribed.', 'email-subscribers' );
					$error   = true;
				}
			} elseif ( $is_wysiwyg_editor ) {
				// For WYSIWYG forms, lists can be in settings even if Lists field is not enabled
				// This allows users to select target lists without requiring a Lists field in the form
				if ( empty( $lists ) ) {
					$message = __( 'Please select audience list(s) for the form.', 'email-subscribers' );
					$error   = true;
				}
			}			if ( ! $error ) {
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
			$editor_type   = ! empty( $data['settings']['editor_type'] ) ? sanitize_text_field( $data['settings']['editor_type'] ) : 'wysiwyg';
			$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
			$is_wysiwyg_editor = 'wysiwyg' === $editor_type;
	
			// Handle popup settings
			$es_form_popup = 'no';
			$es_popup_headline = '';
			
			if ( ! empty( $data['show_in_popup'] ) ) {
				$es_form_popup = 'yes';
			} elseif ( ! empty( $data['settings']['show_in_popup'] ) && 'yes' === $data['settings']['show_in_popup'] ) {
				$es_form_popup = 'yes';
			}
			
			$es_popup_headline = ! empty( $data['popup_headline'] ) ? sanitize_text_field( $data['popup_headline'] ) : 
								( ! empty( $data['settings']['popup_headline'] ) ? sanitize_text_field( $data['settings']['popup_headline'] ) : '' );
			
			if ( $is_wysiwyg_editor ) {
				// Handle new WYSIWYG editor format
				$body_data = ! empty( $data['body'] ) ? $data['body'] : array();
				
				// If body is already serialized string, use as is, otherwise serialize
				if ( is_string( $body_data ) ) {
					$form_data['body'] = $body_data;
				} else {
					// Serialize the field configuration array with values
					$form_data['body'] = maybe_serialize( $body_data );
				}
				
				// Prepare settings for WYSIWYG editor
				$settings = array(
					'editor_type'   => 'wysiwyg',
					'form_version'  => ES()->forms_db->version,
					'lists'         => ! empty( $data['settings']['lists'] ) ? $data['settings']['lists'] : ( ! empty( $data['lists'] ) ? $data['lists'] : array() ),
					'desc'          => ! empty( $data['settings']['desc'] ) ? wp_kses_post( $data['settings']['desc'] ) : '',
					'show_in_popup' => $es_form_popup,
					'popup_headline'=> $es_popup_headline,
					'success_message' => ! empty( $data['settings']['success_message'] ) ? sanitize_text_field( $data['settings']['success_message'] ) : '',
					'redirect_url'  => ! empty( $data['settings']['redirect_url'] ) ? esc_url( $data['settings']['redirect_url'] ) : '',
					'form_style'    => ! empty( $data['settings']['form_style'] ) ? sanitize_text_field( $data['settings']['form_style'] ) : 'inherit',
				);
				
				// Handle GDPR settings
				if ( ! empty( $data['settings']['gdpr'] ) ) {
					$settings['gdpr'] = array(
						'consent'      => ! empty( $data['settings']['gdpr']['consent'] ) ? sanitize_text_field( $data['settings']['gdpr']['consent'] ) : 'no',
						'consent_text' => ! empty( $data['settings']['gdpr']['consent_text'] ) ? wp_kses_post( $data['settings']['gdpr']['consent_text'] ) : '',
					);
				}
				
				// Handle captcha
				$settings['captcha'] = ! empty( $data['settings']['captcha'] ) ? sanitize_text_field( $data['settings']['captcha'] ) : 'no';
				
				// Handle toggle functionality fields
				if ( ! empty( $data['settings']['action_after_submit'] ) ) {
					$settings['action_after_submit'] = sanitize_text_field( $data['settings']['action_after_submit'] );
				} else {
					// Map new UI settings to action_after_submit for backward compatibility
					$redirect_to_url = ! empty( $data['settings']['redirect_to_url'] ) ? sanitize_text_field( $data['settings']['redirect_to_url'] ) : 'no';
					$show_message = ! empty( $data['settings']['show_message'] ) ? sanitize_text_field( $data['settings']['show_message'] ) : 'yes';
					$redirection_url = ! empty( $data['settings']['redirection_url'] ) ? esc_url( $data['settings']['redirection_url'] ) : '';
					
					// Only set redirect if redirect is enabled AND a URL is provided
					if ( 'yes' === $redirect_to_url && ! empty( $redirection_url ) ) {
						$settings['action_after_submit'] = 'redirect_to_url';
					} else {
						$settings['action_after_submit'] = 'show_success_message';
					}
				}
				
				if ( ! empty( $data['settings']['redirect_to_url'] ) ) {
					$settings['redirect_to_url'] = sanitize_text_field( $data['settings']['redirect_to_url'] );
				}
				if ( ! empty( $data['settings']['show_message'] ) ) {
					$settings['show_message'] = sanitize_text_field( $data['settings']['show_message'] );
				}
				// Note: form_width is stored in styles, not settings
				if ( ! empty( $data['settings']['redirection_url'] ) ) {
					$settings['redirection_url'] = esc_url( $data['settings']['redirection_url'] );
				} elseif ( ! empty( $data['settings']['redirect_url'] ) ) {
					// Handle backward compatibility for redirect_url field name
					$settings['redirection_url'] = esc_url( $data['settings']['redirect_url'] );
				}
				
				// Handle embedded form settings (Pro feature integration)
				if ( ! empty( $data['settings']['is_embed_form_enabled'] ) ) {
					$settings['is_embed_form_enabled'] = sanitize_text_field( $data['settings']['is_embed_form_enabled'] );
				}
				if ( ! empty( $data['settings']['embed_form_remote_urls'] ) && is_array( $data['settings']['embed_form_remote_urls'] ) ) {
					$cleaned_urls = array();
					foreach ( $data['settings']['embed_form_remote_urls'] as $url ) {
						if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
							$cleaned_urls[] = esc_url_raw( $url );
						}
					}
					$settings['embed_form_remote_urls'] = $cleaned_urls;
				}
				
				// Apply filters to allow Pro version to add additional settings
				$settings = apply_filters( 'ig_es_form_settings', $settings, $data );
				
				$form_data['settings'] = maybe_serialize( $settings );
				
				// Handle styles
				$styles = array();
				if ( ! empty( $data['styles'] ) ) {
					if ( ! empty( $data['styles']['custom_css'] ) ) {
						// Don't strip tags from CSS, just sanitize for storage
						$styles['custom_css'] = sanitize_textarea_field( $data['styles']['custom_css'] );
					}
					if ( ! empty( $data['styles']['form_bg_color'] ) ) {
						// Use a more permissive color validation
						$color = sanitize_text_field( $data['styles']['form_bg_color'] );
						if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
							$styles['form_bg_color'] = $color;
						}
					}
					if ( ! empty( $data['styles']['form_width'] ) ) {
						$styles['form_width'] = sanitize_text_field( $data['styles']['form_width'] );
					}
					// Note: form_style is stored in settings, not styles
				}
				$form_data['styles'] = ! empty( $styles ) ? maybe_serialize( $styles ) : null;
				
				// Handle preview image - validate base64 data URLs without truncation
				// Now supports both single images (legacy) and JSON arrays of multiple images (new format)
				if ( ! empty( $data['preview_image'] ) ) {
					// For base64 image data, we need to preserve the full string without truncation
					// Both sanitize_textarea_field and sanitize_text_field can truncate base64 data
					
					// Check if it's a JSON array of images (new format)
					$decoded_images = json_decode( $data['preview_image'], true );
					if ( is_array( $decoded_images ) ) {
						// It's a JSON array of images - validate each image
						$valid_images = array();
						foreach ( $decoded_images as $image_data ) {
							if ( preg_match( '/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $image_data ) ) {
								$url_parts = explode( ',', $image_data, 2 );
								if ( count( $url_parts ) === 2 ) {
									$header = $url_parts[0];
									$base64_data = $url_parts[1];
									
									// Basic validation that it looks like base64
									if ( preg_match( '/^[A-Za-z0-9+\/]*={0,2}$/', $base64_data ) ) {
										$valid_images[] = $image_data; // Keep valid image
									}
								}
							}
						}
						$form_data['preview_image'] = ! empty( $valid_images ) ? json_encode( $valid_images ) : '';
					} 
					// Check if it's a single data URL (legacy format)
					elseif ( preg_match( '/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $data['preview_image'] ) ) {
						// It's a single data URL - validate the base64 part but don't truncate
						$url_parts = explode( ',', $data['preview_image'], 2 );
						if ( count( $url_parts ) === 2 ) {
							$header = $url_parts[0];
							$base64_data = $url_parts[1];
							
							// Basic validation that it looks like base64
							if ( preg_match( '/^[A-Za-z0-9+\/]*={0,2}$/', $base64_data ) ) {
								$form_data['preview_image'] = $data['preview_image']; // Keep full data URL
							} else {
								$form_data['preview_image'] = ''; // Invalid base64
							}
						} else {
							$form_data['preview_image'] = ''; // Invalid data URL format
						}
					} else {
						// If not a data URL or JSON array, treat as regular text but still avoid truncation
						$form_data['preview_image'] = wp_strip_all_tags( $data['preview_image'] );
					}
				} else {
					$form_data['preview_image'] = '';
				}
				
			} elseif ( ! $is_dnd_editor ) {
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
				// Handle captcha for legacy forms - use direct setting instead of helper function to be consistent
				$captcha            = ! empty( $data['captcha'] ) && 'yes' === $data['captcha'] ? 'yes' : 'no';
	
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
				// Set styles to null for non-WYSIWYG editors
				$form_data['styles'] = null;
			}

			$af_id = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;		

			$form_data['name']       = $name;
			$form_data['settings']   = maybe_serialize( $settings );
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
		
		// If body_data is a string (JSON), decode it to array
			if ( is_string( $body_data ) && ! empty( $body_data ) ) {
				$decoded_body = json_decode( $body_data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_body ) ) {
					$body_data = $decoded_body;
				} else {
					$body_data = array();
				}
			}
		
		// Ensure body_data is an array for foreach loop
			if ( ! is_array( $body_data ) ) {
				$body_data = array();
			}$settings_data = maybe_unserialize( $data['settings'] );
		
		// Store original body data for frontend before processing
		$original_body_data = $body_data;
		
		$desc          = ! empty( $settings_data['desc'] ) ? $settings_data['desc'] : '';
			// Set default form_version to 3.0 for new React-based forms
			$form_version  = ! empty( $settings_data['form_version'] ) ? $settings_data['form_version'] : '3.0';
			$editor_type   = ! empty( $settings_data['editor_type'] ) ? $settings_data['editor_type'] : '';
			$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
	
			if ( ! $is_dnd_editor ) {
				$gdpr_consent      	  = 'no';
				$gdpr_consent_text 	  = '';
				$es_form_popup     	  = ! empty( $settings_data['es_form_popup']['show_in_popup'] ) ? $settings_data['es_form_popup']['show_in_popup'] : 'no';
				$es_popup_headline 	  = ! empty( $settings_data['es_form_popup']['popup_headline'] ) ? $settings_data['es_form_popup']['popup_headline'] : '';
		
				// Use captcha from settings for consistency with new approach
				$captcha = ! empty( $settings_data['captcha'] ) && 'yes' === $settings_data['captcha'] ? 'yes' : 'no';
		
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
				'settings'             => $settings_data,  // Add settings to response
				'body'                 => $original_body_data,      // Use original body data before processing
			);				foreach ( $body_data as $d ) {
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
			} elseif ( !empty( $d['is_custom_field'] ) || ( !empty( $d['id'] ) && ( strpos( $d['id'], 'es_custom_' ) === 0 || strpos( $d['id'], 'custom_' ) === 0 ) ) ) {
				// Handle custom fields - either marked with is_custom_field property or with ID starting with 'es_custom_' or 'custom_' (legacy)
				$form_data['custom_fields'][] = $d;
				}
				}
				
				// Ensure lists field is always set for old forms
				if ( ! isset( $form_data['lists'] ) ) {
					$form_data['lists'] = array();
					
					// Try to get lists from settings if available
					if ( ! empty( $settings_data['lists'] ) ) {
						$form_data['lists'] = is_array( $settings_data['lists'] ) ? $settings_data['lists'] : array( $settings_data['lists'] );
					}
					
					// Try to get lists from af_id (legacy audience/list field)
					if ( empty( $form_data['lists'] ) && ! empty( $af_id ) ) {
						$form_data['lists'] = is_array( $af_id ) ? $af_id : array( $af_id );
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
					'lists'             => array(), // Ensure lists field exists for DnD forms too
				);
				
				// Apply filters for DnD and WYSIWYG editors too
				$form_data = apply_filters('ig_es_form_fields_data', $form_data, $settings_data, $body_data);
			}
			
			// Add debug data for frontend
			$form_data['debug_method_called'] = 'get_form_data_from_body_called';
			$form_data['debug_lists_data'] = isset( $form_data['lists'] ) ? $form_data['lists'] : 'NOT_SET';
			
			// Extract form field values from body structure and prepare for frontend
			if ( ! empty( $body_data ) && is_array( $body_data ) ) {
				$extracted_values = array();
				foreach ( $body_data as $field ) {
					if ( isset( $field['id'] ) && isset( $field['value'] ) ) {
						$extracted_values[ $field['id'] ] = $field['value'];
					}
				}
				if ( ! empty( $extracted_values ) ) {
					$form_data['form_values'] = $extracted_values;
				}
			}
			
			// Add styles data to form_data from raw database data
			if ( ! empty( $data['styles'] ) ) {
				$styles_data = maybe_unserialize( $data['styles'] );
				
				if ( is_array( $styles_data ) ) {
					// Send styles as an object, not serialized string, and ensure it's JSON-ready
					$form_data['styles'] = $styles_data;
				} else {
					$form_data['styles'] = null;
				}
			} else {
				$form_data['styles'] = null;
			}
			
			// Add preview_image data from raw database data
			if ( ! empty( $data['preview_image'] ) ) {
				$form_data['preview_image'] = $data['preview_image'];
			} else {
				$form_data['preview_image'] = '';
			}
	
			return $form_data;
		}

		public static function sanitize_data( $form_data ) {

			// Handle settings - check if it's a serialized string or an array
			$settings_is_serialized = false;
			if ( isset( $form_data['settings'] ) && is_string( $form_data['settings'] ) ) {
				// Try to unserialize settings if it's a string
				$unserialized_settings = @unserialize( $form_data['settings'] );
				if ( $unserialized_settings !== false ) {
					$form_data['settings'] = $unserialized_settings;
					$settings_is_serialized = true;
				}
			}

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
		
			// If settings were originally serialized, serialize them back
			if ( $settings_is_serialized ) {
				$form_data['settings'] = serialize( $form_data['settings'] );
			}
		
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

		/**
		 * Get form-specific CSS based on selected style
		 *
		 * @param int $form_id Form ID
		 * @param string $style Selected form style
		 * @return string CSS rules
		 */
		public static function get_form_style_css( $form_id, $style ) {
			$css = '';
			$selector = ".es-form-{$form_id}";

			switch ( $style ) {
				case 'straight-border':
					$css = self::get_straight_border_css( $selector );
					break;
				case 'rounded-border':
					$css = self::get_rounded_border_css( $selector );
					break;
				case 'grey-background':
					$css = self::get_grey_background_css( $selector );
					break;
				case 'minimalistic':
					$css = self::get_minimalistic_css( $selector );
					break;
				case 'compact':
					$css = self::get_compact_css( $selector );
					break;
				case 'inline':
					$css = self::get_inline_css( $selector );
					break;
				case 'dark':
					$css = self::get_dark_css( $selector );
					break;
				case 'inherit':
				default:
					// Provide default styling for inherit and unknown styles
					$css = self::get_default_css( $selector );
					break;
			}

			return $css;
		}

		/**
		 * Get CSS for straight border style
		 */
		private static function get_straight_border_css( $selector ) {
			return "
				{$selector} form {
					background: #ffffff !important;
					border: 1px solid #d1d5db !important;
					border-radius: 0px !important;
					padding: 20px !important;
				}
				{$selector} .es-field-wrap {
					margin-bottom: 16px !important;
				}
				{$selector} input[type='text'],
				{$selector} input[type='email'],
				{$selector} input[type='number'],
				{$selector} input[type='date'],
				{$selector} textarea,
				{$selector} select {
					border: 1px solid #d1d5db !important;
					border-radius: 0px !important;
					padding: 8px 12px !important;
					font-size: 14px !important;
					background: #ffffff !important;
					color: #374151 !important;
					width: 100% !important;
					box-sizing: border-box !important;
					outline: none !important;
				}
				{$selector} input[type='text']:focus,
				{$selector} input[type='email']:focus,
				{$selector} input[type='number']:focus,
				{$selector} input[type='date']:focus,
				{$selector} textarea:focus,
				{$selector} select:focus {
					outline: none !important;
					ring: 0 !important;
				}
				{$selector} .es_subscription_form_submit {
					background: rgb(88,80,236) !important;
					border: none !important;
					border-radius: 0px !important;
					color: #ffffff !important;
					padding: 8px 16px !important;
					font-size: 14px !important;
					font-weight: 500 !important;
					cursor: pointer !important;
					margin-top: 8px !important;
				}
				{$selector} .es_subscription_form_submit:hover {
					background: rgb(104,117,245) !important;
				}
			";
		}

		/**
		 * Get CSS for rounded border style
		 */
		private static function get_rounded_border_css( $selector ) {
			return "
				{$selector} form {
					background: #ffffff !important;
					border: 1px solid #d1d5db !important;
					border-radius: 8px !important;
					padding: 20px !important;
				}
				{$selector} .es-field-wrap {
					margin-bottom: 16px !important;
				}
				{$selector} input[type='text'],
				{$selector} input[type='email'],
				{$selector} input[type='number'],
				{$selector} input[type='date'],
				{$selector} textarea,
				{$selector} select {
					border: 1px solid #d1d5db !important;
					border-radius: 6px !important;
					padding: 8px 12px !important;
					font-size: 14px !important;
					background: #ffffff !important;
					color: #374151 !important;
					width: 100% !important;
					box-sizing: border-box !important;
					outline: none !important;
				}
				{$selector} input[type='text']:focus,
				{$selector} input[type='email']:focus,
				{$selector} input[type='number']:focus,
				{$selector} input[type='date']:focus,
				{$selector} textarea:focus,
				{$selector} select:focus {
					outline: none !important;
					ring: 0 !important;
				}
				{$selector} .es_subscription_form_submit {
					background: rgb(88,80,236) !important;
					border: none !important;
					border-radius: 6px !important;
					color: #ffffff !important;
					padding: 8px 16px !important;
					font-size: 14px !important;
					font-weight: 500 !important;
					cursor: pointer !important;
					margin-top: 8px !important;
				}
				{$selector} .es_subscription_form_submit:hover {
					background: rgb(104,117,245) !important;
				}
			";
		}

		/**
		 * Get CSS for grey background style
		 */
		private static function get_grey_background_css( $selector ) {
			return "
				{$selector} form {
					background: #d1d5db !important;
					border: 2px solid #3b82f6 !important;
					border-radius: 8px !important;
					padding: 20px !important;
				}
				{$selector} .es-field-wrap {
					margin-bottom: 16px !important;
				}
				{$selector} input[type='text'],
				{$selector} input[type='email'],
				{$selector} input[type='number'],
				{$selector} input[type='date'],
				{$selector} textarea,
				{$selector} select {
					border: 1px solid #d1d5db !important;
					border-radius: 4px !important;
					padding: 8px 12px !important;
					font-size: 14px !important;
					background: #f9fafb !important;
					color: #111827 !important;
					width: 100% !important;
					box-sizing: border-box !important;
					outline: none !important;
				}
				{$selector} input[type='text']:focus,
				{$selector} input[type='email']:focus,
				{$selector} input[type='number']:focus,
				{$selector} input[type='date']:focus,
				{$selector} textarea:focus,
				{$selector} select:focus {
					outline: none !important;
					ring: 0 !important;
				}
				{$selector} .es_subscription_form_submit {
					background: rgb(88,80,236) !important;
					border: none !important;
					border-radius: 4px !important;
					color: #ffffff !important;
					padding: 8px 16px !important;
					font-size: 14px !important;
					font-weight: 500 !important;
					cursor: pointer !important;
					margin-top: 8px !important;
				}
				{$selector} .es_subscription_form_submit:hover {
					background: rgb(104,117,245) !important;
				}
			";
		}

		/**
		 * Get CSS for minimalistic style
		 */
		private static function get_minimalistic_css( $selector ) {
			return "
				{$selector} form {
					background: transparent !important;
					border: none !important;
					border-radius: 0px !important;
					padding: 16px !important;
					box-shadow: none !important;
				}
				{$selector} .es-field-wrap {
					margin-bottom: 16px !important;
				}
				{$selector} input[type='text'],
				{$selector} input[type='email'],
				{$selector} input[type='number'],
				{$selector} input[type='date'],
				{$selector} textarea,
				{$selector} select {
					border: 0 !important;
					border-bottom: 1px solid #d1d5db !important;
					border-radius: 0px !important;
					padding: 8px 0px !important;
					font-size: 14px !important;
					background: transparent !important;
					color: #111827 !important;
					width: 100% !important;
					box-sizing: border-box !important;
					outline: none !important;
				}
				{$selector} input[type='text']:focus,
				{$selector} input[type='email']:focus,
				{$selector} input[type='number']:focus,
				{$selector} input[type='date']:focus,
				{$selector} textarea:focus,
				{$selector} select:focus {
					outline: none !important;
					ring: 0 !important;
				}
				{$selector} .es_subscription_form_submit {
					background: transparent !important;
					border: 1px solid #374151 !important;
					border-radius: 0px !important;
					color: #374151 !important;
					padding: 8px 16px !important;
					font-size: 14px !important;
					font-weight: 400 !important;
					cursor: pointer !important;
					margin-top: 8px !important;
					text-transform: uppercase !important;
					letter-spacing: 0.05em !important;
				}
				{$selector} .es_subscription_form_submit:hover {
					background: #374151 !important;
					color: #ffffff !important;
				}
			";
		}

		/**
		 * Get CSS for compact style
		 */
		private static function get_compact_css( $selector ) {
			return "
				{$selector} form {
					background: #ffffff !important;
					border: 1px solid #e5e7eb !important;
					border-radius: 4px !important;
					padding: 8px !important;
				}
				{$selector} .es-field-wrap {
					margin-bottom: 8px !important;
				}
				{$selector} input[type='text'],
				{$selector} input[type='email'],
				{$selector} input[type='number'],
				{$selector} input[type='date'],
				{$selector} textarea,
				{$selector} select {
					border: 1px solid #d1d5db !important;
					border-radius: 4px !important;
					padding: 6px 8px !important;
					font-size: 12px !important;
					background: #ffffff !important;
					color: #111827 !important;
					width: 100% !important;
					box-sizing: border-box !important;
					outline: none !important;
				}
				{$selector} input[type='text']:focus,
				{$selector} input[type='email']:focus,
				{$selector} input[type='number']:focus,
				{$selector} input[type='date']:focus,
				{$selector} textarea:focus,
				{$selector} select:focus {
					outline: none !important;
					ring: 0 !important;
				}
				{$selector} .es_subscription_form_submit {
					background: rgb(88,80,236) !important;
					border: none !important;
					border-radius: 4px !important;
					color: #ffffff !important;
					padding: 6px 12px !important;
					font-size: 12px !important;
					font-weight: 500 !important;
					cursor: pointer !important;
					margin-top: 4px !important;
				}
				{$selector} .es_subscription_form_submit:hover {
					background: rgb(104,117,245) !important;
				}
			";
		}

		/**
		 * Get CSS for inline style
		 */
		private static function get_inline_css( $selector ) {
			return "
				{$selector} form {
					background: transparent !important;
					border: none !important;
					border-radius: 0px !important;
					padding: 0px !important;
					display: flex !important;
					flex-direction: row !important;
					align-items: end !important;
					gap: 8px !important;
				}
				{$selector} .es-field-wrap {
					margin-bottom: 0px !important;
					flex: 1 !important;
				}
				{$selector} input[type='text'],
				{$selector} input[type='email'],
				{$selector} input[type='number'],
				{$selector} input[type='date'],
				{$selector} textarea,
				{$selector} select {
					border: 1px solid #d1d5db !important;
					border-radius: 4px !important;
					padding: 8px 12px !important;
					font-size: 14px !important;
					background: #ffffff !important;
					color: #111827 !important;
					width: 100% !important;
					box-sizing: border-box !important;
					outline: none !important;
				}
				{$selector} input[type='text']:focus,
				{$selector} input[type='email']:focus,
				{$selector} input[type='number']:focus,
				{$selector} input[type='date']:focus,
				{$selector} textarea:focus,
				{$selector} select:focus {
					outline: none !important;
					ring: 0 !important;
				}
				{$selector} .es_subscription_form_submit {
					background: rgb(88,80,236) !important;
					border: none !important;
					border-radius: 4px !important;
					color: #ffffff !important;
					padding: 8px 16px !important;
					font-size: 14px !important;
					font-weight: 500 !important;
					cursor: pointer !important;
					margin-top: 0px !important;
					flex-shrink: 0 !important;
				}
				{$selector} .es_subscription_form_submit:hover {
					background: rgb(104,117,245) !important;
				}
			";
		}

		/**
		 * Get CSS for dark style
		 */
		private static function get_dark_css( $selector ) {
			return "
				{$selector} form {
					background: #000000 !important;
					border: 2px solid #ef4444 !important;
					border-radius: 8px !important;
					padding: 20px !important;
					color: #ffffff !important;
				}
				{$selector} .es-field-wrap {
					margin-bottom: 16px !important;
				}
				{$selector} input[type='text'],
				{$selector} input[type='email'],
				{$selector} input[type='number'],
				{$selector} input[type='date'],
				{$selector} textarea,
				{$selector} select {
					border: 0 !important;
					border-radius: 4px !important;
					padding: 8px 12px !important;
					font-size: 14px !important;
					background: #2d2d2d !important;
					color: #ffffff !important;
					width: 100% !important;
					box-sizing: border-box !important;
					outline: none !important;
				}
				{$selector} input[type='text']:focus,
				{$selector} input[type='email']:focus,
				{$selector} input[type='number']:focus,
				{$selector} input[type='date']:focus,
				{$selector} textarea:focus,
				{$selector} select:focus {
					outline: none !important;
					ring: 0 !important;
				}
				{$selector} input[type='text']::placeholder,
				{$selector} input[type='email']::placeholder,
				{$selector} input[type='number']::placeholder,
				{$selector} input[type='date']::placeholder,
				{$selector} textarea::placeholder {
					color: #9ca3af !important;
				}
				{$selector} .es_subscription_form_submit {
					background: #2d2d2d !important;
					border: none !important;
					border-radius: 0px !important;
					color: #ffffff !important;
					padding: 8px 16px !important;
					font-size: 14px !important;
					font-weight: 500 !important;
					cursor: pointer !important;
					margin-top: 8px !important;
				}
				{$selector} .es_subscription_form_submit:hover {
					background: rgba(45,45,45,0.75) !important;
				}
				{$selector} label {
					color: #ffffff !important;
				}
			";
		}

		/**
		 * Get CSS for default/inherit style
		 */
		private static function get_default_css( $selector ) {
			return "
				{$selector} form {
					background: #ffffff !important;
					border: 1px solid #e5e7eb !important;
					border-radius: 8px !important;
					padding: 20px !important;
					max-width: 600px !important;
					margin: 0 auto !important;
				}
				{$selector} .es-field-wrap {
					margin-bottom: 16px !important;
				}
				{$selector} input[type='text'],
				{$selector} input[type='email'],
				{$selector} input[type='number'],
				{$selector} input[type='date'],
				{$selector} textarea,
				{$selector} select {
					border: 1px solid #d1d5db !important;
					border-radius: 4px !important;
					padding: 8px 12px !important;
					font-size: 14px !important;
					background: #ffffff !important;
					color: #374151 !important;
					width: 100% !important;
					box-sizing: border-box !important;
					outline: none !important;
				}
				{$selector} input[type='text']:focus,
				{$selector} input[type='email']:focus,
				{$selector} input[type='number']:focus,
				{$selector} input[type='date']:focus,
				{$selector} textarea:focus,
				{$selector} select:focus {
					outline: none !important;
					ring: 0 !important;
				}
				{$selector} .es_subscription_form_submit {
					background: rgb(88,80,236) !important;
					border: none !important;
					border-radius: 4px !important;
					color: #ffffff !important;
					padding: 8px 16px !important;
					font-size: 14px !important;
					font-weight: 500 !important;
					cursor: pointer !important;
					margin-top: 8px !important;
				}
				{$selector} .es_subscription_form_submit:hover {
					background: rgb(104,117,245) !important;
				}
				{$selector} label {
					color: #374151 !important;
					font-weight: 500 !important;
				}
			";
		}
	}

}

ES_Form_Controller::get_instance();
