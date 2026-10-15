<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
 * Supply Icegram Express-specific system info for the Contact Us modal.
 * Hooked to '{plugin_abbr}_contact_us_system_info' so the common library stays plugin-agnostic.
 *
 * @param  array $info Base array (empty from the library).
 * @return array
 */
if ( ! function_exists( 'ig_es_get_contact_us_system_info' ) ) {

	function ig_es_get_contact_us_system_info( $info ) {

		global $wpdb;

		$theme           = wp_get_theme();
		$mailer_settings = get_option( 'ig_es_mailer_settings', array() );
		$active_mailer   = ! empty( $mailer_settings['mailer'] ) ? sanitize_key( $mailer_settings['mailer'] ) : 'unknown';

		$cron_last_hit_data = get_option( 'ig_es_cron_last_hit', array() );
		$cron_last_hit_ts   = ! empty( $cron_last_hit_data['timestamp'] ) ? (int) $cron_last_hit_data['timestamp'] : 0;
		$cron_last_hit      = $cron_last_hit_ts > 0 ? wp_date( 'Y-m-d H:i:s', $cron_last_hit_ts ) : 'Never';

		$es_disable_wp_cron = ( 'yes' === get_option( 'ig_es_disable_wp_cron', 'no' ) ) ? 'Yes' : 'No';

		return array_merge( $info, array(
			'es_version'         => defined( 'ES_PLUGIN_VERSION' ) ? sanitize_text_field( ES_PLUGIN_VERSION ) : 'n/a',
			'wp_version'         => sanitize_text_field( get_bloginfo( 'version' ) ),
			'php_version'        => sanitize_text_field( PHP_VERSION ),
			'db_version'         => sanitize_text_field( $wpdb->db_version() ),
			'memory_limit'       => sanitize_text_field( ini_get( 'memory_limit' ) ),
			'multisite'          => is_multisite() ? 'Yes' : 'No',
			'disable_wp_cron'    => ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? 'Yes' : 'No',
			'active_theme'       => sanitize_text_field( $theme->get( 'Name' ) ),
			'active_mailer'      => $active_mailer,
			'es_disable_wp_cron' => $es_disable_wp_cron,
			'cron_last_hit'      => $cron_last_hit,
		) );
	}
}

add_filter( 'ig_es_contact_us_system_info', 'ig_es_get_contact_us_system_info', 10, 1 );

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

//add_action( 'admin_footer', 'ig_es_render_iges_merge_feedback' );

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

	$existing_options = array();
	foreach ( $options as $option ) {
		if ( isset( $option['slug'] ) ) {
			$existing_options[ $option['slug'] ] = $option;
		}
	}

	$new_slugs = array(
		'emails-not-sending'  => array( 'title' => __( 'Emails aren\'t sending or landing in spam', 'email-subscribers' ) ),
		'setup-confusing'     => array( 'title' => __( 'Setup or configuration was confusing', 'email-subscribers' ) ),
		'performance-issues'  => array( 'title' => __( 'The plugin is affecting performance', 'email-subscribers' ) ),
		'switching-plugin'    => array( 'title' => __( 'I\'m switching to another plugin', 'email-subscribers' ) ),
		'missing-feature'     => array( 'title' => __( 'It\'s missing features I need', 'email-subscribers' ) ),
		'other'               => array( 'title' => __( 'Others', 'email-subscribers' ) ),
	);

	$new_options = array();
	foreach ( $new_slugs as $slug => $reason ) {
		if ( isset( $existing_options[ $slug ] ) ) {
			$option = $existing_options[ $slug ];
		} else {
			$option = array( 'slug' => $slug );
		}
		$option['title'] = $reason['title'];
		$new_options[]   = $option;
	}

	return $new_options;
}
add_filter( 'ig_es_deactivation_reasons', 'ig_es_add_deactivation_reasons' );

/**
 * Headline shown at the top of Screen 1 of the deactivation modal.
 */
function ig_es_deactivation_headline( $headline ) {
	return __( '&#x1F614; Before you go&hellip;', 'email-subscribers' );
}
add_filter( 'ig_es_deactivation_headline', 'ig_es_deactivation_headline' );

/**
 * Main question shown on Screen 1 of the deactivation survey modal.
 */
function ig_es_deactivation_question( $question ) {
	return __( 'What made you deactivate Icegram Express?', 'email-subscribers' );
}
add_filter( 'ig_es_deactivation_question', 'ig_es_deactivation_question' );

/**
 * Thank-you message shown at the bottom of Screen 2.
 */
function ig_es_deactivation_thankyou( $text ) {
	return __( '&#x2764;&#xFE0F; Thanks for trying Icegram Express', 'email-subscribers' );
}
add_filter( 'ig_es_deactivation_thankyou', 'ig_es_deactivation_thankyou' );

/**
 * Returns the deactivation achievement stats card HTML.
 *
 * The required CSS is embedded inline on the first call (via a static flag)
 * so no separate hook or function is needed.
 *
 * Displays a stats-grid card showing subscribers added, campaigns created,
 * and emails sent — along with a tenure-based headline and a closing note.
 *
 * @return string HTML string, or empty string if there is no activity to show.
 */
