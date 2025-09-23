<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines Shortcode
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/public
 */
class ES_Shortcode {

	/**
	 * Unique form identifier based on number of forms rendered on the page
	 * 
	 * @var string
	 * 
	 * @since 4.7.5
	 */
	public static $form_identifier;

	/**
	 * Variable to store form submission response
	 *
	 * @var array
	 * 
	 * @since 4.7.5
	 */
	public static $response = array();

	public function __construct() {
	}

	public static function render_es_subscription_shortcode( $atts ) {
		ob_start();

		$atts = shortcode_atts( array(
			'namefield' => '',
			'desc'      => '',
			'group'     => ''
		), $atts, 'email-subscribers' );

		$data['name_visible'] = $atts['namefield'];
		$data['list_visible'] = 'no';
		$data['lists']        = array();
		$data['form_id']      = 0;
		$data['list']         = $atts['group'];
		$data['desc']         = $atts['desc'];

		self::render_form( $data );

		return ob_get_clean();
	}

	/**
	 * Render Subscription form using ES 4.0+ Shortcode
	 *
	 * @param $atts
	 *
	 * @return false|string
	 */
	public static function render_es_form( $atts ) {
		ob_start();
		
		$atts = shortcode_atts( array( 
			'id' => '',
			'show-in-popup' => ''
		), $atts, 'email-subscribers-form' );

		$id = $atts['id'];

		if ( ! empty( $id ) ) {
			$form = ES()->forms_db->get_form_by_id( $id );

			if ( $form ) {
				// Check if this is a new WYSIWYG form
				$settings = maybe_unserialize( $form['settings'] );
				$editor_type = ! empty( $settings['editor_type'] ) ? $settings['editor_type'] : '';
				
				if ( 'wysiwyg' === $editor_type ) {
					// For new WYSIWYG forms, prepare data in the expected format
					$form_data = array(
						'form_id' => $form['id'],
						'body' => $form['body'], // Keep as JSON string for the render method to decode
						'settings' => $settings,
						'styles' => maybe_unserialize( $form['styles'] ),
						'show-in-popup-attr' => isset( $atts['show-in-popup'] ) ? sanitize_text_field( $atts['show-in-popup'] ) : ''
					);
				} else {
					// For old forms, use the existing method
					$form_data = ES_Forms_Table::get_form_data_from_body( $form );
					$form_data['show-in-popup-attr'] = isset( $atts['show-in-popup'] ) ? sanitize_text_field( $atts['show-in-popup'] ) : '';
				}
				
				$form_html = self::render_form( $form_data );
			}
		}

		return ob_get_clean();
	}

	// Handle Email Subscribers Group Selector Shortcode
	// Backward Compatibility
	public static function render_es_advanced_form( $atts ) {
		ob_start();

		$atts = shortcode_atts( array(
			'id' => ''
		), $atts, 'email-subscribers-advanced-form' );

		$af_id = $atts['id'];

		if ( ! empty( $af_id ) ) {
			$form = ES()->forms_db->get_form_by_af_id( $af_id );
			if ( $form ) {
				$form_data = ES_Forms_Table::get_form_data_from_body( $form );

				self::render_form( $form_data );
			}
		}

		return ob_get_clean();
	}

	/**
	 * Get the name field to render inside the form
	 *
	 * @param string $show_name
	 * @param string $name_label
	 * @param string $name_required
	 * @param string $name_placeholder
	 * @param string $submitted_name
	 *
	 * @return string
	 */
	public static function get_name_field_html( $show_name, $name_label, $name_required, $name_placeholder, $submitted_name = '' ) {
		$required = '';
		if ( ! empty( $show_name ) && 'no' !== $show_name ) {
			if ( 'yes' === $name_required ) {
				$required = 'required';
				if ( ! empty( $name_label ) ) {
					$name_label .= '*';
				}
			}
			$name_html = '<div class="es-field-wrap"><label>' . $name_label . '<br/><input type="text" name="esfpx_name" class="ig_es_form_field_name" placeholder="' . $name_placeholder . '" value="' . $submitted_name . '" ';

			/* Adding required="required" as attribute name, value pair because wp_kses will strip off the attribute if only 'required' attribute is provided. */
			$name_html .= 'required' === $required ? 'required="' . $required . '"' : '';
			$name_html .= '/></label></div>';
			return $name_html;
		}

		return '';
	}

	/**
	 * Get the E-Mail field to render inside the form
	 *
	 * @param string $email_label
	 * @param string $email_placeholder
	 * @param string $submitted_email
	 *
	 * @return string
	 */
	public static function get_email_field_html( $email_label, $email_placeholder, $submitted_email = '' ) {
		$email_html = '<div class="es-field-wrap ig-es-form-field"><label class="es-field-label">';
		if ( ! empty( $email_label ) ) {
			$email_html .= $email_label . '*<br/>';
		}
		$email_html .= '<input class="es_required_field es_txt_email ig_es_form_field_email ig-es-form-input" type="email" name="esfpx_email" value="' . $submitted_email . '" placeholder="' . $email_placeholder . '" required="required"/></label></div>';

		return $email_html;
	}

	/**
	 *
	 * Get the List field to render inside the form
	 *
	 * @param string $show_list
	 * @param string $list_label
	 * @param mixed  $list_ids
	 * @param mixed  $list
	 * @param array  $selected_list_ids
	 *
	 * @return string
	 */
	public static function get_list_field_html( $show_list, $list_label, $list_ids, $list, $selected_list_ids = array() ) {
		if ( ! empty( $list_ids ) && $show_list ) {
			$lists_id_name_map = ES()->lists_db->get_list_id_name_map();
			$lists_id_hash_map = ES()->lists_db->get_list_id_hash_map( $list_ids );
			$list_html         = self::prepare_lists_checkboxes( $lists_id_name_map, $list_ids, 1, $selected_list_ids, $list_label, 0, 'esfpx_lists[]', $lists_id_hash_map );
		} elseif ( ! empty( $list_ids ) && ! $show_list ) {
			$list_html = '';
			$lists     = ES()->lists_db->get_lists_by_id( $list_ids );
			if ( ! empty( $lists ) ) {
				foreach ( $lists as $list ) {
					if ( ! empty( $list ) && ! empty( $list['hash'] ) ) {
						$list_html .= '<input type="hidden" name="esfpx_lists[]" value="' . $list['hash'] . '" />';
					}
				}
			}
		} elseif ( is_numeric( $list ) ) {
			$lists     = ES()->lists_db->get_lists_by_id( $list );
			$list_html = '';
			if ( ! empty( $lists ) ) {
				$list_hash = ! empty( $lists[0]['hash'] ) ? $lists[0]['hash'] : '';
				if ( ! empty( $list_hash ) ) {
					$list_html = '<input type="hidden" name="esfpx_lists[]" value="' . $list_hash . '" />';
				}
			}
		} else {
			$list_data = ES()->lists_db->get_list_by_name( $list );
			if ( empty( $list_data ) ) {
				$list_id = ES()->lists_db->add_list( $list );
			} else {
				$list_id = $list_data['id'];
			}

			$lists     = ES()->lists_db->get_lists_by_id( $list_id );
			$list_html = '';
			if ( ! empty( $lists ) ) {
				$list_hash = ! empty( $lists[0]['hash'] ) ? $lists[0]['hash'] : '';
				if ( ! empty( $list_hash ) ) {
					$list_html = '<input type="hidden" name="esfpx_lists[]" value="' . $list_hash . '" />';
				}
			}
		}

		return $list_html;
	}

