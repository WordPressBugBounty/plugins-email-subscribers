<?php

if ( ! class_exists( 'ES_Help_Info_Controller' ) ) {

	/**
	 * Class to handle hel and info operation
	 * 
	 * @class ES_Help_Info_Controller
	 */
	class ES_Help_Info_Controller {

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

		public static function	get_other_plugins() {
			$ig_install_url            = admin_url( 'plugin-install.php?s=icegram&tab=search&type=term' );
			$rainmaker_install_url     = admin_url( 'plugin-install.php?s=rainmaker&tab=search&type=term' );
			$smart_manager_install_url = admin_url( 'plugin-install.php?s=smart+manager&tab=search&type=term' );
			$tlwp_install_url          = admin_url( 'plugin-install.php?s=temporary+login+without+password&tab=search&type=term' );
			$duplicate_install_url     = admin_url( 'plugin-install.php?s=icegram&tab=search&type=author' );
			$ig_mailer_install_url     = admin_url( 'plugin-install.php?s=icegram%2520mailer&tab=search&type=term' );
			$ig_coockie_manager_install_url     = admin_url( 'plugin-install.php?s=Icegram%2520Cookie%2520Manager&tab=search&type=term' );
			$ig_user_switching_install_url		= admin_url( 'plugin-install.php?s=switch%2520user%2520login%2520by%2520icegram&tab=search&type=term' );
			
			return array(
				array(
					'title'       => __( 'Icegram mailer', 'email-subscribers' ),
					'logo'        => 'https://ps.w.org/icegram-mailer/assets/icon-256x256.png',
					'desc'        => __( 'Use our built-in service, Mailer, for stress-free email delivery. No SMTP, no tech setup required. Send emails that actually reach inboxes.', 'email-subscribers' ),
					'name'        => 'icegram-mailer/icegram-mailer.php',
					'install_url' => $ig_mailer_install_url,
					'plugin_url'  => 'https://wordpress.org/plugins/icegram-mailer/',
				),
				array(
					'title'       => __( 'Icegram Engage', 'email-subscribers' ),
					'logo'        => 'https://ps.w.org/icegram/assets/icon-128x128.png',
					'desc'        => __( 'The best WP popup plugin that creates a popup. Customize popup, target popups to show offers, email signups, social buttons, etc and increase conversions on your website.', 'email-subscribers' ),
					'name'        => 'icegram/icegram.php',
					'install_url' => $ig_install_url,
					'plugin_url'  => 'https://wordpress.org/plugins/icegram/',
				),
				array(
					'title'       => __( 'Icegram Collect', 'email-subscribers' ),
					'logo'        => 'https://ps.w.org/icegram-rainmaker/assets/icon-128x128.png',
					'desc'        => __( 'Get readymade contact forms, email subscription forms and custom forms for your website. Choose from beautiful templates and get started within seconds', 'email-subscribers' ),
					'name'        => 'icegram-rainmaker/icegram-rainmaker.php',
					'install_url' => $rainmaker_install_url,
					'plugin_url'  => 'https://wordpress.org/plugins/icegram-rainmaker/',
			
				),
				array(
					'title'       => __( 'Duplicate Pages and Posts', 'email-subscribers' ),
					'logo'        => 'https://ps.w.org/duplicate-post-page-copy-clone-wp/assets/icon-256X256.png',
					'desc'        => __( 'A Duplicate Pages and Posts Plugin is a tool for WordPress that enables users to easily create duplicate versions of existing posts, pages, or custom post types with just a click.', 'email-subscribers' ),
					'name'        => 'duplicate-post-page-copy-clone-wp/duplicate-post-page-copy-clone-wp.php',
					'install_url' => $duplicate_install_url,
					'plugin_url'  => 'https://wordpress.org/plugins/duplicate-post-page-copy-clone-wp/',
				),
				array(
					'title'       => __( 'Icegram Cookie Manager', 'email-subscribers' ),
					'logo'        => 'https://s.w.org/plugins/geopattern-icon/switch-user-login-by-icegram.svg',
					'desc'        => __( 'Show a clean cookie consent bar on your site. Stay GDPR compliant, control tracking scripts, and give visitors full transparency without slowing down your site.', 'email-subscribers' ),
					'name'        => 'icegram-cookie-manager/icegram-cookie-manager.php',
					'install_url' => $ig_coockie_manager_install_url,
					'plugin_url'  => 'https://wordpress.org/plugins/icegram-cookie-manager/',
				),
				array(
					'title'       => __( 'Switch User Login By Icegram', 'email-subscribers' ),
					'logo'        => 'https://s.w.org/plugins/geopattern-icon/switch-user-login-by-icegram.svg',
					'desc'        => __( 'Quickly switch between WordPress users without logging out. Simplify admin tasks, test user roles, and manage accounts effortlessly with one click.', 'email-subscribers' ),
					'name'        => 'switch-user-login-by-icegram/switch-user-login-by-icegram.php',
					'install_url' => $ig_user_switching_install_url,
					'plugin_url'  => 'https://wordpress.org/plugins/switch-user-login-by-icegram/',
				),
				array(
					'title'       => __( 'Smart Manager For WooCommerce', 'email-subscribers' ),
					'logo'        => 'https://ps.w.org/smart-manager-for-wp-e-commerce/assets/icon-128x128.png',
					'desc'        => __( 'The #1 and a powerful tool to manage stock, inventory from a single place. Super quick and super easy', 'email-subscribers' ),
					'name'        => 'smart-manager-for-wp-e-commerce/smart-manager.php',
					'install_url' => $smart_manager_install_url,
					'plugin_url'  => 'https://wordpress.org/plugins/smart-manager-for-wp-e-commerce/',
				),
			
				array(
					'title'       => __( 'Temporary Login Without Password', 'email-subscribers' ),
					'logo'        => 'https://ps.w.org/temporary-login-without-password/assets/icon-128x128.png',
					'desc'        => __( 'Create self-expiring, automatic login links for WordPress. Give them to developers when they ask for admin access to your site.', 'email-subscribers' ),
					'name'        => 'temporary-login-without-password/temporary-login-without-password.php',
					'install_url' => $tlwp_install_url,
					'plugin_url'  => 'https://wordpress.org/plugins/temporary-login-without-password/',
				),
			);

		}
		public static function	get_help_articles() {

			return array(
				array(
					'title' => 'Create and Send Newsletter Emails',
					'link'  => 'https://www.icegram.com/documentation/es-how-to-create-and-send-newsletter-emails/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page',
					'class' => 'font-medium text-blue-500 hover:text-blue-700',
				),
				array(
					'title' => 'Schedule Cron Emails in cPanel',
					'link'  => 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page',
					'class' => 'font-medium text-blue-500 hover:text-blue-700',
				),
				array(
					'title' => 'How to enable consent checkbox in the subscribe form?',
					'link'  => 'https://www.icegram.com/documentation/es-gdpr-how-to-enable-consent-checkbox-in-the-subscription-form/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page',
					'class' => 'font-medium text-blue-500 hover:text-blue-700',
				),
				array(
					'title' => 'What data Icegram Express stores on your end?',
					'link'  => 'https://www.icegram.com/documentation/es-gdpr-what-data-email-subscribers-stores-on-your-end/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page',
					'class' => 'font-medium text-blue-500 hover:text-blue-700',
				),
				array(
					'title' => 'Create and Send Post Notification Emails when new posts are published',
					'link'  => 'https://www.icegram.com/documentation/es-how-to-create-and-send-post-notification-emails-when-new-posts-are-published/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page',
					'class' => 'font-medium text-blue-500 hover:text-blue-700',
				),
				array(
					'title' => 'Keywords in the Broadcast',
					'link'  => 'https://www.icegram.com/documentation/what-keywords-can-be-used-while-designing-the-campaign/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page',
					'class' => 'font-medium text-blue-500 hover:text-blue-700',
				),
				array(
					'title' => 'Keywords in the Post Notifications',
					'link'  => 'https://www.icegram.com/documentation/what-keywords-can-be-used-while-designing-the-campaign/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page',
					'class' => 'font-medium text-blue-500 hover:text-blue-700',
				),
			);

		}
		public static function	get_useful_articles() {
			return ES_Common::get_useful_articles();
		}

		public static function	get_plugins() {
			global $ig_es_tracker;
			$active_plugins            = $ig_es_tracker::get_active_plugins();
			$inactive_plugins          = $ig_es_tracker::get_inactive_plugins();
			$all_plugins               = $ig_es_tracker::get_plugins();

		return array(
				'active_plugins' => $active_plugins,
				'inactive_plugins' => $inactive_plugins,
				'all_plugins' => $all_plugins,
			);

		}


		
	}

}

ES_Help_Info_Controller::get_instance();