function ig_es_get_user_deactivation_achievement_message() {

	$emails_sent_count = (int) ES()->actions_db->get_total_emails_sent();
	$audience_count    = (int) ES()->contacts_db->count();
	$campaigns_count   = (int) ES()->campaigns_db->count();

	$installed_on        = get_option( 'ig_es_installed_on', 0 );
	$installed_timestamp = ! empty( $installed_on ) ? strtotime( $installed_on ) : 0;

	// Do not show the achievement card if the installation date is unavailable.
	if ( empty( $installed_timestamp ) ) {
		return '';
	}

	// Calculate plugin tenure in months.
	$tenure_months = max(
		1,
		(int) round( ( time() - $installed_timestamp ) / MONTH_IN_SECONDS )
	);

	// Do not show the achievement card if the plugin has been installed
	// for less than 3 months.
	if ( $tenure_months < 3 ) {
		return '';
	}

	// Do not show the achievement card if the plugin has been installed
	// for less than 90 days.
	if ( ( time() - $installed_timestamp ) < ( 90 * DAY_IN_SECONDS ) ) {
		return '';
	}

	// activity threshold has been reached.
	if (
		$emails_sent_count <= 10000 &&
		$audience_count <= 1000 &&
		$campaigns_count < 50
	) {
		return '';
	}

	/* translators: %d: number of months the plugin has been installed */
	$intro_headline = sprintf(
		_n(
			"Over the last month, you've made great use of Express.",
			"Over the last %d months, you've made great use of Express.",
			$tenure_months,
			'email-subscribers'
		),
		$tenure_months
	);

	$closing_text = __(
		'If you deactivate Express, these subscriber, campaigns and email features will no longer be available on your site.',
		'email-subscribers'
	);

	$fmt_emails_counts    = number_format_i18n( $emails_sent_count );
	$fmt_audience_counts  = number_format_i18n( $audience_count );
	$fmt_campaigns_counts = number_format_i18n( $campaigns_count );

	// SVG icons for each stat (inline, trusted hardcoded markup).
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	$icon_users = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';

	$icon_send = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>';

	$icon_email = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>';
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

	$stats = array(
		array(
			'icon'  => $icon_users,
			'count' => $fmt_audience_counts,
			'label' => __( 'subscribers added', 'email-subscribers' ),
		),
		array(
			'icon'  => $icon_send,
			'count' => $fmt_campaigns_counts,
			'label' => __( 'campaigns created', 'email-subscribers' ),
		),
		array(
			'icon'  => $icon_email,
			'count' => $fmt_emails_counts,
			'label' => __( 'emails sent', 'email-subscribers' ),
		),
	);

	// Embed CSS only on the first call; subsequent calls reuse the already-printed styles.
	static $css_printed = false;
	$style = '';
	if ( ! $css_printed ) {
		$css_printed = true;
		$style = '<style>
			.ig-deactivation-achievement-card {
				background: #eff6ff;
				border: 1px solid #dbeafe;
				border-radius: 12px;
				padding: 18px 20px 16px;
				margin: 0px 0 18px 0;
				box-sizing: border-box;
			}
			.ig-deactivation-achievement-intro {
				font-size: 14px;
				font-weight: 700;
				color: #1d4ed8;
				line-height: 1.45;
				margin-bottom: 5px;
			}
			.ig-deactivation-achievement-stats {
				display: grid;
				grid-template-columns: repeat(3, 1fr);
				gap: 0;
				margin-bottom: 14px;
			}
			.ig-deactivation-achievement-stat {
				display: flex;
				flex-direction: column;
				align-items: center;
				text-align: center;
				padding: 10px 8px;
				border-right: 1px solid #dbeafe;
			}
			.ig-deactivation-achievement-stat:last-child {
				border-right: none;
			}
			.ig-deactivation-achievement-stat-icon {
				width: 48px;
				height: 48px;
				background: #dbeafe;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				margin-bottom: 8px;
			}
			.ig-deactivation-achievement-stat-count {
				font-size: 20px;
				font-weight: 800;
				color: #1d4ed8;
				line-height: 1.2;
				margin-bottom: 3px;
			}
			.ig-deactivation-achievement-stat-label {
				font-size: 13px;
				color: #6b7280;
				line-height: 1.4;
			}
			.ig-deactivation-achievement-divider {
				height: 1px;
				background: #dbeafe;
				margin-bottom: 12px;
			}
			.ig-deactivation-achievement-closing {
				font-size: 13px;
				line-height: 1.6;
				color: #374151;
			}
		</style>';
	}

	$html  = $style;
	$html .= '<div class="ig-deactivation-achievement-card">';
	$html .= '<div class="ig-deactivation-achievement-intro">' . esc_html( $intro_headline ) . '</div>';

	$html .= '<div class="ig-deactivation-achievement-stats">';
	foreach ( $stats as $stat ) {
		$html .= '<div class="ig-deactivation-achievement-stat">';
		$html .= '<div class="ig-deactivation-achievement-stat-icon">' . $stat['icon'] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$html .= '<div class="ig-deactivation-achievement-stat-count">' . esc_html( $stat['count'] ) . '</div>';
		$html .= '<div class="ig-deactivation-achievement-stat-label">' . esc_html( $stat['label'] ) . '</div>';
		$html .= '</div>';
	}
	$html .= '</div>';

	$html .= '<div class="ig-deactivation-achievement-divider"></div>';
	$html .= '<div class="ig-deactivation-achievement-closing">' . esc_html( $closing_text ) . '</div>';

	$html .= '</div>';

	return $html;
}