	/**
	 * Render subscription form
	 *
	 * @param array $data Form data
	 *
	 * @return string
	 */
	public static function render_form( $data ) {

		/**
		 * - Show name? -> Prepare HTML for name
		 * - Show email? -> Prepare HTML for email // Always true
		 * - Show lists? -> Preapre HTML for Lists list_ids
		 * - Hidden Field -> form_id,
		 *      list,
		 *      es_email_page,
		 *      es_email_page_url,
		 *      es-subscribe,
		 *      honeypot field
		 */
		// Compatibility for GDPR
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		$editor_type   = ! empty( $data['settings']['editor_type'] ) ? $data['settings']['editor_type'] : '';
		$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
		$is_wysiwyg_editor = 'wysiwyg' === $editor_type;

		if ( $is_wysiwyg_editor ) {
			// Handle new WYSIWYG editor
			self::render_wysiwyg_form( $data );
			return; // Exit early for WYSIWYG forms
		} elseif ( ! $is_dnd_editor ) {
			$show_name          = ! empty( $data['name_visible'] ) ? strtolower( $data['name_visible'] ) : false;
			$required_name      = ! empty( $data['name_required'] ) ? $data['name_required'] : false;
			$name_label         = ! empty( $data['name_label'] ) ? $data['name_label'] : '';
			$name_placeholder   = ! empty( $data['name_place_holder'] ) ? $data['name_place_holder'] : '';
			$email_label        = ! empty( $data['email_label'] ) ? $data['email_label'] : '';
			$email_placeholder  = ! empty( $data['email_place_holder'] ) ? $data['email_place_holder'] : '';
			$button_label       = ! empty( $data['button_label'] ) ? $data['button_label'] : __( 'Subscribe', 'email-subscribers' );
			$list_label         = ! empty( $data['list_label'] ) ? $data['list_label'] : __( 'Select list(s)', 'email-subscribers' );
			$show_list          = ! empty( $data['list_visible'] ) ? $data['list_visible'] : false;
			$list_ids           = ! empty( $data['lists'] ) ? $data['lists'] : array();
			$form_id            = ! empty( $data['form_id'] ) ? $data['form_id'] : 0;
			$list               = ! empty( $data['list'] ) ? $data['list'] : 0;
			$desc               = ! empty( $data['desc'] ) ? $data['desc'] : '';
			$form_version       = ! empty( $data['form_version'] ) ? $data['form_version'] : '0.1';
			$gdpr_consent       = ! empty( $data['gdpr_consent'] ) ? $data['gdpr_consent'] : 'no';
			$gdpr_consent_text  = ! empty( $data['gdpr_consent_text'] ) ? $data['gdpr_consent_text'] : '';
			$es_form_popup      = isset( $data['show_in_popup'] ) ? $data['show_in_popup'] : 'no';
			$es_popup_headline  = isset( $data['popup_headline'] ) ? $data['popup_headline'] : '';
			$show_in_popup_attr = isset( $data['show-in-popup-attr'] ) ? $data['show-in-popup-attr'] : '';
		} else {
			$show_list          = ! empty( $data['list_visible'] ) ? $data['list_visible'] : false;
			$list_ids           = ! empty( $data['settings']['lists'] ) ? $data['settings']['lists'] : array();
			$form_id            = ! empty( $data['form_id'] ) ? $data['form_id'] : 0;
			$list               = ! empty( $data['list'] ) ? $data['list'] : 0;
			$desc               = ! empty( $data['desc'] ) ? $data['desc'] : '';
			$form_version       = ! empty( $data['form_version'] ) ? $data['form_version'] : '0.1';
			$es_form_popup      = isset( $data['settings']['show_in_popup'] ) ? $data['settings']['show_in_popup'] : 'no';
			$es_popup_headline  = isset( $data['settings']['popup_headline'] ) ? $data['settings']['popup_headline'] : '';
			$show_in_popup_attr = isset( $data['show-in-popup-attr'] ) ? $data['show-in-popup-attr'] : '';
			$button_label = '';
		}


		
		$allowedtags 		= ig_es_allowed_html_tags_in_esc();

		/**
		 * We did not have $email_label, $name_label in
		 * ES < 4.2.2
		 *
		 * Since ES 4.2.2, we are adding form_version in form settings.
		 *
		 * If we don't find Form Version in settings, we are setting as 0.1
		 *
		 * So, if form_version is 0.1 then set default label
		 */
		if ( '0.1' == $form_version ) {
			$email_label = __( 'Email', 'email-subscribers' );
			$name_label  = __( 'Name', 'email-subscribers' );
		}

		self::$form_identifier = self::generate_form_identifier( $form_id );

		$submitted_name    = '';
		$submitted_email   = '';
		$message_class     = '';
		$message_text      = '';
		$selected_list_ids = array();

		if ( self::is_posted() ) {
			// self::$response is set by ES_Handle_Subscription::handle_subscription() when subscription form is posted
			$response = ! empty( self::$response ) ? self::$response: array();
			if ( ! empty( $response ) ) {
				$message_class = ! empty( $response['status'] ) && 'SUCCESS' === $response['status'] ? 'success' : 'error';
				$message_text  = ! empty( $response['message_text'] ) ? $response['message_text'] : '';
			}

			$submitted_name       = ig_es_get_post_data( 'esfpx_name' );
			$submitted_email      = ig_es_get_post_data( 'esfpx_email' );
			$selected_list_hashes = ig_es_get_post_data( 'esfpx_lists' );

			if ( ! empty( $selected_list_hashes ) ) {
				$selected_lists = ES()->lists_db->get_lists_by_hash( $selected_list_hashes );
				if ( $selected_lists ) {
					$selected_list_ids = array_column( $selected_lists, 'id' );
				}
			}
		} else {
			if ( is_user_logged_in() ) {
				$prefill_form = apply_filters( 'ig_es_prefill_subscription_form', 'yes' );
				if ( 'yes' === $prefill_form ) {
					$current_user    = wp_get_current_user();
					$submitted_email = $current_user->user_email;

					if ( ! empty( $current_user->user_firstname ) && ! empty( $current_user->user_lastname ) ) {
						$submitted_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
					}
				}
			}
		}

		//replace total contact
		$total_contacts = ES()->contacts_db->count_active_contacts_by_list_id();
		$desc           = str_replace( '{{TOTAL-CONTACTS}}', $total_contacts, $desc );

		$current_page     = get_the_ID();
		$current_page_url = get_the_permalink( $current_page );

		$unique_id = uniqid();
		$hp_style  = 'position:absolute;top:-99999px;' . ( is_rtl() ? 'right' : 'left' ) . ':-99999px;z-index:-99;';
		$nonce     = wp_create_nonce( 'es-subscribe' );

		// Form html
		$form_html = '<input type="hidden" name="esfpx_form_id" value="' . $form_id . '" />';

		$form_header_html = '';
		$form_data_html   = '';
		$form_orig_html   = '';

		$form_orig_html = $form_header_html;
		// Don't show form if submission was successful.
		if ( 'success' !== $message_class) {
			$form_action_url             = ES_Common::get_current_request_url();
			$enable_ajax_form_submission = get_option( 'ig_es_enable_ajax_form_submission', 'yes' );
			$extra_form_class            = ( 'yes' == $enable_ajax_form_submission ) ? ' es_ajax_subscription_form' : '';

			$form_header_html .= '<form action="' . $form_action_url . '#es_form_' . self::$form_identifier . '" method="post" class="es_subscription_form es_shortcode_form ' . esc_attr( $extra_form_class ) . '" id="es_subscription_form_' . $unique_id . '" data-source="ig-es" data-form-id="' . $form_id . '">';
				
			if ( '' != $desc ) {
				$form_header_html .= '<div class="es_caption">' . $desc . '</div>';
			} 
			
			$form_data_html = '<input type="hidden" name="es" value="subscribe" />
			<input type="hidden" name="esfpx_es_form_identifier" value="' . self::$form_identifier . '" />
			<input type="hidden" name="esfpx_es_email_page" value="' . $current_page . '"/>
			<input type="hidden" name="esfpx_es_email_page_url" value="' . $current_page_url . '"/>
			<input type="hidden" name="esfpx_status" value="Unconfirmed"/>
			<input type="hidden" name="esfpx_es-subscribe" id="es-subscribe-' . $unique_id . '" value="' . $nonce . '"/>
			<label style="' . $hp_style . '" aria-hidden="true"><span hidden>' . __( 'Please leave this field empty.', 'email-subscribers' ) . '</span><input type="email" name="esfpx_es_hp_email" class="es_required_field" tabindex="-1" autocomplete="-1" value=""/></label>';

			$spinner_image_path = ES_PLUGIN_URL . 'lite/public/images/spinner.gif';

			$editor_type   = ! empty( $data['settings']['editor_type'] ) ? $data['settings']['editor_type'] : '';
			$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;
			if ( ! $is_dnd_editor ) {

				// Name
				$name_html = self::get_name_field_html($show_name, $name_label, $required_name, $name_placeholder, $submitted_name);
		
				// Lists
				$list_html = self::get_list_field_html($show_list, $list_label, $list_ids, $list, $selected_list_ids);
				$email_html = self::get_email_field_html($email_label, $email_placeholder, $submitted_email);

				$form = array( $form_header_html, $name_html, $email_html, $list_html, $form_html, $form_data_html );
				$form_orig_html = implode( '', $form );
				$form_data_html = apply_filters( 'ig_es_after_form_fields', $form_orig_html, $data );
	
				if ( 'yes' === $gdpr_consent ) { 
					
					$form_data_html .= '<label style="display: inline"><input type="checkbox" name="es_gdpr_consent" value="true" required="required"/>&nbsp;' . $gdpr_consent_text . '</label><br/>'; 
				} elseif ( ( in_array( 'gdpr/gdpr.php', $active_plugins ) || array_key_exists( 'gdpr/gdpr.php', $active_plugins ) ) ) {
					GDPR::consent_checkboxes();
				}
	
				
				$form_data_html .= '<input type="submit" name="submit" class="es_subscription_form_submit es_submit_button es_textbox_button" id="es_subscription_form_submit_' . $unique_id . '" value="' . $button_label . '"/>'; 
	
				
			} else {
				if ( ! empty( $list_ids ) ) {
					$list_html  = self::get_list_field_html(false, '', $list_ids, '', $selected_list_ids);
					$form_html .= $list_html;
				}

				$form_body = '';
				if ( ! empty( $data['settings']['dnd_editor_css'] ) ) {
					$editor_css = $data['settings']['dnd_editor_css'];
					// We are using attribute selector data-form-id to apply Form style and not form unique id since when using it, it overrides GrapeJs custom style changes done through GrapeJS style editor.
					$editor_css = str_replace( '.es-form-field-container', 'form[data-form-id="' . $form_id . '"] .es-form-field-container', $editor_css );
					$editor_css = str_replace( '* {', 'form.es_subscription_form[data-form-id="' . $form_id . '"] * {', $editor_css );
					$form_body  = '<style type="text/css">' . $editor_css . '</style>';
				}
				$form_body .= ! empty( $data['body'] ) ? do_shortcode( $data['body'] ) : '';
				$form = array( $form_header_html, $form_html, $form_data_html, $form_body );
				$form_orig_html = implode( '', $form );
				$form_data_html = $form_orig_html;
				
				if ( ! empty( $submitted_name ) ) {
					$form_data_html = str_replace( 'name="esfpx_name"', 'name="esfpx_name" value="' . esc_attr( $submitted_name ) . '"', $form_data_html );
				}

				if ( ! empty( $submitted_email ) ) {
					$form_data_html = str_replace( 'name="esfpx_email"', 'name="esfpx_email" value="' . esc_attr( $submitted_email ) . '"', $form_data_html );
				}
			}

			$form_data_html .= '<span class="es_spinner_image" id="spinner-image"><img src="' . $spinner_image_path . '" alt="Loading"/></span></form>';
		
		}
		
		$form_data_html .= '<span class="es_subscription_message ' . $message_class . '" id="es_subscription_message_' . $unique_id . '" role="alert" aria-live="assertive">' . $message_text . '</span>';

		// Wrap form html inside a container.
		$form_data_html = '<div class="emaillist" id="es_form_' . self::$form_identifier . '">' . $form_data_html . '</div>';
		$form_data_html = ES_Common::strip_js_code($form_data_html);
		$form = str_replace(['`', '´','&#096;'], '', $form_data_html);

		$show_in_popup = false;
	
		if ( ! empty( $es_form_popup ) && 'yes' === $es_form_popup ) {
			if ( empty( $show_in_popup_attr ) || 'yes' === $show_in_popup_attr ) {
				$show_in_popup = true;
			}
		}

		if ( $show_in_popup ) {

			if ( ! wp_style_is( 'ig-es-popup-frontend' ) ) {
				wp_enqueue_style( 'ig-es-popup-frontend' );
			}
			
			if ( ! wp_style_is( 'ig-es-popup-css' ) ) {
				wp_enqueue_style( 'ig-es-popup-css' );
			}
			
			?>
			<script type="text/javascript">
				if( typeof(window.icegram) === 'undefined'){
				<?php
				if ( ! wp_script_is( 'ig-es-popup-js' ) ) {
					wp_enqueue_script( 'ig-es-popup-js' );
				}		
				?>
				}
			</script>
			
			<script type="text/javascript">
				jQuery( function () { 
					var form_id = <?php echo esc_js($form_id); ?>;
					
					var es_message_id = "es" + form_id ;
					var message = '<h3 style=\"text-align: center;\"><?php echo esc_js( $es_popup_headline ); ?></h3>';
					
					es_pre_data.ajax_url = '<?php echo esc_url(admin_url( 'admin-ajax.php' )); ?>';
					es_pre_data.messages[0].form_html = `<?php echo wp_kses( html_entity_decode( $form ), $allowedtags); ?>`;
					es_pre_data.messages[0].id = es_pre_data.messages[0].campaign_id = es_message_id;
					es_pre_data.messages[0].label = <?php echo json_encode($button_label); ?>;
					es_pre_data.messages[0].message = message;
					
					var es_data = es_pre_data;
					
					if( typeof(window.icegram) === 'undefined'){
						window.icegram = new Icegram();
						window.icegram.init( es_data );
					} 
					
					jQuery( window ).on( "preinit.icegram", function( e, data ) {
						var icegram_data = es_data['messages'].concat(data['messages']);
						data.messages = icegram_data;
					});

				});
			</script>
			<?php
			return $form; 
			
		} else {
			add_filter( 'safe_style_css', 'ig_es_allowed_css_style', 999 );
			echo wp_kses( $form, $allowedtags );
			remove_filter( 'safe_style_css', 'ig_es_allowed_css_style', 999 );
		}
	}
	
