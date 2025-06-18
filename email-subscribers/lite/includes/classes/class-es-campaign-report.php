<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Campaign_Report extends ES_List_Table {

	public static $instance;

	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Report', 'email-subscribers' ), // singular name of the listed records
				'plural'   => __( 'Reports', 'email-subscribers' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?,
				'screen'   => 'es_reports',
			)
		);

		add_action( 'ig_es_view_report_data_lite', array( 'ES_Reports_Controller', 'view_report_data_lite' ) );
		add_action( 'ig_es_view_report_description_lite', array( $this, 'view_report_description_lite' ), 10, 2 );
		add_action( 'ig_es_view_activity_table_html', array( $this, 'view_activity_report_table' ), 10, 3 );
		add_action( 'admin_footer', array( $this, 'es_view_activity_report_sort_and_filter' ) );
	}


	


	/**
	 * Display Report header information and KPI values
	 *
	 * @version 5.4.2
	 */
	public function view_report_description_lite( $notification, $report_kpi_statistics ) {
		if ( ! empty( $notification['type'] ) ) {
			$campaign_url = '';
			if ( IG_CAMPAIGN_TYPE_SEQUENCE === $notification['type'] ) {
				$campaign_url = '?page=es_sequence&action=edit&id=' . $notification['campaign_id'];
			} else {
				$campaign_url = '?page=es_campaigns#!/campaign/edit/' . $notification['campaign_id'];
			}
		}
		?>
		<div class="font-san">
			<div class="sticky top-0 z-10">
				<header>
					<nav aria-label="Global" class="pb-5 w-full pt-2">
						<div class="brand-logo">
							<span>
								<img src="<?php echo ES_PLUGIN_URL . 'lite/admin/images/new/brand-logo/IG LOGO 192X192.svg'; ?>" alt="brand logo" />
								<div class="divide"></div>
								<h1><?php echo esc_html__( 'Report', 'email-subscribers' ); ?></h1>
							</span>
						</div>
					</nav>
				</header>
			</div>

			<div class="overview reports-statistic">
				<div class="campaign-info">
					<div class="campaign-title">
						<div class="title">
							<a href="<?php echo esc_url( $campaign_url ); ?>" target="_blank" title="<?php echo esc_attr__( 'Go to campaign', 'email-subscribers' ); ?>">
								<?php echo esc_html( $notification['subject'] ); ?>
							</a>
						</div>
						<div class="campaign-sent-status">
							<?php
							switch ( $notification['status'] ) {
								case 'Sent':
									?>
									<svg class="sent" fill="currentColor" viewBox="0 0 20 20">
										<title><?php echo esc_attr__( 'Sent', 'email-subscribers' ); ?></title>
										<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
									</svg>
								<?php
									break;
								case 'In Queue':
									?>
								<svg class="queue" fill="currentColor" viewBox="0 0 20 20">
									<title><?php echo esc_attr__( 'In Queue', 'email-subscribers' ); ?></title>
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
								</svg>
								<?php
									break;
								case 'Sending':
									?>
								<svg class="sending" fill="currentColor" viewBox="0 0 20 20">
									<title><?php echo esc_attr__( 'Sending', 'email-subscribers' ); ?></title>
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
								</svg>
							<?php
									break;
								case '1':
									?>
								<span class="active"><?php echo esc_html__('Active', 'email-subscribers'); ?></span>
							<?php
									break;
								case '':
									?>
									 <span class="inactive"><?php echo esc_html__('Inactive', 'email-subscribers'); ?></span>
						<?php } ?>
						</div>
					</div>
					<div class="campaign-other-info">
						<p class="type"><?php echo esc_html__( 'Type: ', 'email-subscribers' ); ?>
							<span><?php echo esc_html( $notification['type'] ); ?></span>
						</p>
						<p class="from"><?php echo esc_html__( 'From: ', 'email-subscribers' ); ?>
							<span><?php echo esc_html( $notification['from_email'] ); ?></span>
						</p>
						<div class="recipient">
						<span class="recipient-text"><?php echo esc_html__( 'Recipient(s): ', 'email-subscribers' ); ?></span>
							<div class="recipient-info">
								<?php
								if ( ! empty( $notification['list_name'] ) ) {
									echo esc_html( $notification['list_name'] );
								} else {
									if ( ! empty( $notification['list_conditions'] ) ) {
										do_action( 'ig_es_campaign_show_conditions', $notification['list_conditions'] );
									}
								}
								?>
							</div>
						</div>
						<?php if ( ! in_array( $notification['type'], array( 'Sequence Message', 'Workflow Email' ), true ) ) { ?>
						<p class="date"><?php echo esc_html__( 'Date: ', 'email-subscribers' ); ?>
							<span><?php echo wp_kses_post( ig_es_format_date_time( $notification['start_at'] ) ); ?></span>
						</p>
					<?php } ?>
					</div>
				</div>
				<div class="statistic-info">
					<div class="stat-sec">
						<p class="title">
							<?php echo esc_html__( 'Statistics', 'email-subscribers' ); ?>
						</p>
						<div class="stat-grid-sec">

							<div class="p-2">
								<span class = "email_viewed_count">
									<?php	echo esc_html( $report_kpi_statistics['email_viewed_count']); ?>
								</span>

								<span class = "open_rate">
									<?php	echo esc_html( ' (' . $report_kpi_statistics['avg_open_rate'] . '%)'); ?>
								</span>

								<p class="opened">
									<?php echo esc_html__( 'Opened', 'email-subscribers' ); ?>
								</p>
							</div>

							<div class="p-2">
								<span class = "click_count">
									<?php	echo esc_html( '0' ); ?>
								</span>

								<span class = "click_rate">
									<?php	echo esc_html( '(0.00%)'); ?>
								</span>
								<?php
								$utm_args = array(
									'utm_medium' => 'campaign-report-analytics',
									'url'		 => 'https://www.icegram.com/documentation/what-analytics-does-email-subscribers-track/'
								);
						
								$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
								?>
								<p class="clicked">
									<?php echo esc_html__( 'Clicked', 'email-subscribers' ); ?>
									<a target="_blank" href="<?php echo esc_url( $pricing_url ); ?>">
										<span class="premium-icon max ml-2 mb-1"></span>
									</a>	
								</p>
							</div>

							<div class="p-2">
								<span class="total_email_sent">
									<?php echo esc_html( $report_kpi_statistics['total_email_sent'] ); ?>
								</span>
								<p class="emailsent">
									<?php echo esc_html__( 'Sent', 'email-subscribers' ); ?>
								</p>
							</div>

							<div class="p-2">
								<span class = "email_unsubscribed_count">
									<?php	echo esc_html( '0' ); ?>
								</span>
								<span class = "text-xl font-bold leading-none text-indigo-600">
									<?php	echo esc_html( '(0.00%)' ); ?>
								</span>

								<p class="email_unsubscribed">
									<?php echo esc_html__( 'Unsubscribed', 'email-subscribers' ); ?>
									<a target="_blank" href="<?php echo esc_url( $pricing_url ); ?>">
										<span class="premium-icon max ml-2 mb-1"></span>
									</a>
								</p>
							</div>

						</div>
					</div>
				</div>
			</div>
	<?php
	}


	public function es_campaign_report_callback() {
		?>

		<?php
		$this->ajax_response();
		$paged          = ig_es_get_request_data( 'paged', 1 );
		$campaign_class = '';

		if ( ES()->is_pro() ) {
			$campaign_class = 'es_campaign_premium';
		}
		
		?>
		<div id="poststuff" class="es-items-lists es-campaign-reports-table">
			<div id="post-body" class="metabox-holder column-1">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<form method="get" class="es_campaign_report <?php echo esc_html( $campaign_class ); ?>" id="es_campaign_report">
							<input type="hidden" name="order" />
							<input type="hidden" name="orderby" />
							<input type="hidden" name="paged" value='<?php echo esc_attr( $paged ); ?>'/>
							<p class="inline text-lg font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'Activity Info', 'email-subscribers' ); ?></p>
							<?php $this->display(); ?>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_response() {

		$this->prepare_items();
		$no_placeholder = ig_es_get_request_data( 'no_placeholder', '' );
		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $no_placeholder ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination( 'top' );
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination( 'bottom' );
		$pagination_bottom = ob_get_clean();

		$response = array( 'rows' => $rows );

		$response['column_headers']       = $headers;
		$response['pagination']['top']    = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;

		if ( isset( $total_items ) ) {
			/* translators: %s: Total items in the table */
			$response['total_items_i18n'] = sprintf( _n( '%s item', '%s items', $total_items, 'email-subscribers' ), number_format_i18n( $total_items ) );
		}

		if ( isset( $total_pages ) ) {
			$response['total_pages']      = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			die( json_encode( $response ) );

		} else {
			return $response;
		}
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		$sortable_columns = array(
			'email'        => array( 'email', false ),
			'country_flag' => array( 'country_flag', false ),
			'os'           => array( 'os', false ),
			'email_client' => array( 'email_client', false ),
			'sent_at'      => array( 'sent_at', false ),
			'opened_at'    => array( 'opened_at', false ),
			'status'       => array( 'status', false ),
		);

		return $sortable_columns;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$per_page = 100;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$args = array(
			'hash'              => ig_es_get_request_data( 'list', '' ),
			'campaign_id'       => ig_es_get_request_data( 'campaign_id', '' ),
			'filter_by_status'  => ig_es_get_request_data( 'status', '' ),
			'filter_by_country' => ig_es_get_request_data( 'country_code', '' ),
			'search'            => ig_es_get_request_data( 's' ),
			'orderby'           => ig_es_get_request_data( 'orderby' ),
			'order'             => ig_es_get_request_data( 'order', 'DESC' ),
			'page_number'       => ig_es_get_request_data( 'paged', 1 ),
			'return_count'      => true, //  First call: true
		);
		
		$total_items = ES_Reports_Controller::get_activity_table_data( $args );
		
		$args['return_count'] = false; //  Disable for second call
		
		$data = ES_Reports_Controller::get_activity_table_data( $args );
		
		$this->items = $data;
		

		/**
		 * Call to _set_pagination_args method for informations about
		 * total items, items for page, total pages and ordering
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Method to handle display of WP_List table
	 *
	 * @Override of display method
	 */
	public function display() {
		$search = ig_es_get_request_data( 's' );
		$this->search_box( $search, 'campaign-reports-search-input' );
		parent::display();
	}

	/**
	 * Prepare search box
	 *
	 * @param string $text
	 * @param string $input_id
	 *
	 * @since 4.6.12
	 */
	public function search_box( $text = '', $input_id = '' ) {
		do_action( 'ig_es_campaign_reports_filter_options', $text, $input_id );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'sr_no'     => '#',
			'email'     => __( 'Email', 'email-subscribers' ),
			'status'    => __( 'Status', 'email-subscribers' ),
			'sent_at'   => __( 'Sent Date', 'email-subscribers' ),
			'opened_at' => __( 'Viewed Date', 'email-subscribers' ),

		);

		$columns = apply_filters( 'additional_es_campaign_report_columns', $columns );

		return $columns;
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		$column_data = ! empty( $item[ $column_name ] ) ? $item[ $column_name ] : '-';
		return $column_data;
	}

	public function column_status( $item ) {
		$status = ! empty( $item['status'] ) ? $item['status'] : ( ! empty( $item['es_deliver_sentstatus'] ) ? $item['es_deliver_sentstatus'] : '' );

		switch ( $status ) {
			case 'Sent':
				?>
				<svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
					<title><?php echo esc_html__( 'Sent', 'email-subscribers' ); ?></title>
					<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
				</svg>
				<?php
				break;
			case 'In Queue':
				?>
				<svg class=" h-6 w-6 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
				<title><?php echo esc_html__( 'In Queue', 'email-subscribers' ); ?></title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
			</svg>
				<?php
				break;
			case 'Sending':
				?>
				<svg class=" h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
				<title><?php echo esc_html__( 'Sending', 'email-subscribers' ); ?></title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
			</svg>
				<?php
				break;
			case 'Opened':
				?>
				<svg xmlns="http://www.w3.org/2000/svg" class="" width="28" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color:green">
					<title><?php echo esc_html__( 'Opened', 'email-subscribers' ); ?></title>
					  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
					  <path d="M7 12l5 5l10 -10" />
					  <path d="M2 12l5 5m5 -5l5 -5" />
				</svg>
				<?php
				break;
			case 'Failed':
				?>
				<svg xmlns="http://www.w3.org/2000/svg" class="text-red-600" width="28" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
					<title><?php echo esc_html__( 'Failed', 'email-subscribers' ); ?></title>
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg>
				<?php
				break;
			case '':
				?>
				<i class="dashicons dashicons-es dashicons-minus"/>
				<?php
				break;
			default:
				echo esc_html( $status );
				break;

		}
	}

	

	/**
	 * Handling filtering and sorting for view activity table
	 */
	public function es_view_activity_report_sort_and_filter() {
		$hash        = ig_es_get_request_data( 'list', '' );
		$campaign_id = ig_es_get_request_data( 'campaign_id', '' );
	
		// Escaping and sanitizing data
		$hash        = esc_attr( $hash );
		$campaign_id = absint( $campaign_id );
	
		?>
	
		<script type="text/javascript">
	
		(function ($) {
	
			$(document).ready(
	
				function () {
	
					$('#es_campaign_report').on('click', '.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a', function (e) {
						e.preventDefault();
						var query = this.search.substring(1);
						var paged = list.__query( query, 'paged' ) || '1';
						var order = list.__query( query, 'order' ) || 'desc';
						var orderby = list.__query( query, 'orderby' ) || '';
						$("input[name='order']").val(order);
						$("input[name='orderby']").val(orderby);
						$("input[name='paged']").val(paged);
						check_filter_value();
					});
	
					$('#campaign-report-search-submit').on('click', function (e) {
						e.preventDefault();
						$("input[name='paged']").val(1);
						check_filter_value();
					});
				});
	
				var list = {
	
					/** AJAX call
					 *
					 * Send the call and replace table parts with updated version!
					 *
					 * @param    object    data The data to pass through AJAX
					 */
					update: function (data) {
	
						$.ajax({
	
							url: ajaxurl,
							data: $.extend(
								{
									action: 'ajax_fetch_report_list',
									security: ig_es_js_data.security
								},
								data
							),
							beforeSend: function(){
								$('#es_campaign_report table.wp-list-table.widefat.fixed.striped.table-view-list.reports tbody').addClass('es-pulse-animation').css({'filter': 'blur(1px)', '-webkit-filter' : 'blur(1px)'});
							},
							success: function (response) {
								var response = $.parseJSON(response);
								if (response.rows.length)
									$('#the-list').html(response.rows);
								if (response.column_headers.length)
									$('#es_campaign_report thead tr, #es_campaign_report tfoot tr').html(response.column_headers);
								if (response.pagination.bottom.length)
									$('.tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());
								if (response.pagination.top.length)
									$('.tablenav.top .tablenav-pages').html($(response.pagination.top).html());
							},
							error: function (err) {
	
							}
						}).always(function(){
							$('#es_campaign_report table.wp-list-table.widefat.fixed.striped.table-view-list.reports tbody').removeClass('es-pulse-animation').css({'filter': 'blur(0px)', '-webkit-filter' : 'blur(0px)'});
						});
					},
	
					/**
					 * Filter the URL Query to extract variables
					 *
					 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
					 *
					 * @param    string    query The URL query part containing the variables
					 * @param    string    variable Name of the variable we want to get
					 *
					 * @return   string|boolean The variable value if available, false else.
					 */
					__query: function (query, variable) {
	
						var vars = query.split("&");
						for (var i = 0; i < vars.length; i++) {
							var pair = vars[i].split("=");
							if (pair[0] == variable)
								return pair[1];
						}
						return false;
					},
				}
	
	
				function check_filter_value( filter_value = '' ){
						var search  = $('#campaign-reports-search-input').val();
						var country_code             = $('#ig_es_filter_activity_report_by_country').val();
						var report_activity_status   = $('#ig_es_filter_activity_report_by_status').val();
						var order   = $("input[name='order']").val();
						var orderby = $("input[name='orderby']").val();
						var paged   = $("input[name='paged']").val();
	
						data =
						{
							list : "<?php echo esc_js($hash); ?>",
							campaign_id     : "<?php echo esc_js($campaign_id); ?>",
							order           : order,
							orderby         : orderby,
							paged           : paged,
							s               : search,
							country_code    : country_code,
							status          : report_activity_status
	
						};
	
						list.update(data);
				}
			})(jQuery);
	
		</script>
		<?php
	}
	


}