/**
 * Returns the Icegram Mailer promotion notice HTML shown on deactivation follow-up.
 *
 * @param string $context The reason context: 'not_sending', 'going_to_spam', or 'service_setup'.
 *
 * @return string HTML content.
 */
function ig_es_get_mailer_promote_card_html( $context = 'not_sending' ) {

	global $ig_es_tracker;
	$plugin_file = 'icegram-mailer/icegram-mailer.php';

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Do not show the mailer promotion card if Icegram Mailer is already active.
	if ( is_plugin_active( $plugin_file ) || ( isset( $ig_es_tracker ) && $ig_es_tracker::is_plugin_activated( $plugin_file ) ) ) {
		return '';
	}

	$badge_text = __( 'RECOMMENDED SOLUTION', 'email-subscribers' );

	if ( 'going_to_spam' === $context ) {
		$title    = __( 'Want to improve email deliverability?', 'email-subscribers' );
		$subtitle = __( 'Send your emails through a dedicated sending service with Icegram Mailer.', 'email-subscribers' );
		$features = array(
			__( 'Authenticated email sending', 'email-subscribers' ),
			__( 'Separate from your website’s hosting mail server', 'email-subscribers' ),
			__( 'Easy setup with no SMTP credentials', 'email-subscribers' ),
		);
		$btn_text = __( 'Try Icegram Mailer — Free to start', 'email-subscribers' );
	} elseif ( 'service_setup' === $context ) {
		$title    = __( 'Email setup shouldn’t be this hard', 'email-subscribers' );
		$subtitle = __( 'Skip complex SMTP setup and send emails from Icegram Express with Icegram Mailer.', 'email-subscribers' );
		$features = array(
			__( 'No SMTP host, port or password to configure', 'email-subscribers' ),
			__( 'Set up directly from your WordPress dashboard', 'email-subscribers' ),
			__( 'Reliable email sending without server limitations', 'email-subscribers' ),
		);
		$btn_text = __( 'Set up Icegram Mailer — Free to start', 'email-subscribers' );
	} else {
		$title    = __( 'Having trouble sending your emails?', 'email-subscribers' );
		$subtitle = __( 'Use Icegram Mailer to send your Express campaigns reliably.', 'email-subscribers' );
		$features = array(
			__( 'Reliable and consistent email sending', 'email-subscribers' ),
			__( 'No SMTP credentials to configure', 'email-subscribers' ),
			__( 'Easy setup with just a few clicks', 'email-subscribers' ),
		);
		$btn_text = __( 'Try Icegram Mailer — Free to start', 'email-subscribers' );
	}

	$install_nonce = wp_create_nonce( 'ig_es_install_mailer_nonce' );
	$link_text     = __( 'See how it works &rarr;', 'email-subscribers' );
	$link_url      = 'https://www.icegram.com/mailer/?utm_source=in-app&utm_medium=deactivation&utm_campaign=ig-es';

	// Inline SVG for the green checkmark
	$check_icon = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';

	$html  = '<div class="ig-mailer-promote-card">';
	$html .= '<div class="ig-mailer-promote-badge">' . esc_html( $badge_text ) . '</div>';
	$html .= '<h4 class="ig-mailer-promote-title">' . esc_html( $title ) . '</h4>';
	$html .= '<p class="ig-mailer-promote-desc">' . esc_html( $subtitle ) . '</p>';

	$html .= '<ul class="ig-mailer-promote-features">';
	foreach ( $features as $feature ) {
		$html .= '<li class="ig-mailer-promote-feature-item">';
		$html .= '<span class="ig-mailer-promote-feature-icon">' . $check_icon . '</span>';
		$html .= '<span class="ig-mailer-promote-feature-text">' . esc_html( $feature ) . '</span>';
		$html .= '</li>';
	}
	$html .= '</ul>';

	$html .= '<div class="ig-mailer-promote-actions">';
		$html .= '<button type="button" class="ig-mailer-promote-btn ig-mailer-install-btn" data-nonce="' . esc_attr( $install_nonce ) . '"><span class="ig-mailer-btn-text">' . esc_html( $btn_text ) . '</span><span class="ig-mailer-spinner" style="display:none;"></span></button>';
	$html .= '<a href="' . esc_url( $link_url ) . '" target="_blank" class="ig-mailer-promote-link">' . wp_kses_post( $link_text ) . '</a>';
	$html .= '</div>';

	$html .= '</div>';

	return $html;
}

/**
 * AJAX handler to install and activate Icegram Mailer directly from the deactivation survey.
 */
