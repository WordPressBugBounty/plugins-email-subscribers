<?php

if ( ! class_exists( 'ES_Forms_Controller' ) ) {

	/**
	 * Class to handle single form operation
	 * 
	 * @class ES_Forms_Controller
	 */
	class ES_Forms_Controller {

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

		public static function duplicate_form( $args ) {

			if ( empty( $args['form_id'] ) ) {
				return false;
			}
		
		$duplicated_form_id = ES()->forms_db->duplicate_form( $args['form_id'] );
			if ( empty( $duplicated_form_id ) ) {
				return false;
			}

		return $duplicated_form_id;
		}
	 /**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed  
	 */ 
		public static function get_forms( $args) {

			global $wpdb, $wpbd;

			$order_by     = isset( $args['order_by'] ) ? esc_sql( $args['order_by'] ) : 'created_at';
			$order        = isset( $args['order'] ) ? strtoupper( $args['order'] ) : 'DESC';
			$search       = isset( $args['search'] ) ? $args['search'] : '';
			$per_page     = isset( $args['per_page'] ) ? (int) $args['per_page'] : 20;
			$page_number  = isset( $args['page_number'] ) ? (int) $args['page_number'] : 1;
			$do_count_only = ! empty( $args['do_count_only'] );

			$forms_table = IG_FORMS_TABLE;
			if ( $do_count_only ) {
				$sql = "SELECT count(*) as total FROM {$forms_table}";
			} else {
				$sql = "SELECT * FROM {$forms_table}";
			}

			$args  = array();
			$query = array();

			$add_where_clause = false;

			if ( ! empty( $search ) ) {
				$query[] = ' name LIKE %s ';
				$args[]  = '%' . $wpdb->esc_like( $search ) . '%';

				$add_where_clause = true;
			}

			if ( $add_where_clause ) {
				$sql .= ' WHERE ';

				if ( count( $query ) > 0 ) {
					$sql .= implode( ' AND ', $query );
					if ( count( $args ) > 0 ) {
						$sql = $wpbd->prepare( $sql, $args );
					}
				}
			}

			if ( ! $do_count_only ) {

				$order                 = ! empty( $order ) ? strtolower( $order ) : 'desc';
				$expected_order_values = array( 'asc', 'desc' );
				if ( ! in_array( $order, $expected_order_values ) ) {
					$order = 'desc';
				}

				$default_order_by = esc_sql( 'created_at' );

				$expected_order_by_values = array( 'name', 'created_at' );

				if ( ! in_array( $order_by, $expected_order_by_values ) ) {
					$order_by_clause = " ORDER BY {$default_order_by} DESC";
				} else {
					$order_by        = esc_sql( $order_by );
					$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
				}

				$sql .= $order_by_clause;
				$sql .= " LIMIT $per_page";
				$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

				$result = $wpbd->get_results( $sql, 'ARRAY_A' );
			} else {
				$result = $wpbd->get_var( $sql );
			}

			return $result;
		}
	
	/**
	 * Process the Form data  and then convert it to the HTML
	 *
	 * @param $args
	 *
	 * @return string
	 */
		public static function get_embed_form( $args ) {
			$form_id    = $args['form_id'];
			$form       = ES()->forms_db->get_form_by_id( $form_id );
			$embed_html = '';
			if ( $form ) {
				$form_data = ES_Form_Controller::get_form_data_from_body( $form );
				$editor_type = ! empty( $form_data['settings']['editor_type'] ) ? $form_data['settings']['editor_type'] : '';
				$form_version = ! empty( $form_data['form_version'] ) ? $form_data['form_version'] : '0.1';
				$list_ids   = ! empty( $form_data['settings']['lists'] ) ? $form_data['settings']['lists'] : array();

				$is_dnd_editor = IG_ES_DRAG_AND_DROP_EDITOR === $editor_type;

				$form_identifier = ES_Shortcode::generate_form_identifier( $form_id );

				/**
				 * Referenced from ES_Shortcode::render_form
				 */
				if ( '0.1' == $form_version ) {
					$email_label = __( 'Email', 'email-subscribers' );
					$name_label  = __( 'Name', 'email-subscribers' );
				}
				$unique_id = uniqid();

			
				$form_action_url  = admin_url( 'admin-ajax.php' );
				$form_action_url  = add_query_arg( array( 'action' => 'ig_es_external_subscription_form_submission' ), $form_action_url );

				$embed_html .= "<form method='post' action='$form_action_url' class='ig-es-embeded-from'>";

				if ( ! $is_dnd_editor ) {
					//To avoid Captcha in embed form
					$form_data['captcha'] = 'no';
					//Name field
					$show_name        = ! empty( $form_data['name_visible'] ) ? strtolower( $form_data['name_visible'] ) : false;
					$name_required    = ! empty( $form_data['name_required'] ) ? $form_data['name_required'] : false;
					$name_label       = ! empty( $form_data['name_label'] ) ? $form_data['name_label'] : '';
					$name_placeholder = ! empty( $form_data['name_place_holder'] ) ? $form_data['name_place_holder'] : '';
					//Email field
					$email_label       = ! empty( $form_data['email_label'] ) ? $form_data['email_label'] : '';
					$email_placeholder = ! empty( $form_data['email_place_holder'] ) ? $form_data['email_place_holder'] : '';
	
					$button_label = ! empty( $form_data['button_label'] ) ? $form_data['button_label'] : __( 'Subscribe', 'email-subscribers' );
					//List Field
					$list_label = ! empty( $form_data['list_label'] ) ? $form_data['list_label'] : __( 'Select list(s)', 'email-subscribers' );
					$show_list  = ! empty( $form_data['list_visible'] ) ? $form_data['list_visible'] : false;
					$list       = ! empty( $form_data['list'] ) ? $form_data['list'] : 0;
	
					$desc         = ! empty( $form_data['desc'] ) ? $form_data['desc'] : '';
				
	
					//GDRP content
					$gdpr_consent      = ! empty( $form_data['gdpr_consent'] ) ? $form_data['gdpr_consent'] : 'no';
					$gdpr_consent_text = ! empty( $form_data['gdpr_consent_text'] ) ? $form_data['gdpr_consent_text'] : '';

					$name_field_html  = ES_Shortcode::get_name_field_html( $show_name, $name_label, $name_required, $name_placeholder );
					$email_field_html = ES_Shortcode::get_email_field_html( $email_label, $email_placeholder );
					$list_field_html  = ES_Shortcode::get_list_field_html( $show_list, $list_label, $list_ids, $list );
					if ( '' !== $desc ) {
						$embed_html .= '<div class="es_caption">' . $desc . '</div>';
					}
					$embed_html .= $name_field_html;
					$embed_html .= $email_field_html;
					$embed_html .= $list_field_html;
					$embed_html  = apply_filters( 'ig_es_after_form_fields', $embed_html, $form_data );
				} else {
					if ( ! empty( $list_ids ) ) {
						$list_html  = ES_Shortcode::get_list_field_html(false, '', $list_ids, '');
						$embed_html .= $list_html;
					}

					$embed_html  .= ! empty( $form_data['settings']['dnd_editor_css'] ) ? '<style>' . $form_data['settings']['dnd_editor_css'] . '</style>' : '';
					$embed_html .= ! empty( $form_data['body'] ) ? do_shortcode( $form_data['body'] ) : '';
				}


			
				$embed_html .= "<input type='hidden' name='esfpx_es_form_identifier' value='$form_identifier' />";
				$embed_html .= "<input type='hidden' name='esfpx_status' value='Unconfirmed' />";
				$embed_html .= "<input type='hidden' name='esfpx_es-subscribe' value='$unique_id' />";
				$embed_html .= "<input type='hidden' name='esfpx_form_id' value='$form_id' />";
				$embed_html .= "<label style='position:absolute;top:-99999px;'><input type='email' name='esfpx_es_hp_email' class='es_required_field' tabindex='-1' autocomplete='-1' value=''/></label>";

				if ( ! $is_dnd_editor ) {
					if ( 'yes' === $gdpr_consent ) {
						$embed_html .= "<div class='ig-es-gdpr-content'><label><input type='checkbox' name='es_gdpr_consent' value='true' required='required'/>&nbsp;$gdpr_consent_text</label></div>";
					}
					$embed_html .= "<input type='submit' name='submit' class='es_subscription_form_submit es_submit_button es_textbox_button' value='$button_label' />";
				}

				$spinner_image_path = ES_PLUGIN_URL . 'lite/public/images/spinner.gif';

				$embed_html .= "<span class='es_spinner_image' style='display: none' id='spinner-image'><img src='$spinner_image_path' alt='Loading'/></span>";
				$embed_html .= '</form>';

				$embed_form_js = ES_PLUGIN_URL . 'pro/public/js/embed-form.js';

				$embed_html .= '<script '; // To fix phpcs error, we have spitted the script tag.
				$embed_html	.=		"src='$embed_form_js' id='ig-es-embed-form-script'></script>";
			}

			return $embed_html;
		}

		
	}

}

ES_Forms_Controller::get_instance();
