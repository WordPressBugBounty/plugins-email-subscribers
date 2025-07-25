<?php

/**
 * Get additional system & plugin specific information for feedback
 */
if ( ! function_exists( 'ig_es_get_additional_info' ) ) {

	function ig_es_get_additional_info( $additional_info = array(), $system_info = false ) {
		global $ig_es_tracker;

		$additional_info['version'] = ES_PLUGIN_VERSION;

		if ( $system_info ) {

			$additional_info['active_plugins']   = implode( ', ', $ig_es_tracker::get_active_plugins() );
			$additional_info['inactive_plugins'] = implode( ', ', $ig_es_tracker::get_inactive_plugins() );
			$additional_info['current_theme']    = $ig_es_tracker::get_current_theme_info();
			$additional_info['wp_info']          = $ig_es_tracker::get_wp_info();
			$additional_info['server_info']      = $ig_es_tracker::get_server_info();

			// ES Specific information
			$additional_info['plugin_meta_info'] = ES_Plugin_Usage_Data_Collector::get_ig_es_meta_info();
		}

		$admin_email = ES_Common::get_admin_email();
		$user        = get_user_by( 'email', $admin_email );
		$admin_name  = '';
		if ( $user instanceof WP_User ) {
			$admin_name = $user->display_name;
		}

		$additional_info['email'] = $admin_email;
		$additional_info['name']  = $admin_name;

		return $additional_info;
	}
}

add_filter( 'ig_es_additional_feedback_meta_info', 'ig_es_get_additional_info', 10, 2 );

/**
 * Render general feedback on click of "Feedback" button from ES sidebar
 */
function ig_es_render_general_feedback_widget() {

	if ( is_admin() ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$event = 'plugin.feedback';

		$params = array(
			'type'              => 'feedback',
			'event'             => $event,
			'title'             => 'Have feedback or question for us?',
			'position'          => 'center',
			'width'             => 700,
			'force'             => true,
			'confirmButtonText' => __( 'Send', 'email-subscribers' ),
			'consent_text'      => __( 'Allow Icegram Express to track plugin usage. It will help us to understand your issue better. We guarantee no sensitive data is collected.', 'email-subscribers' ),
			'name'              => '',
		);

		ES_Common::render_feedback_widget( $params );
	}
}

add_action( 'admin_footer', 'ig_es_render_general_feedback_widget' );

/**
 * Render Broadcast Created feedback widget.
 *
 * @since 4.1.14
 */
function ig_es_render_broadcast_created_feedback_widget() {

	$event = 'broadcast.created';

	$params = array(
		'type'              => 'emoji',
		'event'             => $event,
		'title'             => "How's your experience sending broadcast?",
		'position'          => 'top-end',
		'width'             => 300,
		'delay'             => 2, // seconds
		'confirmButtonText' => __( 'Send', 'email-subscribers' ),
	);

	ES_Common::render_feedback_widget( $params );
}

// add_action( 'ig_es_broadcast_created', 'ig_es_render_broadcast_created_feedback_widget' );

/**
 * Render Broadcast Created feedback widget.
 *
 * @since 4.1.14
 */
function ig_es_render_fb_widget() {

	if ( is_admin() ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$total_contacts = ES()->contacts_db->count();

		// Got 25 contacts?
		// It's time to Join Icegram Express Secret Club on Facebook
		if ( $total_contacts >= 25 ) {

			$event = 'join.fb';

			$params = array(
				'type'              => 'fb',
				'title'             => __( 'Not a member yet?', 'email-subscribers' ),
				'event'             => $event,
				'html'              => '<div style="text-align:center;"> ' . __( 'Join', 'email-subscribers' ) . '<strong> ' . __( 'Icegram Express Secret Club', 'email-subscribers' ) . '</strong> ' . __( 'on Facebook', 'email-subscribers' ) . '</div>',
				'position'          => 'bottom-center',
				'width'             => 500,
				'delay'             => 2, // seconds
				'confirmButtonText' => '<i class="dashicons dashicons-es dashicons-facebook"></i> ' . __( 'Join Now', 'email-subscribers' ),
				'confirmButtonLink' => 'https://www.facebook.com/groups/2298909487017349/',
				'show_once'         => true,
			);

			ES_Common::render_feedback_widget( $params );
		}
	}
}

