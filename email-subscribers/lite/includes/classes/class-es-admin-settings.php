<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Admin Settings
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 */
class ES_Admin_Settings {

	public static $instance;

	public $subscribers_obj;


	public function __construct() {
		add_filter( 'ig_es_registered_email_sending_settings', array( $this, 'show_cron_info' ) );

		// Start-IG-Code.
		// Add setting for plugin usage tracking
		add_filter( 'ig_es_registered_settings', array( $this, 'show_usage_tracking_optin_setting' ), 30 );
		// End-IG-Code.
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.8.4
	 */
	public static function screen_options() {

		$action = ig_es_get_request_data( 'action' );

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Number of seetings per page', 'email-subscribers' ),
			'default' => 20,
			//'option'  => self::$option_per_page,
		);

		add_screen_option( $option, $args );

	}

	public function es_settings_callback() {

		$submitted     = ig_es_get_request_data( 'submitted' );
		$submit_action = ig_es_get_request_data( 'submit_action' );

		if ( 'submitted' === $submitted && 'ig-es-save-admin-settings' === $submit_action ) {

			$nonce = ig_es_get_request_data( 'update-settings' );
			if ( ! wp_verify_nonce( $nonce, 'update-settings' ) ) {
				$message = __( 'You do not have permission to update settings', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {

				$options = ig_es_get_data( $_POST );
				
				ES_Settings_Controller::save_settings( $options );

				$message = __( 'Settings saved successfully!' );
				$status  = 'success';
				ES_Common::show_message( $message, $status );
			}
		}
		$allowedtags = ig_es_allowed_html_tags_in_esc();

		?>

		<div class="sticky top-0 z-10">
			<header>
				<nav aria-label="Global" class="pb-5 w-full pt-2">
					<div class="brand-logo">
						<span>
							<img src="<?php echo ES_PLUGIN_URL . 'lite/admin/images/new/brand-logo/IG LOGO 192X192.svg'; ?>" alt="brand logo" />
							<div class="divide"></div>
							<h1><?php esc_html_e( 'Settings', 'email-subscribers' ); ?></h1>
						</span>
					</div>
				</nav>
			</header>
		</div>
		
		<form action="" method="post" id="email_tabs_form" class="overview bg-white rounded-lg shadow">
			<div class="flex flex-wrap mt-7">
				<?php
				settings_fields( 'email_subscribers_settings' );
				$es_settings_tabs = array(
					'general'             => array(
						'icon' => '<svg class="w-6 h-6 inline -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>',
						'name' => __( 'General', 'email-subscribers' ),
					),
					'email_sending'       => array(
						'icon' => '<svg class="w-6 h-6 inline -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>',
						'name' => __( 'Email Sending', 'email-subscribers' ),
					),
					'security_settings'   => array(
						'icon' => '<svg class="w-6 h-6 inline -mt-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>',
						'name' => __( 'Security', 'email-subscribers' ),
					),
				);
				if ( ES_Common::is_rest_api_supported() ) {
					$es_settings_tabs['rest_api_settings'] = array(
						'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 inline -mt-1.5" style="stroke-width:2">
						<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z" />
					  </svg>',
						'name' => __( 'API', 'email-subscribers' ),
					);
				}
				$es_settings_tabs = apply_filters( 'ig_es_settings_tabs', $es_settings_tabs );
				?>
				<div id="es-settings-menu" class="">
					<div class="es-menu-list" id="menu-content">
						<ul id="menu-nav" class="list-reset">
							<?php
							foreach ( $es_settings_tabs as $key => $value ) {
								?>
								<li id="menu-content">
									<a href="#tabs-<?php echo esc_attr( $key ); ?>" id="menu-content-change"><?php echo wp_kses( $value['icon'], $allowedtags ); ?>&nbsp;<span><?php echo esc_html( $value['name'] ); ?></span></a></li>
									<?php
							}
							?>
						</ul>
					</div>
				</div>

				<div class="w-4/5" id="es-menu-tab-content">
					<?php
					$settings = ES_Settings_Controller::get_registered_settings();
					foreach ( $settings as $key => $value ) {
						?>
						<div id="tabs-<?php echo esc_attr( $key ); ?>" class="setting-content"><?php ES_Settings_Controller::render_settings_fields( $value ); ?></div>
						<?php
					}
					?>

				</div>
			</div>
		</form>
		<?php
	}


	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

		

	/**
	 * Register ES cron info
	 *
	 * @return array $email_sending_settings ES send email settings.
	 *
	 * @since 4.4.9
	 */
	public function show_cron_info( $email_sending_settings ) {
		$es_cron_enabled = ES()->cron->is_wp_cron_enable();
		if ( $es_cron_enabled ) {
			$es_cron_info           = array(
				'ig_es_cron_info' => array(
					'id'   => 'ig_es_cron_info',
					'name' => __( 'Cron Info', 'email-subscribers' ),
					'type' => 'html',
					'html' => self::render_cron_info_html(),
				),
			);
			$email_sending_settings = ig_es_array_insert_after( $email_sending_settings, 'ig_es_cronurl', $es_cron_info );
		}

		return $email_sending_settings;
	}

	/**
	 * Render ES cron info html
	 *
	 * @return false|string
	 *
	 * @since 4.4.9
	 */
	public static function render_cron_info_html() {
		$site_crons = get_option( 'cron' );

		if ( empty( $site_crons ) ) {
			return;
		}

		$es_cron_enabled = ES()->cron->is_wp_cron_enable();

		$es_crons_data  = array();
		$es_cron_events = array(
			'ig_es_cron',
			'ig_es_cron_worker',
			'ig_es_cron_auto_responder',
			'ig_es_summary_automation',
		);

		$cron_schedules = wp_get_schedules();
		$time_offset    = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		$date_format    = get_option( 'date_format' );
		$time_format    = get_option( 'time_format' );

		foreach ( $site_crons as $next_scheduled_time => $scheduled_crons ) {
			if ( ! empty( $scheduled_crons ) && is_array( $scheduled_crons ) ) {
				foreach ( $scheduled_crons as $cron_event => $cron_data ) {
					if ( ! in_array( $cron_event, $es_cron_events, true ) ) {
						continue;
					}
					foreach ( $cron_data as $cron_info ) {
						if ( ! empty( $cron_info['schedule'] ) ) {
							$cron_schedule                = $cron_info['schedule'];
							$cron_interval                = ! empty( $cron_schedules[ $cron_schedule ]['interval'] ) ? $cron_schedules[ $cron_schedule ]['interval'] : 0;
							$es_crons_data[ $cron_event ] = array(
								'cron_interval'       => $cron_interval,
								'next_scheduled_time' => $next_scheduled_time,
							);
						}
					}
				}
			}
		}

		$html = '';
		if ( ! empty( $es_crons_data ) ) {
			ob_start();
			?>
			<table class="cron-info">
				<thead>
				<tr>
					<th><?php echo esc_html__( 'Event', 'email-subscribers' ); ?></th>
					<th><?php echo esc_html__( 'Interval', 'email-subscribers' ); ?></th>
					<th><?php echo esc_html__( 'Next Execution', 'email-subscribers' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $es_cron_events as $cron_event ) {
					$cron_interval       = '';
					$next_scheduled_time = '';
					if ( ! empty( $es_crons_data[ $cron_event ] ) ) {
						$es_cron_data        = $es_crons_data[ $cron_event ];
						$cron_interval       = $es_cron_data['cron_interval'];
						$next_scheduled_time = $es_cron_data['next_scheduled_time'];
					} else {
						if ( 'ig_es_cron_auto_responder' === $cron_event ) {
							wp_schedule_event( floor( time() / 300 ) * 300 - 120, 'ig_es_cron_interval', 'ig_es_cron_auto_responder' );
						} elseif ( 'ig_es_cron_worker' === $cron_event ) {
							wp_schedule_event( floor( time() / 300 ) * 300, 'ig_es_cron_interval', 'ig_es_cron_worker' );
						} elseif ( 'ig_es_cron' === $cron_event ) {
							wp_schedule_event( strtotime( 'midnight' ) - 300, 'hourly', 'ig_es_cron' );
						}
						$next_scheduled_time = wp_next_scheduled( $cron_event );
						if ( 'ig_es_cron' === $cron_event ) {
							$cron_interval = 3600; // Hourly interval for ig_es_cron.
						} else {
							$cron_interval = ES()->cron->get_cron_interval();
						}
					}
					if ( empty( $cron_interval ) || empty( $next_scheduled_time ) ) {
						continue;
					}
					?>
					<tr>
						<td>
							<div class="flex items-center">
								<div class="flex-shrink-0">
									<b><?php echo esc_html( $cron_event ); ?></b>
								</div>
							</div>
						</td>
						<td>
							<?php
								echo esc_html( ig_es_get_human_interval( $cron_interval ) );
							?>
						</td>
						<td>
							<?php /* translators: %s: Next scheduled time */ ?>
							<b><?php echo esc_html( sprintf( __( 'In %s', 'email-subscribers' ), human_time_diff( time(), $next_scheduled_time ) ) ); ?></b><br>
							<span title="<?php echo esc_attr( 'UTC: ' . date_i18n( $date_format . ' ' . $time_format, $next_scheduled_time ) ); ?>">
								<?php echo esc_html( date_i18n( $date_format . ' ' . $time_format, $next_scheduled_time + $time_offset ) ); ?>
							</span>
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
			<?php
			$html = ob_get_clean();
		}

		return $html;
	}

	/**
	 * Add setting for plugin usage tracking
	 *
	 * @param array $es_settings
	 *
	 * @return array $es_settings
	 *
	 * @since 4.7.7
	 */
	public function show_usage_tracking_optin_setting( $es_settings ) {

		// Show option to enable/disable tracking if user isn't a premium user and trial is not valid i.e. has expired.
		if ( ! ES()->is_premium() && ! ES()->trial->is_trial_valid() ) {

			$allow_tracking = array(
				'ig_es_allow_tracking' => array(
					'id'      => 'ig_es_allow_tracking',
					'name'    => __( 'Plugin usage tracking', 'email-subscribers' ),
					'type'    => 'checkbox',
					'default' => 'no',
					'info'    => __( 'Help us to improve Icegram Express by opting in to share non-sensitive plugin usage data.', 'email-subscribers' ),
				),
			);

			$general_fields = $es_settings['general'];

			$general_fields = ig_es_array_insert_after( $general_fields, 'ig_es_intermediate_unsubscribe_page', $allow_tracking );

			$es_settings['general'] = $general_fields;
		}

		return $es_settings;
	}

	

	
}