	/**
	 * Prepare lists checkboxes HTML
	 *
	 * @param array $lists Lists data
	 * @param array $list_ids List IDs
	 * @param int   $columns Number of columns
	 * @param array $selected_lists Selected lists
	 * @param string $list_label List label
	 * @param int   $contact_id Contact ID
	 * @param string $name Field name
	 * @param array $lists_id_hash_map Lists ID hash map
	 *
	 * @return string
	 */
	public static function prepare_lists_checkboxes( $lists, $list_ids = array(), $columns = 3, $selected_lists = array(), $list_label = '', $contact_id = 0, $name = 'lists[]', $lists_id_hash_map = array() ) {

		$list_label = ! empty( $list_label ) ? $list_label : __( 'Select list(s)', 'email-subscribers' );
		$lists_html = '<div><p><b class="font-medium text-gray-500 pb-2">' . $list_label . '*</b></p><table class="ig-es-form-list-selection"><tr>';
		$i          = 0;

		if ( ! empty( $contact_id ) ) {
			$list_contact_status_map = ES()->lists_contacts_db->get_list_contact_status_map( $contact_id );
		}

		$lists = apply_filters( 'ig_es_lists', $lists );

		foreach ( $lists as $list_id => $list_name ) {
			if ( 0 != $i && 0 === ( $i % $columns ) ) {
				$lists_html .= '</tr><tr>';
			}
			$status_span = '';
			if ( in_array( $list_id, $list_ids ) ) {

				// Check if list hash has been passed for given list id, if yes then use list hash, else use list id
				if ( ! empty( $lists_id_hash_map[ $list_id ] ) ) {
					$list_value = $lists_id_hash_map[ $list_id ];
				} else {
					$list_value = $list_id;
				}
				
				if ( in_array( $list_id, $selected_lists ) ) {
					if ( ! empty( $contact_id ) ) {
						$status_span = '<span class="es_list_contact_status ' . $list_contact_status_map[ $list_id ] . '" title="' . ucwords( $list_contact_status_map[ $list_id ] ) . '">';
					}
					$lists_html .= '<td class="pt-4">';
					$lists_html .= $status_span . '<label class="flex items-center"><input type="checkbox" class="form-checkbox" name="' . $name . '" checked="checked" value="' . $list_value . '" style="margin-right: 8px;" /><span class="text-gray-500 text-sm font-normal">' . $list_name . '</span></label></td>';
				} else {
					$lists_html .= '<td class="pt-4"><label class="flex items-center"><input type="checkbox" class="form-checkbox" name="' . $name . '" value="' . $list_value . '" style="margin-right: 8px;" /><span class="text-gray-500 text-sm font-normal">' . $list_name . '</span></label></td>';
				}
				$i ++;
			}
		}

		$lists_html .= '</tr></table></div>';

		return $lists_html;
	}

	/**
	 * Prepare lists multi-select HTML
	 *
	 * @param array $lists Lists data
	 * @param array $list_ids List IDs
	 * @param int   $columns Number of columns
	 * @param array $selected_lists Selected lists
	 * @param string $list_label List label
	 * @param int   $contact_id Contact ID
	 * @param string $name Field name
	 * @param array $lists_id_hash_map Lists ID hash map
	 *
	 * @return string
	 */
	public static function prepare_lists_multi_select( $lists, $list_ids = array(), $columns = 3, $selected_lists = array(), $list_label = '', $contact_id = 0, $name = 'lists[]', $lists_id_hash_map = array() ) {
		$list_label = ! empty( $list_label ) ? $list_label : __( 'Select list(s)', 'email-subscribers' );
		$lists_html = '<div class="max-w-sm mx-auto bg-white shadow-md rounded-lg p-6 pt-0.5">';
		$lists_html .= '<label for="form multi-select" class="block text-gray-700 text-sm font-bold mb-2">' . $list_label . '*</label>';
		$lists_html .= '<select id="ig-es-multiselect-lists" name="' . $name . '" multiple class="block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300">';
		$lists = apply_filters( 'ig_es_lists', $lists );
	
		foreach ( $lists as $list_id => $list_name ) {
			$is_selected = in_array( $list_id, $selected_lists ) ? 'selected' : '';
			$list_value = ! empty( $lists_id_hash_map[ $list_id ] ) ? $lists_id_hash_map[ $list_id ] : $list_id;
			$lists_html .= '<option value="' . esc_attr( $list_value ) . '" ' . $is_selected . '>' . esc_html( $list_name ) . '</option>';
		}
	
		$lists_html .= '</select></div>';
	
		return $lists_html;
	}

	/**
	 * Generate a unique form identifier based on number of forms already rendered on the page.
	 * 
	 * @return string $form_identifier
	 * 
	 * @since 4.7.5
	 */
	public static function generate_form_identifier( $form_id = 0 ) {
		
		static $form_count = 1;
		
		$form_identifier = '';

		if ( in_the_loop() ) {
			$page_id         = get_the_ID();
			$form_identifier = sprintf( 'f%1$d-p%2$d-n%3$d',
				$form_id,
				$page_id,
				$form_count
			);
		} else {
			$form_identifier = sprintf( 'f%1$d-n%2$d',
				$form_id,
				$form_count
			);
		}

		$form_count++;

		return $form_identifier;
	}

	/**
	 * Get form's identifier
	 * 
	 * @return string
	 * 
	 * @since 4.7.5
	 */
	public static function get_form_identifier() {
		return self::$form_identifier;
	}

	/**
	 * Return true if this form is the same one as currently posted.
	 * 
	 * @return boolean
	 * 
	 * @since 4.7.5
	 */
	public static function is_posted() {

		$form_identifier = ig_es_get_request_data( 'esfpx_es_form_identifier' );
		if ( empty( $form_identifier ) ) {
			return false;
		}

		$current_identifier = self::get_form_identifier();
		
		// If current identifier is empty, we can't match
		if ( empty( $current_identifier ) ) {
			return false;
		}
		
		// Extract form ID from both identifiers and compare
		$submitted_form_id = explode('-', $form_identifier)[0] ?? '';
		$current_form_id = explode('-', $current_identifier)[0] ?? '';
		
		// Match if same form ID (simpler approach)
		return $submitted_form_id === $current_form_id && !empty($submitted_form_id);
	}

