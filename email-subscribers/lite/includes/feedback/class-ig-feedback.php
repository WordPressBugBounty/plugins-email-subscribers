<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'IG_Feedback_V_1_2_13' ) ) {
	/**
	 * IG Feedback
	 *
	 * The IG Feedback class adds functionality to get quick interactive feedback from users.
	 * There are different types of feedabck widget like Stars, Emoji, Thubms Up/ Down, Number etc.
	 *
	 * @class       IG_Feedback_V_1_2_10
	 * @since       1.0.0
	 * @copyright   Copyright (c) 2019, Icegram
	 * @license     https://opensource.org/licenses/gpl-license GNU Public License
	 * @package     feedback
	 */
	class IG_Feedback_V_1_2_13 {

		/**
		 * Version of Feedback Library
		 *
		 * @since 1.0.13
		 * @var string
		 */
		public $version = '1.2.13';
		/**
		 * The API URL where we will send feedback data.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $api_url = 'https://api.icegram.com/store/feedback/'; // Production

		/**
		 * Name for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $name;

		/**
		 * Unique slug for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $plugin;

		/**
		 * Unique slug for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $ajax_action;

		/**
		 * Plugin Abbreviation
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $plugin_abbr;

		/**
		 * Enable/Disable Dev Mode
		 *
		 * @var bool
		 */
		public $is_dev_mode = true;

		/**
		 * Set feedback event
		 *
		 * @var string
		 */
		public $event_prefix;

		/**
		 * Footer HTML
		 */
		public $footer = '<span class="ig-powered-by">Made With&nbsp;💜&nbsp;by&nbsp;<a href="https://www.icegram.com/" target="_blank">Icegram</a></span>';

		/**
		 * Primary class constructor.
		 *
		 * @param string $name Plugin name.
		 * @param string $plugin Plugin slug.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $name = '', $plugin = '', $plugin_abbr = 'ig_fb', $event_prefix = 'igfb.', $is_dev_mode = false ) {

			$this->name         = $name;
			$this->plugin       = $plugin;
			$this->plugin_abbr  = $plugin_abbr;
			$this->event_prefix = $event_prefix;
			$this->ajax_action  = $this->plugin_abbr . '_submit-feedback';
			$this->is_dev_mode  = $is_dev_mode;

			// Don't run deactivation survey on dev sites.
			if ( ! $this->can_show_feedback_widget() ) {
				return;
			}

			add_action( 'wp_ajax_' . $this->ajax_action, array( $this, 'submit_feedback' ) );

			// Contact Us enhanced flow — system info fetch and multipart submission.
			add_action( 'wp_ajax_' . $this->plugin_abbr . '_get_contact_system_info', array( $this, 'handle_contact_system_info_ajax' ) );
			add_action( 'wp_ajax_' . $this->plugin_abbr . '_contact_us_submit', array( $this, 'handle_contact_us_feedback' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			/**
			 * Show 5 star review notice to user
			 *
			 * @since 1.0.12
			 */
			add_action( 'admin_notices', array( &$this, 'show_review_notice' ) );
		}

		/**
		 * Ask for user review
		 *
		 * @since 1.0.12
		 */
		public function show_review_notice() {

			if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {

				$enable_review_notice = apply_filters( $this->plugin_abbr . '_enable_review_notice', true );

				$can_ask_user_for_review = true;

				if ( $enable_review_notice ) {

					$current_user_id = get_current_user_id();

					$review_done_option      = $this->plugin_abbr . '_feedback_review_done';
					$no_bug_option           = $this->plugin_abbr . '_feedback_do_not_ask_again';
					$already_did_option      = $this->plugin_abbr . '_feedback_already_did';
					$maybe_later_option      = $this->plugin_abbr . '_feedback_maybe_later';
					$review_done_time_option = $review_done_option . '_time';
					$no_bug_time_option      = $no_bug_option . '_time';
					$already_did_time_option = $already_did_option . '_time';
					$maybe_later_time_option = $maybe_later_option . '_time';

					$no_bug_days_before = 1;
					$no_bug_value       = get_user_meta( $current_user_id, $no_bug_option, true );
					$no_bug_time_value  = get_user_meta( $current_user_id, $no_bug_time_option, true );

					$review_done_value      = get_user_meta( $current_user_id, $review_done_option, true );
					$review_done_time_value = get_user_meta( $current_user_id, $review_done_time_option, true );

					if ( ! empty( $no_bug_time_value ) && 0 !== $no_bug_time_value ) {
						$no_bug_time_diff   = time() - $no_bug_time_value;
						$no_bug_days_before = floor( $no_bug_time_diff / 86400 ); // 86400 seconds == 1 day
					}

					$already_did_value      = get_user_meta( $current_user_id, $already_did_option, true );
					$already_did_time_value = get_user_meta( $current_user_id, $already_did_time_option, true );

					$maybe_later_days_before = 1;
					$maybe_later_value       = get_user_meta( $current_user_id, $maybe_later_option, true );
					$maybe_later_time_value  = get_user_meta( $current_user_id, $maybe_later_time_option, true );

					if ( $maybe_later_value && ! empty( $maybe_later_time_value ) && 0 !== $maybe_later_time_value ) {
						$maybe_later_time_diff   = time() - $maybe_later_time_value;
						$maybe_later_days_before = floor( $maybe_later_time_diff / 86400 ); // 86400 seconds == 1 day
					}

					// Is user fall in love with our plugin in 15 days after when they said may be later?
					// But, make sure we are asking user only after 15 days.
					// We are good people. Respect the user decision.
					if ( $review_done_value || $no_bug_value || $already_did_value || ( $maybe_later_value && $maybe_later_days_before < 15 ) || $already_did_value ) {
						$can_ask_user_for_review = false;
					}

					$review_data = array(
						'review_done_value'      => $review_done_value,
						'review_done_time_value' => $review_done_time_value,
						'no_bug_value'           => $no_bug_value,
						'no_bug_time_value'      => $no_bug_time_value,
						'maybe_later_value'      => $maybe_later_value,
						'maybe_later_time_value' => $maybe_later_time_value,
						'already_did_value'      => $already_did_value,
						'already_did_time_value' => $already_did_time_value,
					);

					$can_ask_user_for_review = apply_filters( $this->plugin_abbr . '_can_ask_user_for_review', $can_ask_user_for_review, $review_data );

					if ( $can_ask_user_for_review ) {

						$current_page_url = '';
						if ( ! empty( $_SERVER['HTTP_HOST'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
							$current_page_url = esc_url_raw( '//' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
						}

						$got_feedback = false;
						/************** Update Review Status */
						$nonce          = ! empty( $_GET['ig_feedback_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['ig_feedback_nonce'] ) ) : '';
						$nonce_verified = wp_verify_nonce( $nonce, 'review' );

						$action = '';
						if ( $nonce_verified ) {
							$action = ! empty( $_GET['ig_feedback_action'] ) ? sanitize_text_field( wp_unslash( $_GET['ig_feedback_action'] ) ) : '';

							if ( ! empty( $action ) && $this->is_valid_action( $action ) ) {
								update_user_meta( $current_user_id, $action, 1 );
								update_user_meta( $current_user_id, $action . '_time', time() );

								// Got the review request?
								// Redirect them to review page
								if ( $action === $review_done_option ) {

									$url = ! empty( $_GET['review_url'] ) ? esc_url_raw( wp_unslash( $_GET['review_url'] ) ) : '';

									if ( ! empty( $url ) ) {
										?>

										<meta http-equiv="refresh" content="0; url=<?php echo esc_url( $url ); ?>"/>

										<?php
									}
								}
							}

							$got_feedback = true;
						}
						/************** Update Review Status (End) */

						if ( ! $got_feedback ) {

							$review_url = "https://wordpress.org/support/plugin/{$this->plugin}/reviews/";
							$icon_url   = plugin_dir_url( __FILE__ ) . 'assets/images/icon-64.png';
							/* translators: %s: Plugin name */
							$message = __( sprintf( "<span><p>We hope you're enjoying <b>%s</b> plugin! Could you please do us a BIG favor and give us a 5-star rating on WordPress to help us spread the word and boost our motivation?</p>", $this->name ), $this->plugin );

							$message_data = array(
								'review_url' => $review_url,
								'icon_url'   => $icon_url,
								'message'    => $message,
							);

							$message_data = apply_filters( $this->plugin_abbr . '_review_message_data', $message_data );

							$message    = ! empty( $message_data['message'] ) ? $message_data['message'] : '';
							$review_url = ! empty( $message_data['review_url'] ) ? $message_data['review_url'] : '';
							$icon_url   = ! empty( $message_data['icon_url'] ) ? $message_data['icon_url'] : '';

							$nonce = wp_create_nonce( 'review' );

							$review_url      = add_query_arg( 'review_url', $review_url, add_query_arg( 'ig_feedback_nonce', $nonce, add_query_arg( 'ig_feedback_action', $review_done_option, $current_page_url ) ) );
							$maybe_later_url = add_query_arg( 'ig_feedback_nonce', $nonce, add_query_arg( 'ig_feedback_action', $maybe_later_option, $current_page_url ) );
							$already_did_url = add_query_arg( 'ig_feedback_nonce', $nonce, add_query_arg( 'ig_feedback_action', $already_did_option, $current_page_url ) );
							$no_bug_url      = add_query_arg( 'ig_feedback_nonce', $nonce, add_query_arg( 'ig_feedback_action', $no_bug_option, $current_page_url ) );

							?>

							<style type="text/css">

								.ig-feedback-notice-links li {
									display: inline-block;
									margin-right: 15px;
								}

								.ig-feedback-notice-links li a {
									display: inline-block;
									color: #5850EC;
									text-decoration: none;
									padding-left: 26px;
									position: relative;
								}

								.ig-feedback-notice {
									display: flex;
									align-items: center;
								}

								.ig-feedback-plugin-icon {
									float: left;
									margin-right: 0.5em;
								}

							</style>

							<?php

							echo '<div class="notice notice-success ig-feedback-notice">';
							echo '<span class="ig-feedback-plugin-icon"> <img src="' . esc_url( $icon_url ) . '" alt="Logo"/></span>';
							echo wp_kses_post( $message );
							echo "<ul class='ig-feedback-notice-links'>";
							echo sprintf(
								'<li><a href="%s" class="px-4 py-2 ml-6 mr-2 align-middle cursor-pointer button button-primary bg-indigo-600" target="_blank" data-rated="' . esc_attr__(
									'Thank You :) ',
									$this->plugin
								) . '"><span class="dashicons dashicons-external"></span>&nbsp;&nbsp;Ok, you deserve it</a></li> <li><a href="%s"><span class="dashicons dashicons-calendar-alt"></span>&nbsp;&nbsp;Maybe later</a></li><li><a href="%s"><span class="dashicons dashicons-smiley"></span>&nbsp;&nbsp;I already did!</a></li><li><a href="%s"><span class="dashicons dashicons-no"></span>&nbsp;&nbsp;Don\'t ask me again</a></li>',
								esc_url( $review_url ),
								esc_url( $maybe_later_url ),
								esc_url( $already_did_url ),
								esc_url( $no_bug_url )
							);
							echo '</ul></span>';
							echo '</div>';
						}
					}
				}
			}
		}

		/**
		 * Is valid action?
		 *
		 * @param string $action
		 *
		 * @return bool
		 *
		 * @since 1.0.14
		 */
		public function is_valid_action( $action = '' ) {
			if ( empty( $action ) ) {
				return false;
			}

			$available_actions = array(
				'_feedback_review_done',
				'_feedback_already_did',
				'_feedback_maybe_later',
				'_feedback_do_not_ask_again',
			);

			foreach ( $available_actions as $available_action ) {
				if ( strpos( $action, $available_action ) !== false ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Render Deactivation Feedback
		 *
		 * @since 1.0.0
		 */
		public function render_deactivate_feedback() {
			add_action( 'admin_print_footer_scripts', array( $this, 'js' ), 20 );
			add_action( 'admin_print_scripts', array( $this, 'css' ) );
			add_action( 'admin_footer', array( $this, 'modal' ) );
		}

		/**
		 * Load Javascripts
		 *
		 * @since 1.0.1
		 *
		 * @modify 1.1.0
		 */
		public function enqueue_scripts() {

			$can_load = apply_filters( $this->plugin_abbr . '_can_load_sweetalert_js', false );

			if ( $can_load ) {
				// phpcs:disable WordPress.WP.EnqueuedResourceParameters.NotInFooter
				wp_enqueue_script( 'sweetalert', plugin_dir_url( __FILE__ ) . 'assets/js/sweetalert2.min.js', array( 'jquery' ) );
				// phpcs:enable
			}
		}

		/**
		 * Load Styles
		 *
		 * @since 1.0.1
		 */
		public function enqueue_styles() {

			$can_load = apply_filters( $this->plugin_abbr . '_can_load_sweetalert_css', false );

			if ( $can_load ) {
				wp_register_style( "sweetalert_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/sweetalert2.min.css', array(), $this->version );
				wp_enqueue_style( "sweetalert_{$this->version}" );
	
				wp_register_style( "animate_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/animate.min.css', array(), $this->version );
				wp_enqueue_style( "animate_{$this->version}" );
	
				wp_register_style( "ig-feedback-star-rating_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/star-rating.min.css', array(), $this->version );
				wp_enqueue_style( "ig-feedback-star-rating_{$this->version}" );
	
				wp_register_style( "ig-feedback-emoji_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/emoji.min.css', array(), $this->version );
				wp_enqueue_style( "ig-feedback-emoji_{$this->version}" );
	
				wp_register_style( "ig-feedback_{$this->version}", plugin_dir_url( __FILE__ ) . 'assets/css/feedback.min.css', array(), $this->version );
				wp_enqueue_style( "ig-feedback_{$this->version}" );

				// Contact Us enhanced modal styles — registered separately so they only add
				// to the existing Swal CSS, never replacing it.
				wp_register_style(
					"ig-feedback-contact-us_{$this->version}",
					plugin_dir_url( __FILE__ ) . 'assets/css/contact-us-modal.css',
					array( "sweetalert_{$this->version}" ),
					$this->version
				);
				wp_enqueue_style( "ig-feedback-contact-us_{$this->version}" );
			}
		}

		/**
		 * Prepare Widget Params
		 *
		 * @param array $params
		 *
		 * @return array
		 *
		 * @since 1.0.3
		 */
		public function prepare_widget_params( $params = array() ) {

			$default_params = array(
				'event'             => 'feedback',
				'title'             => 'How do you rate ' . $this->plugin,
				'position'          => 'top-end',
				'width'             => 300,
				'set_transient'     => true,
				'allowOutsideClick' => false,
				'allowEscapeKey'    => true,
				'showCloseButton'   => true,
				'confirmButtonText' => 'Ok',
				'backdrop'          => true,
				'delay'             => 3, // In Seconds
				'consent_text'      => 'You are agree to our terms and condition',
				'email'             => $this->get_contact_email(),
				'name'              => '',
				'consent'           => false,
			);

			$params = wp_parse_args( $params, $default_params );

			return $params;
		}

		/**
		 * Render Widget
		 *
		 * @param array $params
		 *
		 * @since 1.0.0
		 */
		public function render_widget( $params = array() ) {

			$params = $this->prepare_widget_params( $params );

			$title = $params['title'];
			$slug  = sanitize_title( $title );
			$event = $this->event_prefix . $params['event'];
			$html  = ! empty( $params['html'] ) ? $params['html'] : '';

			?>

			<script>

				function doSend(rating, details) {

					var data = {
						action: '<?php echo esc_js( $this->ajax_action ); ?>',
						feedback: {
							type: '<?php echo esc_js( $params['type'] ); ?>',
							slug: '<?php echo esc_js( $slug ); ?>',
							title: '<?php echo esc_js( $title ); ?>',
							value: rating,
							details: details
						},

						event: '<?php echo esc_js( $event ); ?>',

						// Add additional information
						misc: {
							plugin: '<?php echo esc_js( $this->plugin ); ?>',
							plugin_abbr: '<?php echo esc_js( $this->plugin_abbr ); ?>',
							is_dev_mode: '<?php echo esc_js( $this->is_dev_mode ); ?>',
							set_transient: '<?php echo esc_js( $params['set_transient'] ); ?>'
							//system_info: enable_system_info
						},
						security: '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>'
					};

					return jQuery.post(ajaxurl, data);
				}

				function showWidget(delay) {

					setTimeout(function () {

						Swal.mixin({
							footer: '<?php echo wp_kses_post( $this->footer ); ?>',
							position: '<?php echo esc_js( $params['position'] ); ?>',
							width: <?php echo esc_js( $params['width'] ); ?>,
							animation: false,
							focusConfirm: false,
							allowEscapeKey: '<?php echo esc_js( $params['allowEscapeKey'] ); ?>',
							showCloseButton: '<?php echo esc_js( $params['showCloseButton'] ); ?>',
							allowOutsideClick: '<?php echo esc_js( $params['allowOutsideClick'] ); ?>',
							showLoaderOnConfirm: true,
							confirmButtonText: '<?php echo wp_kses_post( $params['confirmButtonText'] ); ?>',
							backdrop: '<?php echo (int) esc_js( $params['backdrop'] ); ?>'
						}).queue([
							{
								title: '<p class="ig-feedback-title"><?php echo esc_js( $params['title'] ); ?></p>',
								html: '<?php echo wp_kses_post( $html ); ?>',
								customClass: {
									popup: 'animated fadeInUpBig'
								},
								onOpen: () => {
									var clicked = false;
									var selectedReaction = '';
									jQuery('.ig-emoji').hover(function () {
										reaction = jQuery(this).attr('data-reaction');
										jQuery('#emoji-info').text(reaction);
									}, function () {
										if (!clicked) {
											jQuery('#emoji-info').text('');
										} else {
											jQuery('#emoji-info').text(selectedReaction);
										}
									});

									jQuery('.ig-emoji').on('click', function () {
										clicked = true;
										jQuery('.ig-emoji').removeClass('active');
										jQuery(this).addClass('active');
										selectedReaction = jQuery(this).attr('data-reaction');
										jQuery('#emoji-info').text(reaction);
									});
								},
								preConfirm: () => {

									var rating = jQuery("input[name='rating']:checked").val();
									var details = '';

									if (rating === undefined) {
										Swal.showValidationMessage('Please give your input');
										return;
									}

									return doSend(rating, details);
								}
							},

						]).then(response => {

							if (response.hasOwnProperty('value')) {

								Swal.fire({
									type: 'success',
									width: <?php echo esc_js( $params['width'] ); ?>,
									title: '<?php echo esc_attr__( 'Thank You!', $this->plugin ); ?>',
									showConfirmButton: false,
									position: '<?php echo esc_js( $params['position'] ); ?>',
									timer: 1500,
									animation: false
								});

							}
						});

					}, delay * 1000);
				}

				var delay = <?php echo esc_js( $params['delay'] ); ?>;
				showWidget(delay);


			</script>
			<?php
		}

		/**
		 * Render star feedback widget
		 *
		 * @param array $params
		 *
		 * @since 1.0.1
		 */
		public function render_stars( $params = array() ) {

			ob_start();

			?>

			<div class="rating">
				<!--elements are in reversed order, to allow "previous sibling selectors" in CSS-->
				<input class="ratings" type="radio" name="rating" value="5" id="5"><label for="5">☆</label>
				<input class="ratings" type="radio" name="rating" value="4" id="4"><label for="4">☆</label>
				<input class="ratings" type="radio" name="rating" value="3" id="3"><label for="3">☆</label>
				<input class="ratings" type="radio" name="rating" value="2" id="2"><label for="2">☆</label>
				<input class="ratings" type="radio" name="rating" value="1" id="1"><label for="1">☆</label>
			</div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$params['html'] = $html;

			$this->render_widget( $params );
		}

		/**
		 * Render Emoji Widget
		 *
		 * @param array $params
		 *
		 * @since 1.0.1
		 */
		public function render_emoji( $params = array() ) {

			ob_start();

			?>

			<div class="emoji">
				<!--elements are in reversed order, to allow "previous sibling selectors" in CSS-->
				<input class="emojis" type="radio" name="rating" value="love" id="5"/><label for="5" class="ig-emoji" data-reaction="Love">😍</label>
				<input class="emojis" type="radio" name="rating" value="smile" id="4"/><label for="4" class="ig-emoji" data-reaction="Smile">😊</label>
				<input class="emojis" type="radio" name="rating" value="neutral" id="3"/><label for="3" class="ig-emoji" data-reaction="Neutral">😐</label>
				<input class="emojis" type="radio" name="rating" value="sad" id="1"/><label for="2" class="ig-emoji" data-reaction="Sad">😠</label>
				<input class="emojis" type="radio" name="rating" value="angry" id="1"/><label for="1" class="ig-emoji" data-reaction="Angry">😡</label>
			</div>
			<div id="emoji-info"></div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$params['html'] = $html;

			$this->render_widget( $params );

		}

		/**
		 * Render General Feedback Sidebar Button Widget
		 *
		 * @since 1.0.3
		 */
		public function render_general_feedback( $params = array() ) {

			$params = $this->prepare_widget_params( $params );

			$title               = $params['title'];
			$slug                = sanitize_title( $title );
			$event               = $this->event_prefix . $params['event'];
			$escape_allowed_tags = $this->get_escape_allowed_tags();

			// Logged-in WordPress user info for Contact Us form autofill
			$current_user = wp_get_current_user();
			$cu_name      = '';
			$cu_email     = '';
			if ( $current_user instanceof WP_User && $current_user->exists() ) {
				$cu_name = trim( (string) $current_user->display_name );
				if ( empty( $cu_name ) ) {
					$cu_name = trim( (string) $current_user->first_name . ' ' . (string) $current_user->last_name );
				}
				if ( empty( $cu_name ) ) {
					$cu_name = trim( (string) $current_user->user_login );
				}
				$cu_email = trim( (string) $current_user->user_email );
			}

			ob_start();
			?>

			<script type="text/javascript">

				jQuery(document).ready(function ($) {

					// ---- Original send/validate helpers (Feedback tab, unchanged) ----

					function doSend(details, meta, system_info) {

						var data = {
							action: '<?php echo esc_js( $this->ajax_action ); ?>',
							feedback: {
								type: '<?php echo esc_js( $params['type'] ); ?>',
								slug: '<?php echo esc_js( $slug ); ?>',
								title: '<?php echo esc_js( $title ); ?>',
								details: details
							},

							event: '<?php echo esc_js( $event ); ?>',

							// Add additional information
							misc: {
								plugin: '<?php echo esc_js( $this->plugin ); ?>',
								plugin_abbr: '<?php echo esc_js( $this->plugin_abbr ); ?>',
								is_dev_mode: '<?php echo esc_js( $this->is_dev_mode ); ?>',
								set_transient: '<?php echo esc_js( $params['set_transient'] ); ?>',
								meta_info: meta,
								system_info: system_info
							},
							security: '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>'
						};

						return jQuery.post(ajaxurl, data);
					}

					function validateEmail(email) {
						var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
						if (!emailReg.test(email)) {
							return false;
						} else {
							return true;
						}
					}

					var feedbackButtonClass = 'ig-feedback-button-<?php echo esc_js( $this->plugin ); ?>';

					$('#wpwrap').append('<div class="ig-es-feedback-button ' + feedbackButtonClass + '">Feedback</div>');

					// ---- Contact Us configuration (resolved at PHP render time) ----

					var igCuConfig = {
						nonce:         '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>',
						ajaxAction:    '<?php echo esc_js( $this->plugin_abbr ); ?>_contact_us_submit',
						sysInfoAction: '<?php echo esc_js( $this->plugin_abbr ); ?>_get_contact_system_info',
						event:         '<?php echo esc_js( $event ); ?>',
						slug:          '<?php echo esc_js( $slug ); ?>',
						feedbackTitle: '<?php echo esc_js( $title ); ?>',
						pluginName:    '<?php echo esc_js( $this->plugin ); ?>',
						pluginAbbr:    '<?php echo esc_js( $this->plugin_abbr ); ?>',
						isDevMode:     '<?php echo esc_js( $this->is_dev_mode ); ?>',
						setTransient:  '<?php echo esc_js( $params['set_transient'] ); ?>'
					};

					// ---- Click handler — branches on data-entry="contact-us" ----

					$('.' + feedbackButtonClass).on('click', function () {
						var $btn = $(this);
						var isContactUs = $btn.data('entry') === 'contact-us';
						var preselectedPlugin = ''; // not used in single-step mode

						if (isContactUs) {
							igShowContactUs();
							return;
						}

						// ---- Feedback tab — redesigned with ig-cu-popup CSS (same as Contact Us modal) ----

						var fbFormHtml =
							'<p class="ig-cu-modal-title">Feedback</p>'
							+ '<div class="ig-cu-form-body">'
							+ '<div class="ig-cu-row">'
							+   '<div class="ig-cu-field">'
							+     '<label class="ig-cu-label" for="ig-feedback-data-name">Name</label>'
							+     '<input type="text" id="ig-feedback-data-name" class="ig-cu-input" placeholder="Your name" value="<?php echo esc_js( $params['name'] ); ?>">'
							+   '</div>'
							+   '<div class="ig-cu-field">'
							+     '<label class="ig-cu-label" for="ig-feedback-data-email">Email</label>'
							+     '<input type="email" id="ig-feedback-data-email" class="ig-cu-input" placeholder="you@example.com" value="<?php echo esc_js( $params['email'] ); ?>">'
							+   '</div>'
							+ '</div>'
							+ '<div class="ig-cu-field">'
							+   '<label class="ig-cu-label" for="ig-feedback-data-message">Message <span class="ig-cu-req" aria-hidden="true">*</span></label>'
							+   '<textarea id="ig-feedback-data-message" class="ig-cu-textarea" placeholder="Tell us what you think..."></textarea>'
							+ '</div>'
							+ '</div>';

						Swal.fire({
							customClass:         { popup: 'ig-cu-popup' },
							html:                fbFormHtml,
							confirmButtonText:   '<?php echo esc_js( wp_strip_all_tags( $params['confirmButtonText'] ) ); ?>',
							showCancelButton:    false,
							allowOutsideClick:   false,
							allowEscapeKey:      true,
							showCloseButton:     true,
							showLoaderOnConfirm: true,
							footer:              '<?php echo wp_kses_post( $this->footer ); ?>',
							position:            '<?php echo esc_js( $params['position'] ); ?>',
							width:               <?php echo esc_js( $params['width'] ); ?>,
							animation:           false,
							focusConfirm:        false,

							onOpen: function () {
								// Bind close button (same safety-net as Contact Us modal)
								$(document).off('click.igfbclose')
									.on('click.igfbclose', '.ig-cu-popup .swal2-close', function (e) {
										e.stopPropagation();
										Swal.close();
									});
								// Reset validation message on input
								$(document).off('.igfb2')
									.on('input.igfb2', '#ig-feedback-data-message, #ig-feedback-data-email', function () {
										Swal.resetValidationMessage();
									});
							},

							preConfirm: function () {
								var email   = $('#ig-feedback-data-email').val();
								var name    = $('#ig-feedback-data-name').val();
								var message = $('#ig-feedback-data-message').val();

								if (email !== '' && !validateEmail(email)) {
									Swal.showValidationMessage('Please enter a valid email address.');
									return false;
								}

								if (message === '') {
									Swal.showValidationMessage('Please enter your message.');
									return false;
								}

								var meta = { name: name, email: email };
								return doSend(message, meta, false);
							}
						}).then(function (response) {
							if (response.hasOwnProperty('value')) {
								Swal.fire({
									type:              'success',
									customClass:       { popup: 'ig-cu-popup' },
									width:              <?php echo esc_js( $params['width'] ); ?>,
									title:             '<?php echo esc_attr__( 'Thank You!', $this->plugin ); ?>',
									showConfirmButton: false,
									position:          '<?php echo esc_js( $params['position'] ); ?>',
									timer:             1500,
									animation:         false
								});
							}
						});

					});

					// ================================================================
					// Contact Us — Step 1: plugin dropdown + subject
					// ================================================================


					// ================================================================
					// Contact Us — single-step form
					// ================================================================

					function igShowContactUs() {

						var formHtml =
							'<p class="ig-cu-modal-title">Contact Us</p>'
							+ '<div class="ig-cu-form-body">'
							+ '<div class="ig-cu-row">'
							+   '<div class="ig-cu-field">'
							+     '<label class="ig-cu-label" for="ig-feedback-data-name">Name</label>'
							+     '<input type="text" id="ig-feedback-data-name" class="ig-cu-input" placeholder="Your name" value="<?php echo esc_attr( $cu_name ); ?>">'
							+   '</div>'
							+   '<div class="ig-cu-field">'
							+     '<label class="ig-cu-label" for="ig-feedback-data-email">Email</label>'
							+     '<input type="email" id="ig-feedback-data-email" class="ig-cu-input" placeholder="you@example.com" value="<?php echo esc_attr( $cu_email ); ?>">'
							+   '</div>'
							+ '</div>'
							+ '<div class="ig-cu-field">'
							+   '<label class="ig-cu-label" for="ig-feedback-data-message">Message <span class="ig-cu-req" aria-hidden="true">*</span></label>'
							+   '<textarea id="ig-feedback-data-message" class="ig-cu-textarea" placeholder="Describe your question or issue"></textarea>'
							+   '<a href="https://www.icegram.com/docs/express/" target="_blank" rel="noopener" class="ig-cu-docs-link"><span class="dashicons dashicons-book" aria-hidden="true"></span> Documentation</a>'
							+ '</div>'
							+ '<div class="ig-cu-opt-row">'
							+   '<input type="checkbox" id="ig-cu-include-sysinfo" checked>'
							+   '<label for="ig-cu-include-sysinfo">Include system info \u2014 helps us diagnose your issue faster</label>'
							+ '</div>'
							+ '</div>';

						Swal.fire({
							customClass:         { popup: 'ig-cu-popup' },
							html:                formHtml,
							confirmButtonText:   '<?php echo esc_js( wp_strip_all_tags( $params['confirmButtonText'] ) ); ?>',
							showCancelButton:    false,
							allowOutsideClick:   false,
							allowEscapeKey:      true,
							showCloseButton:     true,
							showLoaderOnConfirm: true,
							focusConfirm:        false,
							width:               <?php echo esc_js( $params['width'] ); ?>,
							footer:              '<?php echo wp_kses_post( $this->footer ); ?>',
							animation:           false,

							onOpen: function () {
								// Close button safety net
								$(document).off('click.igcuclose')
									.on('click.igcuclose', '.ig-cu-popup .swal2-close', function (e) {
										e.stopPropagation();
										Swal.close();
									});

								// Reset validation message when message/email changes
								$(document).off('.igcu2')
									.on('input.igcu2', '#ig-feedback-data-message, #ig-feedback-data-email', function () {
										Swal.resetValidationMessage();
									});
							},

							onClose: function () {
								$(document).off('.igcu2 .igcuclose');
							},

							preConfirm: function () {
								var email   = (document.getElementById('ig-feedback-data-email').value  || '').trim();
								var name    = (document.getElementById('ig-feedback-data-name').value   || '').trim();
								var message = (document.getElementById('ig-feedback-data-message').value || '').trim();
								var inclEl  = document.getElementById('ig-cu-include-sysinfo');
								var inclSys = (inclEl && inclEl.checked) ? '1' : '0';

								if (email && !validateEmail(email)) {
									Swal.showValidationMessage('Please enter a valid email address.');
									return false;
								}
								if (!message) {
									Swal.showValidationMessage('Please enter your message.');
									return false;
								}

								var fd = new FormData();
								fd.append('action',              igCuConfig.ajaxAction);
								fd.append('security',            igCuConfig.nonce);
								fd.append('event',               igCuConfig.event);
								fd.append('contact_plugin',      'email-subscribers');
								fd.append('contact_subject',     igCuConfig.feedbackTitle || 'Contact Us');
								fd.append('include_system_info', inclSys);
								fd.append('feedback[type]',      'feedback');
								fd.append('feedback[slug]',      igCuConfig.slug);
								fd.append('feedback[title]',     igCuConfig.feedbackTitle);
								fd.append('feedback[details]',   message);
								fd.append('misc[plugin]',        igCuConfig.pluginName);
								fd.append('misc[plugin_abbr]',   igCuConfig.pluginAbbr);
								fd.append('misc[is_dev_mode]',   igCuConfig.isDevMode);
								fd.append('misc[set_transient]', igCuConfig.setTransient);
								fd.append('misc[meta_info][name]',  name);
								fd.append('misc[meta_info][email]', email);

								return jQuery.ajax({ url: ajaxurl, method: 'POST', data: fd, processData: false, contentType: false });
							}

						}).then(function (result) {
							if (result && result.value) {
								Swal.fire({
									type:              'success',
									customClass:       { popup: 'ig-cu-popup' },
									title:             '<?php echo esc_attr__( 'Thank You!', $this->plugin ); ?>',
									text:              'We\u2019ll get back to you as soon as possible.',
									showConfirmButton: false,
									timer:             2000,
									animation:         false
								});
							}
						});
					}

				});

			</script>


			<?php

		}

		/**
		 * Render Facebook Widget
		 *
		 * @since 1.0.9
		 */
		public function render_fb_widget( $params ) {

			$params = $this->prepare_widget_params( $params );

			$title               = $params['title'];
			$widget_tyoe         = ! empty( $params['widget_tyoe'] ) ? $params['widget_tyoe'] : 'question';
			$slug                = sanitize_title( $title );
			$event               = $this->event_prefix . $params['event'];
			$html                = ! empty( $params['html'] ) ? $params['html'] : '';
			$confirm_button_link = ! empty( $params['confirmButtonLink'] ) ? $params['confirmButtonLink'] : '';
			$cancel_button_link  = ! empty( $params['cancelButtonLink'] ) ? $params['cancelButtonLink'] : '';
			$show_cancel_button  = ! empty( $params['showCancelButton'] ) ? 'true' : 'false';
			$cancel_button_text  = ! empty( $params['cancelButtonText'] ) ? $params['cancelButtonText'] : 'Cancel';

			?>

			<script>

				Swal.mixin({
					type: '<?php echo esc_js( $widget_tyoe ); ?>',
					position: '<?php echo esc_js( $params['position'] ); ?>',
					width: <?php echo esc_js( $params['width'] ); ?>,
					animation: false,
					focusConfirm: true,
					allowEscapeKey: true,
					showCancelButton: <?php echo esc_js( $show_cancel_button ); ?>,
					confirmButtonColor: '#0e9f6e',
					cancelButtonColor: '#5850ec',
					cancelButtonText: '<?php echo  wp_kses_post( $cancel_button_text ); ?>',
					showCloseButton: '<?php echo esc_js( $params['showCloseButton'] ); ?>',
					allowOutsideClick: '<?php echo esc_js( $params['allowOutsideClick'] ); ?>',
					showLoaderOnConfirm: true,
					confirmButtonText: '<?php echo wp_kses_post( $params['confirmButtonText'] ); ?>',
					backdrop: '<?php echo (int) $params['backdrop']; ?>'
				}).queue([
					{
						title: '<p class="ig-feedback-title"><?php echo esc_js( $params['title'] ); ?></p>',
						html: '<?php echo wp_kses_post( $html ); ?>',
						customClass: {
							popup: 'animated fadeInUpBig'
						},

						preConfirm: () => {
							window.open(
								'<?php echo esc_url( $confirm_button_link ); ?>',
								'_blank' // <- This is what makes it open in a new window.
							);
						}
					}
				]).then(response => {

					if (response.hasOwnProperty('value')) {

						Swal.fire({
							type: 'success',
							width: <?php echo esc_js( $params['width'] ); ?>,
							title: "Thank You!",
							showConfirmButton: false,
							position: '<?php echo esc_js( $params['position'] ); ?>',
							timer: 1500,
							animation: false
						});
					} else if(response.dismiss == 'cancel') {
						window.open(
							'<?php echo esc_url( $cancel_button_link ); ?>',
							'_blank' // <- This is what makes it open in a new window.
						);
					}
				});

			</script>

			<?php
		}

		/**
		 * Render Poll widget
		 *
		 * @param array $params
		 *
		 * @since 1.1.0
		 */
		public function render_poll_widget( $params = array() ) {
			$params = $this->prepare_widget_params( $params );

			$fields = ! empty( $params['fields'] ) ? $params['fields'] : array();
			if ( empty( $fields ) ) {
				return;
			}
			$default_values = ! empty( $params['default_values'] ) ? $params['default_values'] : array();

			$allow_multiple = ! empty( $params['allow_multiple'] ) ? $params['allow_multiple'] : false;

			$title = $params['title'];
			if ( ! empty( $params['slug'] ) ) {
				$slug = $params['slug'];
			} else {
				$slug = sanitize_title( $title );
			}
			$event       = $this->event_prefix . $params['event'];
			$desc        = ! empty( $params['desc'] ) ? $params['desc'] : '';
			$display_as  = ! empty( $params['display_as'] ) ? $params['display_as'] : 'inline';
			$system_info = isset( $params['system_info'] ) ? $params['system_info'] : true;

			ob_start();

			?>

			<div class="ig-general-feedback" id="ig-general-feedback-<?php echo esc_attr( $this->plugin ); ?>">
				<form class="ig-general-feedback" id="ig-general-feedback">
					<?php
					if ( 'inline' === $display_as ) {
						if ( ! empty( $title ) ) {
							?>
							<h2><?php echo wp_kses_post( $title ); ?></h2>
							<?php
						}
					}

					if ( ! empty( $desc ) ) {
						?>
						<p><?php echo wp_kses_post( $desc ); ?></p>
						<?php
					}
					?>
					<p class="ig-general-feedback mb-3">
						<?php
						foreach ( $fields as $index => $field ) {
							if ( $allow_multiple ) {
								$this->render_multiple_poll_field( $field );
							} else {
								$this->render_single_poll_field( $field );
							}

							if ( ! empty(  $field['value'] ) && 'other' === $field['value'] ) {
								?>
								<div class="ig_feedback_text_wrapper">
									<textarea 
										id="feedback_text"
										name="feedback[feedback_text]"
										class="form-textarea text-sm w-2/3 mt-3 mb-1 border-gray-400 w-3/5 hidden"
										placeholder="<?php echo esc_attr__( 'Tell us more about your desired feature', $this->plugin ); ?>"></textarea>
								</div>
								<?php
							}
						}
						?>
					</p>
					<?php
					if ( 'inline' === $display_as ) {
						?>
						<button type="submit" class="button">
							<?php
								echo esc_html__( 'Submit your feedback', $this->plugin );
							?>
						</button>
						<br/>
						<br/>
						<?php
					}
					?>
				</form>
			</div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$event = $this->event_prefix . $params['event'];
			
			if ( 'inline' === $display_as ) {
				$this->render_inline_feedback_widget( $html, $params );
			} elseif ( 'popup' === $display_as ) {
				$this->render_popup_feedback_widget( $html, $params );
			}
			?>
			<script type="text/javascript">

				function doSend(data) {
					var system_info = <?php echo $system_info ? 'true' : 'false'; ?>;
					var requestData = {
						action: '<?php echo esc_js( $this->ajax_action ); ?>',
						feedback: {
							type: '<?php echo esc_js( $params['type'] ); ?>',
							slug: '<?php echo esc_js( $slug ); ?>',
							data: data
						},

						event: '<?php echo esc_js( $event ); ?>',

						// Add additional information
						misc: {
							plugin: '<?php echo esc_js( $this->plugin ); ?>',
							plugin_abbr: '<?php echo esc_js( $this->plugin_abbr ); ?>',
							is_dev_mode: '<?php echo esc_js( $this->is_dev_mode ); ?>',
							set_transient: '<?php echo esc_js( $params['set_transient'] ); ?>',
							system_info: system_info
						},
						security: '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>'
					};

					return jQuery.post(ajaxurl, requestData, null, 'json');
				}
			</script>
			<?php
		}

		/**
		 * Render inline feedback widget
		 *
		 * @since 1.2.7
		 * 
		 * @param string $html
		 * @param array $params
		 * @return void
		 */
		public function render_inline_feedback_widget( $html, $params ) {
			$escape_allowed_tags = $this->get_escape_allowed_tags();
			?>
			<style type="text/css">
				.ig-feedback-inline h2{
					font-size:2em;
					padding: 0.5em 0;
				}
				.ig-feedback-inline{
					border:none;
					width:90%;
					margin: 1em auto !important;
					/* background:#fbe2597d; */
				}
				.ig-feedback-inline button{
					background: green !important;
					color: #FFF !important;
				}
				.ig-feedback-inline radio{
					line-height:1em;
				}
				.ig-feedback-logo{
					float:right;
				}
				.ig-feedback-inline label{
					font-size: 1.1em;
					padding-bottom: 0.5em;
				}
				.ig-feedback-inline .button{
				padding: 0.7em;
					font-size: 1.2em;
					border-radius: 0;
				}
				</style>
			<div class="notice ig-feedback-notice ig-feedback-inline">
				<?php
				if ( $params['logo_img_url'] ) {
					?>
				 <div class="ig-feedback-logo">
					 <img src="<?php echo esc_url( $params['logo_img_url'] ); ?>">
				 </div>
					<?php
				}
				?>
				<?php echo wp_kses( $html, $escape_allowed_tags ); ?>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					var $overlay = $('#ig-general-feedback-<?php echo esc_js( $this->plugin ); ?>');
					var $form = $overlay.find('form');
					$('.<?php echo esc_attr( $this->plugin_abbr ); ?>-feedback-field').on('change', function(){
						let value = $(this).val();
						if ( 'other' === value ) {
							$form.find('#feedback_text').removeClass('hidden');
						} else {
							$form.find('#feedback_text').addClass('hidden');
						}
					});

					var $overlay = $('#ig-general-feedback-<?php echo esc_js( $this->plugin ); ?>');
					var $form = $overlay.find('form');
					$($form).on('submit', function(e){
						e.preventDefault();
						let selected_feature = $form.find("input[name='feedback_data[feature]']:checked").val();
						let form_data = {
							questions_response : {
								selected_feature: selected_feature
							}
						}

						if ( selected_feature === 'other' ) {
							form_data.other_feature = $form.find('textarea[name="feedback[feedback_text]"]').val();
						}

						var request = doSend( form_data );
						request.done(response => {
							if (response.status && response.status === 'success') {
								let success_text = '<?php echo esc_html__( 'Your feedback submitted successfully.', $this->plugin ); ?>';
								$('.ig-feedback-notice').html('<p>' + success_text + '</p>');
							} else {
								let error_text = '<?php echo esc_html__( 'An error has occured. Please try again.', $this->plugin ); ?>';
								$('.ig-feedback-notice').removeClass('notice-success').addClass('notice-error').append('<p>' + error_text + '</p>');
							}
						}).fail( response => {
							let error_text = '<?php echo esc_html__( 'An error has occured. Please try again.', $this->plugin ); ?>';
									$('.ig-feedback-notice').removeClass('notice-success').addClass('notice-error').append('<p>' + error_text + '</p>');
						});
					});
				});
			</script>
			<?php
		}

		/**
		 * Render popup feedback widget
		 *
		 * @since 1.2.7
		 * 
		 * @param string $html
		 * @param array $params
		 * @return void
		 */
		public function render_popup_feedback_widget( $html, $params ) {
			$escape_allowed_tags = $this->get_escape_allowed_tags();
			?>
			<style>
				.ig-general-feedback label{
					font-size: 14px;
					padding-bottom: 0.5em;
				}
				.ig-general-feedback p{
					font-size: 16px;
					padding-bottom: 0.5em;
				}

			</style>
			<script type="text/javascript">

			jQuery(document).ready(function ($) {
			
				Swal.mixin({
					footer: '<i><?php echo !empty($params['after_button_text']) ? esc_html( $params['after_button_text'] ) : ''; ?></i>',
					position: '<?php echo esc_js( $params['position'] ); ?>',
					width: <?php echo esc_js( $params['width'] ); ?>,
					animation: false,
					focusConfirm: false,
					allowEscapeKey: true,
					showCloseButton: '<?php echo esc_js( $params['showCloseButton'] ); ?>',
					allowOutsideClick: '<?php echo esc_js( $params['allowOutsideClick'] ); ?>',
					showLoaderOnConfirm: true,
					confirmButtonText: '<?php echo wp_kses_post( $params['confirmButtonText'] ); ?>',
					backdrop: '<?php echo (int) $params['backdrop']; ?>'
				}).queue([
					{
						title: '<p class="ig-feedback-title"><?php echo esc_js( $params['title'] ); ?></p>',
						html: '<?php echo wp_kses( addslashes($html), $escape_allowed_tags ); ?>',
						customClass: {
							popup: 'animated fadeInUpBig'
						},
						preConfirm: () => {
							var $overlay = $('#ig-general-feedback-<?php echo esc_js( $this->plugin ); ?>');
							var $form = $overlay.find('form');
							if ( ! jQuery($form)[0].checkValidity()) {
								Swal.showValidationMessage('<?php echo esc_html__( 'Please select option', $this->plugin ); ?>');
								return;
							}

							var poll_options_fields = $form.find('input[name^="feedback_data[poll_options]"]:checked,textarea[name^="feedback_data[poll_options]"]');

							var poll_options = {};
							if ( poll_options_fields.length > 1 ) {
								poll_options_fields.each( function( field_index, field ) {
									var field_slug = $(this).data('slug');
									var field_value = $(this).val();
									var field_type = $(this).attr('type');
									if(field_slug) {
										if ( field_type === 'checkbox' ) {
											if ( ! poll_options.hasOwnProperty( field_slug ) ) {
												poll_options[field_slug] = [];
											}
											if ($(this).is(":checked")) {
												poll_options[field_slug].push(field_value);

												if (field_value === 'other') {
													var other_text_value = $form.find('#feedback_text').val();
													if (other_text_value) {
														poll_options[field_slug + '_other'] = other_text_value;
													}
												}
											}
										} else {
											poll_options[field_slug] = field_value;
										}
									}
								})
							} else {
								poll_options = poll_options_fields.val();
							}

							var message = $form.find("#ig-feedback-data-poll-additional-message").val();

							var data = {
								poll_option: poll_options,
								additional_feedback: message
							};

							var meta = {};

							return doSend(data);
						}
					},

				]).then(response => {

					if (response.hasOwnProperty('value')) {

						Swal.fire({
							type: 'success',
							width: <?php echo esc_js( $params['width'] ); ?>,
							title: '<?php echo esc_attr__( 'Thank You!', $this->plugin ); ?>',
							showConfirmButton: false,
							position: '<?php echo esc_js( $params['position'] ); ?>',
							timer: 1500,
							animation: false
						});

					}
				});

				jQuery(document).on('click', '.other_reason_option', function(){
					var $overlay = $('#ig-general-feedback-<?php echo esc_js( $this->plugin ); ?>');
					var $form = $overlay.find('form');
					if(jQuery(this).attr('type') === 'radio') {
						if(jQuery(this).val() === 'other') {
							$form.find('#feedback_text').removeClass('hidden');
						} else {
							$form.find('#feedback_text').addClass('hidden');
						}
					} else if(jQuery(this).attr('type') === 'checkbox') { 
						if(jQuery(this).is(':checked') && jQuery(this).val() === 'other') {
							$form.find('#feedback_text').removeClass('hidden');
						} else if( !jQuery(this).is(':checked') && jQuery(this).val() === 'other' ) {
							$form.find('#feedback_text').addClass('hidden');
						}
					}
				});
			});

			</script>
			<?php
		}

		public function render_multiple_poll_field( $field ) {
			$field_type = $field['type'];
			$field_slug = $field['slug'];
			$field_placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
			$field_required    = isset ( $field['required'] ) ? $field['required'] : false;
			$field_question    = ! empty( $field['question'] ) ? $field['question'] : '';
			?>
			<p><b><?php echo esc_html($field_question); ?></b></p>
			<?php
			switch ( $field_type ) {
				case 'textarea':
					?>
						<p class="ig-feedback-data-poll-message mb-3" id="ig-feedback-data-poll-message">
							<textarea name="feedback_data[poll_options][<?php echo esc_attr( $field_slug ); ?>]" data-slug="<?php echo esc_attr( $field_slug ); ?>" placeholder="<?php echo esc_attr( $field_placeholder ); ?>" <?php echo $field_required ? 'required' : ''; ?>></textarea>
						</p>
					<?php
					break;
				case 'radio':
				case 'checkbox':
					foreach ($field['options'] as $key => $value) {
						?>
						<label>
						<input type="<?php echo esc_attr( $field['type'] ); ?>" name="feedback_data[poll_options][<?php echo esc_attr( $field_slug ); ?>]" data-slug="<?php echo esc_attr( $field_slug ); ?>" value="<?php echo esc_attr( $key ); ?>" class="<?php echo !empty($field['additional']) ? esc_attr( 'other_reason_option' ) : ''; ?>" <?php echo $field_required ? esc_attr( 'required' ) : ''; ?>><span><?php echo esc_html( $value ); ?></span>
						</label>
						<br/>
						<?php
						if ( 'other' === $key ) {
							?>
							<div class="ig_feedback_text_wrapper">
								<textarea 
									id="feedback_text"
									name="feedback_data[poll_options][<?php echo esc_attr( $field_slug ); ?>]"
									class="form-textarea text-sm w-2/3 mt-3 mb-1 border-gray-400 w-3/5 hidden"
									placeholder="<?php echo esc_attr__( 'Tell us more about your desired feature', $this->plugin ); ?>"></textarea>
							</div>
							<?php
						}
					}
					break;
				default:
					?>
					<label>
							<input 
								type="<?php echo esc_attr( $field_type ); ?>" 
								name="feedback_data[poll_options][<?php echo esc_attr( $field_slug ); ?>]" 
								value="<?php echo esc_attr( $field['value'] ); ?>"
								class="<?php echo esc_attr( $this->plugin_abbr ); ?>-feedback-field" <?php echo $field_required ? 'required' : ''; ?>>
						<?php echo wp_kses_post( $field['label'] ); ?>
					</label>
					<br/>
					<?php
			}
		}
		
		public function render_single_poll_field( $field ) {
			$field_type = $field['type'];
			$field_name = $field['name'];
			$field_placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
			$field_value       = ! empty( $field['value'] ) ? $field['value'] : '';
			$field_required    = isset ( $field['required'] ) ? $field['required'] : false;
			$field_question      = ! empty( $field['question'] ) ? $field['question'] : '';
			?>
			<p><lable><?php echo esc_html($field_question); ?></label></p>
			<?php
			switch ( $field_type ) {
				case 'textarea':
					?>
						<p class="ig-feedback-data-poll-message mb-3" id="ig-feedback-data-poll-message">
							<textarea name="feedback_data[<?php echo esc_attr( $field_name ); ?>]" id="ig-feedback-data-poll-additional-message" placeholder="<?php echo esc_attr( $field_placeholder ); ?>"></textarea>
						</p>
						<br/>
					<?php
					break;
				case 'radio':
				case 'checkbox':
					?>
					<label>
							<input 
								type="<?php echo esc_attr( $field_type ); ?>" 
								name="feedback_data[<?php echo esc_attr( $field_name ); ?>]" 
								value="<?php echo esc_attr( $field_value ); ?>"
								class="<?php echo esc_attr( $this->plugin_abbr ); ?>-feedback-field"
								<?php echo $field_required ? 'required' : ''; ?>>
						<?php echo wp_kses_post( $field['label'] ); ?>
					</label>
					<br/>
					<?php
					break;
				default:
					?>
					<label>
							<input 
								type="<?php echo esc_attr( $field_type ); ?>" 
								name="feedback_data[<?php echo esc_attr( $field_name ); ?>]" 
								value="<?php echo esc_attr( $field['value'] ); ?>"
								class="<?php echo esc_attr( $this->plugin_abbr ); ?>-feedback-field">
						<?php echo wp_kses_post( $field['label'] ); ?>
					</label>
					<br/>
					<?php
			}
		}
		
		/**
		 * Get Feedback API url
		 *
		 * @param $is_dev_mode
		 *
		 * @return string
		 *
		 * @since 1.0.1
		 */
		public function get_api_url( $is_dev_mode ) {

			if ( $is_dev_mode ) {
				$this->api_url = 'http://localhost:9094/store/feedback/';
			}

			return $this->api_url;
		}

		/**
		 * Deactivation Survey javascript.
		 *
		 * @since 1.0.0
		 */
		public function js() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}

			$title = 'Why are you deactivating ' . $this->name . '?';
			$slug  = sanitize_title( $title );
			$event = $this->event_prefix . 'plugin.deactivation';
			$plugin_js = $this->plugin;

			?>
			<script type="text/javascript">
			jQuery(function($){
				var pluginSlug  = '<?php echo esc_js( $plugin_js ); ?>';
				var $deactivateLink = $('#the-list').find('[data-slug="' + pluginSlug + '"] span.deactivate a');
				var $modal      = $('#ig-deactivation-modal-' + pluginSlug);
				var $screen1    = $modal.find('.ig-deactivation-screen1');
				var $screen2       = $modal.find('.ig-deactivation-screen2');
				var $s2Label       = $('#ig-deactivation-s2-label-' + pluginSlug);
				var $s2Achievement = $('#ig-deactivation-s2-achievement-' + pluginSlug);
				var $s2Content     = $('#ig-deactivation-s2-content-' + pluginSlug);
				var $s2Footer      = $('#ig-deactivation-s2-footer-' + pluginSlug);
				var selectedSlug = '';
				var selectedTitle = '';

				// Follow-up config provided by the plugin via filter.
				var followups = <?php echo wp_json_encode( apply_filters( $this->plugin_abbr . '_deactivation_followups', array() ) ); ?>;

				function resetModal() {
					$screen2.hide();
					$screen1.show();
					$s2Achievement.empty();
					$s2Content.empty();
					$s2Footer.empty();
					$s2Label.text('');
					selectedSlug  = '';
					selectedTitle = '';
					$modal.find('.ig-deactivation-option').removeClass('selected');
					$modal.find('input[type=radio]').prop('checked', false);
				}

				function deactivate() {
					var href = $deactivateLink.attr('href');
					location.href = href + '&survey_status=skipped';
				}

				function submitAndDeactivate( extraMeta, skipRedirect = false ) {
					var detail = $s2Content.find('.ig-deactivation-textarea').val() || '';
					var subVal = $s2Content.find('input[type=radio]:checked').val() || '';
					var details = subVal ? subVal + (detail ? ': ' + detail : '') : detail;

					var metaInfo = $.extend({}, extraMeta || {});

					var data = {
						action: '<?php echo esc_js( $this->ajax_action ); ?>',
						feedback: {
							type: 'radio',
							title: '<?php echo esc_js( $title ); ?>',
							slug: '<?php echo esc_js( $slug ); ?>',
							value: selectedSlug,
							details: details,
							sub_option: subVal,        // key stays snake_case: PHP reads this
							reason_title: selectedTitle // key stays snake_case: PHP reads this
						},
						event: '<?php echo esc_js( $event ); ?>',
						misc: {
							plugin: '<?php echo esc_js( $this->plugin ); ?>',
							plugin_abbr: '<?php echo esc_js( $this->plugin_abbr ); ?>',
							is_dev_mode: '<?php echo esc_js( $this->is_dev_mode ); ?>',
							set_cookie: '',
							meta_info: metaInfo,        // key stays snake_case: PHP reads this
							system_info: false
						},
						security: '<?php echo esc_js( wp_create_nonce( $this->plugin_abbr . '-admin-ajax-nonce' ) ); ?>'
					};
					$.post(ajaxurl, data).always(function(){
						if ( ! skipRedirect ) {
							var href = $deactivateLink.attr('href');
							href += '&feedback_provided=1';
							location.href = href;
						}
					});
				}

				function buildScreen2(slug) {
					var cfg = followups[slug];
					if (!cfg) { deactivate(); return; }
					var html = '';

					// Achievement card goes into its own dedicated div (above the label).
					if (cfg.achievement_message) {
						$s2Achievement.html(cfg.achievement_message);
					} else {
						$s2Achievement.empty();
					}

					if (cfg.heading) {
						html += '<h3 class="ig-deactivation-s2-heading">' + cfg.heading + '</h3>';
					}
					if (cfg.body) {
						html += '<p class="ig-deactivation-s2-body">' + cfg.body.replace(/\n/g,'<br>') + '</p>';
					}					
					if (cfg.sub_options && cfg.sub_options.length) {
						html += '<ul class="ig-deactivation-sub-options">';
						$.each(cfg.sub_options, function(i, opt){
							var optLabel = (typeof opt === 'object' && opt !== null) ? opt.label : opt;
							var optValue = (typeof opt === 'object' && opt !== null) ? (opt.value || opt.label) : opt;
							var safeVal  = optValue ? String(optValue).replace(/"/g, '&quot;') : '';
							html += '<li class="ig-deactivation-sub-option" data-value="' + safeVal + '"><input type="radio" name="ig_deactivation_sub" value="' + safeVal + '" id="ig-deactivation-sub-' + i + '"><label for="ig-deactivation-sub-' + i + '">' + optLabel + '</label></li>';
						});
						html += '</ul>';
						html += '<div class="ig-deactivation-sub-option-notice-container" id="ig-deactivation-sub-option-notice-' + pluginSlug + '" style="display:none;"></div>';
					}
					if (cfg.has_textarea) {
						html += '<textarea class="ig-deactivation-textarea" placeholder="' + (cfg.textarea_placeholder || '') + '"></textarea>';
					}
					if (cfg.body2) {
						html += '<p class="ig-deactivation-s2-body">' + cfg.body2 + '</p>';
					}
					$s2Content.html(html);

					function updateSubOptionNotice(selectedVal) {
						var matchingNotice = '';
						if (cfg.sub_options && cfg.sub_options.length) {
							$.each(cfg.sub_options, function(i, opt){
								if (typeof opt === 'object' && opt !== null) {
									var optVal = opt.value || opt.label;
									if (optVal === selectedVal && opt.notice) {
										matchingNotice = opt.notice;
									}
								}
							});
						}
						var $noticeContainer = $s2Content.find('.ig-deactivation-sub-option-notice-container');
						if (matchingNotice) {
							$noticeContainer.html(matchingNotice).show();
						} else {
							$noticeContainer.empty().hide();
						}
					}

					// Sub-option click handler
					$s2Content.find('.ig-deactivation-sub-option').on('click', function(){
						var $row = $(this);
						$row.find('input[type=radio]').prop('checked', true);
						$s2Content.find('.ig-deactivation-sub-option').removeClass('selected');
						$row.addClass('selected');
						updateSubOptionNotice($row.find('input[type=radio]').val());
					});

					$s2Content.find('input[name="ig_deactivation_sub"]').on('change', function(){
						var $radio = $(this);
						var $row = $radio.closest('.ig-deactivation-sub-option');
						$s2Content.find('.ig-deactivation-sub-option').removeClass('selected');
						$row.addClass('selected');
						updateSubOptionNotice($radio.val());
					});

					// Build footer buttons
					var footerHtml = '';
					$.each(cfg.buttons, function(i, btn){
						var extraData = '';
						if ( btn.skip_deactivation ) {  // reading PHP config key — stays snake_case
							extraData += ' data-skip-deactivation="1"';
						}
						if ( btn.success_message ) {    // reading PHP config key — stays snake_case
							extraData += ' data-success-message="' + btn.success_message + '"';
						}
						
						if (btn.action === 'url') {
							footerHtml += '<a href="' + btn.url + '" target="_blank" class="' + btn.cls + ' ig-deactivation-url-redirect">' + btn.label + '</a>';
						} else if (btn.action === 'submit') {
							footerHtml += '<button type="button" class="' + btn.cls + ' ig-deactivation-submit-deactivate"' + extraData + '>' + btn.label + '</button>';
						} else {
							footerHtml += '<button type="button" class="' + btn.cls + ' ig-deactivation-deactivate-only">' + btn.label + '</button>';
						}
					});
					$s2Footer.html(footerHtml);

					$s2Footer.find('.ig-deactivation-url-redirect').on('click', function(){
						resetModal();
						$modal.removeClass('is-open');
					});

					$s2Footer.find('.ig-deactivation-submit-deactivate').on('click', function(){
						var $btn = $(this);
						var extraMeta = {};
						var skipDeactivation = $btn.data('skip-deactivation');
						if ( skipDeactivation ) {
							extraMeta.skip_deactivation  = 1; // property stays snake_case: PHP reads this
							extraMeta.send_support_email = 1; // property stays snake_case: ETP reads this
							var successMsg = $btn.data('success-message') || 'Success!';
							$s2Content.html('<div class="ig-deactivation-success-msg">' + successMsg + '</div>');
							$s2Footer.empty();
							setTimeout( function() {
								resetModal();
								$modal.removeClass('is-open');
							}, 5000 );
						}

						submitAndDeactivate( extraMeta, skipDeactivation );
					});
					$s2Footer.find('.ig-deactivation-deactivate-only').on('click', deactivate);
				}

				// Open modal on deactivate link click — always reset to Screen 1 first
				$deactivateLink.on('click', function(e){
					e.preventDefault();
					resetModal();
					$modal.addClass('is-open');
				});

				// Option row click → go to screen 2
				$modal.on('click', '.ig-deactivation-option', function(){
					var $row = $(this);
					$row.find('input[type=radio]').prop('checked', true);
					$modal.find('.ig-deactivation-option').removeClass('selected');
					$row.addClass('selected');
					selectedSlug  = $row.data('slug');
					selectedTitle = $row.data('title'); 
					$s2Label.text(selectedTitle);
					buildScreen2(selectedSlug);

					$screen1.hide();
					$screen2.show();
				});

				// Back button
				$modal.on('click', '.ig-deactivation-back', function(){
					$screen2.hide();
					$screen1.show();
				});

				// Screen 1 skip / deactivate
				$modal.on('click', '.ig-deactivation-skip-deactivate', deactivate);

				// Esc key closes
				$(document).on('keyup', function(e){
					if (e.keyCode === 27 && $modal.hasClass('is-open')) {
						resetModal();
						$modal.removeClass('is-open');
					}
				});


			});
			</script>
			<?php
		}

		/**
		 * Survey CSS.
		 *
		 * @since 1.0.0
		 */
		public function css() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}
			?>
			<style type="text/css">
				.ig-deactivation-modal {
					display: none;
					position: fixed;
					z-index: 999999;
					top: 0; left: 0;
					width: 100%; height: 100%;
					background: rgba(0,0,0,0.75);
					align-items: center;
					justify-content: center;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
				}
				.ig-deactivation-modal.is-open { display: flex; }
				.ig-deactivation-card {
					background: #fff;
					border-radius: 12px;
					width: 540px;
					max-width: 96vw;
					max-height: 92vh;
					overflow-y: auto;
					box-shadow: 0 20px 60px rgba(0,0,0,0.3);
					padding: 32px 36px 24px;
					box-sizing: border-box;
				}
				.ig-deactivation-headline { font-size: 22px; font-weight: 700; color: #1d2327; margin: 0 0 6px 0; }
				.ig-deactivation-question { font-size: 14px; color: #50575e; margin: 0 0 20px 0; }
				.ig-deactivation-options { list-style: none; margin: 0; padding: 0; }
				.ig-deactivation-option {
					display: flex; align-items: center; gap: 12px;
					padding: 11px 14px; border: 1px solid #dde0e4; border-radius: 8px;
					margin-bottom: 8px; cursor: pointer;
					transition: border-color .15s, background .15s;
					font-size: 14px; color: #1d2327;
				}
				.ig-deactivation-option:hover, .ig-deactivation-option.selected { border-color: #2271b1; background: #f0f6fc; }
				.ig-deactivation-option input[type=radio] { accent-color: #2271b1; flex-shrink: 0; }
				.ig-deactivation-screen1-footer {
					display: flex; justify-content: flex-end; align-items: center;
					margin-top: 16px; padding-top: 12px; border-top: 1px solid #f0f0f1;
				}
				.ig-deactivation-btn-deactivate-plain {
					background: none; border: none; font-size: 13px; color: #a0a5aa;
					cursor: pointer; padding: 4px 8px; width: auto; display: inline-block;
				}
				.ig-deactivation-btn-deactivate-plain:hover { color: #646970; text-decoration: underline; }
				.ig-deactivation-screen2 { display: none; }
				.ig-deactivation-back {
					display: inline-flex; align-items: center; gap: 6px;
					font-size: 13px; color: #2271b1; cursor: pointer;
					border: none; background: none; padding: 0; margin-bottom: 18px;
				}
				.ig-deactivation-back:hover { text-decoration: underline; }
				.ig-deactivation-s2-selected-label {
					font-size: 12px; font-weight: 600; text-transform: uppercase;
					letter-spacing: .5px; color: #8c8f94; margin-bottom: 4px;
				}
				.ig-deactivation-s2-heading { font-size: 17px; font-weight: 700; color: #1d2327; margin: 0 0 8px 0; }
				.ig-deactivation-s2-body { font-size: 13px; color: #50575e; margin: 0 0 14px 0; line-height: 1.6; }
				.ig-deactivation-sub-options { list-style: none; margin: 0 0 14px; padding: 0; }
				.ig-deactivation-sub-option {
					display: flex; align-items: center; gap: 10px;
					padding: 9px 12px; border: 1px solid #dde0e4; border-radius: 6px;
					margin-bottom: 6px; cursor: pointer; font-size: 13px; color: #1d2327;
					transition: border-color .15s, background .15s;
				}
				.ig-deactivation-sub-option:hover, .ig-deactivation-sub-option.selected { border-color: #2271b1; background: #f0f6fc; }
				.ig-deactivation-sub-option input[type=radio] { accent-color: #2271b1; flex-shrink: 0; }
				.ig-deactivation-textarea {
					width: 100%; min-height: 72px; resize: vertical;
					padding: 8px 10px; border: 1px solid #c3c4c7; border-radius: 6px;
					font-size: 13px; font-family: inherit; color: #1d2327;
					box-sizing: border-box; margin-bottom: 14px; display: block;
				}
				.ig-deactivation-textarea:focus { border-color: #2271b1; outline: none; }
				.ig-deactivation-success-msg {
					background: #f0f6fc; border-left: 4px solid #2271b1;
					padding: 12px 16px; font-size: 14px; color: #1d2327;
					margin-bottom: 20px; border-radius: 4px; line-height: 1.5;
				}

				.ig-deactivation-s2-footer {
					display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
					padding-top: 16px; border-top: 1px solid #eee; margin-top: 4px;
				}
				.ig-deactivation-btn-primary {
					background: #2271b1; color: #fff; border: none; border-radius: 6px;
					padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
					text-decoration: none; display: inline-block;
				}
				.ig-deactivation-btn-primary:hover { background: #135e96; color: #fff; }
				.ig-deactivation-btn-secondary {
					background: #fff; color: #2271b1; border: 1px solid #2271b1;
					border-radius: 6px; padding: 8px 16px; font-size: 13px; font-weight: 600;
					cursor: pointer; text-decoration: none; display: inline-block;
				}
				.ig-deactivation-btn-secondary:hover { background: #f0f6fc; }
				.ig-deactivation-btn-ghost {
					background: none; border: none; font-size: 13px; color: #a0a5aa;
					cursor: pointer; padding: 0; margin-left: auto; text-decoration: none;
				}
				.ig-deactivation-btn-ghost:hover { color: #646970; text-decoration: underline; }
				.ig-deactivation-thank-you { font-size: 12px; color: #8c8f94; text-align: center; margin-top: 14px; }
				.ig-deactivation-loader { display: none; vertical-align: middle; width: 20px; height: 20px; }
			</style>
			<?php
		}

		/**
		 * Survey modal.
		 *
		 * @since 1.0.0
		 */
		public function modal() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}

			$options = apply_filters( $this->plugin_abbr . '_deactivation_reasons', array() );

			$plugin_js = $this->plugin;
			?>
			<div class="ig-deactivation-modal" id="ig-deactivation-modal-<?php echo esc_attr( $plugin_js ); ?>">
				<div class="ig-deactivation-card">

					<!-- Screen 1: Options -->
					<div class="ig-deactivation-screen1">
						<h2 class="ig-deactivation-headline"><?php echo wp_kses_post( apply_filters( $this->plugin_abbr . '_deactivation_headline', '' ) ); ?></h2>
						<p class="ig-deactivation-question"><?php echo wp_kses_post( apply_filters( $this->plugin_abbr . '_deactivation_question', '' ) ); ?></p>
						<ul class="ig-deactivation-options">
							<?php foreach ( $options as $idx => $option ) : ?>
							<li class="ig-deactivation-option" data-slug="<?php echo esc_attr( $option['slug'] ); ?>" data-title="<?php echo esc_attr( $option['title'] ); ?>">
								<input type="radio" name="ig_deactivation_reason" value="<?php echo esc_attr( $option['slug'] ); ?>" id="ig-deactivation-opt-<?php echo esc_attr( $this->plugin . '-' . $idx ); ?>">
								<label for="ig-deactivation-opt-<?php echo esc_attr( $this->plugin . '-' . $idx ); ?>"><?php echo esc_html( $option['title'] ); ?></label>
							</li>
							<?php endforeach; ?>
						</ul>
						<div class="ig-deactivation-screen1-footer">
							<button type="button" class="ig-deactivation-btn-deactivate-plain ig-deactivation-skip-deactivate"><?php esc_html_e( 'Skip &amp; Deactivate', $this->plugin ); ?></button>
						</div>
					</div>

					<!-- Screen 2: Follow-up (populated by JS) -->
					<div class="ig-deactivation-screen2" id="ig-deactivation-screen2-<?php echo esc_attr( $plugin_js ); ?>">
						<button type="button" class="ig-deactivation-back ig-deactivation-back">&#8592; Back</button>
						<div id="ig-deactivation-s2-achievement-<?php echo esc_attr( $plugin_js ); ?>"></div>
						<div class="ig-deactivation-s2-selected-label" id="ig-deactivation-s2-label-<?php echo esc_attr( $plugin_js ); ?>"></div>
						<div id="ig-deactivation-s2-content-<?php echo esc_attr( $plugin_js ); ?>"></div>
						<div class="ig-deactivation-s2-footer" id="ig-deactivation-s2-footer-<?php echo esc_attr( $plugin_js ); ?>"></div>
						<p class="ig-deactivation-thank-you"><?php echo wp_kses_post( apply_filters( $this->plugin_abbr . '_deactivation_thankyou', '' ) ); ?></p>
					</div>

				</div>
			</div>
			<?php
		}

		/**
		 * Can we show feedback widget in this environment
		 *
		 * @return bool
		 */
		public function can_show_feedback_widget() {

			// Is development mode? Enable it.
			if ( $this->is_dev_mode ) {
				return true;
			}

			// Don't show on dev setup if dev mode is off.
			if ( $this->is_dev_url() ) {
				return false;
			}

			return true;
		}

		/**
		 * Checks if current admin screen is the plugins page.
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public function is_plugin_page() {

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
			if ( empty( $screen ) ) {
				return false;
			}

			return ( ! empty( $screen->id ) && in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) );
		}


		/**
		 * Checks if current site is a development one.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public function is_dev_url() {

			$url          = network_site_url( '/' );
			$is_local_url = false;

			// Trim it up
			$url = strtolower( trim( $url ) );

			// Need to get the host...so let's add the scheme so we can use parse_url
			if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
				$url = 'http://' . $url;
			}

			$url_parts = parse_url( $url );
			$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

			// Discard our development environment
			if ( '192.168.0.112' === $host ) {
				return false;
			}

			if ( ! empty( $url ) && ! empty( $host ) ) {
				if ( false !== ip2long( $host ) ) {
					if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						$is_local_url = true;
					}
				} elseif ( 'localhost' === $host ) {
					$is_local_url = true;
				}

				$tlds_to_check = array( '.dev', '.local', ':8888' );
				foreach ( $tlds_to_check as $tld ) {
					if ( false !== strpos( $host, $tld ) ) {
						$is_local_url = true;
						continue;
					}
				}
				if ( substr_count( $host, '.' ) > 1 ) {
					$subdomains_to_check = array( 'dev.', '*.staging.', 'beta.', 'test.' );
					foreach ( $subdomains_to_check as $subdomain ) {
						$subdomain = str_replace( '.', '(.)', $subdomain );
						$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );
						if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
							$is_local_url = true;
							continue;
						}
					}
				}
			}

			return $is_local_url;
		}

		/**
		 * Store plugin feedback data into option
		 *
		 * @param $plugin_abbr
		 * @param $event
		 * @param $data
		 *
		 * @since 1.0.1
		 */
		public function set_feedback_data( $plugin_abbr, $event, $data = array() ) {

			$feedback_option = $plugin_abbr . '_feedback_data';

			$feedback_data = get_option( $feedback_option, array() );

			$data['created_on'] = gmdate( 'Y-m-d H:i:s' );

			$feedback_data[ $event ][] = $data;

			update_option( $feedback_option, $feedback_data );

		}

		/**
		 * Get plugin feedback data
		 *
		 * @param $plugin_abbr
		 *
		 * @return mixed|void
		 *
		 * @since 1.0.1
		 */
		public function get_feedback_data( $plugin_abbr ) {

			$feedback_option = $plugin_abbr . '_feedback_data';

			return get_option( $feedback_option, array() );
		}

		/**
		 * Get event specific feedback data
		 *
		 * @param $plugin_abbr
		 * @param $event
		 *
		 * @return array|mixed
		 */
		public function get_event_feedback_data( $plugin_abbr, $event ) {

			$feedback_data = $this->get_feedback_data( $plugin_abbr );

			$event_feedback_data = ! empty( $feedback_data[ $event ] ) ? $feedback_data[ $event ] : array();

			return $event_feedback_data;
		}

		/**
		 * Check whether event tracked or not.
		 *
		 * @param $plugin_abbr
		 * @param $event
		 *
		 * @return bool
		 *
		 * @since 1.1.0
		 */
		public function is_event_tracked( $plugin_abbr = '', $event = '' ) {

			if ( empty( $plugin_abbr ) || empty( $event ) ) {
				return false;
			}

			$feedback_data = $this->get_feedback_data( $plugin_abbr );

			if ( count( $feedback_data ) > 0 ) {

				$events = array_keys( $feedback_data );

				foreach ( $events as $key => $value ) {
					if ( strpos( $value, $event ) ) {
						return true;
					}
				}
			}

			return false;
		}


		/**
		 * Set event into transient
		 *
		 * @param $event
		 * @param int   $expiry in days
		 */
		public function set_event_transient( $event, $expiry = 45 ) {
			set_transient( $event, 1, time() + ( 86400 * $expiry ) );
		}

		/**
		 * Check whether event transient is set or not.
		 *
		 * @param $event
		 *
		 * @return bool
		 *
		 * @since 1.0.1
		 */
		public function is_event_transient_set( $event ) {
			return get_transient( $event );
		}

		/**
		 * Get contact email
		 *
		 * @return string
		 *
		 * @since 1.0.8
		 */
		public function get_contact_email() {

			$email = '';

			// Get logged in User Email Address
			$current_user = wp_get_current_user();
			if ( $current_user instanceof WP_User ) {
				$email = $current_user->user_email;
			}

			// If email empty, get admin email
			if ( empty( $email ) ) {
				$email = get_option( 'admin_email' );
			}

			return $email;
		}

		/**
		 * AJAX handler — returns system info block for the Contact Us modal.
		 * Verifies the same nonce used by all feedback AJAX calls.
		 *
		 * @since 1.2.14
		 */
		public function handle_contact_system_info_ajax() {

			check_ajax_referer( $this->plugin_abbr . '-admin-ajax-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
				return;
			}

			wp_send_json_success( $this->get_system_info_for_feedback() );
		}

		/**
		 * Build a read-only system-info array for the Contact Us modal.
		 * Excludes secrets (API keys, passwords, full email lists).
		 *
		 * @since 1.2.14
		 * @return array
		 */
		public function get_system_info_for_feedback() {
			return apply_filters( $this->plugin_abbr . '_contact_us_system_info', array() );
		}

		/**
		 * AJAX handler for the enhanced Contact Us form submission.
		 *
		 * Validates plugin slug (allowlist) and subject.
		 * Assembles $data with contact_plugin, contact_subject, and contact_system_info,
		 * then calls wp_remote_post to the same API endpoint as submit_feedback() —
		 * without modifying submit_feedback() itself.
		 *
		 * @since 1.2.14
		 */
		public function handle_contact_us_feedback() {

			check_ajax_referer( $this->plugin_abbr . '-admin-ajax-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
				return;
			}

			/* ---- Plugin slug: allowlist ---- */
			$allowed_plugins = array(
				'email-subscribers',
				'icegram',
				'icegram-mailer',
				'icegram-rainmaker',
				'icegram-cookie-manager',
			);

			$raw_plugin  = isset( $_POST['contact_plugin'] ) ? sanitize_key( wp_unslash( $_POST['contact_plugin'] ) ) : '';
			$plugin_slug = in_array( $raw_plugin, $allowed_plugins, true ) ? $raw_plugin : 'email-subscribers';

			/* ---- Subject ---- */
			$subject = isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '';
			if ( empty( $subject ) && ! empty( $_POST['feedback']['title'] ) ) {
				$subject = sanitize_text_field( wp_unslash( $_POST['feedback']['title'] ) );
			}
			if ( empty( $subject ) ) {
				$subject = 'Contact Us';
			}
			$subject = mb_substr( $subject, 0, 100 );

			/* ---- Misc / meta — mirrors submit_feedback() ---- */
			$plugin        = ! empty( $_POST['misc']['plugin'] )      ? sanitize_text_field( wp_unslash( $_POST['misc']['plugin'] ) )      : $this->plugin;
			$plugin_abbr   = ! empty( $_POST['misc']['plugin_abbr'] ) ? sanitize_text_field( wp_unslash( $_POST['misc']['plugin_abbr'] ) ) : $this->plugin_abbr;
			$is_dev_mode   = ! empty( $_POST['misc']['is_dev_mode'] );
			$set_transient = ! empty( $_POST['misc']['set_transient'] );
			$meta_info_raw = ! empty( $_POST['misc']['meta_info'] ) && is_array( $_POST['misc']['meta_info'] )
							? $_POST['misc']['meta_info'] : array();

			$default_meta = array(
				'plugin'      => sanitize_key( $plugin ),
				'locale'      => get_locale(),
				'wp_version'  => get_bloginfo( 'version' ),
				'php_version' => PHP_VERSION,
			);

			$meta_info       = wp_parse_args( array_map( 'sanitize_text_field', wp_unslash( $meta_info_raw ) ), $default_meta );
			$include_sysinfo = isset( $_POST['include_system_info'] ) && '1' === $_POST['include_system_info'];

			$additional_info = apply_filters( $plugin_abbr . '_additional_feedback_meta_info', array(), $include_sysinfo );
			if ( is_array( $additional_info ) && count( $additional_info ) > 0 ) {
				$meta_info = array_merge( $meta_info, $additional_info );
			}

			/* ---- Assemble $data (extends the existing pattern from submit_feedback) ---- */
			$event    = ! empty( $_POST['event'] ) ? sanitize_text_field( wp_unslash( $_POST['event'] ) ) : $this->event_prefix . 'feedback';
			$feedback = ! empty( $_POST['feedback'] ) && is_array( $_POST['feedback'] )
						? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['feedback'] ) )
						: array();

			$data = array(
				'action'              => $this->ajax_action,
				'event'               => $event,
				'feedback'            => $feedback,
				'site'                => esc_url( home_url() ),
				'meta'                => $meta_info,
				'contact_plugin'      => $plugin_slug,
				'contact_subject'     => $subject,
				'contact_system_info' => $include_sysinfo ? $this->get_system_info_for_feedback() : array(),
			);

			$args = array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $data,
				'blocking'  => false,
			);

			$this->set_feedback_data( $plugin_abbr, $event, $data['feedback'] );

			if ( $set_transient ) {
				$this->set_event_transient( $event );
			}

			wp_remote_post( $this->get_api_url( $is_dev_mode ), $args );

			do_action( $this->plugin_abbr . '_contact_us_feedback_submitted', $data );

			wp_send_json_success( array( 'status' => 'success' ) );
		}

		/**
		 * Hook to ajax_action
		 *
		 * Send feedback to server
		 */
		public function submit_feedback() {

			check_ajax_referer( $this->plugin_abbr . '-admin-ajax-nonce', 'security' );

			$data = ! empty( $_POST ) ? $_POST : array();
			 
			$data['site'] = esc_url( home_url() );

			$plugin        = ! empty( $data['misc']['plugin'] ) ? $data['misc']['plugin'] : 'ig_feedback';
			$plugin_abbr   = ! empty( $data['misc']['plugin_abbr'] ) ? $data['misc']['plugin_abbr'] : 'ig_feedback';
			$is_dev_mode   = ! empty( $data['misc']['is_dev_mode'] ) ? $data['misc']['is_dev_mode'] : false;
			$set_transient = ! empty( $data['misc']['set_transient'] ) ? $data['misc']['set_transient'] : false;
			$system_info   = ( isset( $data['misc']['system_info'] ) && $data['misc']['system_info'] === 'true' ) ? true : false;
			$meta_info     = ! empty( $data['misc']['meta_info'] ) ? $data['misc']['meta_info'] : array();

			unset( $data['misc'] );		
			$default_meta_info = array(
				'plugin'      => sanitize_key( $plugin ),
				'locale'      => get_locale(),
				'wp_version'  => get_bloginfo( 'version' ),
				'php_version' => PHP_VERSION,
			);
			
			$meta_info = wp_parse_args( $meta_info, $default_meta_info );
			$additional_info = array();
			$additional_info = apply_filters( $plugin_abbr . '_additional_feedback_meta_info', $additional_info, $system_info ); // Get Additional meta information

			if ( is_array( $additional_info ) && count( $additional_info ) > 0 ) {
				$meta_info = array_merge( $meta_info, $additional_info );
			}
			
			$data['meta'] = $meta_info;
			
			$data = wp_unslash( $data );
			
			$args = array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $data,
				'blocking'  => false,
			);

			$this->set_feedback_data( $plugin_abbr, $data['event'], $data['feedback'] );

			// Set Cookie
			if ( $set_transient ) {
				$this->set_event_transient( $data['event'] );
			}

			$response         = wp_remote_post( $this->get_api_url( $is_dev_mode ), $args );
			$result['status'] = 'success';
			if ( $response instanceof WP_Error ) {
				$error_message     = $response->get_error_message();
				$result['status']  = 'error';
				$result['message'] = $error_message;
			}

			do_action( $this->plugin_abbr.'_deactivation_feedback_submitted', $data );

			die( json_encode( $result ) );
		}

		/**
		 * Get list of tags allowed in escaping
		 * 
		 * @return array $ig_es_escape_allowed_tags
		 */
		public function get_escape_allowed_tags() {
			$ig_es_escape_allowed_tags = apply_filters( $this->plugin_abbr . '_escape_allowed_tags', array() );
			return $ig_es_escape_allowed_tags;
		}

		/**
		 * Render Quick Feedback Widget
		 *
		 * @param $params
		 *
		 * @since 4.1.0
		 */
		public function render_feedback_widget( $params ) {

			if ( ! $this->can_show_feedback_widget() ) {
				return;
			}

			$default_params = array(
				'set_transient' => true,
				'force'         => false,
				'show_once'     => false,
			);

			$params = wp_parse_args( $params, $default_params );

			if ( ! empty( $params['event'] ) ) {

				$event = $this->event_prefix . $params['event'];
				$force = ! empty( $params['force'] ) ? $params['force'] : false;

				$can_show = false;

				if ( $force ) {
					$can_show = true;
				} else {
					if ( ! $this->is_event_transient_set( $event ) ) {
						$can_show = true;

						$feedback_data = $this->get_event_feedback_data( $this->plugin_abbr, $event );
						if ( count( $feedback_data ) > 0 ) {
							$show_once              = $params['show_once'];
							$feedback_data          = array_reverse( $feedback_data );
							$last_feedback_given_on = $feedback_data[0]['created_on'];

							// If event feedback given within 45 days or show event only once?
							// Don't show now
							if ( $show_once || ( strtotime( $last_feedback_given_on ) > strtotime( '-45 days' ) ) ) {
								$can_show = false;
							}
						}
					}
				}

				if ( $can_show ) {
					if ( 'star' === $params['type'] ) {
						$this->render_stars( $params );
					} elseif ( 'emoji' === $params['type'] ) {
						$this->render_emoji( $params );
					} elseif ( 'feedback' === $params['type'] ) {
						$this->render_general_feedback( $params );
					} elseif ( 'fb' === $params['type'] ) {
						/**
						 * We are not calling home for this event and we want to show
						 * this Widget only once. So, we are storing feedback data now.
						 */
						$this->set_feedback_data( $this->plugin_abbr, $event );
						$this->render_fb_widget( $params );
					} elseif ( 'poll' === $params['type'] ) {
						if ( $params['show_once'] ) {
							$this->set_feedback_data( $this->plugin_abbr, $event );
						}
						$this->render_poll_widget( $params );
					}
				}
			}

		}
	}
} // End if().