add_action( 'admin_footer', 'ig_es_render_fb_widget' );

if ( ! function_exists( 'ig_es_review_message_data' ) ) {
	/**
	 * Filter 5 star review data
	 *
	 * @param $review_data
	 *
	 * @return mixed
	 *
	 * @since 4.3.8
	 */
	function ig_es_review_message_data( $review_data ) {

		$review_url = 'https://wordpress.org/support/plugin/email-subscribers/reviews/';
		$icon_url   = ES_PLUGIN_URL . 'lite/admin/images/icon-64.png';
		$message    = __( "<span><p>We hope you're enjoying <b>Icegram Express</b> plugin! Could you please do us a BIG favor and give us a 5-star rating on WordPress to help us spread the word and boost our motivation?</p>", 'email-subscribers' );

		$review_data['review_url'] = $review_url;
		$review_data['icon_url']   = $icon_url;
		$review_data['message']    = $message;

		return $review_data;
	}
}

add_filter( 'ig_es_review_message_data', 'ig_es_review_message_data', 10 );

if ( ! function_exists( 'ig_es_can_ask_user_for_review' ) ) {
	/**
	 * Can we ask user for 5 star review?
	 *
	 * @return bool
	 *
	 * @since 4.3.8
	 */
	function ig_es_can_ask_user_for_review( $enable, $review_data ) {

		if ( $enable ) {

			if ( ! ES()->is_es_admin_screen() ) {
				return false;
			}

			$total_contacts   = ES()->contacts_db->count_active_contacts_by_list_id();
			$total_email_sent = ES_DB_Mailing_Queue::get_notifications_count();

			// Don't show if - less than 3 post notifications or Newsletters sent OR less than 10 subscribers
			if ( $total_contacts < 10 && $total_email_sent < 3 ) {
				return false;
			}
		}

		return $enable;
	}
}

add_filter( 'ig_es_can_ask_user_for_review', 'ig_es_can_ask_user_for_review', 10, 2 );

/**
 * Render Icegram-Email Subscribers merge feedback widget.
 *
 * @since 4.3.13
 */
function ig_es_render_iges_merge_feedback() {

	global $ig_es_feedback;

	if ( is_admin() ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$event = 'poll.merge_iges';

		// If user has already given feedback on Icegram page, don't ask them again
		$is_event_tracked = $ig_es_feedback->is_event_tracked( 'ig', $event );

		if ( $is_event_tracked ) {
			return;
		}

		$total_contacts = ES()->contacts_db->count_active_contacts_by_list_id();

		if ( $total_contacts >= 5 ) {

			$params = array(
				'type'              => 'poll',
				'title'             => __( 'Subscription forms and CTAs??', 'email-subscribers' ),
				'event'             => $event,
				'desc'              => '<div><p class="mt-4">You use <a href="https://wordpress.org/plugins/email-subscribers" target="_blank"><b class="text-blue-700 font-semibold underline">Icegram Express</b></a> to send email campaigns.</p><p class="mt-3">Would you like us to include onsite popups and action bars in the plugin as well? This way you can <b class="font-semibold">convert visitors to subscribers, drive traffic and run email marketing from a single plugin</b>.</p> <p class="mt-3">Why do we ask?</p> <p class="mt-3">Our <a class="text-blue-700 font-semibold underline" href="https://wordpress.org/plugins/icegram" target="_blank"><b>Icegram</b></a> plugin already does onsite campaigns. We are thinking of merging Icegram & Icegram Express into a single plugin.</p> <p class="mt-3"><b class="font-semibold">Will a comprehensive ConvertKit / MailChimp like email + onsite campaign plugin be useful to you?</b></p> </div><p class="mt-3">',
				'fields' => array(
					array(
						'type' => 'radio',
						'name' => 'poll_options',
						'label'	   => __( 'Yes', 'email-subscribers' ),
						'value'	=> 'yes',
						'required' => true,
					),
					array(
						'type' => 'radio',
						'name' => 'poll_options',
						'label'	   => __( 'No', 'email-subscribers' ),
						'value'	=> 'no',
					),
					array(
						'type' => 'textarea',
						'name' => 'details',
						'placeholder' => __( 'Additional feedback', 'email-subscribers' ),
					),
				),
				'allow_multiple'    => false,
				'position'          => 'bottom-center',
				'width'             => 400,
				'delay'             => 2, // seconds
				'display_as'		=> 'popup',
				'confirmButtonText' => __( 'Send my feedback to <b>Icegram team</b>', 'email-subscribers' ),
				'show_once'         => true,
			);

			ES_Common::render_feedback_widget( $params );
		}
	}
}

