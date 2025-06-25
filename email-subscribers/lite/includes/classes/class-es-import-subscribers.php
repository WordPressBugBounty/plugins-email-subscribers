<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Import_Subscribers {

	private $api;
	/**
	 * ES_Import_Subscribers constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Method to hook ajax handler for import process
	 */
	public function init() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_ig_es_import_subscribers_upload_handler', array( &$this, 'ajax_import_subscribers_upload_handler' ) );
			add_action( 'wp_ajax_ig_es_get_import_data', array( &$this, 'ajax_get_import_data' ) );
			add_action( 'wp_ajax_ig_es_do_import', array( &$this, 'ajax_do_import' ) );
			add_action( 'wp_ajax_ig_es_mailchimp_verify_api_key', array( &$this, 'api_request' ) );
			add_action( 'wp_ajax_ig_es_mailchimp_lists', array( &$this, 'api_request' ) );
			add_action( 'wp_ajax_ig_es_mailchimp_import_list', array( &$this, 'api_request' ) );

			add_action( 'ig_es_remove_import_data', array( __CLASS__, 'remove_import_data' ) );
		}
		add_action( 'ig_es_after_bulk_contact_import', array( $this, 'handle_after_bulk_contact_import' ) );
		add_action( 'ig_es_new_contact_inserted', array( $this, 'handle_new_contact_inserted' ) );
	}

	/**
	 * Import Contacts
	 *
	 * @since 4.0,0
	 *
	 * @modify 4.3.1
	 *
	 * @modfiy 4.4.4 Moved importing code section to maybe_start_import method.
	 */
	public function import_callback() {

		$this->prepare_import_subscriber_form();
	}

	public function prepare_import_subscriber_form() {

		if ( is_multisite() && ! is_upload_space_available() ) {
			return;
		}

		$max_upload_size = $this->get_max_upload_size();
		$post_params     = array(
			'action'         => 'ig_es_import_subscribers_upload_handler',
			'importing_from' => 'csv',
			'security'       => wp_create_nonce( 'ig-es-admin-ajax-nonce' ),
		);

		$upload_action_url = admin_url( 'admin-ajax.php' );
		$plupload_init     = array(
			'browse_button'    => 'plupload-browse-button',
			'container'        => 'plupload-upload-ui',
			'drop_element'     => 'drag-drop-area',
			'file_data_name'   => 'async-upload',
			'url'              => $upload_action_url,
			'filters'          => array(
				'max_file_size' => $max_upload_size . 'b',
				'mime_types'    => array( array( 'extensions' => 'csv' ) ),
			),
			'multipart_params' => $post_params,
		);

		$allowedtags = ig_es_allowed_html_tags_in_esc();
		?>
		<script type="text/javascript">
			let wpUploaderInit = <?php echo wp_json_encode( $plupload_init ); ?>;
		</script>
		<div class="tool-box">
			<div class="meta-box-sortables ui-sortable bg-white rounded-lg">
				<div class="es-import-option bg-white rounded-lg">
					<div class="mx-auto flex justify-center pt-2">
						<label class="inline-flex items-center cursor-pointer mr-2 w-48">
							<input type="radio" class="absolute w-0 h-0 opacity-0 es_mailer" name="es-import-subscribers" value="es-import-with-csv" checked />
							<div class="mt-6 px-3 py-1 border border-gray-200 rounded-lg shadow-md es-mailer-logo es-importer-logo bg-white">
								<div class="border-0 es-logo-wrapper">
									<svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
								</div>
								<p class="mb-2 text-sm inline-block font-medium text-gray-600">
									<?php echo esc_html__( 'Import from CSV', 'email-subscribers' ); ?>
								</p>
							</div>
						</label>
						<label class="inline-flex items-center cursor-pointer w-48 mr-2">
							<input type="radio" class="absolute w-0 h-0 opacity-0 es_mailer" name="es-import-subscribers" value="es-import-mailchimp-users" />
							<div class="mt-6 px-1 mx-4 border border-gray-200 rounded-lg shadow-md es-mailer-logo es-importer-logo bg-white">
								<div class="border-0 es-logo-wrapper">
									<img class="h-full w-24" src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/admin/images/mailchimp_logo.png' ); ?>" alt="Icegram.com" />
								</div>
								<p class="mb-2 text-sm inline-block font-medium text-gray-600">
									<?php echo esc_html__( 'Import from MailChimp', 'email-subscribers' ); ?>
								</p>
							</div>
						</label>
						<?php
							do_action( 'ig_es_subscriber_import_method_tab_heading' );
						?>
					</div>
					<hr class="mx-4 border-gray-100 mt-6">
				</div>
				<form class="ml-8 mr-4 text-left py-4 my-2 item-center" method="post" name="form_import_subscribers" id="form_import_subscribers" action="#" enctype="multipart/form-data">
					<div class="es-import-step1 flex flex-row">
						<div class="w-full flex flex-row es-import-with-csv es-import">
							<div class="es-import-processing flex es-w-25">
								<div class="pt-6">
									<label for="select_csv">
										<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">
											<?php esc_html_e( 'Select CSV file', 'email-subscribers' ); ?>
										</span>
										<p class="mt-2 italic text-xs text-gray-400">
											<?php
											/* translators: %s: Max upload size */
											echo sprintf( esc_html__( 'File size should be less than %s', 'email-subscribers' ), esc_html( size_format( $max_upload_size ) ) );
											?>
										</p>
										<p class="mt-2 italic text-xs text-gray-400">
											<?php esc_html_e( 'Check CSV structure', 'email-subscribers' ); ?>
											<a class="font-bold hover:underline text-indigo-600 font-sans" target="_blank" href="<?php echo esc_attr( plugin_dir_url( __FILE__ ) ) . '../../admin/partials/sample.csv'; ?>"><?php esc_html_e( 'from here', 'email-subscribers' ); ?></a>
										</p>
										<p class="mt-3">
											<a class="hover:underline text-base font-bold italic text-sm text-indigo-600 font-sans" href="https://www.icegram.com/documentation/es-how-to-import-or-export-email-addresses/?utm_source=in_app&utm_medium=import_contacts&utm_campaign=es_doc" target="_blank">
											<?php esc_html_e( 'How to import contacts using CSV?', 'email-subscribers' ); ?>&rarr;
										</a>
										</p>
									</label>
								</div>
							</div>
							<div class="es-w-65 ml-12 mr-4">
								<div class="es-import-step1-body">
									<div class="upload-method">
										<div id="media-upload-error"></div>
										<div id="plupload-upload-ui" class="hide-if-no-js">
											<div id="drag-drop-area">
												<div class="drag-drop-inside">
													<p class="drag-drop-info"><?php esc_html_e( 'Drop your CSV here', 'email-subscribers' ); ?></p>
													<p><?php echo esc_html_x( 'or', 'Uploader: Drop files here - or - Select Files', 'email-subscribers' ); ?></p>
													<p class="drag-drop-buttons"><button id="plupload-browse-button" type="submit" class="primary"><?php esc_attr_e( 'Select File', 'email-subscribers' ); ?></button></p>
												</div>
											</div>
										</div>
									</div>
								</div>
								<p class="import-status pt-4 pb-1 text-base font-medium text-gray-600 tracking-wide hidden">&nbsp;</p>
								<div id="progress" class="progress hidden"><span class="bar" style="width:0%"><span></span></span></div>
							</div>
						</div>

						<div class="w-full flex flex-row es-import-mailchimp-users es-import" style="display: none">
							<div class="es-import-processing flex es-w-25">
								<div class="ml-6 pt-6">
									<label for="select_mailchimp_users">
										<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">
											<?php esc_html_e( 'Enter your API Key', 'email-subscribers' ); ?>
										</span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug" id="apikey-info-text"><?php esc_html_e( 'You need your API key from Mailchimp to import your data.', 'email-subscribers' ); ?>
										</p>
										<p class="italic text-xs font-medium hover:underline text-indigo-600 font-sans mt-3">
											 <a href="https://admin.mailchimp.com/account/api-key-popup/" onclick="window.open(this.href, 'email-subscribers', 'width=600,height=600');return false;"><?php esc_html_e( 'Click here to get it.', 'email-subscribers' ); ?>
											 </a>
										</p>
									</label>
								</div>
							</div>
							<div class="es-w-65 ml-8 pt-6 mr-4">
								<div>
									<label><input name="apikey" type="text" id="api-key" class="form-input text-sm w-1/2" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus tabindex="1" placeholder="12345678901234567890123456789012-xx1" class=""></label>
								</div>
								<p class="es-api-import-status pt-4 text-sm font-medium text-gray-600 tracking-wide hidden">&nbsp;</p>
								<div class="clearfix clear mt-8 ">
									<button id="es_mailchimp_verify_api_key" class="primary" data-callback="verify_api_key">
										<?php echo esc_html__( 'Next', 'email-subscribers' ); ?>
											&nbsp;
										<svg style="display:none" class="es-import-loader mr-1 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
										  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
										</svg>
									</button>
								</div>

							</div>
						</div>

						<?php
							do_action( 'ig_es_subscriber_import_method_tab_content' );
						?>


					</div>

					<div class="mailchimp_import_step_1 w-full" style="display:none">
						<div class="flex flex-row pt-6 pb-5 border-b">
							<div class="flex es-w-25 pt-6">
								<div>
									<label for="select_mailchimp_list">
										<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">
											<?php echo esc_html_e( 'Select list', 'email-subscribers' ); ?>
										</span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug" id="apikey-info-text"><?php esc_html_e( 'Select all the lists that you want to import from MailChimp', 'email-subscribers' ); ?>
										</p>
									</label>
								</div>
							</div>

							<div class="es-w-65 ml-8">
								<ul class="es_mailchimp_lists_and_status_input mailchimp-lists">
									<li class="hidden" data-error-counter="0">
										<input type="checkbox" name="lists[]" class="form-checkbox" value="" id="">

										<label for="">
											<i></i>

											<span class="mailchimp_list_name"></span>
											<span class="mailchimp_list_contact_fetch_count"></span>
										</label>
									</li>
								</ul>
							</div>
						</div>

						<div class="flex flex-row">
							<div class="flex es-w-25 pt-6">
								<div class="ml-6">
									<label for="select_mailchimp_list">
										<span class="block pr-4 text-sm font-medium text-gray-600 pb-1"><?php esc_html_e( 'Select Status', 'email-subscribers' ); ?></span><span class="chevron"></span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug" id="apikey-info-text"><?php esc_html_e( 'Select the status of the contacts that you want to import from MailChimp', 'email-subscribers' ); ?>
										</p>
									</label>
								</div>
							</div>
							<div class="es-w-65 ml-8">
								<div>
									<ul class="es_mailchimp_lists_and_status_input pt-6">
										<li>
											<input type="checkbox" name="options" class="form-checkbox" value="subscribed" checked id="import_subscribed">
											<label for="import_subscribed">
												<i></i>
												<span><?php esc_html_e( 'Import with status "subscribed"', 'email-subscribers' ); ?></span>
											</label>
										</li>

										<li>
											<input type="checkbox" name="options" class="form-checkbox" value="pending" id="import_pending">
											<label for="import_pending">
												<i></i>
												<span><?php esc_html_e( 'Import with status "pending"', 'email-subscribers' ); ?></span>
											</label>
										</li>

										<li>
											<input type="checkbox" name="options" class="form-checkbox" value="unsubscribed" id="import_unsubscribed">
											<label for="import_unsubscribed">
												<i></i>
												<span><?php esc_html_e( 'Import with status "unsubscribed"', 'email-subscribers' ); ?></span>
											</label>
										</li>

										<li>
											<input type="checkbox" name="options" class="form-checkbox" value="cleaned" id="import_cleaned">
											<label for="import_cleaned">
												<i></i>
												<span><?php esc_html_e( 'Import with status "cleaned"', 'email-subscribers' ); ?></span>
											</label>
										</li>
									</ul>
								</div>
								<div> <span class="mailchimp_notice_nowindow_close text-sm font-medium text-yellow-600 tracking-wide"></span></div>
								<div class="clearfix clear mt-8">
									<button id="es_import_mailchimp_list_members" class="primary" data-callback="import_lists">
										<?php esc_html_e( 'Next', 'email-subscribers' ); ?> &nbsp;
										<svg style="display:none" class="es-list-import-loader mr-1 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
										  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
										</svg>
									</button>
								</div>
							</div>
						</div>

					</div>

					<div class="step2 w-full overflow-auto mb-6 mr-4 mt-4 border-b border-gray-100">
						<h2 class="import-status text-base font-medium text-gray-600 tracking-wide"></h2>
						<div class="step2-body overflow-auto pb-4"></div>
						<p class="import-instruction text-base font-medium text-yellow-600 tracking-wide"></p>
						<div id="importing-progress" class="importing-progress hidden mb-4 mr-2 text-center"><span class="bar" style="width:0%"><p class="block import_percentage text-white font-medium text-sm"></p></span></div>
					</div>
					<div class="step2-status es-email-status-container">
						<div class="step2-status flex flex-row border-b border-gray-100">
							<div class="flex es-w-25">
								<div class="ml-3 pt-5">
									<label for="import_contact_list_status"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Select status', 'email-subscribers' ); ?> </span>
									</label>
								</div>
							</div>
							<div class="es-w-65 mb-6 mr-4 mt-3">
								<select class="relative form-select shadow-sm border border-gray-400 w-32" name="es_email_status" id="es_email_status">
									<?php
									$statuses_dropdown = ES_Common::prepare_statuses_dropdown_options();
									echo wp_kses( $statuses_dropdown, $allowedtags );
									?>
								</select>
							</div>
						</div>
					</div>
					<div class="step2-list">
						<div class="step2-list flex flex-row border-b border-gray-100">
							<div class="flex es-w-25">
								<div class="ml-3 pt-5">
									<label for="tag-email-group"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Select list', 'email-subscribers' ); ?></span>
									</label>
								</div>
							</div>
							<div class="w-40 mb-6 mr-4 mt-3">
								<?php
								// Allow multiselect for lists field in the pro version by changing list field's class,name and adding multiple attribute.
								if ( ES()->is_pro() ) {
									$select_list_attr  = 'multiple="multiple"';
									$select_list_name  = 'list_id[]';
									$select_list_class = 'ig-es-form-multiselect';
								} else {
									$select_list_attr  = '';
									$select_list_name  = 'list_id';
									$select_list_class = 'form-select';
								}
								?>
								<div>
									<select name="<?php echo esc_attr( $select_list_name ); ?>" id="list_id" class="relative shadow-sm border border-gray-400 w-32 <?php echo esc_attr( $select_list_class ); ?>" <?php echo esc_attr( $select_list_attr ); ?>>
										<?php
										$lists_dropdown = ES_Common::prepare_list_dropdown_options();
										echo wp_kses( $lists_dropdown, $allowedtags );
										?>
									</select>
								</div>
							</div>
						</div>

					</div>
					<div class="step2-send-optin-emails hidden">
						<div class="step2-send-optin-emails flex flex-row border-b border-gray-100">
							<div class="flex es-w-25">
								<div class="ml-3 pt-5">
									<label for="import_contact_list_status"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Send Confirmation/Welcome emails for this import?', 'email-subscribers' ); ?> </span>
									</label>
								</div>
							</div>
							<div class="w-40 mr-4 h-10 mt-3">
								<label for="send_optin_emails"
									   class="inline-flex items-center mt-4 mb-1 cursor-pointer">
									<span class="relative">
										<input id="send_optin_emails" type="checkbox" name="send_optin_emails"
											   value="yes" class="sr-only peer absolute es-check-toggle opacity-0 w-0 h-0 ">
										<div class="w-11 h-6 bg-gray-200 rounded-full peer  dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
									</span>
								</label>
							</div>
						</div>
					</div>

					<div class="step2-update-existing-subscribers">
						<div class="flex flex-row border-gray-100">
							<div class="flex es-w-25">
								<div class="ml-3 pt-5">
									<label><span class="pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Update existing subscribers', 'email-subscribers' ); ?> </span>
									</label>
								</div>
							</div>
								
							<div class="w-auto mb-6 mr-4 mt-4 pt-2">
								<div class="flex flex-row">
									<div class="w-1/2">
										<label class="mr-4">
											<input type="radio" name="ig-es-update-subscriber-data" class="form-radio" value="yes">
											<?php echo esc_html__( 'Yes', 'email-subscribers' ); ?>
										</label>
										<label>
											<input type="radio" name="ig-es-update-subscriber-data" class="form-radio" value="no" checked>
											<?php echo esc_html__( 'No', 'email-subscribers' ); ?>
										</label>
									</div>									
								</div>
							</div>	

						</div>
					</div>

					<div class="wrapper-start-contacts-import" style="padding-top:10px;">
							<?php wp_nonce_field( 'import-contacts', 'import_contacts' ); ?>
							<button type="submit" name="submit" class="primary"><?php esc_html_e( 'Import', 'email-subscribers' ); ?></button>
					</div>
				</form>
			</div>
			<div class="import-progress">
			</div>
		</div>
		<?php
	}

	/**
	 * Show import contacts
	 *
	 * @since 4.0.0
	 */
	public function import_subscribers_page() {

		$audience_tab_main_navigation = array();
		$active_tab                   = 'import';
		$audience_tab_main_navigation = apply_filters( 'ig_es_audience_tab_main_navigation', $active_tab, $audience_tab_main_navigation );

		?>

		<div class="max-w-full font-sans">
			<?php
			ES_Contacts_Table::render_header('import'); //Rendering Header
			
			$this->import_callback(); 
			?>
		</div>

		<?php
	}

	/**
	 * Get CSV file delimiter
	 *
	 * @param $file
	 * @param int  $check_lines
	 *
	 * @return mixed
	 *
	 * @since 4.3.1
	 */
	public function get_delimiter( $file, $check_lines = 2 ) {

		$file = new SplFileObject( $file );

		$delimiters = array( ',', '\t', ';', '|', ':' );
		$results    = array();
		$i          = 0;
		while ( $file->valid() && $i <= $check_lines ) {
			$line = $file->fgets();
			foreach ( $delimiters as $delimiter ) {
				$regExp = '/[' . $delimiter . ']/';
				$fields = preg_split( $regExp, $line );
				if ( count( $fields ) > 1 ) {
					if ( ! empty( $results[ $delimiter ] ) ) {
						$results[ $delimiter ] ++;
					} else {
						$results[ $delimiter ] = 1;
					}
				}
			}
			$i ++;
		}

		if ( count( $results ) > 0 ) {

			$results = array_keys( $results, max( $results ) );

			return $results[0];
		}

		return ',';

	}

	/**
	 * Method to get max upload size
	 *
	 * @return int $max_upload_size
	 *
	 * @since 4.4.6
	 */
	public function get_max_upload_size() {

		$max_upload_size    = 5242880; // 5MB.
		$wp_max_upload_size = wp_max_upload_size();
		$max_upload_size    = min( $max_upload_size, $wp_max_upload_size );

		return apply_filters( 'ig_es_max_upload_size', $max_upload_size );
	}

	/**
	 * Ajax handler to insert import CSV data into temporary table.
	 *
	 * @since 4.6.6
	 */
	public function ajax_import_subscribers_upload_handler() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
		$args = [
			'importing_from'  => ig_es_get_request_data( 'importing_from' ),
			'selected_roles'  => ig_es_get_request_data( 'selected_roles' ),
			'file'            => isset( $_FILES['async-upload']['tmp_name'] )
				? sanitize_text_field( $_FILES['async-upload']['tmp_name'] )
				: '',
		];
	
		$response   = ES_Contact_Import_Controller::import_subscribers_upload_handler( $args );
		wp_send_json( $response );
	}

	/**
	 * Ajax handler to get import data from temporary table.
	 *
	 * @since 4.6.6
	 */
	public function ajax_get_import_data() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$response = array(
			'success' => false,
		);

		global $wpdb;

		$identifier = '';
		if ( isset( $_POST['identifier'] ) ) {
			$identifier = sanitize_text_field( $_POST['identifier'] );
		}

		if ( ! empty( $identifier ) ) {

			$metadata = ES_Contact_Import_Controller::get_import_metadata( $identifier );

			if ( $metadata ) {
				$response['identifier'] = $metadata['identifier'];
				$response['data']       = $metadata['data'];
				$entries                = $metadata['entries'];
			// phpcs:enable

			$first = unserialize( base64_decode( $entries->first ) );
			$last  = unserialize( base64_decode( $entries->last ) );

			$data         = str_getcsv( $first[0], $response['data']['separator'], '"' );
			$cols         = count( $data );
			$contactcount = $response['data']['lines'];
			$fields       = array(
				'email'      => __( 'Email', 'email-subscribers' ),
				'first_name' => __( 'First Name', 'email-subscribers' ),
				'last_name'  => __( 'Last Name', 'email-subscribers' ),
				'first_last' => __( '(First Name) (Last Name)', 'email-subscribers' ),
				'last_first' => __( '(Last Name) (First Name)', 'email-subscribers' ),
				'created_at' => __( 'Subscribed at', 'email-subscribers' ),
			);
				if ( ! empty( $response['data']['importing_from'] ) && 'wordpress_users' !== $response['data']['importing_from'] ) {
					$fields['list_name'] = __( 'List Name', 'email-subscribers' );
					$fields['status']    = __( 'Status', 'email-subscribers' );
				}

			$fields = apply_filters( 'es_import_show_more_fields_for_mapping', $fields );

			$html      = '<div class="flex flex-row mb-6">
			<div class="es-import-processing flex es-w-25">
			<div class="ml-6 mr-2 pt-6">
			<label for="select_csv">
			<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">'
			. esc_html__( 'Select columns for mapping', 'email-subscribers' ) .
			'</span>
			<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug">'
			. esc_html__( 'Define which column represents which field', 'email-subscribers' ) . '

			</p>

			</label>
			</div>
			</div>';
			$html     .= '<div class="es-w-65 mx-4 border-b border-gray-200 shadow rounded-lg"><table class="w-full bg-white rounded-lg shadow overflow-hidden ">';
			$html     .= '<thead><tr class="border-b border-gray-200 bg-gray-50 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider"><th class="pl-3 py-4" style="width:20px;">#</th>';
			$phpmailer = ES()->mailer->get_phpmailer();
			$headers   = array();
				if ( ! empty( $response['data']['headers'] ) ) {
					$headers = $response['data']['headers'];
				}
				for ( $i = 0; $i < $cols; $i++ ) {
					$col_data = trim( $data[ $i ] );
					// Convert special characters in the email domain name to ascii.
					if ( is_callable( array( $phpmailer, 'punyencodeAddress' ) ) ) {
						$col_data = $phpmailer->punyencodeAddress( $col_data );
					}
					$is_email = is_email( trim( $col_data ) );
					$select   = '<select class="form-select font-normal text-gray-600 h-8 shadow-sm" name="mapping_order[]">';
					$select  .= '<option value="-1">' . esc_html__( 'Ignore column', 'email-subscribers' ) . '</option>';
					foreach ( $fields as $key => $value ) {
						$is_selected = false;
						if ( $is_email && 'email' === $key ) {
							$is_selected = true;
						} elseif ( ! empty( $headers[ $i ] ) ) {
							if ( strip_tags( $headers[ $i ] ) === $fields[ $key ] ) {
								$is_selected = ( 'first_name' === $key ) || ( 'last_name' === $key ) || ( 'list_name' === $key && 'mailchimp-api' === $response['data']['importing_from'] ) || ( 'status' === $key && 'mailchimp-api' === $response['data']['importing_from'] );
							}
						}
						$select .= '<option value="' . $key . '" ' . ( $is_selected ? 'selected' : '' ) . '>' . $value . '</option>';
					}
					$select .= '</select>';
					$html   .= '<th class="pl-3 py-4 font-medium">' . $select . '</th>';
				}
			$html .= '</tr>';
				if ( ! empty( $headers ) ) {
					$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider rounded-md"><th></th>';
					foreach ( $headers as $header ) {
						$html .= '<th class="pl-3 py-3 font-medium">' . esc_html ($header) . '</th>';
					}
					$html .= '</tr>';
				}
			$html .= '</thead><tbody>';
				for ( $i = 0; $i < min( 3, $contactcount ); $i++ ) {
					$data  = str_getcsv( ( $first[ $i ] ), $response['data']['separator'], '"' );
					$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 text-gray-500 tracking-wide"><td class="pl-3">' . number_format_i18n( $i + 1 ) . '</td>';
					foreach ( $data as $cell ) {
						if ( ! empty( $cell ) && is_email( $cell ) ) {
							$cell = sanitize_email( strtolower( $cell ) );
						}
						$html .= '<td class="pl-3 py-3" title="' . strip_tags( $cell ) . '">' . ( esc_html ( $cell ) ) . '</td>';
					}
					$html .= '<tr>';
				}
				if ( $contactcount > 3 ) {
					$hidden_contacts_count = $contactcount - 4;
					if ( $hidden_contacts_count > 0 ) {
						/* translators: %s: Hidden contacts count */
						$html .= '<tr class="alternate bg-gray-50 pl-3 py-3 border-b border-gray-200 text-gray-500"><td class="pl-2 py-3">&nbsp;</td><td colspan="' . ( $cols ) . '"><span class="description">&hellip;' . sprintf( esc_html__( '%s contacts are hidden', 'email-subscribers' ), number_format_i18n( $contactcount - 4 ) ) . '&hellip;</span></td>';
					}

					$data  = str_getcsv( array_pop( $last ), $response['data']['separator'], '"' );
					$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 text-gray-500 tracking-wider"><td class="pl-3 py-3">' . number_format_i18n( $contactcount ) . '</td>';
					foreach ( $data as $cell ) {
						$html .= '<td class="pl-3 py-3 " title="' . strip_tags( $cell ) . '">' . ( esc_html ( $cell ) ) . '</td>';
					}
					$html .= '<tr>';
				}
			$html .= '</tbody>';

			$html .= '</table>';
			$html .= '<input type="hidden" id="identifier" value="' . $identifier . '">';
			$html .= '</div></div>';

			$response['html']    = $html;
			$response['success'] = true;
			}
		}

		wp_send_json( $response );
	}

	/**
	 * Ajax handler to import subscirbers from temporary table
	 *
	 * @since 4.6.6
	 */
	public function ajax_do_import() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$args = array(
			'id'         => $_POST['id'],
			'options'    => $_POST['options'],
		);

	$return = ES_Contact_Import_Controller::import_contact( $args );
	wp_send_json( $return );

	}

	/**
	 * Handle adding contact id to excluded contact list
	 *
	 * @param $contact_id
	 */
	public function handle_new_contact_inserted( $contact_id ) {

		ES_Contact_Import_Controller::handle_new_contact_inserted( $contact_id );

		// $import_status = get_transient( 'ig_es_contact_import_is_running' );
		// if ( ! empty( $import_status ) && 'yes' == $import_status && ! empty( $contact_id ) ) {
		// 	$old_excluded_contact_ids = $this->get_excluded_contact_id_on_import();
		// 	array_push( $old_excluded_contact_ids, $contact_id );
		// 	$this->set_excluded_contact_id_on_import($old_excluded_contact_ids);
		//}
	}

	/**
	 * Get the excluded contact ID's list
	 *
	 * @return array|mixed
	 */
	public function get_excluded_contact_id_on_import() {
		$old_excluded_contact_ids = get_transient( 'ig_es_excluded_contact_ids_on_import' );
		if ( empty( $old_excluded_contact_ids ) || ! is_array( $old_excluded_contact_ids ) ) {
			$old_excluded_contact_ids = array();
		}

		return $old_excluded_contact_ids;
	}

	/**
	 * Set the excluded contact ID's list in transient
	 */
	// public function set_excluded_contact_id_on_import( $list ) {
	// 	if ( ! is_array( $list ) ) {
	// 		return false;
	// 	}
	// 	if ( empty( $list ) ) {
	// 		delete_transient( 'ig_es_excluded_contact_ids_on_import' );
	// 	} else {
	// 		set_transient( 'ig_es_excluded_contact_ids_on_import', $list, 24 * HOUR_IN_SECONDS );
	// 	}

	// 	return true;
	// }

	/**
	 * Handle sending bulk welcome and confirmation email to customers using cron job
	 */
	public function handle_after_bulk_contact_import() {
		ES_Contact_Import_Controller::handle_after_bulk_contact_import();
	}

	/**
	 * Method to truncate temp import table and options used during import process
	 *
	 * @param string import identifier
	 *
	 * @since 4.6.6
	 *
	 * @since 4.7.5 Renamed the function, converted to static method
	 */
	public static function remove_import_data( $identifier = '' ) {
		ES_Contact_Import_Controller::remove_import_data($identifier);
	}

	// public function api() {

	// 	return ES_Contact_Import_Controller::api();
	// }

	

	public function api_request() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
		$endpoint = str_replace( 'wp_ajax_ig_es_mailchimp_', '', current_filter() );

		$args = array(
			'limit'      => ig_es_get_request_data( 'limit' ),
			'offset'     => ig_es_get_request_data( 'offset' ),
			'status'     => ig_es_get_request_data( 'status' ),
			'identifier' => ig_es_get_request_data( 'identifier' ),
			'id'         => ig_es_get_request_data( 'id' ),
			'list_name'  => ig_es_get_request_data( 'list_name' ),
			'endpoint'   => $endpoint,
		);

		ES_Contact_Import_Controller::api_request_data( $args );

	}
	
	// public static function insert_into_temp_table( $raw_data, $seperator = ',', $data_contain_headers = false, $headers = array(), $identifier = '', $importing_from = 'csv' ) {
	// 	global $wpdb;
	// 	$raw_data = ( trim( str_replace( array( "\r", "\r\n", "\n\n" ), "\n", $raw_data ) ) );

	// 	if ( function_exists( 'mb_convert_encoding' ) ) {
	// 		$encoding = mb_detect_encoding( $raw_data, 'auto' );
	// 	} else {
	// 		$encoding = 'UTF-8';
	// 	}

	// 	$lines = explode( "\n", $raw_data );

	// 	// If data itself contains headers(in case of CSV), then remove it.
	// 	if ( $data_contain_headers ) {
	// 		array_shift( $lines );
	// 	}

	// 	$lines_count = count( $lines );

	// 	$batch_size = min( 500, max( 200, round( count( $lines ) / 200 ) ) ); // Each entry in temporary import table will have this much of subscribers data
	// 	$parts      = array_chunk( $lines, $batch_size );
	// 	$partcount  = count( $parts );

	// 	do_action( 'ig_es_remove_import_data', $identifier );

	// 	$identifier             = empty( $identifier ) ? uniqid() : $identifier;
	// 	$response['identifier'] = $identifier;

	// 	for ( $i = 0; $i < $partcount; $i++ ) {

	// 		$part = $parts[ $i ];
	// 		$new_value = base64_encode( serialize( $part ) );
	// 	 // phpcs:disable
	// 		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}ig_temp_import (data, identifier) VALUES (%s, %s)", $new_value, $identifier ) );
	// 	 // phpcs:enable
	// 	}

	// 	$bulk_import_data = get_option( 'ig_es_bulk_import', array() );
	// 	if ( ! empty( $bulk_import_data ) ) {
	// 		$partcount   += $bulk_import_data['parts'];
	// 		$lines_count += $bulk_import_data['lines'];
	// 	}

	// 	$bulkimport = array(
	// 		'imported'               => 0,
	// 		'errors'                 => 0,
	// 		'duplicate_emails_count' => 0,
	// 		'existing_contacts'		 => 0,
	// 		'updated_contacts'		 => 0,
	// 		'encoding'               => $encoding,
	// 		'parts'                  => $partcount,
	// 		'lines'                  => $lines_count,
	// 		'separator'              => $seperator,
	// 		'importing_from'         => $importing_from,
	// 		'data_contain_headers'   => $data_contain_headers,
	// 		'headers'                => $headers,
	// 	);

	// 	$response['success']     = true;
	// 	$response['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
	// 	update_option( 'ig_es_bulk_import', $bulkimport, 'no' );

	// 	return $response;
	// }	
}