function ig_es_ajax_install_activate_mailer() {
	check_ajax_referer( 'ig_es_install_mailer_nonce', 'security' );

	if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to install plugins.', 'email-subscribers' ) ) );
	}

	global $ig_es_tracker;
	$plugin_file = 'icegram-mailer/icegram-mailer.php';

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// 1. Install if not installed
	if ( ! $ig_es_tracker::is_plugin_installed( $plugin_file ) ) {
		$install_result = ES_Common::install_plugin( 'icegram-mailer' );
		if ( is_wp_error( $install_result ) ) {
			wp_send_json_error( array( 'message' => $install_result->get_error_message() ) );
		} elseif ( ! $install_result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to install Icegram Mailer. Please try again.', 'email-subscribers' ) ) );
		}
	}

	// 2. Activate if not activated
	if ( ! $ig_es_tracker::is_plugin_activated( $plugin_file ) && ! is_plugin_active( $plugin_file ) ) {
		$activate_result = activate_plugin( $plugin_file );
		if ( is_wp_error( $activate_result ) ) {
			wp_send_json_error( array( 'message' => $activate_result->get_error_message() ) );
		}
	}

	$redirect_url = admin_url( 'admin.php?page=icegram_mailer_dashboard' );

	wp_send_json_success( array(
		'message'      => __( 'Icegram Mailer has been installed and activated successfully! Redirecting...', 'email-subscribers' ),
		'redirect_url' => $redirect_url,
	) );
}
add_action( 'wp_ajax_ig_es_install_activate_mailer', 'ig_es_ajax_install_activate_mailer' );

/**
 * Output Icegram Mailer promote card CSS & JS on the plugins page.
 *
 * This renders the styles for the mailer promotion card shown inside the
 * deactivation survey, as well as the click handler that installs and
 * activates Icegram Mailer via AJAX.
 *
 * @since 5.8.x
 */