add_action( 'admin_footer', 'ig_es_render_iges_merge_feedback' );

/**
 * Can load sweetalert js file
 *
 * @param bool $load
 *
 * @return bool
 *
 * @since 4.3.13
 */
function ig_es_can_load_sweetalert_js( $load = false ) {

	if ( ES()->is_es_admin_screen() ) {
		return true;
	}

	return $load;
}

add_filter( 'ig_es_can_load_sweetalert_js', 'ig_es_can_load_sweetalert_js', 10, 1 );


/**
 * Render Broadcast Created feedback widget.
 *
 * @since 4.4.7
 */
function ig_es_render_broadcast_ui_review() {

	if ( is_admin() ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$event = 'broadcast.ui.review';

		$params = array(
			'type'              => 'fb',
			'widget_tyoe'       => 'success',
			'title'             => __( 'Broadcast Created Successfully!', 'email-subscribers' ),
			'event'             => $event,
			'html'              => '<div style="margin-bottom:30px;"> ' . __( 'If you like new Broadcast UI, leave us a <b>5 stars review</b>. <br /><br />Do you have a feedback? Contact Us.', 'email-subscribers' ) . '</div>',
			'position'          => 'top-right',
			'width'             => 500,
			'delay'             => 2, // seconds
			'confirmButtonText' => '<i class="dashicons dashicons-star-empty"></i> ' . __( 'Leave Review', 'email-subscribers' ),
			'confirmButtonLink' => 'https://wordpress.org/support/plugin/email-subscribers/reviews/?filter=5',
			'showCancelButton'  => true,
			'cancelButtonText'  => __( 'Contact Us', 'email-subscribers' ),
			'cancelButtonLink'  => 'https://icegram.com',
			'show_once'         => true,
		);

		ES_Common::render_feedback_widget( $params );
	}
}

add_action( 'ig_es_broadcast_created', 'ig_es_render_broadcast_ui_review' );

if ( ! function_exists( 'ig_es_show_plugin_usage_tracking_notice' ) ) {

	/**
	 * Can we show tracking usage optin notice?
	 *
	 * @return bool
	 *
	 * @since 4.7.7
	 */
	function ig_es_show_plugin_usage_tracking_notice( $enable ) {

		// Show notice ES pages except the dashboard page.
		if ( ES()->is_es_admin_screen() ) {

			$current_page = ig_es_get_request_data( 'page' );

			if ( 'es_dashboard' !== $current_page ) {

				$enable = true;
			}
		}

		return $enable;
	}
}

add_filter( 'ig_es_show_plugin_usage_tracking_notice', 'ig_es_show_plugin_usage_tracking_notice' );

if ( ! function_exists('ig_es_can_load_sweetalert_js') ) {
	/**
	 * Can load sweetalert js
	 *
	 * @param bool $load
	 *
	 * @return bool
	 *
	 * @since 5.4.12
	 */
	function ig_es_can_load_sweetalert_js( $load = false ) {

		if ( ES()->is_es_admin_screen() ) {
			return true;
		}

		return $load;
	}
}