	/**
	 * Render WYSIWYG editor forms (New forms with JSON body structure)
	 * 
	 * @param array $data Form data
	 * 
	 * @since 5.8.0
	 */
	public static function render_wysiwyg_form( $data ) {
		$form_id = ! empty( $data['form_id'] ) ? $data['form_id'] : 0;
		$settings = ! empty( $data['settings'] ) ? $data['settings'] : array();
		$styles = ! empty( $data['styles'] ) ? $data['styles'] : array();
		$body = ! empty( $data['body'] ) ? $data['body'] : array();
		
		// Parse body fields if it's JSON string
		if ( is_string( $body ) ) {
			$decoded_body = json_decode( $body, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_body ) ) {
				$body = $decoded_body;
			} else {
				$body = maybe_unserialize( $body );
			}
		}
		
		if ( ! is_array( $body ) ) {
			$body = array();
		}
		
		// Check if this form should be displayed as popup
		$show_in_popup = false;
		$es_popup_headline = '';
		
		
		// Check for popup setting in settings array
		if ( ! empty( $settings['show_in_popup'] ) && 'yes' === $settings['show_in_popup'] ) {
			$show_in_popup = true;
			$es_popup_headline = ! empty( $settings['popup_headline'] ) ? $settings['popup_headline'] : '';
		} 
		
		// If it's a popup form, use the popup implementation
		if ( $show_in_popup ) {
			self::render_wysiwyg_popup_form( $data );
			return;
		}
		
		// Continue with inline form rendering
		// Set form identifier first so it's available for is_posted() check
		$form_identifier = self::generate_form_identifier( $form_id );
		self::$form_identifier = $form_identifier;
		
		// Handle form submission response
		$submitted_data = self::get_submitted_data();
		$message_class = '';
		$message_text = '';
		
		if ( self::is_posted() && ! empty( self::$response ) ) {
			$message_class = ! empty( self::$response['status'] ) && 'SUCCESS' === self::$response['status'] ? 'success' : 'error';
			$message_text = ! empty( self::$response['message_text'] ) ? self::$response['message_text'] : '';
		}
		
		// Don't show form if submission was successful and check show_message setting
		$show_message = ! empty( $settings['show_message'] ) ? $settings['show_message'] : 'yes';
		if ( 'success' === $message_class ) {
			if ( 'yes' === $show_message ) {
				echo '<div class="es_subscription_message ' . esc_attr( $message_class ) . '">' . esc_html( $message_text ) . '</div>';
			}
			return;
		}
		
		// Form attributes
		$form_class = 'ig_es_subscription_form es_subscription_form es_widget_form wysiwyg-form es_shortcode_form';
		$form_style = '';
		
		// Add form style class if specified in settings
		if ( ! empty( $settings['form_style'] ) ) {
			// Sanitize form style name for CSS class
			$sanitized_style = sanitize_html_class( str_replace( ' ', '-', strtolower( $settings['form_style'] ) ) );
			$form_class .= ' es-form-style-' . $sanitized_style;
			
			// Also add the original style name as class for backward compatibility
			$original_style = sanitize_html_class( str_replace( ' ', '.', $settings['form_style'] ) );
			if ( $original_style !== $sanitized_style ) {
				$form_class .= ' es-form-style-' . $original_style;
			}
		}
		
		// Apply custom styles from the styles column
		if ( ! empty( $styles ) && is_array( $styles ) ) {
			if ( ! empty( $styles['form_bg_color'] ) ) {
				$form_style .= 'background-color: ' . esc_attr( $styles['form_bg_color'] ) . ';';
			}
			if ( ! empty( $styles['form_width'] ) ) {
				$form_style .= 'max-width: ' . esc_attr( $styles['form_width'] ) . 'px;';
			}
			if ( ! empty( $styles['form_height'] ) ) {
				$form_style .= 'height: ' . esc_attr( $styles['form_height'] ) . 'px;';
			}
			if ( ! empty( $styles['form_border_color'] ) ) {
				$form_style .= 'border-color: ' . esc_attr( $styles['form_border_color'] ) . ';';
			}
			if ( ! empty( $styles['form_border_width'] ) ) {
				$form_style .= 'border-width: ' . esc_attr( $styles['form_border_width'] ) . 'px;';
			}
			if ( ! empty( $styles['form_border_radius'] ) ) {
				$form_style .= 'border-radius: ' . esc_attr( $styles['form_border_radius'] ) . 'px;';
			}
			if ( ! empty( $styles['form_padding'] ) ) {
				$form_style .= 'padding: ' . esc_attr( $styles['form_padding'] ) . 'px;';
			}
			if ( ! empty( $styles['form_margin'] ) ) {
				$form_style .= 'margin: ' . esc_attr( $styles['form_margin'] ) . 'px;';
			}
			if ( ! empty( $styles['text_color'] ) ) {
				$form_style .= 'color: ' . esc_attr( $styles['text_color'] ) . ';';
			}
			if ( ! empty( $styles['font_family'] ) ) {
				$form_style .= 'font-family: ' . esc_attr( $styles['font_family'] ) . ';';
			}
			if ( ! empty( $styles['font_size'] ) ) {
				$form_style .= 'font-size: ' . esc_attr( $styles['font_size'] ) . 'px;';
			}
		}
		
		$unique_id = uniqid();
		$form_action_url = ES_Common::get_current_request_url();
		$enable_ajax_form_submission = get_option( 'ig_es_enable_ajax_form_submission', 'yes' );
		$extra_form_class = ( 'yes' == $enable_ajax_form_submission ) ? ' es_ajax_subscription_form' : '';
		
		echo '<div class="es_form_wrapper es-form-' . esc_attr( $form_id ) . ' ig-es-form-wrapper" id="es_form_' . esc_attr( $form_identifier ) . '">';
		
		// Form description
		if ( ! empty( $settings['desc'] ) ) {
			echo '<div class="es_caption es-form-description">' . wp_kses_post( $settings['desc'] ) . '</div>';
		}
		
		// Custom CSS from styles
		if ( ! empty( $styles['custom_css'] ) ) {
			echo '<style>' . esc_html( wp_strip_all_tags( $styles['custom_css'] ) ) . '</style>';
		}
		
		// Note: Form style CSS will be added after the form to override DND editor styles
		
		$form_tag = '<form action="' . esc_url( $form_action_url ) . '#es_form_' . esc_attr( $form_identifier ) . '" method="post" class="' . esc_attr( $form_class . $extra_form_class ) . '" id="es_subscription_form_' . esc_attr( $unique_id ) . '" data-source="ig-es" data-form-id="' . esc_attr( $form_id ) . '"';
		if ( $form_style ) {
			$form_tag .= ' style="' . esc_attr( $form_style ) . '"';
		}
		$form_tag .= '>';
		// phpcs:disable-next-line WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $form_tag;
		
		// Sort fields by order
		$enabled_fields = array_filter( $body, function( $field ) {
			return ! empty( $field['enabled'] );
		});
		
		usort( $enabled_fields, function( $a, $b ) {
			$order_a = ! empty( $a['order'] ) ? (int) $a['order'] : 999;
			$order_b = ! empty( $b['order'] ) ? (int) $b['order'] : 999;
			return $order_a - $order_b;
		});
		
		// Render each enabled field
		$form_fields_html = '';
		foreach ( $enabled_fields as $field ) {
			$form_fields_html .= self::render_new_form_field( $field, $settings, $submitted_data, $form_id );
		}
		
		// Apply after form fields filter 
		$filter_data = array( 'captcha' => ! empty( $settings['captcha'] ) ? $settings['captcha'] : 'no' );
		$form_fields_html = apply_filters( 'ig_es_after_form_fields', $form_fields_html, $filter_data );
		
		// phpcs:disable-next-line WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $form_fields_html;
		
		// Hidden fields for form processing
		// phpcs:disable-next-line WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_hidden_form_fields( $form_id, $form_identifier, $unique_id, $settings );
		
		// GDPR consent if enabled
		if ( ! empty( $settings['gdpr']['consent'] ) && 'yes' === $settings['gdpr']['consent'] ) {
			$gdpr_text = ! empty( $settings['gdpr']['consent_text'] ) ? $settings['gdpr']['consent_text'] : 
						__( 'I agree to receive emails and accept the terms and conditions.', 'email-subscribers' );
			echo '<div class="es-field-wrap es-gdpr-field">';
			echo '<label style="display: inline"><input type="checkbox" name="es_gdpr_consent" value="true" required="required" />&nbsp;' . wp_kses_post( $gdpr_text ) . '</label>';
			echo '</div>';
		}
		
		// Submit button
		$button_label = ! empty( $settings['button_label'] ) ? $settings['button_label'] : __( 'Subscribe', 'email-subscribers' );
		$button_style = '';
		
		// Apply button styles from styles column
		if ( ! empty( $styles ) && is_array( $styles ) ) {
			if ( ! empty( $styles['button_bg_color'] ) ) {
				$button_style .= 'background-color: ' . esc_attr( $styles['button_bg_color'] ) . ' !important; background-image: none !important; background: ' . esc_attr( $styles['button_bg_color'] ) . ' !important;';
			}
			if ( ! empty( $styles['button_text_color'] ) ) {
				$button_style .= 'color: ' . esc_attr( $styles['button_text_color'] ) . ' !important;';
			}
			if ( ! empty( $styles['button_border_color'] ) ) {
				$button_style .= 'border-color: ' . esc_attr( $styles['button_border_color'] ) . ' !important; border: 1px solid ' . esc_attr( $styles['button_border_color'] ) . ' !important;';
			}
			if ( ! empty( $styles['button_border_radius'] ) ) {
				$button_style .= 'border-radius: ' . esc_attr( $styles['button_border_radius'] ) . 'px;';
			}
			if ( ! empty( $styles['button_padding'] ) ) {
				$button_style .= 'padding: ' . esc_attr( $styles['button_padding'] ) . 'px;';
			}
		}
		
