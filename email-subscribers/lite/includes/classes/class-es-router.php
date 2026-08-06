<?php

if ( ! class_exists( 'ES_Router' ) ) {

	/**
	 * Class to handle single campaign options
	 * 
	 * @class ES_Router
	 */
	class ES_Router {

		// class instance
		public static $instance;

		const LAST_VISITED_PAGE_OPTION = 'ig_es_last_visited_page';

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
			add_action( 'wp_ajax_icegram-express', array( $this, 'handle_ajax_request' ) );

			add_action( 'current_screen', array( __CLASS__, 'save_last_visited_plugin_page' ) );

			add_action( 'wp_ajax_ig_es_save_last_visited_react_route', array( __CLASS__, 'save_last_visited_react_route' ) );
		}

		/**
		 * Method to draft a campaign
		 *
		 * @return $response Broadcast response.
		 *
		 * @since 4.4.7
		 */
		public function handle_ajax_request() {

			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
		
			$can_access_audience  = ES_Common::ig_es_can_access( 'audience' );
			$can_access_campaign  = ES_Common::ig_es_can_access( 'campaigns' );
			$can_access_forms     = ES_Common::ig_es_can_access( 'forms' );
			$can_access_sequence  = ES_Common::ig_es_can_access( 'sequence' );
			$can_access_reports   = ES_Common::ig_es_can_access( 'reports' );
			$can_access_workflows = ES_Common::ig_es_can_access( 'workflows' );
			if ( ! ( $can_access_audience || $can_access_campaign || $can_access_forms || $can_access_sequence || $can_access_reports || $can_access_workflows ) ) {
				return 0;
			}
		$response = array();
		$request = $_REQUEST;
		
		$handler       = ig_es_get_data( $request, 'handler' );
		$handler_class = 'ES_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $handler ) ) ) . '_Controller';
		
			if ( empty( $handler ) || ! class_exists( $handler_class ) ) {
				$response = array(
				'message' => __( 'No request handler found.', 'email-subscribers' ),
				);
				wp_send_json_error( $response );
			}

		$method = ig_es_get_data( $request, 'method' );			if ( ! method_exists( $handler_class, $method ) || ! is_callable( array( $handler_class, $method ) ) ) {
				$response = array(
					'message' => __( 'No request method found.', 'email-subscribers' ),
				);
				wp_send_json_error( $response );
			}
			
		$data   = ig_es_get_request_data( 'data', array(), false );
		
		// Decode JSON data if it's a string
			if ( is_string( $data ) && ! empty( $data ) ) {
				$decoded = json_decode( $data, true );
				$data = is_array( $decoded ) ? $decoded : array();
			}
		
			if ( isset( $_FILES['async-upload']['tmp_name'] ) ) {
				// Ensure $data is an array when handling file uploads
				if ( ! is_array( $data ) ) {
					$data = array();
				}
				$data['file'] =  sanitize_text_field( $_FILES['async-upload']['tmp_name'] );
			}			$result = call_user_func( array( $handler_class, $method ), $data );

			if ( is_array( $result ) && isset( $result['error'] ) ) {
				$response['success'] = false;
				$response['message'] = $result['error'];
			} elseif ( $result !== false && $result !== null ) {
				$response['success'] = true;
				$response['data']    = $result;
			} else {
				$response['success'] = false;
			}

			wp_send_json( $response );
		}

		/**
		 * Update last visited Express page.
		 *
		 * Avoids unnecessary database writes.
		 *
		 * @since 5.9.32
		 *
		 * @param string $page Page identifier.
		 */
		private static function update_last_visited_plugin_page( $page ) {

			if ( empty( $page ) ) {
				return;
			}

			$last_page = get_option( self::LAST_VISITED_PAGE_OPTION, '' );

			if ( is_array( $last_page ) && isset( $last_page['page'] ) && $last_page['page'] === $page ) {
				return;
			}

			update_option(
				self::LAST_VISITED_PAGE_OPTION,
				array(
					'page'       => $page,
					'visited_at' => ig_get_current_date_time(),
				),
				false
			);
		}

		/**
		 * Store last visited WordPress admin page.
		 *
		 * @since 5.9.32
		 */
		public static function save_last_visited_plugin_page() {

			$current_page = sanitize_key( ig_es_get_request_data( 'page' ) );

			if ( empty( $current_page ) ) {
				return;
			}

			if ( ES()->is_es_admin_screen() ) {
				self::update_last_visited_plugin_page( $current_page );
			}
		}

		/**
		 * Store last visited React dashboard page.
		 *
		 * Called via wp_ajax on every React route change.
		 *
		 * @since 5.9.32
		 */
		public static function save_last_visited_react_route() {

			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( null, 403 );
			}

			$raw_data = ig_es_get_request_data( 'data', '', false );
			$data     = json_decode( $raw_data, true );

			if ( ! is_array( $data ) ) {
				wp_send_json_success();
			}

			$wp_page = isset( $data['wp_page'] )
				? sanitize_key( $data['wp_page'] )
				: '';

			$route = isset( $data['page'] )
				? sanitize_text_field( $data['page'] )
				: '';

			if ( ! in_array( $wp_page, ig_es_get_es_admin_pages(), true ) ) {
				wp_send_json_success();
			}

			if ( ! empty( $route ) && preg_match( '#^/[a-zA-Z0-9/_-]*$#', $route ) ) {

				$page = $wp_page . '#' . $route;

				self::update_last_visited_plugin_page( $page );
			}

			wp_send_json_success();
		}
	}
}

ES_Router::get_instance();