add_filter( 'ig_es_can_load_sweetalert_js', 'ig_es_can_load_sweetalert_js' );

if ( ! function_exists('ig_es_can_load_sweetalert_css') ) {
	/**
	 * Can load sweetalert css
	 *
	 * @param bool $load
	 *
	 * @return bool
	 *
	 * @since 5.4.12
	 */
	function ig_es_can_load_sweetalert_css( $load = false ) {

		if ( ES()->is_es_admin_screen() ) {
			return true;
		}

		return $load;
	}
}

add_filter( 'ig_es_can_load_sweetalert_css', 'ig_es_can_load_sweetalert_css' );

if ( ! function_exists( 'ig_es_show_feature_survey' ) ) {
	function ig_es_show_feature_survey() {

		if ( ! ES()->is_es_admin_screen() ) {
			return;
		}

		$current_utc_time = time();
		$current_ist_time = $current_utc_time + ( 5.5 * HOUR_IN_SECONDS ); // Add IST offset to get IST time
		$offer_start_time = strtotime( '2022-11-23 12:30:00' ); // Offer start time in IST
		$offer_end_time   = strtotime( '2022-12-01 12:30:00' ); // Offer end time in IST

		$is_offer_period = $current_ist_time >= $offer_start_time && $current_ist_time <= $offer_end_time;
		// Don't show survey in offer period.
		if ( $is_offer_period ) {
			return;
		}

		$can_ask_user_for_review = false;
		$total_contacts          = ES()->contacts_db->count();

		if ( $total_contacts >= 10 ) {
			$can_ask_user_for_review = true;
		} else {
			$plugin_activation_time  = get_option( 'ig_es_installed_on', 0 );
			$feedback_wait_period    = 10 * DAY_IN_SECONDS;
			$feedback_time           = strtotime( $plugin_activation_time ) + $feedback_wait_period;
			$current_time            = time();
			$can_ask_user_for_review = $current_time > $feedback_time;
		}

		if ( ! $can_ask_user_for_review ) {
			return;
		}

		global $ig_es_feedback;

		$survey_title     = __( '📣 Hey! What new feature would you like us to develop?', 'email-subscribers'  );
		$survey_slug      = 'ig-es-feature-survey';
		$survey_questions = array(
			'email_sending_service'      => __( "Icegram's own email sending service (so you don't have to bother with external services / SMTP)", 'email-subscribers' ), 
			'whatsapp_sms_support'       => __( 'WhatsApp & SMS Text Message support (just like you can do email campaigns currently)', 'email-subscribers' ), 
			'ab_split_testing'           => __( 'A/B split testing for campaigns (to figure out which subject / body works better)', 'email-subscribers' ),
			'more_workflow_integrations' => __( 'More workflow automations and tighter integration with other plugins (for example - send an email when a subscription is cancelled or renewal is coming up...)', 'email-subscribers' ),
			'other'                      => __( 'Something else? Tell us what do you want...', 'email-subscribers' ),
		);

		$survey_fields = array();
		foreach ( $survey_questions as $question_slug => $question_text ) {
			$survey_fields[] = array(
				'type' => 'radio',
				'name' => 'feature',
				'label' => $question_text,
				'value' => $question_slug,
			);
		}

		// Store default values in field_name => default_value format.
		$default_values = array(
			'feature' => 'email_sending_service',
		);

		$feedback_data = array(
			'event'          => 'feature_survey',
			'title'          => $survey_title,
			'slug'           => $survey_slug,
			'logo_img_url'   => ES_PLUGIN_URL . '/lite/admin/images/icon-64.png',
			'fields'         => $survey_fields,
			'default_values' => $default_values,
			'type'	         => 'poll',
			'system_info'    => false,
		);
		
		$ig_es_feedback->render_feedback_widget( $feedback_data );
	}
}

