<?php

if ( ! class_exists( 'ES_Settings_Controller' ) ) {

	/**
	 * Class to handle dashboard operation
	 * 
	 * @class ES_Settings_Controller
	 */
	class ES_Settings_Controller {

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

		public static function save_settings( $options = array() ) {
			$options = apply_filters( 'ig_es_before_save_settings', $options );
		
			$options = self::set_default_settings( $options );
			$options = self::set_trial_based_settings( $options );
			
			do_action( 'ig_es_before_settings_save', $options );
		
			self::sanitize_and_save_options( $options );
		
			do_action( 'ig_es_after_settings_save', $options );
		}
		
		private static function set_default_settings( $options ) {
			$defaults = array(
				'ig_es_disable_wp_cron'             => 'no',
				'ig_es_ess_branding_enabled'        => 'no',
				'ig_es_track_email_opens'           => 'no',
				'ig_es_enable_ajax_form_submission' => 'no',
				'ig_es_enable_welcome_email'        => 'no',
				'ig_es_notify_admin'                => 'no',
				'ig_es_enable_cron_admin_email'     => 'no',
				'ig_es_delete_plugin_data'          => 'no',
				'ig_es_run_cron_on'                 => 'monday',
				'ig_es_run_cron_time'               => '4pm',
				'ig_es_allow_api'                   => 'no',
				'ig_es_powered_by'                  => 'no',
			);
		
			foreach ( $defaults as $key => $default ) {
				$options[ $key ] = isset( $options[ $key ] ) ? $options[ $key ] : $default;
			}
		
			return $options;
		}
		
		private static function set_trial_based_settings( $options ) {
			if ( ! ES()->is_premium() && ! ES()->trial->is_trial_valid() ) {
				$options['ig_es_allow_tracking'] = isset( $options['ig_es_allow_tracking'] ) ? $options['ig_es_allow_tracking'] : 'no';
			}
			return $options;
		}
		
		private static function sanitize_and_save_options( $options ) {
			$text_fields = array(
				'ig_es_from_name',
				'ig_es_admin_emails',
				'ig_es_email_type',
				'ig_es_optin_type',
				'ig_es_post_image_size',
				'ig_es_track_email_opens',
				'ig_es_enable_ajax_form_submission',
				'ig_es_enable_welcome_email',
				'ig_es_welcome_email_subject',
				'ig_es_confirmation_mail_subject',
				'ig_es_notify_admin',
				'ig_es_admin_new_contact_email_subject',
				'ig_es_enable_cron_admin_email',
				'ig_es_cron_admin_email_subject',
				'ig_es_cronurl',
				'ig_es_hourly_email_send_limit',
				'ig_es_disable_wp_cron',
				'ig_es_allow_api',
			);
		
			$textarea_fields = array(
				'ig_es_unsubscribe_link_content',
				'ig_es_subscription_success_message',
				'ig_es_subscription_error_messsage',
				'ig_es_unsubscribe_success_message',
				'ig_es_unsubscribe_error_message',
				'ig_es_welcome_email_content',
				'ig_es_confirmation_mail_content',
				'ig_es_admin_new_contact_email_content',
				'ig_es_cron_admin_email',
				'ig_es_blocked_domains',
				'ig_es_form_submission_success_message',
			);
		
			$email_fields = array(
				'ig_es_from_email',
			);
		
			foreach ( $options as $key => $value ) {
				if ( strpos( $key, 'ig_es_' ) !== 0 ) {
					continue;
				}
		
				$value = stripslashes_deep( $value );
		
				if ( in_array( $key, $text_fields, true ) ) {
					$value = sanitize_text_field( $value );
				} elseif ( in_array( $key, $textarea_fields, true ) ) {
					$value = wp_kses_post( $value );
				} elseif ( in_array( $key, $email_fields, true ) ) {
					$value = sanitize_email( $value );
				}
		
				update_option( $key, wp_unslash( $value ), false );
			}
		}
		
		public static function get_registered_settings() {

			$from_email_description  = __( 'The "from" email address for all emails.', 'email-subscribers' );
	
			$from_email              = get_option( 'ig_es_from_email' );
			$from_email_description .= '<br/>' . self::get_from_email_notice( $from_email );
			$general_settings = array(
	
				'sender_information'                    => array(
					'id'         => 'sender_information',
					'name'       => __( 'Sender', 'email-subscribers' ),
					'sub_fields' => array(
						'from_name'  => array(
							'id'          => 'ig_es_from_name',
							'name'        => __( 'Name', 'email-subscribers' ),
							'desc'        => __( 'The "from" name people will see when they receive emails.', 'email-subscribers' ),
							'type'        => 'text',
							'placeholder' => __( 'Name', 'email-subscribers' ),
							'default'     => '',
						),
	
						'from_email' => array(
							'id'          => 'ig_es_from_email',
							'name'        => __( 'Email', 'email-subscribers' ),
							'desc'        => $from_email_description,
							'type'        => 'text',
							'placeholder' => __( 'Email Address', 'email-subscribers' ),
							'default'     => '',
						),
					),
				),
	
				'admin_email'                           => array(
					'id'      => 'ig_es_admin_emails',
					'name'    => __( 'Admin emails', 'email-subscribers' ),
					'info'    => __( 'Who should be notified about system events like "someone subscribed", "campaign sent" etc?', 'email-subscribers' ),
					'type'    => 'text',
					'desc'    => __( 'You can enter multiple email addresses - separate them with comma', 'email-subscribers' ),
					'default' => '',
				),
	
				'ig_es_optin_type'                      => array(
					'id'      => 'ig_es_optin_type',
					'name'    => __( 'Opt-in type', 'email-subscribers' ),
					'info'    => '',
					'desc'    => __( 'Single = confirm subscribers as they subscribe.<br> Double = send a confirmation email and require clicking on a link to confirm subscription.', 'email-subscribers' ),
					'type'    => 'select',
					'options' => ES_Common::get_optin_types(),
					'default' => '',
				),
	
				// Start-IG-Code.
				'ig_es_post_image_size'                 => array(
					'id'      => 'ig_es_post_image_size',
					'name'    => __( 'Image size', 'email-subscribers' ),
					'info'    => __( 'Image to use in Post Notification emails' ),
					'type'    => 'select',
					'options' => ES_Common::get_registered_image_sizes(),
					/* translators: %s: Keyword */
					'desc'    => sprintf( __( '%s keyword will use this image size. Use full size only if your template design needs it. Thumbnail should work well otherwise.', 'email-subscribers' ), '{{POSTIMAGE}}' ),
					'default' => 'full',
				),
				// End-IG-Code.
	
				'ig_es_enable_ajax_form_submission'     => array(
					'id'      => 'ig_es_enable_ajax_form_submission',
					'name'    => __( 'Enable AJAX subscription form submission', 'email-subscribers' ),
					'info'    => __( 'Enabling this will let users to submit their subscription form without page reload using AJAX call.', 'email-subscribers' ),
					'type'    => 'checkbox',
					'default' => 'yes',
				),
	
				'ig_es_track_email_opens'               => array(
					'id'      => 'ig_es_track_email_opens',
					'name'    => __( 'Track opens', 'email-subscribers' ),
					'info'    => __( 'Do you want to track when people view your emails? (We recommend keeping it enabled)', 'email-subscribers' ),
					'type'    => 'checkbox',
					'default' => 'yes',
				),
	
				'ig_es_form_submission_success_message' => array(
					'type'         => 'textarea',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_form_submission_success_message',
					'name'         => __( 'Subscription success message', 'email-subscribers' ),
					'info'         => __( 'This message will show when a visitor successfully subscribes using the form.', 'email-subscribers' ),
					'desc'         => '',
				),
	
				'ig_es_unsubscribe_link_content'        => array(
					'type'         => 'textarea',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_unsubscribe_link_content',
					'name'         => __( 'Unsubscribe text in email footer:', 'email-subscribers' ),
					'info'         => __( 'All emails will include this text in the footer so people can unsubscribe if they want.', 'email-subscribers' ),
					/* translators: %s: List of Keywords */
					'desc'         => sprintf( __( 'Use %s keyword to add unsubscribe link.', 'email-subscribers' ), '{{UNSUBSCRIBE-LINK}}' ),
				),
	
				'subscription_messages'                 => array(
					'id'         => 'subscription_messages',
					'name'       => __( 'Double opt-in subscription messages:', 'email-subscribers' ),
					'info'       => __( 'Page and messages to show when people click on the link in a subscription confirmation email.', 'email-subscribers' ),
					'sub_fields' => array(
						'ig_es_subscription_success_message' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'You have been subscribed successfully!', 'email-subscribers' ),
							'id'           => 'ig_es_subscription_success_message',
							'name'         => __( 'Message on successful subscription', 'email-subscribers' ),
							'desc'         => __( 'Show this message if contact is successfully subscribed from double opt-in (confirmation) email', 'email-subscribers' ),
						),
	
						'ig_es_subscription_error_messsage'  => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Oops.. Your request couldn\'t be completed. This email address seems to be already subscribed / blocked.', 'email-subscribers' ),
							'id'           => 'ig_es_subscription_error_messsage',
							'name'         => __( 'Message when subscription fails', 'email-subscribers' ),
							'desc'         => __( 'Show this message if any error occured after clicking confirmation link from double opt-in (confirmation) email.', 'email-subscribers' ),
						),
	
					),
				),
	
				'unsubscription_messages'               => array(
					'id'         => 'unsubscription_messages',
					'name'       => __( 'Unsubscribe messages', 'email-subscribers' ),
					'info'       => __( 'Page and messages to show when people click on the unsubscribe link in an email\'s footer.', 'email-subscribers' ),
					'sub_fields' => array(
	
						'ig_es_unsubscribe_success_message' => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Thank You, You have been successfully unsubscribed. You will no longer hear from us.', 'email-subscribers' ),
							'id'           => 'ig_es_unsubscribe_success_message',
							'name'         => __( 'Message on unsubscribe success', 'email-subscribers' ),
							'desc'         => __( 'Once contact clicks on unsubscribe link, he/she will be redirected to a page where this message will be shown.', 'email-subscribers' ),
						),
	
						'ig_es_unsubscribe_error_message'   => array(
							'type'         => 'textarea',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => __( 'Oops.. There was some technical error. Please try again later or contact us.', 'email-subscribers' ),
							'id'           => 'ig_es_unsubscribe_error_message',
							'name'         => __( 'Message when unsubscribe fails', 'email-subscribers' ),
							'desc'         => __( 'Show this message if any error occured after clicking on unsubscribe link.', 'email-subscribers' ),
						),
					),
				),
	
				// Start-IG-Code.
				'ig_es_powered_by'                      => array(
					'id'      => 'ig_es_powered_by',
					'name'    => __( 'Share Icegram', 'email-subscribers' ),
					'info'    => __( 'Show "Powered By" link in the unsubscription form' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				// End-IG-Code.
	
				'ig_es_delete_plugin_data'              => array(
					'id'      => 'ig_es_delete_plugin_data',
					'name'    => __( 'Delete plugin data on uninstall', 'email-subscribers' ),
					'info'    => __( 'Be careful with this! When enabled, it will remove all lists, campaigns and other data if you uninstall the plugin.', 'email-subscribers' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				
			);
	
			$general_settings = apply_filters( 'ig_es_registered_general_settings', $general_settings );
	
			$signup_confirmation_settings = array(
	
				'worflow_migration_notice' => array(
					'id'   => 'worflow_migration_notice',
					'type' => 'html',
					'html' => self::get_workflow_migration_notice_html(),
				),
			);
	
			$signup_confirmation_settings = apply_filters( 'ig_es_registered_signup_confirmation_settings', $signup_confirmation_settings );
	
			if ( ES()->trial->is_trial_valid() || ES()->is_premium() ) {
				$gmt_offset  = ig_es_get_gmt_offset( true );
				$icegram_cron_last_hit_timestamp = get_option( 'ig_es_cron_last_hit' );
				$icegram_cron_last_hit_message = '';
				if ( !empty( $icegram_cron_last_hit_timestamp['icegram_timestamp'] ) ) {
					$icegram_timestamp_with_gmt_offset = $icegram_cron_last_hit_timestamp['icegram_timestamp'] + $gmt_offset;
					$icegram_cron_last_hit_date_and_time = ES_Common::convert_timestamp_to_date( $icegram_timestamp_with_gmt_offset );
					$icegram_cron_last_hit_message = __( '<br><span class="ml-6">Cron last hit time : <b>' . $icegram_cron_last_hit_date_and_time . '</b></span>', 'email-subscribers' );
				}
			}
	
			$cron_url_setting_desc = '';
	
			if ( ES()->trial->is_trial_valid() || ES()->is_premium() ) {
				$cron_url_setting_desc = __( '<span class="es-send-success es-icon"></span> We will take care of it. You don\'t need to visit this URL manually.' . $icegram_cron_last_hit_message, 'email-subscribers' );
			} else {
				/* translators: %s: Link to Icegram documentation */
				$cron_url_setting_desc = sprintf( __( "You need to visit this URL to send email notifications. Know <a href='%s' target='_blank'>how to run this in background</a>", 'email-subscribers' ), 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page' );
			}
	
			$cron_url_setting_desc .= '<div class="mt-2.5 ml-1"><a class="hover:underline text-sm font-medium text-indigo-600" href=" ' . esc_url( 'https://www.icegram.com/documentation/how-to-configure-email-sending-in-email-subscribers?utm_source=in_app&utm_medium=setup_email_sending&utm_campaign=es_doc' ) . '" target="_blank">' . esc_html__( 'How to configure Email Sending', 'email-subscribers' ) . '→</a></div>';
	
			$pepipost_api_key_defined = ES()->is_const_defined( 'pepipost', 'api_key' );
	
			$test_email = ES_Common::get_admin_email();
	
			$total_emails_sent = ES_Common::count_sent_emails();
			$account_url       = ES()->mailer->get_current_mailer_account_url();
	
			$email_sending_settings = array(
				'ig_es_cronurl'                 => array(
					'type'         => 'text',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'readonly'     => 'readonly',
					'id'           => 'ig_es_cronurl',
					'name'         => __( 'Cron URL', 'email-subscribers' ),
					'desc'         => $cron_url_setting_desc,
				),
				'ig_es_disable_wp_cron'         => array(
					'type'         => 'checkbox',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => 'no',
					'id'           => 'ig_es_disable_wp_cron',
					'name'         => __( 'Disable Wordpress Cron', 'email-subscribers' ),
					'info'         => __( 'Enable this option if you do not want Icegram Express to use WP Cron to send emails.', 'email-subscribers' ),
				),
				'ig_es_cron_interval'           => array(
					'id'      => 'ig_es_cron_interval',
					'name'    => __( 'Send emails at most every', 'email-subscribers' ),
					'type'    => 'select',
					'options' => ES()->cron->cron_intervals(),
					'desc'    => __( 'Optional if a real cron service is used', 'email-subscribers' ),
					'default' => IG_ES_CRON_INTERVAL,
				),
	
				'ig_es_hourly_email_send_limit' => array(
					'type'         => 'number',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => 50,
					'id'           => 'ig_es_hourly_email_send_limit',
					'name'         => __( 'Maximum emails to send in an hour', 'email-subscribers' ),
					/* translators: 1. Break tag 2. ESP Account url with anchor tag 3. ESP name 4. Closing anchor tag */
					'desc'         => __( 'Total emails sent in current hour: <b>' . $total_emails_sent . '</b>' , 'email-subscribers' ) . ( $account_url ? sprintf( __( '%1$sCheck sending limit from your %2$s%3$s\'s account%4$s.', 'email-subscribers' ), '<br/>', '<a href="' . esc_url( $account_url ) . '" target="_blank">', ES()->mailer->get_current_mailer_name(), '</a>' ) : '' ),
				),
	
				'ig_es_max_email_send_at_once'  => array(
					'type'         => 'number',
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => IG_ES_MAX_EMAIL_SEND_AT_ONCE,
					'id'           => 'ig_es_max_email_send_at_once',
					'name'         => __( 'Maximum emails to send at once', 'email-subscribers' ),
					'desc'         => __( 'Maximum emails you want to send on every cron request.', 'email-subscribers' ),
				),
	
				'ig_es_test_send_email'         => array(
					'type'         => 'html',
					'html'         => self::get_test_send_email_html( $test_email ),
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_test_send_email',
					'name'         => __( 'Send test email', 'email-subscribers' ),
					'desc'         => __( 'Enter email address to send test email.', 'email-subscribers' ),
				),
	
				'ig_es_mailer_settings'         => array(
					'type'         => 'html',
					'sub_fields'   => array(
						'mailer'                  => array(
							'id'   => 'ig_es_mailer_settings[mailer]',
							'name' => __( 'Select Mailer', 'email-subscribers' ),
							'type' => 'html',
							'html' => self::mailers_html(),
							'desc' => '',
						),
						'ig_es_pepipost_api_key'  => array(
							'type'         => $pepipost_api_key_defined ? 'text' : 'password',
							'options'      => false,
							'placeholder'  => '',
							'supplemental' => '',
							'default'      => '',
							'id'           => 'ig_es_mailer_settings[pepipost][api_key]',
							'name'         => __( 'Pepipost API key', 'email-subscribers' ),
							'desc'         => $pepipost_api_key_defined ? ES()->get_const_set_message( 'pepipost', 'api_key' ) : '',
							'class'        => 'pepipost',
							'disabled'     => $pepipost_api_key_defined ? 'disabled' : '',
							'value'        => $pepipost_api_key_defined ? '******************' : '',
						),
						'ig_es_pepipost_docblock' => array(
							'type' => 'html',
							'html' => self::pepipost_doc_block(),
							'id'   => 'ig_es_pepipost_docblock',
							'name' => '',
						),
					),
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_mailer_settings',
					'name'         => __( 'Email Sender', 'email-subscribers' ),
					'info'         => '',
				),
			);
	
			$email_sending_settings = apply_filters( 'ig_es_registered_email_sending_settings', $email_sending_settings );
	
			$security_settings = array(
				'blocked_domains' => array(
					'id'      => 'ig_es_blocked_domains',
					'name'    => __( 'Blocked domain(s)', 'email-subscribers' ),
					'type'    => 'textarea',
					'info'    => __( 'Seeing spam signups from particular domains? Enter domains names (one per line) that you want to block here.', 'email-subscribers' ),
					'default' => '',
					'rows'    => 3,
				),
			);
	
			$security_settings = apply_filters( 'ig_es_registered_security_settings', $security_settings );
	
			$es_settings = array(
				'general'             => $general_settings,
				'signup_confirmation' => $signup_confirmation_settings,
				'email_sending'       => $email_sending_settings,
				'security_settings'   => $security_settings,
			);
	
			if ( ES_Common::is_rest_api_supported() ) {
	
				$rest_api_endpoint = get_rest_url( null, 'email-subscribers/v1/subscribers' );
				$rest_api_settings = array(
					'allow_api' => array(
						'id'	=> 'ig_es_allow_api',
						'name'  => __( 'Enable API', 'email-subscribers' ),
						'info'    => __( 'Enable API to add/edit/delete subscribers through third-party sites or apps.', 'email-subscribers' ),
						'type'    => 'checkbox',
						'default' => 'no',
						/* translators: REST API endpoint */
						'desc' => sprintf( __( 'URL endpoint: %s', 'email-subscribers'), '<code class="es-code">' . $rest_api_endpoint . '</code>' )
					),
					'api_key_access_section' => array(
						'id'   => 'ig_es_api_keys_section',
						'name' => __( 'API Keys', 'email-subscribers' ),
						'type' => 'html',
						'html' => self::render_rest_api_keys_section(),
					),
				);
		
				$es_settings['rest_api_settings'] = $rest_api_settings;
			}
	
			return apply_filters( 'ig_es_registered_settings', $es_settings );
		}

		public static function get_from_email_notice( $from_email ) {
			$from_email_notice = '';
	
			$from_email              = get_option( 'ig_es_from_email' );
			$is_popular_domain	     = ES_Common::is_popular_domain( $from_email );
			$from_email_notice_class = $is_popular_domain ? '' : 'hidden';
			$from_email_notice      .= '<span id="ig-es-from-email-notice" class="text-red-600 ' . $from_email_notice_class . '">' . __( 'Your emails might land in spam if you use above email address..', 'email-subscribers' );
			$site_url				 = site_url();
			$site_domain             = ES_Common::get_domain_from_url( $site_url );
			/* translators: %s: Site domain */
			$from_email_notice      .= '<br/>' . sprintf( __( 'Consider using email address matching your site domain like %s', 'email-subscribers' ), 'info@' . $site_domain ) . '</span>';
			return $from_email_notice;
		}

		/**
	 * Get HTML for workflow migration
	 *
	 * @return string
	 */
		public static function get_workflow_migration_notice_html() {
			ob_start();
			$workflow_url = admin_url( 'admin.php?page=es_workflows' );
			?>
		<style>
			#tabs-signup_confirmation .es-settings-submit-btn {
				display: none;
			}
		</style>
		<p class="pb-2 text-sm font-normal text-gray-500">
			<?php echo esc_html__( 'Now you can control all your notifications through workflows.', 'email-subscribers' ); ?>
			<?php
				/* translators: 1. Anchor start tag 2. Anchor end tag */
				echo sprintf( esc_html__( 'Click %1$shere%2$s to go to workflows.', 'email-subscribers' ), '<a href="' . esc_url( $workflow_url ) . '" class="text-indigo-600" target="_blank">', '</a>' );
			?>
		</p>
			<?php
			$html = ob_get_clean();
			return $html;
		}

		public static function get_test_send_email_html( $test_email ) {

			/* translators: %s: Spinner image path */
			$html = sprintf( '<div class="send-email-div flex"><input id="es-test-email" type="email" value=%s class="form-input"/><button type="submit" name="submit" id="es-send-test" class="primary">Send Email</button><span class="es_spinner_image_admin" id="spinner-image" style="display:none"><img src="%s" alt="Loading..."/></span></div>', $test_email, ES_PLUGIN_URL . 'lite/public/images/spinner.gif' );
			return $html;
		}

	/**
	 * Prepare Mailers Setting
	 *
	 * @return string
	 *
	 * @modify 4.3.12
	 */
		public static function mailers_html() {
			$html                     = '';
			$es_email_type            = get_option( 'ig_es_email_type', '' );
			$selected_mailer_settings = get_option( 'ig_es_mailer_settings', array() );

			$selected_mailer = '';
			if ( ! empty( $selected_mailer_settings ) && ! empty( $selected_mailer_settings['mailer'] ) ) {
				$selected_mailer = $selected_mailer_settings['mailer'];
			} else {
				$php_email_type_values = array(
				'php_html_mail',
				'php_plaintext_mail',
				'phpmail',
				);

				if ( in_array( $es_email_type, $php_email_type_values, true ) ) {
					$selected_mailer = 'phpmail';
				}
			}

			$pepipost_doc_block = '';

			$mailers = array(
			'wpmail'   => array(
				'name' => 'WP Mail',
				'logo' => ES_PLUGIN_URL . 'lite/admin/images/wpmail.png',
			),
			'phpmail'  => array(
				'name' => 'PHP mail',
				'logo' => ES_PLUGIN_URL . 'lite/admin/images/phpmail.png',
			),
			'pepipost' => array(
				'name'     => 'Pepipost',
				'logo'     => ES_PLUGIN_URL . 'lite/admin/images/pepipost.png',
				'docblock' => $pepipost_doc_block,
			),
			);

			$mailers = apply_filters( 'ig_es_mailers', $mailers );

			$selected_mailer = ( array_key_exists( $selected_mailer, $mailers ) ) ? $selected_mailer : 'wpmail';

			foreach ( $mailers as $key => $mailer ) {
				$html .= '<label class="es-mailer-label inline-flex items-center cursor-pointer" data-mailer="' . esc_attr( $key ) . '">';
				$html .= '<input type="radio" class="absolute w-0 h-0 opacity-0 es_mailer" name="ig_es_mailer_settings[mailer]" value="' . $key . '" ' . checked( $selected_mailer, $key, false ) . '></input>';

				if ( ! empty( $mailer['url'] ) ) {
					$html .= '<a href="' . $mailer['url'] . '" target="_blank">';
				}

				$html .= '<div class="mt-3 mr-4 border border-gray-200 rounded-lg shadow-md es-mailer-logo">
			<div class="border-0 es-logo-wrapper">
			<img src="' . $mailer['logo'] . '" alt="Default (none)">
			</div><p class="mb-2 inline-block">'
				. $mailer['name'] . '</p>';

				if ( ! empty( $mailer['is_premium'] ) ) {
					$plan  = isset( $mailer['plan'] ) ? $mailer['plan'] : '';
					$html .= '<span class="premium-icon ' . $plan . '"></span>';
				} elseif ( ! empty( $mailer['is_recommended'] ) ) {
					$html .= '<span class="ig-es-recommended-icon text-indigo-600 uppercase">' . __( 'Recommended', 'email-subscribers' ) . '</span>';
				}

				$html .= '</div>';

				if ( ! empty( $mailer['is_premium'] ) ) {
					$html .= '</a>';
				}

				$html .= '</label>';
			}

			return $html;
		}
		public static function pepipost_doc_block() {
			$html = '';

			$url = ES_Common::get_utm_tracking_url(
			array(
				'url'        => 'https://www.icegram.com/email-subscribers-integrates-with-pepipost',
				'utm_medium' => 'pepipost_doc',
			)
			);

			ob_start();
			do_action('ig_es_before_get_pepipost_doc_block');
			?>
		<div class="es_sub_headline ig_es_docblock ig_es_pepipost_div_wrapper pepipost">
			<ul>
				<li><a class="" href="https://app.pepipost.com/index.php/signup/icegram?fpr=icegram" target="_blank"><?php esc_html_e( 'Signup for Pepipost', 'email-subscribers' ); ?></a></li>
				<li><?php esc_html_e( 'How to find', 'email-subscribers' ); ?> <a href="https://developers.pepipost.com/api/getstarted/overview?utm_source=icegram&utm_medium=es_inapp&utm_campaign=pepipost" target="_blank"> <?php esc_html_e( 'Pepipost API key', 'email-subscribers' ); ?></a></li>
				<li><a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php esc_html_e( 'Why to choose Pepipost', 'email-subscribers' ); ?></a></li>
			</ul>
		</div>

		<?php
			do_action('ig_es_after_get_pepipost_doc_block');
			$html = ob_get_clean();

			return $html;
		}

		public static function render_rest_api_keys_section() {
			ob_start();
			$rest_api_keys = get_option('ig_es_rest_api_keys', array());
	
		
			$admin_users = get_users(array(
			'role'   => 'administrator',
			'fields' => array('ID', 'user_email', 'user_login'),
			));
			?>
		<div id="ig-es-rest-api-section">
			<table class="min-w-full rounded-lg">
				<thead>
				<tr class="bg-blue-50 text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
					<th class="px-5 py-4"><?php echo esc_html__('Key', 'email-subscribers'); ?></th>
					<th class="px-2 py-4 text-center"><?php echo esc_html__('Username', 'email-subscribers'); ?></th>
					<th class="px-2 py-4 text-center"><?php echo esc_html__('Actions', 'email-subscribers'); ?></th>
				</tr>
				</thead>
				<tbody class="bg-blue-50">
				<?php
				if (!empty($admin_users)) {
					foreach ($admin_users as $user) {
						$rest_api_keys = get_user_meta($user->ID, 'ig_es_rest_api_keys', true);
						if (!empty($rest_api_keys)) {
							foreach ($rest_api_keys as $index => $rest_api_key) {
								$key_start = substr($rest_api_key, 0, 4);
								$key_end = substr($rest_api_key, -4);
								?>
								<tr class="ig-es-rest-api-row border-b border-gray-200 text-xs leading-4 font-medium"
									data-user-id="<?php echo esc_attr($user->ID); ?>"
									data-api-index="<?php echo esc_attr($index); ?>">
									<td class="px-5 py-4 text-center"><?php echo esc_html($key_start); ?>***********<?php echo esc_html($key_end); ?></td>
									<td class="px-2 py-4 text-center"><?php echo esc_html($user->user_login); ?></td>
									<td class="px-2 py-4 text-center">
										<a class="ig-es-delete-rest-api-key inline-block" href="#">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
												 stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
												<path stroke-linecap="round" stroke-linejoin="round"
													  d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
											</svg>
										</a>
									</td>
								</tr>
								<?php
							}
						}
					}
				}
				?>
				<tr id="ig-es-no-api-keys-message" class="border-b border-gray-200 text-xs leading-4 font-medium">
					<td colspan="3" class="px-5 py-4 text-center">
						<?php echo esc_html__('No API keys found.', 'email-subscribers'); ?>
					</td>
				</tr>
				</tbody>
			</table>
			<div id="ig-es-create-new-rest-api-container" class="mt-2">
				<select id="ig-es-rest-api-user-id">
					<option value=""><?php echo esc_html__('Select user', 'email-subscribers'); ?></option>
					<?php
					foreach ($admin_users as $user) {
						?>
						<option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->user_email); ?></option>
						<?php
					}
					?>
				</select>
				<button type="button" id="ig-es-generate-rest-api-key" class="ig-es-title-button ml-2 align-middle ig-es-inline-loader secondary">
					<span>
						<?php echo esc_html__('Generate API key', 'email-subscribers'); ?>
					</span>
					<svg class="es-btn-loader animate-spin h-4 w-4 text-indigo"
						 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
								stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor"
							  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
				</button>
				<div id="response-messages" class="p-2 mt-2 hidden">
					<div class="message"></div>
				</div>
			</div>
		</div>
			<?php
			$html = ob_get_clean();
			return $html;
		}

		public static function render_settings_fields( $fields ) {
			$html  = '<table>';
			$html .= '<tbody>';
			foreach ( $fields as $key => $field ) {
				if ( ! empty( $field['name'] ) ) {
					$html .= "<tr id='" . $field['id'] . "-field-row'><th scope='row'><span>";
					$html .= $field['name'];

					if ( ! empty( $field['is_premium'] ) ) {
						$premium_plan = isset( $field['plan'] ) ? $field['plan'] : '';
						$html .= '</span><a class="ml-1" href="' . $field['link'] . '" target="_blank"><span class="premium-icon ' . $premium_plan . '"></span></a>';
					}

					// If there is help text
					if ( ! empty( $field['info'] ) ) {
						$helper = $field['info'];
						$html  .= '<br />' . sprintf( '<p>%s</p>', $helper ); // Show it
					}
					$button_html = '<tr>';

					$html .= '</th>';
				}

				$html .= '<td>';

				if ( ! empty( $field['upgrade_desc'] ) ) {
					$html .= "<div class='flex settings_upsell_div'><div class='flex-none w-2/5 upsell_switcher'>";
				}

				if ( ! empty( $field['sub_fields'] ) ) {
					$option_key = '';
					foreach ( $field['sub_fields'] as $field_key => $sub_field ) {
						if ( strpos( $sub_field['id'], '[' ) ) {
							$parts = explode( '[', $sub_field['id'] );
							if ( $option_key !== $parts[0] ) {
								$option_value = get_option( $parts[0] );
								$option_key   = $parts[0];
							}
							$sub_field['option_value'] = is_array( $option_value ) ? $option_value : '';
						}
						$class = ( ! empty( $sub_field['class'] ) ) ? $sub_field['class'] : '';
						$html .= ( reset( $field['sub_fields'] ) !== $sub_field ) ? '<p class="pt-1></p>' : '';
						$html .= '<div class="es_sub_headline ' . $class . ' pt-4" ><strong>' . $sub_field['name'] . '</strong>';
						if ( ! empty( $sub_field['tooltip_text'] ) ) {
							$tooltip_html = ES_Common::get_tooltip_html( $sub_field['tooltip_text'] );
							$html        .= $tooltip_html;
						}
						$html .= '</div>';
						$html .= self::field_callback( $sub_field, $field_key );
					}
				} else {
					$html .= self::field_callback( $field );
				}

				if ( ! empty( $field['upgrade_desc'] ) ) {
					$upsell_info = array(
					'upgrade_title'  => $field['upgrade_title'],
					'pricing_url'    => $field['link'],
					'upsell_message' => $field['upgrade_desc'],
					'cta_html'       => false,
					);
					$html       .= '</div> <div class="w-3/5 upsell_box">';
					$html       .= ES_Common::upsell_description_message_box( $upsell_info, false );
					$html       .= '</div>';
				}

				$html .= '</td></tr>';
			}

			$button_html = empty( $button_html ) ? '<tr>' : $button_html;

			$nonce_field = wp_nonce_field( 'update-settings', 'update-settings', true, false );
			$html       .= $button_html . "<td class='es-settings-submit-btn'>";
			$html       .= '<input type="hidden" name="submitted" value="submitted" />';
			$html       .= '<input type="hidden" name="submit_action" value="ig-es-save-admin-settings" />';
			$html       .= $nonce_field;
			$html       .= '<button type="submit" name="submit" class="primary">' . __( 'Save Settings', 'email-subscribers' ) . '</button>';
			$html       .= '</td></tr>';
			$html       .= '</tbody>';
			$html       .= '</table>';

			$allowedtags = ig_es_allowed_html_tags_in_esc();
			add_filter( 'safe_style_css', 'ig_es_allowed_css_style' );
			echo wp_kses( $html, $allowedtags );
		}

		public static function field_callback( $arguments, $id_key = '' ) {
			$field_html = '';
			if ( 'ig_es_cronurl' === $arguments['id'] ) {
				$value = ES()->cron->url();
			} else {
				if ( ! empty( $arguments['option_value'] ) ) {
					preg_match( '(\[.*$)', $arguments['id'], $m );
					$n     = explode( '][', $m[0] );
					$n     = str_replace( '[', '', $n );
					$n     = str_replace( ']', '', $n );
					$count = count( $n );
					$id    = '';
					foreach ( $n as $key => $val ) {
						if ( '' == $id ) {
							$id = ! empty( $arguments['option_value'][ $val ] ) ? $arguments['option_value'][ $val ] : '';
						} else {
							$id = ! empty( $id[ $val ] ) ? $id[ $val ] : '';
						}
					}
					$value = $id;
				} else {
					$value = get_option( $arguments['id'] ); // Get the current value, if there is one
				}
			}

			if ( ! $value ) { // If no value exists
				$value = ! empty( $arguments['default'] ) ? $arguments['default'] : ''; // Set to our default
			}

			$uid         = ! empty( $arguments['id'] ) ? $arguments['id'] : '';
			$type        = ! empty( $arguments['type'] ) ? $arguments['type'] : '';
			$placeholder = ! empty( $arguments['placeholder'] ) ? $arguments['placeholder'] : '';
			$readonly    = ! empty( $arguments['readonly'] ) ? $arguments['readonly'] : '';
			$html        = ! empty( $arguments['html'] ) ? $arguments['html'] : '';
			$id_key      = ! empty( $id_key ) ? $id_key : $uid;
			$class       = ! empty( $arguments['class'] ) ? $arguments['class'] : '';
			$rows        = ! empty( $arguments['rows'] ) ? $arguments['rows'] : 8;
			$disabled    = ! empty( $arguments['disabled'] ) ? 'disabled="' . $arguments['disabled'] . '"' : '';
			$value       = ! empty( $arguments['value'] ) ? $arguments['value'] : $value;

			// Check which type of field we want
			switch ( $arguments['type'] ) {
				case 'text': // If it is a text field
					$field_html = sprintf( '<input name="%1$s" id="%2$s" placeholder="%4$s" value="%5$s" %6$s class="%7$s form-input h-9 mt-2 mb-1 text-sm border-gray-400 w-3/5" %8$s/>', $uid, $id_key, $type, $placeholder, $value, $readonly, $class, $disabled );
					break;
				case 'password': // If it is a text field
					$field_html = sprintf( '<input name="%1$s" id="%2$s" type="%3$s" placeholder="%4$s" value="%5$s" %6$s class="form-input h-9 mt-2 mb-1 text-sm border-gray-400 w-3/5 %7$s" %8$s/>', $uid, $id_key, $type, $placeholder, $value, $readonly, $class, $disabled );
					break;

				case 'number': // If it is a number field
					$field_html = sprintf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" %5$s min="0" class="w-2/5 mt-2 mb-1 text-sm border-gray-400 h-9 " %6$s/>', $uid, $type, $placeholder, $value, $readonly, $disabled );
					break;

				case 'email':
					$field_html = sprintf( '<input name="%1$s" id="%2$s" type="%3$s" placeholder="%4$s" value="%5$s" class="%6$s form-input w-2/3 mt-2 mb-1 h-9 text-sm border-gray-400 w-3/5" %7$s/>', $uid, $id_key, $type, $placeholder, $value, $class, $disabled );
					break;

				case 'textarea':
					$field_html = sprintf( '<textarea name="%1$s" id="%2$s" placeholder="%3$s" size="100" rows="%6$s" cols="58" class="%5$s form-textarea text-sm w-2/3 mt-3 mb-1 border-gray-400 w-3/5" %7$s>%4$s</textarea>', $uid, $id_key, $placeholder, $value, $class, $rows, $disabled );
					break;

				case 'file':
					$field_html = '<input type="text" id="logo_url" name="' . $uid . '" value="' . $value . '" class="w-2/3 w-3/5 mt-2 mb-1 text-sm border-gray-400 form-input h-9' . $class . '"/> <input id="upload_logo_button" type="button" class="button" value="Upload Logo" />';
					break;

				case 'checkbox':
					$field_html = '<label for="' . $id_key . '" class="inline-flex items-center mt-3 mb-1 cursor-pointer">
			<span class="relative">';

					if ( ! $disabled ) {
						$field_html .= '<input id="' . $id_key . '"  type="checkbox" name="' . $uid . '"  value="yes" ' . checked( $value, 'yes', false ) . ' class="sr-only peer absolute w-0 h-0 mt-6 opacity-0 es-check-toggle ' . $class . '" />';
					}

					$field_html .= $placeholder . '</input>
				<div class="w-11 h-6 bg-gray-200 rounded-full peer  dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
			</span>
			</label>';
					break;

				case 'select':
					if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
						$options_markup = '';
						foreach ( $arguments['options'] as $key => $label ) {
							$options_markup .= sprintf(
							'<option value="%s" %s>%s</option>',
							$key,
							selected( $value, $key, false ),
							$label
							);
						}
						$field_html = sprintf( '<select name="%1$s" id="%2$s" class="%4$s form-select rounded-lg w-48 h-9 mt-2 mb-1 border-gray-400" %5$s>%3$s</select>', $uid, $id_key, $options_markup, $class, $disabled );
					}
					break;

				case 'html':
				default:
					$field_html = $html;
					break;
			}

			// If there is help text
			if ( ! empty( $arguments['desc'] ) ) {
				$helper      = $arguments['desc'];
				$field_html .= sprintf( '<p class="field-desciption helper %s"> %s</p>', $class, $helper ); // Show it
			}

			return $field_html;
		}

	}

}

ES_Settings_Controller::get_instance();