function ig_es_render_mailer_promote_assets() {

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
	if ( empty( $screen ) || ! in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) ) {
		return;
	}
	?>
	<style type="text/css">
		.ig-mailer-promote-card {
			background: #eff6ff;
			border: 1px solid #bfdbfe;
			border-radius: 10px;
			padding: 18px 20px;
			margin: 12px 0 16px;
			box-sizing: border-box;
		}
		.ig-mailer-promote-badge {
			display: inline-block;
			background: #dbeafe;
			color: #2271b1;
			font-size: 10.5px;
			font-weight: 700;
			letter-spacing: .5px;
			text-transform: uppercase;
			padding: 3px 9px;
			border-radius: 9999px;
			margin-bottom: 8px;
			line-height: 1.3;
		}
		.ig-mailer-promote-title {
			font-size: 18px;
			font-weight: 800;
			color: #0f172a;
			margin: 0 0 6px 0;
			line-height: 1.3;
		}
		.ig-mailer-promote-desc {
			font-size: 13.5px;
			color: #475569;
			margin: 0 0 12px 0;
			line-height: 1.45;
		}
		.ig-mailer-promote-features {
			list-style: none;
			margin: 0 0 16px 0;
			padding: 0;
		}
		.ig-mailer-promote-feature-item {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: 13px;
			color: #334155;
			margin-bottom: 6px;
			line-height: 1.4;
		}
		.ig-mailer-promote-feature-icon {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			width: 16px;
			height: 16px;
		}
		.ig-mailer-promote-actions {
			display: flex;
			align-items: center;
			gap: 16px;
			flex-wrap: wrap;
		}
		.ig-mailer-promote-btn {
			background: #2271b1;
			color: #ffffff !important;
			padding: 9px 18px;
			border-radius: 6px;
			font-size: 13px;
			font-weight: 600;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			gap: 8px;
			border: none;
			cursor: pointer;
			font-family: inherit;
			transition: background .15s ease, opacity .15s ease;
		}
		.ig-mailer-promote-btn:hover {
			background: #135e96;
			color: #ffffff !important;
		}
		.ig-mailer-promote-btn:disabled {
			opacity: 0.85;
			cursor: not-allowed;
		}
		.ig-mailer-promote-btn.is-success {
			background: #16a34a !important;
			color: #ffffff !important;
		}
		.ig-mailer-spinner {
			display: inline-block;
			width: 13px;
			height: 13px;
			border: 2px solid rgba(255,255,255,0.4);
			border-radius: 50%;
			border-top-color: #ffffff;
			animation: ig-mailer-spin 0.6s linear infinite;
			vertical-align: middle;
			box-sizing: border-box;
		}
		@keyframes ig-mailer-spin {
			to { transform: rotate(360deg); }
		}
		.ig-mailer-install-notice {
			margin-top: 12px;
			padding: 10px 14px;
			border-radius: 6px;
			font-size: 13px;
			font-weight: 500;
			line-height: 1.4;
		}
		.ig-mailer-install-success {
			background: #dcfce7;
			border: 1px solid #86efac;
			color: #166534;
		}
		.ig-mailer-install-error {
			background: #fee2e2;
			border: 1px solid #fca5a5;
			color: #991b1b;
		}
		.ig-mailer-promote-link {
			color: #2271b1;
			font-size: 13px;
			font-weight: 500;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
		}
		.ig-mailer-promote-link:hover {
			text-decoration: underline;
			color: #135e96;
		}
	</style>

	<script type="text/javascript">
	jQuery(function($){
		// Direct installation & activation of Icegram Mailer from deactivation survey
		$(document).on('click', '.ig-mailer-install-btn', function(e){
			e.preventDefault();
			var $btn = $(this);
			if ($btn.hasClass('is-installing') || $btn.hasClass('is-success')) {
				return;
			}
			var origText = $btn.find('.ig-mailer-btn-text').text();
			$btn.prop('disabled', true).addClass('is-installing');
			$btn.find('.ig-mailer-btn-text').text('<?php echo esc_js( __( 'Installing & Activating...', 'email-subscribers' ) ); ?>');
			$btn.find('.ig-mailer-spinner').show();
			$btn.closest('.ig-mailer-promote-card').find('.ig-mailer-install-notice').remove();

			$.post(ajaxurl, {
				action: 'ig_es_install_activate_mailer',
				security: $btn.data('nonce')
			}).done(function(response){
				if (response && response.success) {
					$btn.removeClass('is-installing').addClass('is-success');
					$btn.find('.ig-mailer-spinner').hide();
					$btn.find('.ig-mailer-btn-text').html('&#10003; <?php echo esc_js( __( 'Installed &amp; Activated!', 'email-subscribers' ) ); ?>');
					var msg = (response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Icegram Mailer installed and activated successfully! Redirecting...', 'email-subscribers' ) ); ?>';
					$btn.closest('.ig-mailer-promote-actions').after('<div class="ig-mailer-install-notice ig-mailer-install-success">' + msg + '</div>');
					if (response.data && response.data.redirect_url) {
						setTimeout(function(){
							window.location.href = response.data.redirect_url;
						}, 1500);
					}
				} else {
					$btn.prop('disabled', false).removeClass('is-installing');
					$btn.find('.ig-mailer-spinner').hide();
					$btn.find('.ig-mailer-btn-text').text(origText);
					var errMsg = (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Failed to install. Please try again.', 'email-subscribers' ) ); ?>';
					$btn.closest('.ig-mailer-promote-actions').after('<div class="ig-mailer-install-notice ig-mailer-install-error">' + errMsg + '</div>');
				}
			}).fail(function(){
				$btn.prop('disabled', false).removeClass('is-installing');
				$btn.find('.ig-mailer-spinner').hide();
				$btn.find('.ig-mailer-btn-text').text(origText);
				$btn.closest('.ig-mailer-promote-actions').after('<div class="ig-mailer-install-notice ig-mailer-install-error"><?php echo esc_js( __( 'Installation request failed. Please try again.', 'email-subscribers' ) ); ?></div>');
			});
		});
	});
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'ig_es_render_mailer_promote_assets' );

/**
 * Per-option follow-up configuration for Screen 2.
 * Each key maps to a deactivation reason slug and defines:
 *   heading, body, sub_options, has_textarea, textarea_placeholder, body2, buttons.
 */
function ig_es_deactivation_followups( $followups ) {

	$achievement_message = ig_es_get_user_deactivation_achievement_message();

	return array(

		// 1. Email not sending / delivery issues
		'emails-not-sending' => array(
			'heading'     => __( 'What issue are you facing specifically?', 'email-subscribers' ),
			'body'        => '',
			'sub_options' => array(
				array(
					'label'  => __( 'Emails are not being sent at all', 'email-subscribers' ),
					'notice' => ig_es_get_mailer_promote_card_html( 'not_sending' ),
				),
				array(
					'label'  => __( 'Emails are going to spam', 'email-subscribers' ),
					'notice' => ig_es_get_mailer_promote_card_html( 'going_to_spam' ),
				),
				array(
					'label'  => __( 'Issue with setting up email service', 'email-subscribers' ),
					'notice' => ig_es_get_mailer_promote_card_html( 'service_setup' ),
				),
				array(
					'label' => __( 'Others', 'email-subscribers' ),
				),
			),
			'has_textarea' => false,
			'body2'        => '',
			'buttons'      => array(
				array( 'label' => __( 'Get Expert Help', 'email-subscribers' ),             'cls' => 'ig-deactivation-btn-secondary', 'action' => 'submit', 'skip_deactivation' => true, 'success_message' => __( 'Thank you for reaching out, we are here for you.', 'email-subscribers' ) ),
				array( 'label' => __( 'Continue Deactivation &rarr;', 'email-subscribers' ), 'cls' => 'ig-deactivation-btn-ghost',     'action' => 'submit' ),
			),
		),

		// 2. Setup or configuration was confusing
		'setup-confusing' => array(
			'heading'     => __( 'What felt confusing or difficult?', 'email-subscribers' ),
			'body'        => '',
			'sub_options' => array(
				__( 'Forms creation issues', 'email-subscribers' ),
				__( 'Campaign creation issues', 'email-subscribers' ),
				__( 'Email service setup issues', 'email-subscribers' ),
				__( 'Other issues', 'email-subscribers' ),
			),
			'has_textarea' => false,
			'body2'        => '',
			'buttons'      => array(
				array( 'label' => __( 'View Documentation', 'email-subscribers' ),   'cls' => 'ig-deactivation-btn-primary', 'action' => 'url', 'url' => 'https://www.icegram.com/docs/category/icegram-express/?utm_source=in-app&utm_medium=deactivation&utm_campaign=ig-es' ),
				array( 'label' => __( 'Continue Deactivation', 'email-subscribers' ), 'cls' => 'ig-deactivation-btn-ghost', 'action' => 'submit' ),
			),
		),

		// 3. Performance issues
		'performance-issues' => array(
			'heading'              => __( 'Tell us more about the issue.', 'email-subscribers' ),
			'body'                 => '',
			'sub_options'          => array(),
			'has_textarea'         => true,
			'textarea_placeholder' => __( 'Describe the performance issue you\'re facing...', 'email-subscribers' ),
			'body2'                => '',
			'buttons'              => array(
				array( 'label' => __( 'Get Expert Help', 'email-subscribers' ),       'cls' => 'ig-deactivation-btn-primary', 'action' => 'submit', 'skip_deactivation' => true, 'success_message' => __( 'Thank you for reaching out, we are here for you.', 'email-subscribers' ) ),
				array( 'label' => __( 'Continue Deactivation', 'email-subscribers' ), 'cls' => 'ig-deactivation-btn-ghost',   'action' => 'submit' ),
			),
		),

		// 4. Switching to another plugin
		'switching-plugin' => array(
			'heading'              => __( 'Which plugin are you switching to?', 'email-subscribers' ),
			'body'                 => '',
			'achievement_message'  => $achievement_message,
			'sub_options'          => array(),
			'has_textarea'         => true,
			'textarea_placeholder' => __( 'Plugin name (optional)...', 'email-subscribers' ),
			'body2'                => '',
			'buttons'              => array(
				array( 'label' => __( 'Submit and Deactivate', 'email-subscribers' ), 'cls' => 'ig-deactivation-btn-primary', 'action' => 'submit' ),
				array( 'label' => __( 'Skip and Deactivate', 'email-subscribers' ),  'cls' => 'ig-deactivation-btn-ghost',   'action' => 'deactivate' ),
			),
		),

		// 5. Missing a feature / Doesn't meet my needs
		'missing-feature' => array(
			'heading'              => __( 'What are you looking for?', 'email-subscribers' ),
			'body'                 => '',
			'achievement_message'  => $achievement_message,
			'sub_options'          => array(),
			'has_textarea'         => true,
			'textarea_placeholder' => __( 'Describe the feature or capability you were looking for...', 'email-subscribers' ),
			'body2'                => "",
			'buttons'              => array(
				array( 'label' => __( 'View Documentation', 'email-subscribers' ),   'cls' => 'ig-deactivation-btn-primary', 'action' => 'url', 'url' => 'https://www.icegram.com/docs/category/icegram-express/?utm_source=in-app&utm_medium=deactivation&utm_campaign=ig-es' ),
				array( 'label' => __( 'Continue Deactivation', 'email-subscribers' ), 'cls' => 'ig-deactivation-btn-ghost', 'action' => 'submit' ),
			),
		),

		// 6. Others
		'other' => array(
			'heading'              => __( 'Any details you share will help us improve.', 'email-subscribers' ),
			'body'                 => '',
			'achievement_message'  => $achievement_message,
			'sub_options'          => array(),
			'has_textarea'         => true,
			'textarea_placeholder' => __( 'Tell us what went wrong or what we could do better...', 'email-subscribers' ),
			'body2'                => '',
			'buttons'              => array(
				array( 'label' => __( 'Submit and Deactivate', 'email-subscribers' ), 'cls' => 'ig-deactivation-btn-primary', 'action' => 'submit' ),
				array( 'label' => __( 'Skip and Deactivate', 'email-subscribers' ),  'cls' => 'ig-deactivation-btn-ghost',   'action' => 'deactivate' ),
			),
		),

	);
}
add_filter( 'ig_es_deactivation_followups', 'ig_es_deactivation_followups' );



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

		// "Get Expert Help" sets skip_deactivation=1 — it's not a real deactivation, don't subscribe.
		if ( ! empty( $data['meta']['skip_deactivation'] ) ) {
			return;
		}

		$admin_email = ES_Common::get_admin_email();
		$user        = get_user_by( 'email', $admin_email );
		$admin_name  = '';
		if ( $user instanceof WP_User ) {
			$admin_name = $user->display_name;
		}

		$email = $admin_email;
		$name  = $admin_name;

		switch ( $data['feedback']['value'] ) {
			case 'switching-plugin':
				$list = '46e63a445c57';
				break;

			case 'emails-not-sending':
				$list = '8e63321da577';
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
			'timeout'  => 15,
			'blocking' => false,
			);

			// Make a get request.
			wp_remote_get( $ig_url, $args );
		}
	}
}
add_action( 'ig_es_deactivation_feedback_submitted', 'ig_es_subscribe_to_plugin_deactivation_list' );