//add_action( 'admin_notices', 'ig_es_show_feature_survey' );

function ig_es_add_deactivation_reasons( $options ) {
	
	$new_options = array(
	   array(
		   'title'   => esc_html__( 'Emails not sending', 'email-subscribers' ),
		   'slug'    => 'emails-not-sending',
	   ),
	   array(
		   'title'   => esc_html__( 'Too many spam sign-ups', 'email-subscribers' ),
		   'slug'    => 'too-many-spam-sign-ups',
	   )
	);

	$slug_to_remove = 'i-could-not-get-the-plugin-to-work'; 
	foreach ( $options as $key => $option ) {
		if ( isset( $option['slug'] ) && $slug_to_remove === $option['slug'] ) {
			unset( $options[$key] );
		}
	}
	$options = array_values( $options );
	$options = array_combine( range(1, count( $options ) ), $options );
	$options = array_merge( $new_options, $options );

	return $options;
}
add_filter( 'ig_es_deactivation_reasons', 'ig_es_add_deactivation_reasons' );


/**
* Ask for 14-day free trial
*/
if ( ! function_exists( 'ig_es_show_trial_optin_reminder_notice' ) ) {
	function ig_es_show_trial_optin_reminder_notice() {

		if ( ! ES()->is_es_admin_screen() ) {
			return false;
		}

		$plugin_activation_time = get_option( 'ig_es_installed_on', 0 );
		$notice_wait_period     = 10 * DAY_IN_SECONDS;
		$notice_time            = strtotime($plugin_activation_time) + $notice_wait_period;
		$can_show_the_notice    = time() > $notice_time;
		
		if ( ! $can_show_the_notice ) {
			return;
		}

		if (!ES()->is_premium() && !ES()->trial->is_trial() ) {

			if (ig_es_get_request_data('ig_es_close_trial_notice') && check_admin_referer('ig_es_close_trial_notice_nonce')) {
				update_option( 'ig_es_close_trial_notice', 'yes' );
			}

			if (get_option('ig_es_close_trial_notice')) {
				return;
			}

			/* translators: 1. Anchar start tag 2. Anchor close tag */
			$trial_optin_link = sprintf(__( ' %1$sfree trial%2$s', 'email-subscribers' ), "<a class='text-indigo-600 font-bold' href='" . esc_url(get_site_url() . '/wp-admin/admin.php?page=es_dashboard#ig-es-trial-optin-block') . "' target='_blank'><b>", '</b></a>' );

			?>

			<div class="notice notice-success is-dismissible" id="ig-trial-custom-notice">
				<span>
					<p><b><?php echo esc_html__('[ Icegram  Express ] 14-Day Free Trial Not Activated', 'email-subscribers' ); ?></b></p>
					<p>
						<?php
							/* translators: %s: Trial optin link */
							echo sprintf( esc_html__( "It looks like you haven't taken advantage of our 14-day %s yet. Start your free trial today to explore the premium features and benefits at no cost for the next two weeks!", 'email-subscribers' ), wp_kses_post( $trial_optin_link ) );
						?>
					</p>
				</span>
				
				<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__('Dismiss this notice.', 'email-subscribers' ); ?></span></button>

				<form method="post" id="trial-dismiss-notice-form" style="display: none;">
					<input type="hidden" name="ig_es_close_trial_notice" value="yes">
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce('ig_es_close_trial_notice_nonce') ); ?>">
				</form>
			</div>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', function() {
					var notice = document.getElementById('ig-trial-custom-notice');
					var form = document.getElementById('trial-dismiss-notice-form');
					notice.querySelector('.notice-dismiss').addEventListener('click', function() {
						form.submit();
					});
				});
			</script>
			<?php
		}
	}
}
add_action( 'admin_notices', 'ig_es_show_trial_optin_reminder_notice' );


