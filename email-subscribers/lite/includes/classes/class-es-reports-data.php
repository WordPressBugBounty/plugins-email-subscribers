<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Reports_Data' ) ) {
	/**
	 * Get Reports Data
	 * Class ES_Reports_Data
	 *
	 * @since 4.3.2
	 */
	class ES_Reports_Data {

		/**
		 * Get total Contacts
		 *
		 * @since 4.3.2
		 */
		public static function get_total_contacts() {
			return ES()->contacts_db->get_total_contacts();
		}

		/**
		 * Get total subscribed contacts in last $days
		 *
		 * @param array $args Filter arguments
		 *
		 * @return int
		 *
		 * @since 4.3.2
		 * @since 4.3.5 Modified ES_DB_Lists_Contacts::get_total_subscribed_contacts to
		 * ES()->lists_contacts_db->get_total_subscribed_contacts
		 * @since 4.3.6 Modified funct			$total_subscribed_args = array(
				'list_id' => $				'average_daily_signups'  => $average_daily_signups,
				'previous_total_unsubscribed' => $previous_unsubscribed,
				'previous_average_daily_signups' => $previous_average_daily_signups,
				'previous_engagement_rate' => 0,
				'previous_inactive_contacts' => 0,
				'previous_avg_bounce_rate' => 0,
				'previous_average_score' => 0,
				'current_period_signups' => $current_period_signups,_id'],
				'days' => 0
			);a			$previous_period_signups = 0;
			$previous_average_daily_signups = 0;
			$previous_unsubscribed = 0;
			
			if ( $days > 0 ) {
				$previous_signups_args = array(
					'list_id' => $args['list_id'],
					'days' => $days * 2
				);
				$double_period_signups = self::get_total_subscribed_contacts( $previous_signups_args );bscribed_contacts_co				'average_daily_signups'  => $average_daily_signups,
				'previous_total_unsubscribed' => $previous_unsubscribed,
				'previous_average_daily_signups' => $previous_average_daily_signups,
				'previous_engagement_rate' => 0,
				'previous_inactive_contacts' => 0,
				'previous_avg_bounce_rate' => 0,
				'previous_average_score' => 0,
				'current_period_signups' => $current_period_signups,_subscribed_contacts_count
		 * @since 5.7.24 Moved SQL logic to ES_DB_Contacts class for better separation of concerns
		 */
		public static function get_total_subscribed_contacts( $args = array() ) {
			return ES()->contacts_db->get_total_subscribed_contacts_with_filters( $args );
		}

		/**
		 * Get total unsubscribed contacts in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.3.2
		 * @since 4.3.5 Modified ES_DB_Lists_Contacts::get_total_unsubscribed_contacts to
		 * ES()->lists_contacts_db->get_total_unsubscribed_contacts
		 * @since 4.3.6 Modified function name from get_total_unsubscribed_contacts to get_unsubscribed_contacts_count
		 */
		public static function get_total_unsubscribed_contacts( $args = array() ) {
			$days = ! empty( $args['days'] ) ? $args['days'] : 0;
			return ES()->lists_contacts_db->get_unsubscribed_contacts_count( $days );
		}

		/**
		 * Get total unconfiremed contacts in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.5.7
		 */
		public static function get_total_unconfirmed_contacts( $args = array() ) {
			$days = ! empty( $args['days'] ) ? $args['days'] : 0;
			return ES()->lists_contacts_db->get_unconfirmed_contacts_count( $days );
		}

		/**
		 * Get total contacts have opened emails in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.3.2
		 *
		 * @modify 5.5.5 Used ES()->actions_db->get_count() function to get type
		 */
		public static function get_total_contacts_opened_emails( $args = array(), $distinct = true ) {
			$args['type'] = IG_MESSAGE_OPEN;
			return ES()->actions_db->get_count( $args, $distinct );
		}

		/**
		 * Get total contacts have clicked on links in emails in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @since 4.3.2
		 *
		 * @modify 5.5.5 Used ES()->actions_db->get_count() function to get type
		 */
		public static function get_total_contacts_clicks_links( $args = array(), $distinct = true ) {
			$args['type'] = IG_LINK_CLICK;
			return ES()->actions_db->get_count( $args, $distinct );
		}

		/**
		 * Get total emails sent in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 *
		 * @modify 5.5.5 Used ES()->actions_db->get_count() function to get type
		 */
		public static function get_total_emails_sent( $args = array(), $distinct = true ) {
			$args['type'] = IG_MESSAGE_SENT;
			return ES()->actions_db->get_count( $args, $distinct );
		}

		/**
		 * Get total contacts lost in last $days
		 *
		 * @param int $days
		 *
		 * @return int
		 * 
		 * @modify 5.5.5 Used ES()->actions_db->get_count() function to get type
		 */
		public static function get_total_contact_unsubscribed( $args = array(), $distinct = true ) {
			$args['type'] = IG_CONTACT_UNSUBSCRIBE;
			return ES()->actions_db->get_count( $args, $distinct );
		}

		/**
		 * Get contacts growth
		 *
		 * @param int $days
		 *
		 * @return array
		 *
		 * @since 4.4.0
		 */
		public static function get_contacts_growth( $days = 60 ) {

			$contacts = ES()->contacts_db->get_total_contacts_by_date();

			$total = ES()->contacts_db->get_total_subscribed_contacts_before_days( $days );

			$data = array();
			for ( $i = $days; $i >= 0; $i -- ) {
				$date = gmdate( 'Y-m-d', strtotime( '-' . $i . ' days' ) );

				$count = isset( $contacts[ $date ] ) ? $contacts[ $date ] : 0;

				$total += $count;

				$data[ $date ] = $total;
			}

			return $data;
		}

		/**
		 * Get contacts monthly growth
		 *
		 * @param int $months
		 *
		 * @return array
		 *
		 * @since 4.4.0
		 */
		public static function get_contacts_growth_monthly( $months = 12 ) {

			$contacts = ES()->contacts_db->get_total_contacts_by_date();

			$start_date = gmdate( 'Y-m-01', strtotime( '-' . $months . ' months' ) );
			$total = ES()->contacts_db->get_total_subscribed_contacts_before_days(
				( $months * 30 )
			);

			$data = array();

			for ( $i = $months; $i >= 0; $i-- ) {
				$month_key = gmdate( 'Y-m', strtotime( '-' . $i . ' months' ) );

				$month_start = gmdate( 'Y-m-01', strtotime( $month_key . '-01' ) );
				$month_end   = gmdate( 'Y-m-t', strtotime( $month_key . '-01' ) );

				$count = 0;
				foreach ( $contacts as $date => $cnt ) {
					if ( $date >= $month_start && $date <= $month_end ) {
						$count += $cnt;
					}
				}

				$total += $count;

				$data[ $month_key ] = $total;
			}

			return $data;
		}



		/**
		 * Get contacts growth percentage
		 *
		 * @param int $days
		 *
		 * @return float|integer
		 *
		 * @since 4.8.0
		 */
		public static function get_contacts_growth_percentage( $args = array() ) {
			$days = ! empty( $args['days'] ) ? $args['days'] : 60;
			//For example, It will get last 60'days subscribers count
			$present_subscribers_count = ES()->lists_contacts_db->get_subscribed_contacts_count( $days );
			//For example, It will get last 120'days subscribers count
			$past_to_present_subscribers_count = ES()->lists_contacts_db->get_subscribed_contacts_count( $days * 2 );
			//For example, It will get last 60-120'days subscribers count
			$past_subscribers_count = intval( $past_to_present_subscribers_count ) - intval( $present_subscribers_count );

			if ( 0 === $past_subscribers_count ) {
				return 0;
			} else {
				return round( ( $present_subscribers_count - $past_subscribers_count ) / $past_subscribers_count * 100, 2 );
			}
		}

		/**
		 * Get all contacts growth percentage
		 *
		 * @param int $days
		 *
		 * @return float|integer
		 *
		 * @since 4.8.0
		 */
		public static function get_total_contacts_growth_percentage( $args = array() ) {
			$days = ! empty( $args['days'] ) ? $args['days'] : 60;
			//For example, It will get last 60'days subscribers count
			$present_contacts_count = ES()->lists_contacts_db->get_all_contacts_count( $days );
			//For example, It will get last 120'days subscribers count
			$past_to_present_contacts_count = ES()->lists_contacts_db->get_all_contacts_count( $days * 2 );
			//For example, It will get last 60-120'days subscribers count
			$past_contacts_count = intval( $past_to_present_contacts_count ) - intval( $present_contacts_count );

			if ( 0 === $past_contacts_count ) {
				return 0;
			} else {
				return round( ( $present_contacts_count - $past_contacts_count ) / $past_contacts_count * 100, 2 );
			}
		}

		/**
		 * Collect dashboard reports data
		 *
		 * @return array
		 *
		 * @since 4.4.0
		 */
	public static function get_dashboard_reports_data( $page, $override_cache = false, $args = array(), $campaign_count = 5 ) {
		global $wpdb;

		/**
		 * - Get Total Contacts
		 * - Get Total Forms
		 * - Get Total Lists
		 * - Get Total Campaigns
		 * - Get Last 3 months contacts data
		 * - Total Email Opened in last 60 days
		 * - Total Message Sent in last 60 days
		 * - Avg. Email Click rate
		 */
		$args['days'] = ! empty( $args['days'] ) ? $args['days'] : 7;
		
		// Include days in cache key to differentiate between different time periods
		$cache_key = 'dashboard_reports_data_' . $args['days'];
		
		if ( ! $override_cache ) {

				$cached_data = ES_Cache::get_transient( $cache_key );

				if ( ! empty( $cached_data ) ) {
					return $cached_data;
				}
			} else {
				ES_Cache::delete_transient( $cache_key );
			}
			
			$total_subscribed = self::get_total_subscribed_contacts( $args );
 
			$action_types       = ES()->get_action_types();
			$args['types']      = $action_types;
			$actions_counts     = ES()->actions_db->get_actions_count( $args );
			$total_email_opens  = $actions_counts['opened'];
			$total_links_clicks = $actions_counts['clicked'];
			$total_message_sent = $actions_counts['sent'];
			$total_unsubscribed = $actions_counts['unsubscribed'];
			$contacts_growth    = self::get_contacts_growth_monthly();

			$avg_open_rate = 0;
			if ( $total_message_sent > 0 ) {
				$avg_open_rate = ( $total_email_opens * 100 ) / $total_message_sent;
			}

			$avg_click_rate = 0;
			if ( $total_message_sent > 0 ) {
				$avg_click_rate = ( $total_links_clicks * 100 ) / $total_message_sent;
			}

			$avg_unsubscribe_rate = 0;
			if ( $total_message_sent > 0 ) {
				$avg_unsubscribe_rate = ( $total_unsubscribed * 100 ) / $total_message_sent;
			}

			/**
		 * Get recent campaigns with statistics
		 */
		$data = array();
		if ( 'es_dashboard' === $page || 'wp_dashboard' === $page ) {
			$data = self::get_campaign_stats( $campaign_count );
			
			$pending_campaign_args = array(
				'order_by_column' => 'ID',
				'limit'           => '5',
				'order'           => 'DESC',
				'status'          => array( 2, 3 ),
				'include_types'   => array(
					IG_CAMPAIGN_TYPE_POST_NOTIFICATION,
					IG_CAMPAIGN_TYPE_POST_DIGEST,
					IG_CAMPAIGN_TYPE_NEWSLETTER
				),
			);
				$pending_campaigns = ES()->campaigns_db->get_campaigns( $pending_campaign_args );

				$pending_campaigns_data = array();
				$seen_campaign_ids      = array();
				if ( ! empty( $pending_campaigns ) ) {
					foreach ( $pending_campaigns as $campaign ) {
						// Fetch latest mailing queue hash for this campaign (not stored in campaigns table)
						$cron_url = '';
						$hash     = '';
						// phpcs:disable
						$mq_hash = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT hash FROM {$wpdb->prefix}ig_mailing_queue WHERE campaign_id = %d AND hash IS NOT NULL ORDER BY id DESC LIMIT 1",
								(int) $campaign['id']
							)
						);
						// phpcs:enable
						if ( ! empty( $mq_hash ) ) {
							$hash     = $mq_hash;
							$cron_url = ES()->cron->url( true, false, $hash );
						}
						$pending_campaigns_data[] = array(
							'id'       => $campaign['id'],
							'title'    => $campaign['subject'],
							'status'   => $campaign['status'],
							'type'     => $campaign['type'],
							'hash'     => $hash,
							'cron_url' => $cron_url,
						);
						$seen_campaign_ids[] = (int) $campaign['id'];
					}
				}

				// Also include queued/sending items from mailing queue for parity
				$mq_table = $wpdb->prefix . 'ig_mailing_queue';
				// phpcs:disable
				$mq_items = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, campaign_id, subject, status, hash FROM {$mq_table} WHERE status IN (%s, %s) ORDER BY id DESC LIMIT %d",
						'In Queue',
						'Sending',
						5
					),
					ARRAY_A
				);
				// phpcs:enable
				if ( ! empty( $mq_items ) ) {
					foreach ( $mq_items as $mq ) {
						$cid = (int) $mq['campaign_id'];
						if ( $cid > 0 && in_array( $cid, $seen_campaign_ids, true ) ) {
							continue;
						}
						$hash     = ! empty( $mq['hash'] ) ? $mq['hash'] : '';
						$cron_url = '';
						if ( ! empty( $hash ) ) {
							$cron_url = ES()->cron->url( true, false, $hash );
						}
						$pending_campaigns_data[] = array(
							'id'       => $cid,
							'title'    => $mq['subject'],
							'status'   => $mq['status'],
							'type'     => ES()->campaigns_db->get_column( 'type', $cid ),
							'hash'     => $hash,
							'cron_url' => $cron_url,
						);
						if ( $cid > 0 ) {
							$seen_campaign_ids[] = $cid;
						}
					}
				}

				$data['pending_campaigns'] = $pending_campaigns_data;
			}

			// Get device and country tracking data
			$device_opens = array();
			$country_opens = array();
			
			if ( ES()->is_pro() ) {
			$days = ! empty( $args['days'] ) ? (int) $args['days'] : 0;
			
			// Use database methods for efficient SQL aggregation
			$device_opens = ES()->actions_db->get_device_open_counts( $days );
			$country_opens = ES()->actions_db->get_country_open_counts( $days, 10 );
		}
		
		// Ensure device_opens is always an object, not an array
		if ( empty( $device_opens ) ) {
			$device_opens = (object) array();
		}
		
		// Ensure country_opens is always an object, not an array
		if ( empty( $country_opens ) ) {
			$country_opens = (object) array();
		}
		// Get top clicked links for the dashboard
		$top_links = array();
		if ( 'es_dashboard' === $page ) {
			$links_where = '';
			
			if ( ! empty( $args['days'] ) ) {
				$start_date = time() - ( $args['days'] * DAY_IN_SECONDS );
				$links_where = $wpdb->prepare( 'created_at >= %d', $start_date );
			}
			
			// Get all links in the time period
			$links = ES()->links_db->get_by_conditions( $links_where );
			
			if ( ! empty( $links ) ) {
				$link_clicks = array();
				foreach ( $links as $link ) {
					$link_id = $link['id'];
					$link_url = $link['link'];
					
					// Count clicks for this link using direct SQL query
					$actions_table = $wpdb->prefix . 'ig_actions';
					$count_query = $wpdb->prepare(
						"SELECT COUNT(*) FROM {$actions_table} WHERE link_id = %d AND type = %d",
						$link_id,
						IG_LINK_CLICK
					);
					
					if ( ! empty( $args['days'] ) ) {
						$count_query = $wpdb->prepare(
							"SELECT COUNT(*) FROM {$actions_table} WHERE link_id = %d AND type = %d AND created_at >= %d",
							$link_id,
							IG_LINK_CLICK,
							$start_date
						);
					}
					
					$click_count = $wpdb->get_var( $count_query );
					
					if ( $click_count > 0 ) {
						$link_clicks[] = array(
							'link' => $link_url,
							'clicks' => (int) $click_count
						);
					}
				}
				
				// Sort by clicks descending and get top 5
				if ( ! empty( $link_clicks ) ) {
					usort( $link_clicks, function( $a, $b ) {
						return $b['clicks'] - $a['clicks'];
					});
					$top_links = array_slice( $link_clicks, 0, 5 );
				}
			}
		}
			
		// Calculate spam complaint count (currently always 0 in lite version)
		$spam_complaint_count = 0;
		
		// Calculate email frequency per week
		$days = ! empty( $args['days'] ) ? $args['days'] : 7;
		$weeks = max( 1, $days / 7 );
		
		// Get total campaigns sent in the period using mailing queue
		$sent_campaigns_count = 0;
		if ( ! empty( $args['days'] ) ) {
			$start_date = time() - ( $args['days'] * DAY_IN_SECONDS );
			$start_date_mysql = gmdate( 'Y-m-d H:i:s', $start_date );
			$mailing_queue_query = $wpdb->prepare(
				"SELECT COUNT(DISTINCT campaign_id) FROM {$wpdb->prefix}ig_mailing_queue WHERE start_at >= %s AND status IN ('Sent', 'Sending')",
				$start_date_mysql
			);
			$sent_campaigns_count = (int) $wpdb->get_var( $mailing_queue_query );
		}
		$email_frequency_per_week = $sent_campaigns_count > 0 ? number_format_i18n( ( $sent_campaigns_count / $weeks ), 2 ) : 0;
		
		// Calculate engagement rate (contacts who opened or clicked / total sent)
		$total_engaged = $total_email_opens + $total_links_clicks;
	$engagement_rate = $total_message_sent > 0 ? number_format_i18n( ( ( $total_engaged / $total_message_sent ) * 100 ), 2 ) : 0;
	
	$reports_data = array(
				'total_subscribed'     => number_format_i18n($total_subscribed ?? 0),
				'total_email_opens'    => number_format_i18n($total_email_opens ?? 0),
				'total_links_clicks'   => number_format_i18n($total_links_clicks ?? 0),
				'total_message_sent'   => number_format_i18n($total_message_sent ?? 0),
				'total_unsubscribed'   => number_format_i18n($total_unsubscribed ?? 0),
				'avg_open_rate'        => number_format_i18n($avg_open_rate ?? 0, 2),
				'avg_click_rate'       => number_format_i18n($avg_click_rate ?? 0, 2),
				'avg_unsubscribe_rate' => number_format_i18n($avg_unsubscribe_rate ?? 0, 2),
				'contacts_growth'      => $contacts_growth ?? 0,
				'device_opens'         => $device_opens,
				'country_opens'        => $country_opens,
				'top_links'            => $top_links,
				'spam_complaint_count' => $spam_complaint_count,
				'email_unsubscribed_count' => $total_unsubscribed,
				'email_frequency_per_week' => $email_frequency_per_week,
				'engagement_rate'      => $engagement_rate,
			);
			

			$include_average_campaigns_stats = 'es_dashboard' === $page || 'es_campaigns' === $page || 'es_subscribers' === $page;
			if ( $include_average_campaigns_stats ) {
				$comp_args         = $args;
				$comp_args['days'] = $args['days'] * 2;

				$last_four_months_actions_count = ES()->actions_db->get_actions_count( $comp_args );
				
				$last_four_months_sent  = $last_four_months_actions_count['sent'];
				$sent_before_two_months = $last_four_months_sent - $total_message_sent;
				if ( $sent_before_two_months > 0 ) {
					$sent_percentage_growth = ( ( $total_message_sent - $sent_before_two_months ) / $sent_before_two_months ) * 100;
				} else {
					$sent_percentage_growth = 0;
				}
				
				$last_four_months_opens = $last_four_months_actions_count['opened'];
				$open_before_two_months = $last_four_months_opens - $total_email_opens;
				if ( $open_before_two_months > 0 ) {
					$open_percentage_growth = ( ( $total_email_opens - $open_before_two_months ) / $open_before_two_months ) * 100;
				} else {
					$open_percentage_growth = 0;
				}

				$last_four_months_clicks = $last_four_months_actions_count['clicked'];
				$click_before_two_months = $last_four_months_clicks - $total_links_clicks;
				if ( $click_before_two_months > 0 ) {
					$click_percentage_growth = ( ( $total_links_clicks - $click_before_two_months ) / $click_before_two_months ) * 100;
				} else {
					$click_percentage_growth = 0;
				}

				$last_four_months_unsubscribes  = $last_four_months_actions_count['unsubscribed'];
				$unsubscribes_before_two_months = $last_four_months_unsubscribes - $total_unsubscribed;
				if ( $unsubscribes_before_two_months > 0 ) {
					$unsubscribes_percentage_growth = ( ( $total_unsubscribed - $unsubscribes_before_two_months ) / $unsubscribes_before_two_months ) * 100;
				} else {
					$unsubscribes_percentage_growth = 0;
				}

				if ( isset( $actions_counts['hard_bounced'] ) ) {
					$total_hard_bounces             = $actions_counts['hard_bounced'];
					$last_four_months_hard_bounces  = $last_four_months_actions_count['hard_bounced'];
					$hard_bounces_before_two_months = $last_four_months_hard_bounces - $total_hard_bounces;
					if ( $hard_bounces_before_two_months > 0 ) {
						$hard_bounces_percentage_growth = ( ( $total_hard_bounces - $hard_bounces_before_two_months ) / $hard_bounces_before_two_months ) * 100;
					} else {
						$hard_bounces_percentage_growth = 0;
					}

					if ( $total_message_sent > 0 ) {
						$avg_bounce_rate                 = ( $total_hard_bounces * 100 ) / $total_message_sent;
						$reports_data['avg_bounce_rate'] = $avg_bounce_rate ? number_format_i18n( $avg_bounce_rate, 2 ) : 0;
					}
	
					$reports_data['total_hard_bounced_contacts']    = number_format_i18n( $total_hard_bounces );
					$reports_data['hard_bounces_before_two_months'] = number_format_i18n( $hard_bounces_before_two_months );
					$reports_data['hard_bounces_percentage_growth'] = 0 !== $hard_bounces_percentage_growth ? number_format_i18n( $hard_bounces_percentage_growth, 2 ) : 0;
					$reports_data['bounce_percentage_growth']       = 0 !== $hard_bounces_percentage_growth ? number_format_i18n( $hard_bounces_percentage_growth, 2 ) : 0;
				}

				$reports_data['sent_percentage_growth']        = 0 !== $sent_percentage_growth ? number_format_i18n( $sent_percentage_growth, 2 ) : 0;
				$reports_data['sent_before_two_months']        = number_format_i18n( $sent_before_two_months );
				$reports_data['open_percentage_growth']        = 0 !== $open_percentage_growth ? number_format_i18n( $open_percentage_growth, 2 ) : 0;
				$reports_data['open_before_two_months']        = number_format_i18n( $open_before_two_months );
				$reports_data['click_percentage_growth']       = 0 !== $click_percentage_growth ? number_format_i18n( $click_percentage_growth, 2 ) : 0;
				$reports_data['click_before_two_months']       = number_format_i18n( $click_before_two_months );
				$reports_data['unsubscribe_percentage_growth'] = 0 !== $unsubscribes_percentage_growth ? number_format_i18n( $unsubscribes_percentage_growth, 2 ) : 0;
				$reports_data['unsubscribe_before_two_months'] = number_format_i18n( $unsubscribes_before_two_months );
				
				$previous_open_rate       = ( $sent_before_two_months > 0 ) ? ( ( $open_before_two_months * 100 ) / $sent_before_two_months ) : 0;
				$previous_click_rate      = ( $sent_before_two_months > 0 ) ? ( ( $click_before_two_months * 100 ) / $sent_before_two_months ) : 0;
				$previous_engagement_rate = ( $sent_before_two_months > 0 ) ? ( ( ( $open_before_two_months + $click_before_two_months ) * 100 ) / $sent_before_two_months ) : 0;
				$current_engagement_rate  = ( $total_message_sent > 0 ) ? ( ( ( $total_engaged / $total_message_sent ) * 100 ) ) : 0;

				$avg_open_rate_percentage_growth   = ( $previous_open_rate > 0 ) ? ( ( ( $avg_open_rate - $previous_open_rate ) / $previous_open_rate ) * 100 ) : 0;
				$avg_click_rate_percentage_growth  = ( $previous_click_rate > 0 ) ? ( ( ( $avg_click_rate - $previous_click_rate ) / $previous_click_rate ) * 100 ) : 0;
				$engagement_rate_percentage_growth = ( $previous_engagement_rate > 0 ) ? ( ( ( $current_engagement_rate - $previous_engagement_rate ) / $previous_engagement_rate ) * 100 ) : 0;

				$reports_data['avg_open_rate_percentage_growth']   = 0 !== $avg_open_rate_percentage_growth ? number_format_i18n( $avg_open_rate_percentage_growth, 2 ) : 0;
				$reports_data['avg_click_rate_percentage_growth']  = 0 !== $avg_click_rate_percentage_growth ? number_format_i18n( $avg_click_rate_percentage_growth, 2 ) : 0;
				$reports_data['engagement_rate_percentage_growth'] = 0 !== $engagement_rate_percentage_growth ? number_format_i18n( $engagement_rate_percentage_growth, 2 ) : 0;
				$reports_data['previous_open_rate']               = number_format_i18n( $previous_open_rate, 2 );
				$reports_data['previous_click_rate']              = number_format_i18n( $previous_click_rate, 2 );
				$reports_data['previous_engagement_rate']         = number_format_i18n( $previous_engagement_rate, 2 );
			}

			$data = array_merge( $data, $reports_data );

			if ( ! $override_cache ) {
				ES_Cache::set_transient( $cache_key, $data, 1 * HOUR_IN_SECONDS );
			}

			return $data;
		}

		/**
		 * Get Campaigns Stats
		 *
		 * @return array
		 *
		 * @since 4.7.8
		 */
		public static function get_campaign_stats( $total_campaigns = 5 ) {

			global $wpdb;

			$campaign_args = array(
				'order_by_column' => 'ID',
				'limit'           => '5',
				'order'           => 'DESC',
				'include_types' => array(
					IG_CAMPAIGN_TYPE_POST_NOTIFICATION,
					IG_CAMPAIGN_TYPE_POST_DIGEST,
					IG_CAMPAIGN_TYPE_NEWSLETTER
				),
			);
			$campaigns = ES()->campaigns_db->get_campaigns( $campaign_args );
			
			$campaigns_data = array();
			if ( ! empty( $campaigns ) && count( $campaigns ) > 0 ) {
			$campaign_ids = array_column( $campaigns, 'id' );
			$campaign_ids = array_filter( $campaign_ids );
			
			$all_stats = array();
			if ( ! empty( $campaign_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $campaign_ids ), '%d' ) );
				// phpcs:disable
				$results = $wpdb->get_results(
					$wpdb->prepare( 
						"SELECT campaign_id, type, COUNT(DISTINCT contact_id) as total 
						FROM {$wpdb->prefix}ig_actions 
						WHERE campaign_id IN ($placeholders) 
						GROUP BY campaign_id, type", 
						$campaign_ids
					), 
					ARRAY_A 
				);
				// phpcs:enable
				
				foreach ( $results as $result ) {
					$campaign_id = $result['campaign_id'];
					$type = $result['type'];
					$total = $result['total'];
					
					if ( ! isset( $all_stats[ $campaign_id ] ) ) {
						$all_stats[ $campaign_id ] = array(
							'total_sent'        => 0,
							'total_opens'       => 0,
							'total_clicks'      => 0,
							'total_unsubscribe' => 0,
						);
					}
					
					switch ( $type ) {
						case IG_MESSAGE_SENT:
							$all_stats[ $campaign_id ]['total_sent'] = $total;
							break;
						case IG_MESSAGE_OPEN:
							$all_stats[ $campaign_id ]['total_opens'] = $total;
							break;
						case IG_LINK_CLICK:
							$all_stats[ $campaign_id ]['total_clicks'] = $total;
							break;
						case IG_CONTACT_UNSUBSCRIBE:
							$all_stats[ $campaign_id ]['total_unsubscribe'] = $total;
							break;
					}
				}
			}

			foreach ( $campaigns as $key => $campaign ) {

				$campaign_id = $campaign['id'];

				if ( 0 === $campaign_id ) {
					continue;
				}
				
				$stats = isset( $all_stats[ $campaign_id ] ) ? $all_stats[ $campaign_id ] : array(
					'total_sent'        => 0,
					'total_opens'       => 0,
					'total_clicks'      => 0,
					'total_unsubscribe' => 0,
				);

				if ( 0 != $stats['total_sent'] ) {
					$campaign_opens_rate        = ( $stats['total_opens'] * 100 ) / $stats['total_sent'];
					$campaign_clicks_rate       = ( $stats['total_clicks'] * 100 ) / $stats['total_sent'];
					$campaign_unsubscribe_rate  = ( $stats['total_unsubscribe'] * 100 ) / $stats['total_sent'];
				} else {
					$campaign_opens_rate        = 0;
					$campaign_clicks_rate       = 0;
					$campaign_unsubscribe_rate  = 0;
				}

				$campaign_type = isset( $campaign['type'] ) ? $campaign['type'] : '';
				
				$type = '';
				if ( 'newsletter' === $campaign_type ) {
					$type = __( 'Broadcast', 'email-subscribers' );
				} elseif ( 'post_notification' === $campaign_type ) {
					$type = __( 'Post Notification', 'email-subscribers' );
				} elseif ( 'post_digest' === $campaign_type ) {
					$type = __( 'Post Digest', 'email-subscribers' );
				}

				$campaigns_data[ $key ]                         = $stats;
				$campaigns_data[ $key ]['id']                   = $campaign['id'];
				$campaigns_data[ $key ]['title']                = $campaign['subject'];
				$campaigns_data[ $key ]['status']               = $campaign['status'];
				$campaigns_data[ $key ]['campaign_type']        = $campaign_type;
				$campaigns_data[ $key ]['type']                 = $type;
				$campaigns_data[ $key ]['total_sent']           = $stats['total_sent'];
				$campaigns_data[ $key ]['campaign_opens_rate']  = number_format_i18n( $campaign_opens_rate, 2);
				$campaigns_data[ $key ]['campaign_clicks_rate'] = number_format_i18n( $campaign_clicks_rate, 2);
				$campaigns_data[ $key ]['campaign_losts_rate']  = number_format_i18n( $campaign_unsubscribe_rate, 2);
			}
		}
		$data['campaigns'] = $campaigns_data;

		return $data;
	}

		public static function show_device_opens_stats( $device_opens_data ) {
			
				//Graph for Device Opens
				$device_opened = array();
				$device_label  = array();
				ob_start();
			if ( ! empty( $device_opens_data ) && ! empty( array_filter( $device_opens_data ) ) ) {
				$device_label  = array_map( 'ucfirst' , array_keys( $device_opens_data ) );
				$device_opened = array_values( $device_opens_data );
					
				?>
			<div class="relative bg-white mt-2" id="device_open_graph"></div>
			
			<?php
			} else {
				?>
				<div class="mt-2 bg-white text-sm text-gray-500 py-3 px-6 tracking-wide">
					<?php echo esc_html__( 'No device data found', 'email-subscribers' ); ?>
				</div>
				<?php
			}
			$stats_html = ob_get_clean();
			$allowedtags     = ig_es_allowed_html_tags_in_esc();
			//$stats_html = ES_Common::get_tooltip_html( $stats_html );
			echo wp_kses( $stats_html, $allowedtags );
			?>
			<script type="text/javascript">

				jQuery(document).ready(function ($) {
					let device_data = {
						labels: <?php echo json_encode( $device_label ); ?>,
						datasets: [
							{
								name: "device", 
								type: "pie",
								values: <?php echo json_encode( $device_opened ); ?>,
							}
						]
					}

					const device_chart = new frappe.Chart("#device_open_graph", {
						title: "",
						data: device_data,
						type: 'pie',
						colors: ['#743ee2', '#5DADE2', '#F6608B'],
						height: 30,
						width:30,
						maxSlices: 3,
					});

				});
			</script>
			<?php
		}

		public static function show_sources_stats( $subscriber_source_counts ) {
			
			
			//Graph for Device Opens
			$source_opened = array();
			$source_label  = array();
			ob_start();
			if ( ! empty( $subscriber_source_counts ) && ! empty( array_filter( $subscriber_source_counts ) ) ) {
				$source_label  = array_map( 'ucfirst' , array_keys( $subscriber_source_counts ) );
				$source_opened = array_values( $subscriber_source_counts );
				?>
		<div class="bg-white mt-2" id="sources_graph"></div>
		
		<?php
			} else {
				?>
			<div class="mt-2 bg-white text-sm text-gray-500 py-3 px-6">
				<?php echo esc_html__( 'No source data found', 'email-subscribers' ); ?>
			</div>
			<?php
			}
		$stats_html = ob_get_clean();
		$allowedtags     = ig_es_allowed_html_tags_in_esc();
		//$stats_html = ES_Common::get_tooltip_html( $stats_html );
		echo wp_kses( $stats_html, $allowedtags );
			?>
		<script type="text/javascript">

			jQuery(document).ready(function ($) {
				let source_data = {
					labels: <?php echo json_encode( $source_label ); ?>,
					datasets: [
						{
							name: "source", 
							type: "percentage",
							values: <?php echo json_encode( $source_opened ); ?>,
						}
					]
				}

				const source_chart = new frappe.Chart("#sources_graph", {
					title: "",
					data: source_data,
					type: 'percentage',
					colors: ['#743ee2', '#5DADE2', '#F6608B'],
					height: 80,
					maxSlices: 3,
				});

			});
		</script>
		<?php
		}

		public static function show_unsubscribe_feedback_percentage_stats( $feedback_percentages ) {
			
			
			//Graph for Device Opens
			$unsubscribe_feedback_opened = array();
			$unsubscribe_feedback_label  = array();
			ob_start();
			if ( ! empty( $feedback_percentages ) && ! empty( array_filter( $feedback_percentages ) ) ) {
				$unsubscribe_feedback_label  = array_map( 'ucfirst' , array_keys( $feedback_percentages ) );
				$unsubscribe_feedback_opened = array_values( $feedback_percentages );
			
				?>
	<div class="relative bg-white mt-2 rounded-md shadow" id="unsubscribe_feedbacks_graph"></div>
	
		<?php
			} else {
				?>
		<div class="mt-2 bg-white text-sm text-gray-500 rounded-md shadow py-3 px-6 tracking-wide">
				<?php echo esc_html__( 'No data found', 'email-subscribers' ); ?>
		</div>
			<?php
			}
		$stats_html  = ob_get_clean();
		$allowedtags = ig_es_allowed_html_tags_in_esc();
		$stats_html  = ES_Common::get_tooltip_html( $stats_html );
		echo wp_kses( $stats_html, $allowedtags );
			?>
	<script type="text/javascript">

		jQuery(document).ready(function ($) {
			let unsubscribe_feedback_data = {
				labels: <?php echo json_encode( $unsubscribe_feedback_label ); ?>,
				datasets: [
					{
						name: "unsubscribe_feedback", 
						type: "pie",
						values: <?php echo json_encode( $unsubscribe_feedback_opened ); ?>,
					}
				]
			}

			const unsubscribe_feedback_chart = new frappe.Chart("#unsubscribe_feedbacks_graph", {
				title: "",
				data: unsubscribe_feedback_data,
				type: 'pie',
				colors: ['#743ee2', '#5DADE2', '#F6608B'],
				height: 280,
				maxSlices: 3,
			});

		});
	</script>
	<?php
		}

		/**
		 * Get audience insights data with enhanced metrics
		 *
		 * @param array $args
		 *
		 * @return array
		 *
		 * @since 5.7.20
		 */
		public static function get_audience_insights_data( $args = array() ) {

			$list_id = isset( $args['list_id'] ) ? intval( $args['list_id'] ) : 0;
			$days = isset( $args['days'] ) ? intval( $args['days'] ) : 7;
			
			try {
  
				// Batch fetch all counts in ONE query instead of 5+ separate queries
				$batch_counts = ES()->contacts_db->get_audience_insights_batch_counts( $list_id, $days );
				
				$total_contacts = isset( $batch_counts['total_contacts'] ) ? $batch_counts['total_contacts'] : 0;
				$total_subscribed = isset( $batch_counts['current_subscribed'] ) ? $batch_counts['current_subscribed'] : 0;
				$total_unsubscribed = isset( $batch_counts['current_unsubscribed'] ) ? $batch_counts['current_unsubscribed'] : 0;
				$current_period_subscribers = isset( $batch_counts['current_subscribed'] ) ? $batch_counts['current_subscribed'] : 0;
				$total_subscribers = $current_period_subscribers;
				
				$total_before_period = ES()->contacts_db->get_total_subscribed_contacts_before_days( $days );
				$previous_total_contacts = $total_before_period;
				
				$double_period_subscribers = isset( $batch_counts['double_subscribed'] ) ? $batch_counts['double_subscribed'] : 0;
				$double_period_unsubscribed = isset( $batch_counts['double_unsubscribed'] ) ? $batch_counts['double_unsubscribed'] : 0;
				
				$previous_period_subscribers = max( 0, $double_period_subscribers - $current_period_subscribers );
				$previous_total_subscribers = $previous_period_subscribers;
				$previous_unsubscribed = max( 0, $double_period_unsubscribed - $total_unsubscribed );
				
				$audience_insights_data = array(
					'total_contacts'         => $total_contacts,
					'total_subscribed'       => $total_subscribed,
					'engagement_rate'        => 0, 
					'inactive_contacts'      => 0, 
					'total_unsubscribed'     => $total_unsubscribed,
					'avg_bounce_rate'        => 0, 
					'average_score'          => 0, 
					'total_subscribers'  => $total_subscribers,
					// Previous period data for comparison
					'previous_total_contacts' => $previous_total_contacts,
					'previous_total_unsubscribed' => $previous_unsubscribed,
					'previous_total_subscribers' => $previous_total_subscribers,
					'previous_engagement_rate' => 0,
					'previous_inactive_contacts' => 0,
					'previous_avg_bounce_rate' => 0,
					'previous_average_score' => 0,
					// Additional debug info
					'current_period_subscribers' => $current_period_subscribers,
					'previous_period_subscribers' => $previous_period_subscribers
				);
			
			$audience_insights_data = apply_filters( 'ig_es_audience_insights_data', $audience_insights_data, $args );				return $audience_insights_data;
				
			} catch ( Exception $e ) {
				return array(
					'total_contacts'         => 0,
					'total_subscribed'       => 0,
					'engagement_rate'        => 0,
					'inactive_contacts'      => 0,
					'total_unsubscribed'     => 0,
					'avg_bounce_rate'        => 0,
					'average_score'          => 0,
					'total_subscribers'  => 0,
					'previous_total_contacts' => 0,
					'previous_total_unsubscribed' => 0,
					'previous_total_subscribers' => 0,
					'previous_engagement_rate' => 0,
					'previous_inactive_contacts' => 0,
					'previous_avg_bounce_rate' => 0,
					'previous_average_score' => 0,
					'current_period_subscribers' => 0,
					'previous_period_subscribers' => 0
				);
			}
		}

		public static function get_all_total_contacts( $args = array() ) {
			return ES()->contacts_db->get_all_total_contacts_with_filters( $args );
		}
	}

}
