<?php

if ( ! class_exists( 'ES_Campaigns_Controller' ) ) {

	/**
	 * Class to handle single campaign options
	 * 
	 * @class ES_Campaigns_Controller
	 */
	class ES_Campaigns_Controller {

		// class instance
		public static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public static function get_campaigns_and_kpis( $args ) {
			$campaigns = self::get_campaigns( $args );
			$kpis      = self::get_kpis( $args );
			return array(
				'campaigns' => $campaigns,
				'kpis'      => $kpis,
			);
		}
		
		public static function get_campaigns_count( $args) {
			$args = ES_Common::decode_args( $args );
			
			$per_page = ! empty( $args['per_page'] ) ? (int) $args['per_page'] : 20;
		$current_page = ! empty( $args['current_page'] ) ? $args['current_page'] : 1;
			$filter_args = array();
			
			if ( ! empty( $args['search'] ) ) {
				$filter_args['search_text'] = sanitize_text_field( $args['search'] );
			}
			
			// Handle include_types - pass array of campaign types
			if ( ! empty( $args['include_types'] ) && is_array( $args['include_types'] ) ) {
				// Pass all types from include_types array
				$filter_args['campaign_type'] = array_map( 'sanitize_text_field', $args['include_types'] );
			} elseif ( ! empty( $args['type'] ) ) {
				// New React approach - single type parameter
				$filter_args['campaign_type'] = sanitize_text_field( $args['type'] );
			}
			
			// Handle status arrays - pass array of statuses
			if ( ! empty( $args['status'] ) && is_array( $args['status'] ) ) {
				// Pass all statuses from status array
				$filter_args['campaign_status'] = array_map( 'sanitize_text_field', $args['status'] );
			} elseif ( isset( $args['status'] ) && ! is_array( $args['status'] ) ) {
				// Single status parameter
				$filter_args['campaign_status'] = sanitize_text_field( $args['status'] );
			}
			
			$total_items = ES_DB_Campaigns::get_lists(0, 0, true, $filter_args);
			$total_campaign_pages = ceil($total_items / $per_page); 
			return [$total_items,$total_campaign_pages,$current_page,$per_page];

		}

		public static function get_campaigns( $args ) {
			$args = ES_Common::decode_args( $args );
			
			$per_page = ! empty( $args['per_page'] ) ? (int) $args['per_page'] : 20;
			$current_page = ! empty( $args['current_page'] ) ? $args['current_page'] : 1;
			
			$filter_args = array(
				'order_by' => ! empty( $args['order_by'] ) ? sanitize_text_field( $args['order_by'] ) : 'created_at',
				'order' => ! empty( $args['order'] ) && in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ) ) ? strtoupper( $args['order'] ) : 'DESC'
			);
			
			// Support both old Mithril order_by_column and new React order_by
			if ( ! empty( $args['order_by_column'] ) ) {
				$filter_args['order_by'] = sanitize_text_field( $args['order_by_column'] );
			}
			
			if ( ! empty( $args['search'] ) ) {
				$filter_args['search_text'] = sanitize_text_field( $args['search'] );
			}
			
			// Handle include_types - pass array of campaign types
			if ( ! empty( $args['include_types'] ) && is_array( $args['include_types'] ) ) {
				// Pass all types from include_types array
				$filter_args['campaign_type'] = array_map( 'sanitize_text_field', $args['include_types'] );
			} elseif ( ! empty( $args['type'] ) ) {
				// New React approach - single type parameter
				$filter_args['campaign_type'] = sanitize_text_field( $args['type'] );
			}
			
			// Handle status arrays - pass array of statuses
			if ( ! empty( $args['status'] ) && is_array( $args['status'] ) ) {
				// Pass all statuses from status array
				$filter_args['campaign_status'] = array_map( 'sanitize_text_field', $args['status'] );
			} elseif ( isset( $args['status'] ) && ! is_array( $args['status'] ) ) {
				// Single status parameter
				$filter_args['campaign_status'] = sanitize_text_field( $args['status'] );
			}
			
			$total_items = ES_DB_Campaigns::get_lists( 0, 0, true, $filter_args );
			$campaigns = ES_DB_Campaigns::get_lists( $per_page, $current_page, false, $filter_args );
		
		if ( ! empty( $campaigns ) && is_array( $campaigns ) ) {
			$campaign_ids = array_column( $campaigns, 'id' );
			
			// Get campaign stats using enhanced get_actions_count
			$actions_data = ES()->actions_db->get_actions_count( array(
				'campaign_ids' => $campaign_ids,
				'types' => array( IG_MESSAGE_SENT, IG_MESSAGE_OPEN, IG_LINK_CLICK )
			) );
			
			// Calculate rates and format stats
			$campaign_stats = array();
			foreach ( $campaign_ids as $campaign_id ) {
				if ( isset( $actions_data[ $campaign_id ] ) ) {
					$row = $actions_data[ $campaign_id ];
					$sent = ! empty( $row['sent'] ) ? (int) $row['sent'] : 0;
					$opened = ! empty( $row['opened'] ) ? (int) $row['opened'] : 0;
					$clicked = ! empty( $row['clicked'] ) ? (int) $row['clicked'] : 0;
					
					$open_rate = ! empty( $sent ) ? number_format_i18n( ( ( $opened * 100 ) / $sent ), 2 ) : 0;
					$click_rate = ! empty( $sent ) ? number_format_i18n( ( ( $clicked * 100 ) / $sent ), 2 ) : 0;
					
					$campaign_stats[ $campaign_id ] = array(
						'total_sent' => $sent,
						'open_rate'  => $open_rate,
						'click_rate' => $click_rate,
					);
				} else {
					$campaign_stats[ $campaign_id ] = array(
						'total_sent' => 0,
						'open_rate'  => 0,
						'click_rate' => 0,
					);
				}
			}
			
			$campaign_list_names = ES()->lists_db->get_list_names_for_campaigns( $campaigns );
			$campaign_queue_data = ES_DB_Mailing_Queue::get( array(
				'campaign_ids' => $campaign_ids,
				'group_by'     => 'campaign_id',
				'keyed_by'     => 'campaign_id'
			) );
			
			foreach ( $campaigns as $index => $campaign ) {
				$formatted_campaign = self::format_campaign_data(
					$campaign,
					$campaign_stats,
					$campaign_list_names,
					$campaign_queue_data
				);
				$campaigns[ $index ] = $formatted_campaign;
			}
		}
			
		$result = array();
		$result['campaigns'] = $campaigns;
		$result['current_page'] = $current_page ? $current_page : 1;
		
		return $result;
	}

	public static function get( $request_data ) {
		if ( is_string( $request_data ) ) {
			}
			
			$campaign_id = ! empty( $request_data['id'] ) ? (int) $request_data['id'] : 0;
			
			if ( $campaign_id <= 0 ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid campaign ID', 'email-subscribers' )
				);
			}
			
		$campaign = ES()->campaigns_db->get( $campaign_id );
		
		if ( empty( $campaign ) ) {
			return array(
				'success' => false,
				'message' => __( 'Campaign not found', 'email-subscribers' )
			);
		}
		
		$campaign = self::format_single_campaign_data( $campaign );
		
		return $campaign;
	}

	/**
	 * Format single campaign data (used for individual campaign retrieval)
	 * For multiple campaigns, use format_campaign_data() instead
	 * 
	 * @param array $campaign Campaign data
	 * @return array Formatted campaign data
	 */
	private static function format_single_campaign_data( $campaign ) {
			if ( ! empty( $campaign ) ) {
				$campaign['es_admin_email'] = ES_Common::get_admin_email();
				$campaign_id = $campaign['id'];
				$campaign_status = (int) $campaign['status'];
				$campaign_type = $campaign['type'];

				$list_names_display = '-';
				
				$all_list_ids = ES()->campaigns_db->get_list_ids( $campaign_id );
				
				// Store list_ids as comma-separated string for frontend
				if ( ! empty( $all_list_ids ) ) {
					$all_list_ids = array_map( 'intval', array_filter( $all_list_ids ) );
					$campaign['list_ids'] = implode( ',', $all_list_ids );
				} else {
					$campaign['list_ids'] = '';
				}
				
				if ( ! empty( $all_list_ids ) ) {
					if ( ! empty( $all_list_ids ) ) {
						$list_names = ES()->lists_db->get_list_name_by_ids( $all_list_ids );
						if ( ! empty( $list_names ) && is_array( $list_names ) ) {
							$valid_names = array_filter( $list_names );
							if ( ! empty( $valid_names ) ) {
								$list_names_display = implode( ', ', $valid_names );
							} else {
								$list_names_display = '-';
							}
						} else {
							$list_names_display = '-';
						}
					}
				}
				
				$campaign['list_names'] = $list_names_display;

				if ( self::is_post_campaign( $campaign_type ) ) {
					$categories_data = self::format_categories( $campaign['categories'] );
					$campaign['formatted_categories'] = $categories_data['formatted_categories'];
					$campaign['category_names'] = $categories_data['category_names'];
				}
				$campaign['status'] = (int) $campaign['status'];
				$campaign['id'] = (int) $campaign['id'];
				
				$campaign['status_text'] = self::get_campaign_status_text( $campaign['status'] );
				$campaign['meta']        = ig_es_maybe_unserialize( $campaign['meta']);
				$args = array(
					'campaign_id' => $campaign_id,
					'types' => array(
						IG_MESSAGE_SENT,
						IG_MESSAGE_OPEN,
						IG_LINK_CLICK
					)
				);
				$actions_count       = ES()->actions_db->get_actions_count( $args );
				$total_email_sent    = $actions_count['sent'];
				$total_email_opened  = $actions_count['opened'];
				$total_email_clicked = $actions_count['clicked'];
				$open_rate  = ! empty( $total_email_sent ) ? number_format_i18n( ( ( $total_email_opened * 100 ) / $total_email_sent ), 2 ) : 0 ;
				$click_rate = ! empty( $total_email_sent ) ? number_format_i18n( ( ( $total_email_clicked * 100 ) / $total_email_sent ), 2 ) : 0;
				$campaign['open_rate']  = $open_rate;
				$campaign['click_rate'] = $click_rate;
				$campaign['total_sent'] = $total_email_sent;
				$campaign['meta'] = ig_es_maybe_unserialize( $campaign['meta']);
				
				$report = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $campaign_id );
				
				if ( self::is_post_campaign( $campaign_type ) ) {
					if ( $report && ! empty( $report['meta'] ) ) {
						$report_meta = ig_es_maybe_unserialize( $report['meta'] );
						if ( ! empty( $report_meta['post_id'] ) ) {
							$post_id = $report_meta['post_id'];
							$post = get_post( $post_id );
							if ( $post ) {
								$campaign['post_title'] = $post->post_title;
								$campaign['post_date'] = $post->post_date;
							}
						}
					}
					if ( $report && ! empty( $report['hash'] ) ) {
						$campaign['report_link'] = admin_url( 'admin.php?page=es_dashboard#/reports/' . $report['hash'] );
						$campaign['hash'] = $report['hash'];
					}
				} elseif ( IG_CAMPAIGN_TYPE_NEWSLETTER === $campaign_type ) {
					if ( $report && !empty( $report['hash'] ) ) {
						$campaign['report_link'] = admin_url( 'admin.php?page=es_dashboard#/reports/' . $report['hash'] );
						$campaign['hash'] = $report['hash'];
					}
				} elseif ( in_array( $campaign_type, array( IG_CAMPAIGN_TYPE_SEQUENCE, IG_CAMPAIGN_TYPE_WORKFLOW ), true ) ) {
					// Use React routing for sequence and workflow campaigns
					$campaign['report_link'] = admin_url( 'admin.php?page=es_dashboard#/reports/sequence-messages?campaign_id=' . $campaign_id );
				}
				
				if ( IG_CAMPAIGN_TYPE_SEQUENCE === $campaign_type ) {
					$campaign['edit_link'] = admin_url( 'admin.php?page=es_sequence&action=edit&id=' . $campaign_id );
				} elseif ( IG_CAMPAIGN_TYPE_WORKFLOW === $campaign_type ) {
					$campaign['edit_link'] = admin_url( 'admin.php?page=es_workflows&action=edit&id=' . $campaign_id );
				} else {
					$campaign['edit_link'] = admin_url( 'admin.php?page=es_campaigns#!/campaign/edit/' . $campaign_id );
				}
			}
			return $campaign;
		}

		/**
		 * Format campaign data with pre-fetched related data
		 * 
		 * @param array $campaign Campaign data
		 * @param array $campaign_stats Pre-fetched stats for all campaigns
		 * @param array $campaign_list_names Pre-fetched list names for all campaigns
		 * @param array $campaign_queue_data Pre-fetched mailing queue data for all campaigns
		 * @return array Formatted campaign data
		 * 
		 * @since 5.8.0
		 */
		private static function format_campaign_data( $campaign, $campaign_stats, $campaign_list_names, $campaign_queue_data ) {
			if ( empty( $campaign ) ) {
				return $campaign;
			}
			
			$campaign['es_admin_email'] = ES_Common::get_admin_email();
			$campaign_id = (int) $campaign['id'];
			$campaign_status = (int) $campaign['status'];
			$campaign_type = $campaign['type'];
			
			// Use pre-fetched list names
			$list_names_display = '-';
			if ( isset( $campaign_list_names[ $campaign_id ] ) && ! empty( $campaign_list_names[ $campaign_id ] ) ) {
				$list_names = $campaign_list_names[ $campaign_id ];
				$valid_names = array_filter( $list_names );
				if ( ! empty( $valid_names ) ) {
					$list_names_display = implode( ', ', $valid_names );
				}
			}
			$campaign['list_names'] = $list_names_display;
			// Ensure list_ids is properly formatted (should already be from optimized query)
			if ( empty( $campaign['list_ids'] ) ) {
				$campaign['list_ids'] = '';
			}
			
			// Format categories for post campaigns
			if ( self::is_post_campaign( $campaign_type ) ) {
				$categories_data = self::format_categories( $campaign['categories'] );
				$campaign['formatted_categories'] = $categories_data['formatted_categories'];
				$campaign['category_names'] = $categories_data['category_names'];
			}
			
			$campaign['status'] = $campaign_status;
			$campaign['id'] = $campaign_id;
			$campaign['status_text'] = self::get_campaign_status_text( $campaign_status );
			$campaign['meta'] = ig_es_maybe_unserialize( $campaign['meta'] );
			
			// Use pre-fetched stats
			if ( isset( $campaign_stats[ $campaign_id ] ) ) {
				$stats = $campaign_stats[ $campaign_id ];
				$campaign['open_rate'] = $stats['open_rate'];
				$campaign['click_rate'] = $stats['click_rate'];
				$campaign['total_sent'] = $stats['total_sent'];
			} else {
				$campaign['open_rate'] = 0;
				$campaign['click_rate'] = 0;
				$campaign['total_sent'] = 0;
			}
			
			// Use pre-fetched mailing queue data
			$report = isset( $campaign_queue_data[ $campaign_id ] ) ? $campaign_queue_data[ $campaign_id ] : array();
			
			if ( self::is_post_campaign( $campaign_type ) ) {
			if ( $report && ! empty( $report['meta'] ) ) {
				$report_meta = ig_es_maybe_unserialize( $report['meta'] );
				if ( ! empty( $report_meta['post_id'] ) ) {
					$post_id = $report_meta['post_id'];
					$post = get_post( $post_id );
					if ( $post ) {
						$campaign['post_title'] = $post->post_title;
						$campaign['post_date'] = $post->post_date;
					}
				}
			}
			if ( $report && ! empty( $report['hash'] ) ) {
				$campaign['report_link'] = admin_url( 'admin.php?page=es_dashboard#/reports/' . $report['hash'] );
				$campaign['hash'] = $report['hash'];
			}
		} elseif ( IG_CAMPAIGN_TYPE_NEWSLETTER === $campaign_type ) {
			if ( $report && ! empty( $report['hash'] ) ) {
				$campaign['report_link'] = admin_url( 'admin.php?page=es_dashboard#/reports/' . $report['hash'] );
				$campaign['hash'] = $report['hash'];
			}
		} elseif ( in_array( $campaign_type, array( IG_CAMPAIGN_TYPE_SEQUENCE, IG_CAMPAIGN_TYPE_WORKFLOW ), true ) ) {
			$campaign['report_link'] = admin_url( 'admin.php?page=es_dashboard#/reports/sequence-messages?campaign_id=' . $campaign_id );
		}
			
			if ( IG_CAMPAIGN_TYPE_SEQUENCE === $campaign_type ) {
				$campaign['edit_link'] = admin_url( 'admin.php?page=es_sequence&action=edit&id=' . $campaign_id );
			} elseif ( IG_CAMPAIGN_TYPE_WORKFLOW === $campaign_type ) {
				$campaign['edit_link'] = admin_url( 'admin.php?page=es_workflows&action=edit&id=' . $campaign_id );
			} else {
				$campaign['edit_link'] = admin_url( 'admin.php?page=es_campaigns#!/campaign/edit/' . $campaign_id );
			}
			
			return $campaign;
		}

		public static function get_kpis( $args ) {
			$args = ES_Common::decode_args( $args );

			$page           = 'es_campaigns';
			$override_cache = true;
			$reports_data   = ES_Reports_Data::get_dashboard_reports_data( $page, $override_cache, $args );
			return $reports_data;
		}

		public static function delete_campaigns( $args ) {
			$args = ES_Common::decode_args( $args );
			
			$campaign_ids = $args['campaign_ids'];
			if ( ! empty( $campaign_ids ) ) {
				return ES()->campaigns_db->delete_campaigns( $campaign_ids );
			}
			return false;
		}

		/**
		 * Method to Duplicate broadcast content
		 *
		 * @return void
		 *
		 * @since 4.6.3
		 */
		public static function duplicate_campaign( $args ) {
			$args = ES_Common::decode_args( $args );
			
			$plan = ES()->get_plan();
			if ( 'pro' !== strtolower( $plan ) ) {
				return array( 'error' => 'Campaign duplication is available only for Pro plan users.' );
			}
			
			$campaign_id = isset( $args['campaign_id'] ) ? $args['campaign_id'] : 0;
			
			if ( empty( $campaign_id ) ) {
				return array( 'error' => 'Campaign ID is required' );
			}

			$original_campaign = ES()->campaigns_db->get( $campaign_id );
			if ( empty( $original_campaign ) ) {
				error_log( 'ES Duplicate Campaign: Original campaign not found - ID: ' . $campaign_id );
				return array( 'error' => 'Original campaign not found' );
			}

			$duplicated_campaign_id = ES()->campaigns_db->duplicate_campaign( $campaign_id );
			if ( empty( $duplicated_campaign_id ) ) {
				error_log( 'ES Duplicate Campaign: Database duplicate failed for ID: ' . $campaign_id );
				return array( 'error' => 'Failed to create duplicate campaign in database' );
			}

			$duplicated_campaign = ES()->campaigns_db->get( $duplicated_campaign_id );
			if ( empty( $duplicated_campaign ) ) {
				error_log( 'ES Duplicate Campaign: Could not retrieve duplicated campaign - ID: ' . $duplicated_campaign_id );
				return array( 'error' => 'Failed to retrieve duplicated campaign' );
			}

		$duplicated_campaign = self::format_single_campaign_data( $duplicated_campaign );

			return $duplicated_campaign;
		}

		public static function format_categories( $categories ) {
			$categories = explode( '##', trim( trim( $categories, '##' ) ) );
			$formatted_categories = array();
			$category_names = array();
			$has_all_categories = false;
			
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $category ) {
					if ( ! empty( $category ) ) {
						$post_categories = explode( '|', $category );
						foreach ( $post_categories as $post_category ) {
							if ( empty( $post_category ) || strpos( $post_category, ':' ) === false ) {
								continue;
							}
							$parts = explode( ':', $post_category, 2 );
							if ( count( $parts ) < 2 ) {
								continue;
							}
							list( $post_type, $categories_list ) = $parts;
							if ( 'none' !== $categories_list && 'all' !== $categories_list && ! empty( $categories_list ) ) {
								$categories_list = array_map( 'absint', explode( ',', $categories_list ) );
								// Convert term IDs to names (handles both categories and custom taxonomies)
								foreach ( $categories_list as $term_id ) {
									// First try to get term from default category taxonomy
									$term = get_term( $term_id, 'category' );
									if ( ! is_wp_error( $term ) && ! empty( $term ) ) {
										$category_names[] = $term->name;
									} else {
										// If not found in categories, search across all taxonomies for this post type
										$taxonomies = get_object_taxonomies( $post_type, 'names' );
										foreach ( $taxonomies as $taxonomy ) {
											$term = get_term( $term_id, $taxonomy );
											if ( ! is_wp_error( $term ) && ! empty( $term ) ) {
												$category_names[] = $term->name;
												break; // Found it, no need to check other taxonomies
											}
										}
									}
								}
							} elseif ( 'all' === $categories_list ) {
								$has_all_categories = true;
							}
							$formatted_categories[$post_type] = $categories_list;
						}
					} 
				} 
			}
			
			if ( $has_all_categories && empty( $category_names ) ) {
				$category_names[] = '-';
			}
			
			return array(
				'formatted_categories' => $formatted_categories,
				'category_names' => array_unique( $category_names )
			);
		}

		public static function is_post_campaign( $campaign_type ) {
			return in_array( $campaign_type, array( IG_CAMPAIGN_TYPE_POST_NOTIFICATION, IG_CAMPAIGN_TYPE_POST_DIGEST ), true );
		}

		/**
		 * Get status text based on status number
		 * 
		 * @param int $status Campaign status number
		 * @return string Status text
		 */
		public static function get_campaign_status_text( $status ) {
			$status = (int) $status;
			switch ( $status ) {
				case 0:
					return 'Draft';
				case 1:
					return 'Active';
				case 2:
					return 'Scheduled';
				case 3:
					return 'Sending';
				case 4:
					return 'Paused';
				case 5:
					return 'Sent';
			}
		}

		/**
		 * Toggle campaign status (enable/disable)
		 *
		 * @param array $args Campaign IDs and new status
		 * @return bool Success status
		 *
		 * @since 4.4.4
		 */
		public static function toggle_status( $args ) {
			$args = ES_Common::decode_args( $args );
			
			if ( isset( $args['campaign_ids'], $args['new_status'] ) ) {
				$campaign_ids = isset($args['campaign_ids']) ? $args['campaign_ids'] : array();
				$campaign_ids = array_map('absint', $campaign_ids);
				$new_status   = absint( $args['new_status'] ); // Convert to integer
		
				if (!empty($campaign_ids)) {
					$status_updated = ES()->campaigns_db->update_status( $campaign_ids, $new_status );
					return $status_updated;
				}
			}
			return false; 
		}

		public static function get_countries_for_filter( $args = array() ) {
			try {
				$countries = ES_Geolocation::get_countries();
				
				// Transform countries array to the expected format for dropdown
				$formatted_countries = array();
				foreach ( $countries as $code => $name ) {
					$formatted_countries[] = array(
						'code' => $code,
						'name' => $name
					);
				}
				
				return $formatted_countries;
			} catch ( Exception $e ) {
				return array();
			}
		}

		/**
		 * Trigger campaign sending immediately
		 *
		 * @param array $args Campaign ID and nonce
		 * @return array Success status and message
		 *
		 * @since 5.0.0
		 */
		public static function trigger_campaign_sending( $args ) {
			$args = ES_Common::decode_args( $args );
			
			$campaign_id = ! empty( $args['campaign_id'] ) ? absint( $args['campaign_id'] ) : 0;
			$nonce = ! empty( $args['nonce'] ) ? sanitize_text_field( $args['nonce'] ) : '';
			
			if ( empty( $campaign_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid campaign ID', 'email-subscribers' ),
				);
			}
			
			// Verify nonce for security
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ig-es-admin-ajax-nonce' ) ) {
				return array(
					'success' => false,
					'message' => __( 'Security check failed', 'email-subscribers' ),
				);
			}
			
			// Get campaign details
			$campaign = ES()->campaigns_db->get_campaign_by_id( $campaign_id );
			
			if ( empty( $campaign ) ) {
				return array(
					'success' => false,
					'message' => __( 'Campaign not found', 'email-subscribers' ),
				);
			}
			
			// Check if campaign is in a sendable status (scheduled or sending)
			$campaign_status = ! empty( $campaign['status'] ) ? absint( $campaign['status'] ) : 0;
			if ( ! in_array( $campaign_status, array( 2, 3 ), true ) ) { // 2 = Scheduled, 3 = Sending
				return array(
					'success' => false,
					'message' => __( 'Campaign is not in a sendable status', 'email-subscribers' ),
				);
			}
			
			// Get mailing queue for this campaign
			$mailing_queue = ES_DB_Mailing_Queue::get_notification_by_campaign_id( $campaign_id );
			
			if ( empty( $mailing_queue ) ) {
				return array(
					'success' => false,
					'message' => __( 'Mailing queue not found for this campaign', 'email-subscribers' ),
				);
			}
			
			$mailing_queue_id   = ! empty( $mailing_queue['id'] ) ? $mailing_queue['id'] : 0;
			$mailing_queue_hash = ! empty( $mailing_queue['hash'] ) ? $mailing_queue['hash'] : '';
			
			if ( empty( $mailing_queue_id ) || empty( $mailing_queue_hash ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid mailing queue', 'email-subscribers' ),
				);
			}
			
			// Update the mailing queue start time to now so it sends immediately
			$current_date_time = ig_get_current_date_time();
			ES_DB_Mailing_Queue::update_notification(
				$mailing_queue_id,
				array( 'start_at' => $current_date_time )
			);
			
			// Queue emails if not already queued (use existing campaign controller method)
			$notification_status = ! empty( $mailing_queue['status'] ) ? $mailing_queue['status'] : '';
			if ( ! in_array( $notification_status, array( 'Sending', 'Sent' ), true ) ) {
				ES_Campaign_Controller::queue_emails( $mailing_queue_id, $mailing_queue_hash, $campaign_id );
			}
			
			// Trigger the mailing queue sending (use existing campaign controller method)
			ES_Campaign_Controller::maybe_send_mailing_queue( $mailing_queue_id, $mailing_queue_hash );
			
			return array(
				'success' => true,
				'message' => __( 'Campaign sending has been triggered successfully', 'email-subscribers' ),
			);
		}

		// Note: paginate_campaigns method removed as it's not needed for React UI
		// React frontend handles pagination through get_campaigns and get_campaigns_count methods

	}

}

ES_Campaigns_Controller::get_instance();