/* This survey show after 20th day when user was upgraded plugin */
if ( ! function_exists( 'ig_es_survey_after_plugin_upgrade' ) ) {
	function ig_es_survey_after_plugin_upgrade() {
		global $ig_es_feedback;

		$plugin_activation_time  = get_option( 'ig_es_installed_on', 0 );
		$feedback_wait_period    = 20 * DAY_IN_SECONDS;
		$feedback_time           = strtotime( $plugin_activation_time ) + $feedback_wait_period;
		$current_time            = time();
		$can_ask_user_for_review = $current_time > $feedback_time;

		if ( ! $can_ask_user_for_review ) {
			return;
		}

		if (ES()->is_premium() && !ES()->trial->is_trial() ) {
			$survey_title     = __( 'No Fluff, Just Facts: Give Us Your Unfiltered Feedback on Our Paid Services!', 'email-subscribers'  );
			$survey_slug      = 'ig-es-survey-after-plugin-upgrade';
			$survey_questions = array(
									array(  'question' => __( 'What feature sold you on the paid plan?', 'email-subscribers' ),
											'options' => array( 'ga_utm_tracking' => __('Google Analytics UTM tracking', 'email-subscribers'), 
																'spam_score_checking' => __('Spam score checking', 'email-subscribers'),
																'background_email_sending' => __('Background email sending', 'email-subscribers'),
																'css_inliner' => __('CSS inliner', 'email-subscribers'),
																'other' =>__('Other', 'email-subscribers'),
															),
											'type' => 'checkbox',
											'slug' => 'paid_plan_feature',
											'additional' => 'reason_field'
										),
									array(  'question' => __( 'Would you genuinely recommend Icegram Express to others?', 'email-subscribers' ),
											'options' => array( 'very_likely' => __('Very likely', 'email-subscribers'), 
																'neutral' => __('Neutral', 'email-subscribers'), 
																'unlikely' => __('Unlikely', 'email-subscribers') 
														),
											'type' => 'radio',
											'slug' => 'ig_recommend_option'
										),
									array(  'question' => __( "What's your true satisfaction level with Icegram Express?", 'email-subscribers' ),
											'options' => array( 'very_satisfied' => __('Very satisfied', 'email-subscribers'), 
																'neutral' => __('Neutral', 'email-subscribers'), 
																'dissatisfied' => __('Dissatisfied', 'email-subscribers') ),
											'type' => 'radio',
											'slug' => 'satisfied_expirence'
									),
									array(  'question' => __( "What's one thing we could do better?", 'email-subscribers' ),
											'type' => 'textarea',
											'slug' => 'plugin_suggestion',
											'placeholder' => 'Describe your features...'
									),
								);

			$feedback_data = array(
				'event'          => 'plugin_survey_after_upgradation',
				'title'          => $survey_title,
				'slug'           => $survey_slug,
				'fields'         => $survey_questions,
				'type'	         => 'poll',
				'allow_multiple' => true,
				'system_info'    => false,
				'display_as'	 => 'popup',
				'position'		 => 'center',
				'width'			 => '900',
				'confirmButtonText' => 'Submit',
				'after_button_text' => 'Every reply matters!',
				'show_once'         => true,
			);
			
			$ig_es_feedback->render_feedback_widget( $feedback_data );
		}
	}
}
add_action('admin_notices', 'ig_es_survey_after_plugin_upgrade');