		echo '<div class="es-field-wrap">';
		$submit_button = '<input type="submit" name="submit" class="es_subscription_form_submit es_submit_button es_textbox_button ig-es-submit-btn" id="es_subscription_form_submit_' . esc_attr( $unique_id ) . '" value="' . esc_attr( $button_label ) . '"';
		if ( $button_style ) {
			$submit_button .= ' style="' . esc_attr( $button_style ) . '"';
		}
		$submit_button .= ' />';
		// phpcs:disable-next-line WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $submit_button;
		echo '</div>';
		
		// Spinner
		$spinner_image_path = ES_PLUGIN_URL . 'lite/public/images/spinner.gif';
		echo '<span class="es_spinner_image" id="spinner-image"><img src="' . esc_url( $spinner_image_path ) . '" alt="Loading"/></span>';
		
		echo '</form>';
		
		// Always show message container for AJAX forms, or show message if there's one
		$enable_ajax_form_submission = get_option( 'ig_es_enable_ajax_form_submission', 'yes' );
		if ( 'yes' == $enable_ajax_form_submission || ! empty( $message_text ) ) {
			$display_message = ! empty( $message_text ) ? esc_html( $message_text ) : '';
			$display_class = ! empty( $message_class ) ? esc_attr( $message_class ) : '';
			echo '<span class="es_subscription_message ' . esc_attr( $display_class ) . '" id="es_subscription_message_' . esc_attr( $unique_id ) . '" role="alert" aria-live="assertive">' . esc_html( $display_message ) . '</span>';
		}
		
		// Add form style CSS AFTER the form to override DND editor styles
		if ( ! empty( $settings['form_style'] ) && class_exists( 'ES_Form_Controller' ) ) {
			$form_style_css = ES_Form_Controller::get_form_style_css( $form_id, $settings['form_style'] );
			
			if ( ! empty( $form_style_css ) ) {
				echo '<style>' . esc_html( wp_strip_all_tags( $form_style_css ) ) . '</style>';
			} else {
				// Debug: Output a comment if no CSS was generated
				echo '<!-- ES Debug: No CSS generated for form style: ' . esc_html( $settings['form_style'] ) . ' (Form ID: ' . esc_html( $form_id ) . ') -->';
			}
		} else {
			// Debug: Output why CSS was not generated
			if ( empty( $settings['form_style'] ) ) {
				echo '<!-- ES Debug: No form_style setting found (Settings: ' . esc_html( print_r( $settings, true ) ) . ') -->';
			} else {
				echo '<!-- ES Debug: ES_Form_Controller class not found -->';
			}
		}
		