/**
 * Render WhatsApp Integration Survey
 *
 * @since 5.x.x
 */
if ( ! function_exists( 'ig_es_show_whatsapp_integration_survey' ) ) {
    function ig_es_show_whatsapp_integration_survey() {
        global $ig_es_feedback;
        
        if ( is_admin() ) {
            
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                return;
            }
            
            if ( ! ES()->is_es_admin_screen() ) {
                return;
            }
            
			$current_page = ig_es_get_request_data( 'page' );

            $allowed_pages = array( 'es_dashboard', 'es_general_information', 'es_subscribers' );
            
            if ( ! in_array( $current_page, $allowed_pages ) ) {
                return;
            }

            $event = 'poll.whatsapp_integration';   
            
			$is_event_tracked = $ig_es_feedback->is_event_tracked( 'ig_es', $event );
						
            if ( $is_event_tracked ) {
                return;
            }
			
			$total_contacts = ES()->contacts_db->count_active_contacts_by_list_id();
            if ( $total_contacts >= 5 ) {  
                
                $survey_title = __( 'Unlock more sales with WhatsApp messaging', 'email-subscribers' );
                
				$survey_slug  = 'ig-es-whatsapp-integration-survey';                 

                $desc = '<div class="wa-survey-container">';
                $desc .= '<p class="wa-survey-intro">' . __( 'Send order updates on WhatsApp', 'email-subscribers' ) . '</p>';
				$desc .= '<p class="wa-survey-intro">' . __( ' where they won\'t get lost in an inbox.', 'email-subscribers' ) . '</p>';

                $desc .= '<div class="wa-stats-container">';
                $desc .= '<div class="wa-stat-item"><strong class="wa-stat-number">98%</strong><br><span class="wa-stat-label">' . __( 'open rate', 'email-subscribers' ) . '</span></div>';
                $desc .= '<div class="wa-stat-item"><strong class="wa-stat-number">5 min</strong><br><span class="wa-stat-label">' . __( 'avg read', 'email-subscribers' ) . '</span></div>';
                $desc .= '<div class="wa-stat-item"><strong class="wa-stat-number">3x CTR</strong><br><span class="wa-stat-label">' . __( 'vs email', 'email-subscribers' ) . '</span></div>';
                $desc .= '</div>';

                $desc .= '</div>';
                                
				$survey_fields = array(
                    array(
                        'type'  => 'radio',
                        'name'  => 'poll_options',
                        'slug'  => 'whatsapp_interest',
                        'label' => __( 'Not interested', 'email-subscribers' ),
                        'value' => 'not_interested',
						'class' => 'whatsapp-survey-option',

                    ),
					array(
                        'type'  => 'radio',
                        'name'  => 'poll_options',
                        'slug'  => 'whatsapp_interest',
                        'label' => __( 'Yes, build it', 'email-subscribers' ),
                        'value' => 'yes_build_it',
						'class' => 'whatsapp-survey-option',
                        'required' => true,
                    )                    
                );

                $params = array(   
                    'type'              => 'poll',
                    'title'             => $survey_title,
					'desc'              => $desc,
                    'event'             => $event,
					'force'             => true,
                    'slug'              => $survey_slug,
                    'fields'            => $survey_fields,
                    'allow_multiple'    => false,
					'system_info'       => true,
                    'position'          => 'center',
                    'width'             => '500',
                    'display_as'        => 'popup',
                    'showConfirmButton' =>  false, 
					'showCancelButton'  => false,  
					'allowOutsideClick' => true,
					'allowEscapeKey'    => true, 
					'show_once'         => false, 
					'set_transient'     => true,
                );
         
                ES_Common::render_feedback_widget( $params );

				?>
                
				<style>
					.wa-survey-container {
						text-align: center;
					}

					.wa-survey-intro {
						font-size: 15px;
						margin-bottom: 15px;
					}

					.wa-stats-container {
						display: flex;
						justify-content: space-around;
						margin-bottom: 25px;
						margin-top: 20px;
					}

					.wa-stat-item {
						display: inline-block;
					}

					.wa-stat-number {
						font-size: 24px;
						color: #5e19cf;
						font-weight: 500;
					}

					.wa-stat-label {
						font-size: 12px;
					}

					p.wa-survey-intro {
						padding-bottom: 0;
						margin-bottom: 5px;
					}
					
					.swal2-popup .ig-general-feedback p.mb-3:empty {
						display: none !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback label {
						display: inline-flex !important;
						align-items: center !important;
						justify-content: center !important;
						padding: 15px 20px !important;
						border-radius: 8px !important;
						cursor: pointer !important;
						text-align: center !important;
						transition: all 0.3s ease !important;
						font-weight: 600 !important;
						font-size: 14px !important;
						margin: 0 !important;
						border: 2px solid transparent !important;
						min-height: 50px !important;
						line-height: 1.4 !important;
						white-space: nowrap !important;
						width: calc(50% - 5px) !important;
						box-sizing: border-box !important;
					}
					
					.swal2-popup form.ig-general-feedback#ig-general-feedback label:first-of-type {
						margin-right: 10px !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback {
						display: flex !important;
						flex-direction: row !important;  /* ADD THIS */
						flex-wrap: wrap !important;
						gap: 0 !important;
						justify-content: space-between !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback .wa-survey-container {
						width: 100% !important;
						order: 1 !important;
						flex-basis: 100% !important;  /* ADD THIS */
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback p.mb-3 {
						width: 100% !important;
						order: 2 !important;
						flex-basis: 100% !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback label:nth-of-type(1) {
						order: 3 !important; 
						flex: 0 0 calc(50% - 5px) !important;
						width: calc(50% - 5px) !important;
						margin-right: 10px !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback label:nth-of-type(2) {
						order: 4 !important;
						flex: 0 0 calc(50% - 5px) !important;
						width: calc(50% - 5px) !important;
						margin-right: 0 !important;
					}
					
					.swal2-popup .ig-general-feedback input[type="radio"] {
						display: none !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback label:has(input[value="not_interested"]) {
						background: #f0f0f1 !important;
						color: #333 !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback label:has(input[value="yes_build_it"]) {
						background: #5e19cf !important;
						color: #fff !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback label:hover {
						opacity: 0.9 !important;
						transform: translateY(-1px) !important;
						box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback label:has(input:checked) {
						border-color: #5e19cf !important;
						box-shadow: 0 0 0 3px rgba(94, 25, 207, 0.2) !important;
					}

					.swal2-popup form.ig-general-feedback#ig-general-feedback label:active {
						transform: translateY(0) !important;
					}

					.swal2-confirm {
						opacity: 0 !important;
						pointer-events: none !important;
						position: absolute !important;
						left: -9999px !important;
					}

					.swal2-container .swal2-footer {
						display: none !important;
					}

					.swal2-popup {
						padding-top: 35px;
						padding-bottom: 35px;
					}

					.swal2-title, 
					.swal2-title #ig-feedback-title {
						font-size: 22px !important;
						font-weight: 600 !important;
						color: #545454 !important;
						line-height: 1.2 !important;
					}

					.swal2-popup.swal2-show {
						animation: slideInFromTop 0.3s ease-out;
					}

					.wa-thankyou-popup.swal2-hide {
						animation: slideOutToTop 0.3s ease-in;
					}

					.swal2-popup .swal2-close {
						display: none !important;
					}

					.wa-thankyou-popup {
						font-family: inherit !important;
					}

					.wa-thankyou-popup .swal2-title {
						font-size: 24px !important;
						font-weight: 600 !important;
						color: #545454 !important;
						margin-bottom: 10px !important;
					}

					.wa-thankyou-popup .swal2-html-container {
						margin-top: 0 !important;
					}

					.wa-thankyou-popup .swal2-icon.swal2-success {
						border-color: #5e19cf !important;
					}

					.wa-thankyou-popup .swal2-icon.swal2-success [class^='swal2-success-line'] {
						background-color: #5e19cf !important;
					}

					.wa-thankyou-popup .swal2-icon.swal2-success .swal2-success-ring {
						border-color: rgba(94, 25, 207, 0.3) !important;
					}

					@keyframes slideInFromTop {
						from {
							opacity: 0;
							transform: translateY(-50px);
						}
						to {
							opacity: 1;
							transform: translateY(0);
						}
					}

					@keyframes slideOutToTop {
						from {
							opacity: 1;
							transform: translateY(0);
						}
						to {
							opacity: 0;
							transform: translateY(-50px);
						}
					}
				</style>

                <script type="text/javascript">
				
                jQuery(document).ready(function($) {
					$('#ig-general-feedback p:empty').remove();
					$('#ig-general-feedback label').next('br').remove();
					
					var allowedHashes = ['', '#', '#settings', '#reports', '#/audience', '#campaigns'];
					
					var currentHash = window.location.hash;
					var isHashAllowed = allowedHashes.indexOf(currentHash) !== -1;
					
					if (!isHashAllowed) {
						setTimeout(function() {
							if (typeof Swal !== 'undefined' && Swal.isVisible()) {
								Swal.close();
							}
						}, 10);
						return; 
					}
									
					$(window).on('hashchange', function() {
						var hash = window.location.hash;
						if (allowedHashes.indexOf(hash) === -1) {
							if (typeof Swal !== 'undefined' && Swal.isVisible()) {
								Swal.close();
							}
						}
					});					
					 
					setTimeout(function() {
						$(document).on('change', 'input[name="feedback_data[poll_options]"]', function() {						
							
							var selectedValue = $(this).val();
														
							var data = {
								poll_option: selectedValue,
								additional_feedback: ''
							};
							
							var request = doSend(data);
							
							Swal.update({
								title: '<?php echo esc_js( __( 'Thanks for your input!', 'email-subscribers' ) ); ?>',
								html: '<p style="font-size:15px; color:#333; margin-top: 10px; text-align:center;"><?php echo esc_js( __( 'Your vote helps us decide what to build next.', 'email-subscribers' ) ); ?></p>',
								showClass: {
									popup: 'swal2-show'
								},
								hideClass: {
									popup: 'thankyou swal2-hide'
								},
								customClass: {
									popup: 'wa-thankyou-popup'
								}
							});
							
							setTimeout(function() {
								Swal.close();
							}, 3000);
							 
						});
						
					}, 1000);
				});
				
                </script>
                <?php
				
            }
        }
    }
}
add_action( 'admin_footer', 'ig_es_show_whatsapp_integration_survey' );