/* This survey show after 20th day when user was not upgraded plugin */
if ( ! function_exists( 'ig_es_survey_before_plugin_upgrade' ) ) {
	function ig_es_survey_before_plugin_upgrade() {
		global $ig_es_feedback;

		$plugin_activation_time  = get_option( 'ig_es_installed_on', 0 );
		$feedback_wait_period    = 20 * DAY_IN_SECONDS;
		$feedback_time           = strtotime( $plugin_activation_time ) + $feedback_wait_period;
		$current_time            = time();
		$can_ask_user_for_review = $current_time > $feedback_time;

		if ( ! $can_ask_user_for_review ) {
			return;
		}

		if (!ES()->is_premium()) {
			$survey_title     = __( 'We Noticed You Haven’t Upgraded—What’s Holding You Back?', 'email-subscribers' );
			$survey_slug      = 'ig-es-feature-survey';
			$survey_questions = array(
									array(  'question' => __( 'What was the main reason for not upgrading to a paid plan?', 'email-subscribers' ),
											'options' => array( 'happy_with_free_version' => __( "I'm happy with the free version", 'email-subscribers' ), 
																'very_expensive_price' => __('Found the price very expensive', 'email-subscribers' ),
																"didn't_find_feature" => __('Did not find the feature I needed', 'email-subscribers' ),
																'encountered_technocal_issue' => __('Encountered a technical issue', 'email-subscribers' ),
																'other' => __('Other', 'email-subscribers' ),
															),
											'type' => 'checkbox',
											'slug' => 'not_upgrading_paid_plan',
											'additional' => 'reason_field'
										),
									array(  'question' => __( 'Would a Discount Make a Difference for You?', 'email-subscribers' ),
											'options' => array( 'need_discount' => __('Yes, give me a discount', 'email-subscribers' ), 
																"don't_need_discount" => __('No, I do not like saving money.', 'email-subscribers' ) 
															),
											'type' => 'radio',
											'slug' => 'discount_option'
										),
									array(  'question' => __( 'What feature did you find missing? Suggestions, if any?', 'email-subscribers' ),
											'type' => 'textarea',
											'slug' => 'plugin_suggestion',
											'placeholder' => 'Describe your features...'
									),
								);

			

			$feedback_data = array(
				'event'          => 'plugin_survey_before_upgradation',
				'title'          => $survey_title,
				'slug'           => $survey_slug,
				'fields'         => $survey_questions,
				'type'	         => 'poll',
				'display_as'	 => 'popup',
				'position'		 => 'center',
				'width'			 => '900',
				'confirmButtonText' => 'Submit',
				'system_info'    => false,
				'allow_multiple' => true,
				'show_once'      => true,
			);
			
			$ig_es_feedback->render_feedback_widget( $feedback_data );
		}
	}
}
add_action('admin_notices', 'ig_es_survey_before_plugin_upgrade');

if ( ! function_exists( 'ig_es_subscribe_to_plugin_deactivation_list' ) ) {
	function ig_es_subscribe_to_plugin_deactivation_list( $data ) {
		
		$admin_email = ES_Common::get_admin_email();
		$user        = get_user_by( 'email', $admin_email );
		$admin_name  = '';
		if ( $user instanceof WP_User ) {
			$admin_name = $user->display_name;
		}

		$email = $admin_email;
		$name  = $admin_name;

		switch ( $data['feedback']['value'] ) {
			case 'i-am-switching-to-a-different-plugin':
				$list = '46e63a445c57';
				break;
			
			case 'i-could-not-get-the-plugin-to-work':
				$list = '8e63321da577';
				break;
			
			case 'it-is-a-temporary-deactivation':
				$list = 'b3d2c4e62f8e';
				break;

			default:
				$list = '';
				break;
		}

		if ( ! empty( $list ) && is_email( $email ) ) {

			$url_params = array(
			'ig_es_external_action' => 'subscribe',
			'name'                  => $name,
			'email'                 => $email,
			'list'                  => $list,
			);

			$ip_address = ig_es_get_ip();
			if ( ! empty( $ip_address ) && 'UNKNOWN' !== $ip_address ) {
				$url_params['ip_address'] = $ip_address;
			}

			$ig_url = 'https://www.icegram.com/';
			$ig_url = add_query_arg( $url_params, $ig_url );

			$args = array(
			'timeout' => 15,
			'blocking'  => false,
			);

			// Make a get request.
			wp_remote_get( $ig_url, $args );
		}
	}
}
add_action( 'ig_es_deactivation_feedback_submitted', 'ig_es_subscribe_to_plugin_deactivation_list' );