		echo '</div>';
	}
	
	/**
	 * Render individual form field for new form structure
	 * 
	 * @param array $field Field configuration
	 * @param array $settings Form settings
	 * @param array $submitted_data Previously submitted data
	 * @param int   $form_id Form ID
	 * 
	 * @return string Field HTML
	 * 
	 * @since 5.8.0
	 */
	public static function render_new_form_field( $field, $settings = array(), $submitted_data = array(), $form_id = 0 ) {
		if ( empty( $field['type'] ) || empty( $field['enabled'] ) ) {
			return '';
		}
		
		$field_type = $field['type'];
		$field_id = ! empty( $field['id'] ) ? $field['id'] : '';
		
		if ( 'captcha' === $field_type || 'captcha' === $field_id ) {
			return '';
		}
		
		$field_label = ! empty( $field['label'] ) ? $field['label'] : '';
		$field_placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
		
		// Check required status: Email is always required, others check field settings and global settings
		$field_required = false;
		if ( 'email' === $field_id ) {
			$field_required = true; // Email is always required
		} else {
			// For other fields, check if field has explicit required setting
			if ( isset( $field['required'] ) ) {
				$field_required = (bool) $field['required'];
			} else {
				// Fallback to false if not specified
				$field_required = false;
			}
		}
		
		$field_value = ! empty( $field['value'] ) ? $field['value'] : '';
		
		// Check if this is a custom field - use explicit property or ID pattern as fallback
		// Support custom_ prefix for custom fields
		$is_custom_field = ! empty( $field['is_custom_field'] ) || 
						   ( ! empty( $field_id ) && strpos( $field_id, 'custom_' ) === 0 );
		
		// Get submitted value if available
		$submitted_value = '';
		if ( 'email' === $field_id ) {
			$submitted_value = ! empty( $submitted_data['email'] ) ? $submitted_data['email'] : $field_value;
		} elseif ( $is_custom_field ) {
			// For custom fields, check es_custom_field array
			$custom_field_key = 'es_custom_field';
			if ( ! empty( $submitted_data[ $custom_field_key ] ) && ! empty( $submitted_data[ $custom_field_key ][ $field_id ] ) ) {
				$submitted_value = $submitted_data[ $custom_field_key ][ $field_id ];
			} else {
				$submitted_value = $field_value;
			}
		} else {
			// For regular form fields, use esfpx_ prefix
			$field_name = 'esfpx_' . $field_id;
			if ( ! empty( $submitted_data[ $field_name ] ) ) {
				$submitted_value = $submitted_data[ $field_name ];
			} else {
				$submitted_value = $field_value;
			}
		}
		
		$html = '';
		
		// PRIORITY: Check for special field IDs first (like frontend does)
		if ( 'list' === $field_id ) {
			// Handle list selection as checkboxes regardless of type
			// For custom fields, use the field options instead of form lists
			if ( ! empty( $field['options'] ) ) {
				// Convert custom field options to proper list format with IDs and hashes
				$lists_array = array();
				$lists_id_hash_map = array();
				
				foreach ( $field['options'] as $index => $option ) {
					$option_text = is_array( $option ) && isset( $option['text'] ) ? $option['text'] : $option;
					$option_value = is_array( $option ) && isset( $option['value'] ) ? $option['value'] : $option_text;
					
					// The option value/text represents a list name - find or create the list
					$list_name = $option_value; // Use value if available, otherwise text
					
					// Try to find existing list by name
					$list_data = ES()->lists_db->get_list_by_name( $list_name );
					if ( empty( $list_data ) ) {
						// List doesn't exist, create it
						$list_id = ES()->lists_db->add_list( $list_name );
						$lists = ES()->lists_db->get_lists_by_id( $list_id );
						if ( ! empty( $lists[0] ) ) {
							$list_data = $lists[0];
						}
					}
					
					if ( ! empty( $list_data ) ) {
						$lists_array[ $list_data['id'] ] = $list_data['name'];
						$lists_id_hash_map[ $list_data['id'] ] = $list_data['hash'];
					}
				}
				
				// Use the existing prepare_lists_checkboxes function with proper list data
				$selected_lists = ! empty( $field['value'] ) && is_array( $field['value'] ) ? $field['value'] : array();
				$html = self::prepare_lists_checkboxes( $lists_array, array_keys($lists_array), 1, $selected_lists, $field_label, 0, 'esfpx_lists[]', $lists_id_hash_map );
			} else {
				// Fallback to form lists if no custom options
				$list_ids = ! empty( $settings['lists'] ) ? $settings['lists'] : array();
				$selected_lists = ! empty( $field['value'] ) && is_array( $field['value'] ) ? $field['value'] : array();
				$html = self::get_list_field_html( true, $field_label, $list_ids, '', $selected_lists );
			}
		} else {
			switch ( $field_type ) {
				case 'email':
					$html = self::get_email_field_html( $field_label, $field_placeholder, $submitted_value );
					break;
					
				case 'text':
					$field_args = array(
						'field_id' => $field_id,
						'label' => $field_label,
						'placeholder' => $field_placeholder,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => $is_custom_field
					);
					$html = self::get_text_field_html( $field_args );
					break;
				
				case 'number':
					$field_args = array(
						'field_id' => $field_id,
						'label' => $field_label,
						'placeholder' => $field_placeholder,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => $is_custom_field
					);
					$html = self::get_number_field_html( $field_args );
					break;
				
				case 'textarea':
					$field_args = array(
						'field_id' => $field_id,
						'label' => $field_label,
						'placeholder' => $field_placeholder,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => $is_custom_field
					);
					$html = self::get_textarea_field_html( $field_args );
					break;
				
				case 'select':
				case 'dropdown': // Handle dropdown as alias for select
					$options = ! empty( $field['options'] ) ? $field['options'] : array();
					$field_args = array(
						'field_id' => $field_id,
						'label' => $field_label,
						'options' => $options,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => $is_custom_field
					);
					$html = self::get_select_field_html( $field_args );
					break;
				
				case 'checkbox':
					if ( 'gdpr' === $field_id ) {
						// Handle GDPR checkbox separately in main function
						break;
					}
					$field_args = array(
						'field_id' => $field_id,
						'label' => $field_label,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => $is_custom_field
					);
					$html = self::get_checkbox_field_html( $field_args );
					break;
				
				case 'radio':
					$options = ! empty( $field['options'] ) ? $field['options'] : array();
					$field_args = array(
						'field_id' => $field_id,
						'label' => $field_label,
						'options' => $options,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => $is_custom_field
					);
					$html = self::get_radio_field_html( $field_args );
					break;
				
				case 'date':
					$field_args = array(
						'field_id' => $field_id,
						'label' => $field_label,
						'placeholder' => $field_placeholder,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => $is_custom_field
					);
					$html = self::get_date_field_html( $field_args );
					break;
				
				case 'firstName':
				case 'lastName':
					// firstName and lastName are not custom fields, so pass false
					$field_args = array(
						'field_id' => 'name',
						'label' => $field_label,
						'placeholder' => $field_placeholder,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => false
					);
					$html = self::get_text_field_html( $field_args );
					break;
				
				case 'list':
					$list_ids = ! empty( $settings['lists'] ) ? $settings['lists'] : array();
					$selected_lists = ! empty( $field['value'] ) && is_array( $field['value'] ) ? $field['value'] : array();
					$html = self::get_list_field_html( true, $field_label, $list_ids, '', $selected_lists );
					break;
				
				default:
					$field_args = array(
						'field_id' => $field_id,
						'label' => $field_label,
						'placeholder' => $field_placeholder,
						'required' => $field_required,
						'value' => $submitted_value,
						'is_custom_field' => $is_custom_field
					);
					$html = self::get_text_field_html( $field_args );
					break;
			}
		}
		
		return $html;
	}
	
	/**
	 * Get text field HTML for new forms
	 *
	 * @param array $args {
	 *     Field arguments.
	 *     @type string $field_id Field ID
	 *     @type string $label Field label
	 *     @type string $placeholder Field placeholder
	 *     @type bool   $required Whether field is required
	 *     @type string $value Field value
	 *     @type bool   $is_custom_field Whether this is a custom field
	 * }
	 *
	 * @return string
	 */
	public static function get_text_field_html( $args ) {
		$defaults = array(
			'field_id' => '',
			'label' => '',
			'placeholder' => '',
			'required' => false,
			'value' => '',
			'is_custom_field' => false
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$required_attr = $args['required'] ? 'required="required"' : '';
		$required_mark = $args['required'] ? '*' : '';
		
		// Generate correct field name based on field type
		if ( $args['is_custom_field'] ) {
			$field_name = 'es_custom_field[' . $args['field_id'] . ']';
		} else {
			$field_name = 'esfpx_' . $args['field_id'];
		}
		
		$html = '<div class="es-field-wrap ig-es-form-field">';
		$html .= '<label class="es-field-label">' . esc_html( $args['label'] ) . $required_mark . '<br/>';
		$html .= '<input type="text" name="' . esc_attr( $field_name ) . '" class="ig_es_form_field_text ig-es-form-input" placeholder="' . esc_attr( $args['placeholder'] ) . '" value="' . esc_attr( $args['value'] ) . '" ' . $required_attr . ' />';
		$html .= '</label>';
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Get textarea field HTML for new forms
	 *
	 * @param array $args {
	 *     Field arguments.
	 *     @type string $field_id Field ID
	 *     @type string $label Field label
	 *     @type string $placeholder Field placeholder
	 *     @type bool   $required Whether field is required
	 *     @type string $value Field value
	 *     @type bool   $is_custom_field Whether this is a custom field
	 * }
	 *
	 * @return string
	 */
	public static function get_textarea_field_html( $args ) {
		$defaults = array(
			'field_id' => '',
			'label' => '',
			'placeholder' => '',
			'required' => false,
			'value' => '',
			'is_custom_field' => false
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$required_attr = $args['required'] ? 'required="required"' : '';
		$required_mark = $args['required'] ? '*' : '';
		
		// Generate correct field name based on field type
		if ( $args['is_custom_field'] ) {
			$field_name = 'es_custom_field[' . $args['field_id'] . ']';
		} else {
			$field_name = 'esfpx_' . $args['field_id'];
		}
		
		$html = '<div class="es-field-wrap ig-es-form-field">';
		$html .= '<label class="es-field-label">' . esc_html( $args['label'] ) . $required_mark . '<br/>';
		$html .= '<textarea name="' . esc_attr( $field_name ) . '" class="ig_es_form_field_textarea ig-es-form-input" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . $required_attr . '>' . esc_textarea( $args['value'] ) . '</textarea>';
		$html .= '</label>';
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Get number field HTML for new forms
	 *
	 * @param array $args {
	 *     Field arguments.
	 *     @type string $field_id Field ID
	 *     @type string $label Field label
	 *     @type string $placeholder Field placeholder
	 *     @type bool   $required Whether field is required
	 *     @type string $value Field value
	 *     @type bool   $is_custom_field Whether this is a custom field
	 * }
	 *
	 * @return string
	 */
	public static function get_number_field_html( $args ) {
		$defaults = array(
			'field_id' => '',
			'label' => '',
			'placeholder' => '',
			'required' => false,
			'value' => '',
			'is_custom_field' => false
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$required_attr = $args['required'] ? 'required="required"' : '';
		$required_mark = $args['required'] ? '*' : '';
		
		// Generate correct field name based on field type
		if ( $args['is_custom_field'] ) {
			$field_name = 'es_custom_field[' . $args['field_id'] . ']';
		} else {
			$field_name = 'esfpx_' . $args['field_id'];
		}
		
		$html = '<div class="es-field-wrap ig-es-form-field">';
		$html .= '<label class="es-field-label">' . esc_html( $args['label'] ) . $required_mark . '<br/>';
		$html .= '<input type="number" name="' . esc_attr( $field_name ) . '" class="ig_es_form_field_number ig-es-form-input" placeholder="' . esc_attr( $args['placeholder'] ) . '" value="' . esc_attr( $args['value'] ) . '" ' . $required_attr . ' />';
		$html .= '</label>';
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Get date field HTML for new forms
	 *
	 * @param array $args {
	 *     Field arguments.
	 *     @type string $field_id Field ID
	 *     @type string $label Field label
	 *     @type string $placeholder Field placeholder (not used for date fields)
	 *     @type bool   $required Whether field is required
	 *     @type string $value Field value
	 *     @type bool   $is_custom_field Whether this is a custom field
	 * }
	 *
	 * @return string
	 */
	public static function get_date_field_html( $args ) {
		$defaults = array(
			'field_id' => '',
			'label' => '',
			'placeholder' => '',
			'required' => false,
			'value' => '',
			'is_custom_field' => false
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$required_attr = $args['required'] ? 'required="required"' : '';
		$required_mark = $args['required'] ? '*' : '';
		
		// Generate correct field name based on field type
		if ( $args['is_custom_field'] ) {
			$field_name = 'es_custom_field[' . $args['field_id'] . ']';
		} else {
			$field_name = 'esfpx_' . $args['field_id'];
		}
		
		$html = '<div class="es-field-wrap ig-es-form-field">';
		$html .= '<label class="es-field-label">' . esc_html( $args['label'] ) . $required_mark . '<br/>';
		$html .= '<input type="date" name="' . esc_attr( $field_name ) . '" class="ig_es_form_field_date ig-es-form-input" value="' . esc_attr( $args['value'] ) . '" ' . $required_attr . ' />';
		$html .= '</label>';
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Get select field HTML for new forms
	 *
	 * @param array $args {
	 *     Field arguments.
	 *     @type string $field_id Field ID
	 *     @type string $label Field label
	 *     @type array  $options Select options
	 *     @type bool   $required Whether field is required
	 *     @type string $value Field value
	 *     @type bool   $is_custom_field Whether this is a custom field
	 * }
	 *
	 * @return string
	 */
	public static function get_select_field_html( $args ) {
		$defaults = array(
			'field_id' => '',
			'label' => '',
			'options' => array(),
			'required' => false,
			'value' => '',
			'is_custom_field' => false
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$required_attr = $args['required'] ? 'required="required"' : '';
		$required_mark = $args['required'] ? '*' : '';
		
		// Generate correct field name based on field type
		if ( $args['is_custom_field'] ) {
			$field_name = 'es_custom_field[' . $args['field_id'] . ']';
		} else {
			$field_name = 'esfpx_' . $args['field_id'];
		}
		
		$html = '<div class="es-field-wrap">';
		$html .= '<label>' . esc_html( $args['label'] ) . $required_mark . '<br/>';
		$html .= '<select name="' . esc_attr( $field_name ) . '" class="ig_es_form_field_select" ' . $required_attr . '>';
		$html .= '<option value="">Select an option</option>';
		
		if ( is_array( $args['options'] ) ) {
			foreach ( $args['options'] as $option ) {
				if ( is_array( $option ) && isset( $option['text'] ) ) {
					$option_value = isset( $option['id'] ) ? $option['id'] : $option['text'];
					$option_text = $option['text'];
					$selected = ( $args['value'] == $option_value || $args['value'] == $option_text ) ? 'selected="selected"' : '';
					$html .= '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . esc_html( $option_text ) . '</option>';
				} elseif ( is_string( $option ) ) {
					$selected = ( $args['value'] == $option ) ? 'selected="selected"' : '';
					$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $option ) . '</option>';
				}
			}
		}
		
		$html .= '</select>';
		$html .= '</label>';
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Get checkbox field HTML for new forms
	 *
	 * @param array $args {
	 *     Field arguments.
	 *     @type string $field_id Field ID
	 *     @type string $label Field label
	 *     @type bool   $required Whether field is required
	 *     @type mixed  $value Field value
	 *     @type bool   $is_custom_field Whether this is a custom field
	 * }
	 *
	 * @return string
	 */
	public static function get_checkbox_field_html( $args ) {
		$defaults = array(
			'field_id' => '',
			'label' => '',
			'required' => false,
			'value' => '',
			'is_custom_field' => false
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$required_attr = $args['required'] ? 'required="required"' : '';
		
		// Generate correct field name based on field type
		if ( $args['is_custom_field'] ) {
			$field_name = 'es_custom_field[' . $args['field_id'] . ']';
		} else {
			$field_name = 'esfpx_' . $args['field_id'];
		}
		
		$checked = ( true === $args['value'] || 'true' === $args['value'] || '1' === $args['value'] ) ? 'checked="checked"' : '';
		
		$html = '<div class="es-field-wrap">';
		$html .= '<label style="display: inline">';
		$html .= '<input type="checkbox" name="' . esc_attr( $field_name ) . '" value="true" ' . $checked . ' ' . $required_attr . ' />&nbsp;';
		$html .= esc_html( $args['label'] );
		$html .= '</label>';
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Get radio field HTML for new forms
	 *
	 * @param array $args {
	 *     Field arguments.
	 *     @type string $field_id Field ID
	 *     @type string $label Field label
	 *     @type array  $options Radio options
	 *     @type bool   $required Whether field is required
	 *     @type string $value Field value
	 *     @type bool   $is_custom_field Whether this is a custom field
	 * }
	 *
	 * @return string
	 */
	public static function get_radio_field_html( $args ) {
		$defaults = array(
			'field_id' => '',
			'label' => '',
			'options' => array(),
			'required' => false,
			'value' => '',
			'is_custom_field' => false
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$required_attr = $args['required'] ? 'required="required"' : '';
		$required_mark = $args['required'] ? '*' : '';
		
		// Generate correct field name based on field type
		if ( $args['is_custom_field'] ) {
			$field_name = 'es_custom_field[' . $args['field_id'] . ']';
		} else {
			$field_name = 'esfpx_' . $args['field_id'];
		}
		
		$html = '<div class="es-field-wrap">';
		$html .= '<fieldset>';
		$html .= '<legend>' . esc_html( $args['label'] ) . $required_mark . '</legend>';
		
		if ( is_array( $args['options'] ) ) {
			foreach ( $args['options'] as $option ) {
				if ( is_array( $option ) && isset( $option['text'] ) ) {
					$option_value = isset( $option['id'] ) ? $option['id'] : $option['text'];
					$option_text = $option['text'];
					$checked = ( $args['value'] == $option_value || $args['value'] == $option_text ) ? 'checked="checked"' : '';
					$html .= '<label style="display: block; margin: 5px 0;">';
					$html .= '<input type="radio" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $option_value ) . '" ' . $checked . ' ' . $required_attr . ' />&nbsp;';
					$html .= esc_html( $option_text );
					$html .= '</label>';
				} elseif ( is_string( $option ) ) {
					$checked = ( $args['value'] == $option ) ? 'checked="checked"' : '';
					$html .= '<label style="display: block; margin: 5px 0;">';
					$html .= '<input type="radio" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $option ) . '" ' . $checked . ' ' . $required_attr . ' />&nbsp;';
					$html .= esc_html( $option );
					$html .= '</label>';
				}
			}
		}
		
		$html .= '</fieldset>';
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Get hidden form fields for processing
	 *
	 * @param int    $form_id Form ID
	 * @param string $form_identifier Form identifier
	 * @param string $unique_id Unique ID
	 * @param array  $settings Form settings
	 *
	 * @return string
	 */
	public static function get_hidden_form_fields( $form_id, $form_identifier, $unique_id, $settings ) {
		$current_page = get_the_ID();
		$current_page_url = get_the_permalink( $current_page );
		$nonce = wp_create_nonce( 'es-subscribe' );
		$hp_style = 'position:absolute;top:-99999px;' . ( is_rtl() ? 'right' : 'left' ) . ':-99999px;z-index:-99;';
		
		$html = '<input type="hidden" name="es" value="subscribe" />';
		$html .= '<input type="hidden" name="esfpx_form_id" value="' . esc_attr( $form_id ) . '" />';
		$html .= '<input type="hidden" name="esfpx_es_form_identifier" value="' . esc_attr( $form_identifier ) . '" />';
		$html .= '<input type="hidden" name="esfpx_es_email_page" value="' . esc_attr( $current_page ) . '"/>';
		$html .= '<input type="hidden" name="esfpx_es_email_page_url" value="' . esc_attr( $current_page_url ) . '"/>';
		$html .= '<input type="hidden" name="esfpx_status" value="Unconfirmed"/>';
		$html .= '<input type="hidden" name="esfpx_es-subscribe" id="es-subscribe-' . esc_attr( $unique_id ) . '" value="' . esc_attr( $nonce ) . '"/>';
		
		// Honeypot field
		$html .= '<label style="' . esc_attr( $hp_style ) . '" aria-hidden="true">';
		$html .= '<span hidden>' . __( 'Please leave this field empty.', 'email-subscribers' ) . '</span>';
		$html .= '<input type="email" name="esfpx_es_hp_email" class="es_required_field" tabindex="-1" autocomplete="-1" value=""/>';
		$html .= '</label>';
		
		// Hidden list IDs
		if ( ! empty( $settings['lists'] ) && is_array( $settings['lists'] ) ) {
			$list_ids = $settings['lists'];
			$lists = ES()->lists_db->get_lists_by_id( $list_ids );
			if ( ! empty( $lists ) ) {
				foreach ( $lists as $list ) {
					if ( ! empty( $list['hash'] ) ) {
						$html .= '<input type="hidden" name="esfpx_lists[]" value="' . esc_attr( $list['hash'] ) . '" />';
					}
				}
			}
		}
		
		return $html;
	}
	
	/**
	 * Get submitted form data
	 *
	 * @return array
	 */
	public static function get_submitted_data() {
		$data = array();
		
		if ( self::is_posted() ) {
			$data['name'] = ig_es_get_post_data( 'esfpx_name' );
			$data['email'] = ig_es_get_post_data( 'esfpx_email' );
			
			// Handle custom fields
			$custom_field_data = ig_es_get_post_data( 'es_custom_field' );
			if ( ! empty( $custom_field_data ) && is_array( $custom_field_data ) ) {
				$data['es_custom_field'] = $custom_field_data;
			}
			
			// Handle logged in user prefill
		} elseif ( is_user_logged_in() ) {
			$prefill_form = apply_filters( 'ig_es_prefill_subscription_form', 'yes' );
			if ( 'yes' === $prefill_form ) {
				$current_user = wp_get_current_user();
				$data['email'] = $current_user->user_email;
				
				if ( ! empty( $current_user->user_firstname ) && ! empty( $current_user->user_lastname ) ) {
					$data['name'] = $current_user->user_firstname . ' ' . $current_user->user_lastname;
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * Get rounded border form CSS
	 */
	public static function get_rounded_border_form_css( $form_id ) {
		$form_selector = 'form.es_subscription_form[data-form-id="' . $form_id . '"].wysiwyg-form';
		return '
		/* Rounded border style with gradient background - ONLY for WYSIWYG forms */
		' . $form_selector . ':not([style*="background-color"]) {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
		}
		' . $form_selector . ' {
			border: none !important;
			border-radius: 20px !important;
			padding: 30px !important;
			box-shadow: 0 8px 32px rgba(0,0,0,0.1) !important;
			color: white !important;
		}
		' . $form_selector . ' .es-field-wrap {
			margin-bottom: 20px !important;
		}
		' . $form_selector . ' label {
			color: white !important;
			font-weight: 600 !important;
			margin-bottom: 8px !important;
			display: block !important;
		}
		' . $form_selector . ' input[type="text"],
		' . $form_selector . ' input[type="email"],
		' . $form_selector . ' textarea,
		' . $form_selector . ' select {
			background-color: rgba(255,255,255,0.9) !important;
			border: none !important;
			border-radius: 15px !important;
			padding: 15px !important;
			width: 100% !important;
			box-sizing: border-box !important;
			font-size: 16px !important;
		}
		' . $form_selector . ' input:focus {
			background-color: rgba(255,255,255,1) !important;
			outline: none !important;
			box-shadow: 0 0 0 3px rgba(255,255,255,0.3) !important;
		}
		' . $form_selector . ' input[type="submit"] {
			background: linear-gradient(45deg, #ff6b6b, #feca57) !important;
			border: none !important;
			border-radius: 25px !important;
			color: white !important;
			padding: 15px 30px !important;
			font-weight: bold !important;
			font-size: 16px !important;
			cursor: pointer !important;
			transition: transform 0.2s !important;
		}
		' . $form_selector . ' input[type="submit"]:hover {
			transform: translateY(-2px) !important;
		}';
	}
	
	/**
	 * Get minimalistic form CSS
	 */
	public static function get_minimalistic_form_css( $form_id ) {
		$form_selector = 'form.es_subscription_form[data-form-id="' . $form_id . '"].wysiwyg-form';
		return '
		/* Minimalistic style - ONLY for WYSIWYG forms */
		' . $form_selector . ':not([style*="background-color"]) {
			background: transparent !important;
		}
		' . $form_selector . ' {
			border: none !important;
			padding: 15px !important;
		}
		' . $form_selector . ' .es-field-wrap {
			margin-bottom: 12px !important;
		}
		' . $form_selector . ' input[type="text"],
		' . $form_selector . ' input[type="email"],
		' . $form_selector . ' textarea,
		' . $form_selector . ' select {
			background-color: transparent !important;
			border: none !important;
			border-bottom: 2px solid #ddd !important;
			border-radius: 0 !important;
			padding: 8px 0 !important;
			width: 100% !important;
			box-sizing: border-box !important;
			font-size: 14px !important;
			transition: border-color 0.3s !important;
		}
		' . $form_selector . ' input:focus {
			border-bottom-color: #0073aa !important;
			outline: none !important;
		}
		' . $form_selector . ' input[type="submit"] {
			background-color: #0073aa !important;
			border: none !important;
			border-radius: 0 !important;
			color: white !important;
			padding: 12px 30px !important;
			cursor: pointer !important;
			font-weight: 500 !important;
			transition: all 0.3s !important;
			margin-top: 10px !important;
		}
		' . $form_selector . ' input[type="submit"]:hover {
			background-color: #005a87 !important;
		}';
	}
	
	/**
	 * Get straight border form CSS
	 */
	public static function get_straight_border_form_css( $form_id ) {
		$form_selector = 'form.es_subscription_form[data-form-id="' . $form_id . '"].wysiwyg-form';
		return '
		/* Straight border style - ONLY for WYSIWYG forms */
		' . $form_selector . ':not([style*="background-color"]) {
			background: #fff !important;
		}
		' . $form_selector . ' {
			border: 1px solid #ccc !important;
			border-radius: 0 !important;
			padding: 20px !important;
		}
		' . $form_selector . ' .es-field-wrap {
			margin-bottom: 15px !important;
		}
		' . $form_selector . ' input[type="text"],
		' . $form_selector . ' input[type="email"],
		' . $form_selector . ' textarea,
		' . $form_selector . ' select {
			background-color: #fff !important;
			border: 1px solid #ccc !important;
			border-radius: 0 !important;
			padding: 10px !important;
			width: 100% !important;
			box-sizing: border-box !important;
			font-size: 14px !important;
		}
		' . $form_selector . ' input:focus {
			border-color: #0073aa !important;
			outline: none !important;
		}
		' . $form_selector . ' input[type="submit"] {
			background-color: #0073aa !important;
			border: none !important;
			border-radius: 0 !important;
			color: white !important;
			padding: 12px 20px !important;
			cursor: pointer !important;
			transition: background-color 0.3s !important;
		}
		' . $form_selector . ' input[type="submit"]:hover {
			background-color: #005a87 !important;
		}';
	}
	
	/**
	 * Get compact form CSS
	 */
	public static function get_compact_form_css( $form_id ) {
		$form_selector = 'form.es_subscription_form[data-form-id="' . $form_id . '"].wysiwyg-form';
		return '
		/* Compact style - ONLY for WYSIWYG forms */
		' . $form_selector . ':not([style*="background-color"]) {
			background: #f9f9f9 !important;
		}
		' . $form_selector . ' {
			border: 1px solid #ddd !important;
			border-radius: 5px !important;
			padding: 15px !important;
		}
		' . $form_selector . ' .es-field-wrap {
			margin-bottom: 10px !important;
		}
		' . $form_selector . ' input[type="text"],
		' . $form_selector . ' input[type="email"],
		' . $form_selector . ' textarea,
		' . $form_selector . ' select {
			background-color: #fff !important;
			border: 1px solid #ccc !important;
			border-radius: 3px !important;
			padding: 8px !important;
			width: 100% !important;
			box-sizing: border-box !important;
			font-size: 13px !important;
		}
		' . $form_selector . ' input[type="submit"] {
			background-color: #0073aa !important;
			border: none !important;
			border-radius: 3px !important;
			color: white !important;
			padding: 10px 18px !important;
			cursor: pointer !important;
			font-size: 13px !important;
		}';
	}
	
	/**
	 * Get grey background form CSS
	 */
	public static function get_grey_background_form_css( $form_id ) {
		$form_selector = 'form.es_subscription_form[data-form-id="' . $form_id . '"].wysiwyg-form';
		return '
		/* Grey background style - ONLY for WYSIWYG forms */
		' . $form_selector . ':not([style*="background-color"]) {
			background: #f5f5f5 !important;
		}
		' . $form_selector . ' {
			border: 1px solid #ddd !important;
			border-radius: 8px !important;
			padding: 20px !important;
		}
		' . $form_selector . ' .es-field-wrap {
			margin-bottom: 15px !important;
		}
		' . $form_selector . ' input[type="text"],
		' . $form_selector . ' input[type="email"],
		' . $form_selector . ' textarea,
		' . $form_selector . ' select {
			background-color: #fff !important;
			border: 1px solid #ccc !important;
			border-radius: 4px !important;
			padding: 10px !important;
			width: 100% !important;
			box-sizing: border-box !important;
		}
		' . $form_selector . ' input[type="submit"] {
			background-color: #0073aa !important;
			border: none !important;
			border-radius: 4px !important;
			color: white !important;
			padding: 12px 20px !important;
			cursor: pointer !important;
			transition: background-color 0.3s !important;
		}
		' . $form_selector . ' input[type="submit"]:hover {
			background-color: #005a87 !important;
		}';
	}
	
	/**
	 * Get default form CSS
	 */
	public static function get_default_form_css( $form_id ) {
		$form_selector = 'form.es_subscription_form[data-form-id="' . $form_id . '"].wysiwyg-form';
		return '
		/* Reset any DND editor styles - ONLY for WYSIWYG forms */
		' . $form_selector . ':not([style*="background-color"]) {
			background: #fff !important;
		}
		' . $form_selector . ' {
			border: 1px solid #ddd !important;
			border-radius: 5px !important;
			padding: 20px !important;
		}
		' . $form_selector . ' .es-field-wrap {
			margin-bottom: 15px !important;
		}
		' . $form_selector . ' input[type="text"],
		' . $form_selector . ' input[type="email"],
		' . $form_selector . ' textarea,
		' . $form_selector . ' select {
			background-color: #fff !important;
			border: 1px solid #ccc !important;
			border-radius: 4px !important;
			padding: 10px !important;
			width: 100% !important;
			box-sizing: border-box !important;
		}
		' . $form_selector . ' input[type="submit"] {
			background-color: #0073aa !important;
			border: none !important;
			border-radius: 4px !important;
			color: white !important;
			padding: 12px 20px !important;
			cursor: pointer !important;
		}';
	}
	
	/**
	 * Render WYSIWYG form as popup
	 * 
	 * @param array $data Form data
	 * 
	 * @since 5.8.0
	 */
	public static function render_wysiwyg_popup_form( $data ) {
		$form_id = ! empty( $data['form_id'] ) ? $data['form_id'] : 0;
		$settings = ! empty( $data['settings'] ) ? $data['settings'] : array();
		
		// Priority for popup headline: 1. Form name, 2. popup_headline setting, 3. default text
		$form_name = ! empty( $data['name'] ) ? $data['name'] : '';
		$popup_headline_setting = ! empty( $settings['popup_headline'] ) ? $settings['popup_headline'] : '';
		
		if ( ! empty( $form_name ) ) {
			$es_popup_headline = $form_name;
		} elseif ( ! empty( $popup_headline_setting ) ) {
			$es_popup_headline = $popup_headline_setting;
		} else {
			$es_popup_headline = __( 'Subscribe to our newsletter!', 'email-subscribers' );
		}

		// Create popup modal that shows on page load
		$popup_id = 'es-popup-modal-' . $form_id;
		
		echo '<div id="' . esc_attr( $popup_id ) . '" class="es-popup-modal" style="position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 2147483647 !important; display: none !important; background: rgba(0, 0, 0, 0.5) !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important;">
			<div class="es-popup-overlay" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 20px !important; box-sizing: border-box !important;">
				<div class="es-popup-content" style="background: white !important; border-radius: 8px !important; padding: 30px !important; max-width: 500px !important; width: 100% !important; max-height: 90vh !important; overflow-y: auto !important; position: relative !important; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important; box-sizing: border-box !important; margin: 0 auto !important;">
					<button type="button" class="es-popup-close" style="position: absolute !important; top: 15px !important; right: 20px !important; background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #999 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important; z-index: 10 !important; line-height: 1 !important;">&times;</button>';
					
		// Add popup headline if provided
		if ( ! empty( $es_popup_headline ) ) {
			echo '<h3 style="margin: 0 0 20px 0; font-size: 24px; color: #333; text-align: center;">' . esc_html( $es_popup_headline ) . '</h3>';
		}
		
		// Temporarily disable popup setting to render inline content
		$temp_settings = $settings;
		$temp_settings['show_in_popup'] = 'no';
		$temp_data = $data;
		$temp_data['settings'] = $temp_settings;
		
		// Render the form inline within the popup
		self::render_wysiwyg_form( $temp_data );
		
		echo '		</div>
			</div>
		</div>';
		
		// Initialize popup with JavaScript
		echo '<script type="text/javascript">
		document.addEventListener("DOMContentLoaded", function() {
			var modal = document.getElementById("' . esc_js( $popup_id ) . '");
			var closeBtn = modal.querySelector(".es-popup-close");
			var overlay = modal.querySelector(".es-popup-overlay");
			
			// Ensure popup is attached to body to avoid parent container issues
			if (modal && modal.parentNode !== document.body) {
				document.body.appendChild(modal);
			}
			
			// Force proper positioning with JavaScript
			function ensureProperPositioning() {
				if (modal) {
					modal.style.cssText = "position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 2147483647 !important; display: none !important; background: rgba(0, 0, 0, 0.5) !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important;";
					
					if (overlay) {
						overlay.style.cssText = "position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 20px !important; box-sizing: border-box !important;";
					}
				}
			}
			
			// Apply positioning
			ensureProperPositioning();
			
			// Show popup after a short delay
			setTimeout(function() {
				ensureProperPositioning(); // Ensure positioning before showing
				modal.style.display = "block";
				document.body.style.overflow = "hidden";
			}, 500);
			
			// Close popup function
			function closePopup() {
				modal.style.display = "none";
				document.body.style.overflow = "";
			}
			
			// Close button click
			if (closeBtn) {
				closeBtn.addEventListener("click", closePopup);
				closeBtn.addEventListener("mouseover", function() {
					this.style.color = "#333";
				});
				closeBtn.addEventListener("mouseout", function() {
					this.style.color = "#999";
				});
			}
			
			// Overlay click to close
			if (overlay) {
				overlay.addEventListener("click", function(e) {
					if (e.target === overlay) {
						closePopup();
					}
				});
			}
			
			// Escape key to close
			document.addEventListener("keydown", function(e) {
				if (e.key === "Escape" && modal.style.display === "block") {
					closePopup();
				}
			});
		});
		</script>';
	}
}


