<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Subscribers_Pricing {

	/**
	 * Get pricing banner configuration
	 * 
	 * @return array Banner configuration
	 */
	public static function get_pricing_banner_config() {

		$current_plan = ES()->get_plan();

		if ( $current_plan !== 'lite' ) {
			return array(
				'enabled' => false,
			);
		}
		
		$activation_dates = get_option( 'ig_es_plan_activation_dates', array() );

		$activation_date = isset( $activation_dates[ 'lite' ] ) ? $activation_dates[ 'lite' ] : '';
		
		$default_offer = array(
			'enabled' => true,
			'discount' => 25,
			'title' => __( '25% off - your exclusive discount, always applied when you choose a plan below.', 'email-subscribers' ),
			'description' => __( 'Your permanent discount is ready.', 'email-subscribers' ),
			'buttonText' => __( 'Boost My Revenue', 'email-subscribers' ),
			'maxbuttonLink' => 'https://www.icegram.com/?buy-now=404335&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=march-2026',
			'probuttonLink' => 'https://www.icegram.com/?buy-now=39043&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=march-2026',
			'smallTextLower' => __( 'Your growth starts the moment you upgrade.', 'email-subscribers' ),
		);
		
		// If activation date is empty so we will show 25% offer (This is for old setup)
		if ( empty( $activation_date ) ) {
			return $default_offer;
		}
		
		// Calculate days since activation
		$activation_timestamp = strtotime( $activation_date );
		$current_timestamp = current_time( 'timestamp' );
		$days_since_activation = floor( ( $current_timestamp - $activation_timestamp ) / DAY_IN_SECONDS );
		
		// Determine offer based on days since activation
		if ( $days_since_activation <= 1 ) {
			// Offer 1: First 2 days - 35% off
			$days_left = 1 - $days_since_activation; // Days remaining in this offer period
			$config = array(
				'enabled' => true,
				'discount' => 35,
				'daysLeft' => max( 0, $days_left ),
				'title' => __( '35% off for the next 48 hours - applied automatically when you choose a plan below.', 'email-subscribers' ),
				'description' => __( 'Offer expires in 48 hours from your first visit.','email-subscribers' ),
				'buttonText' => __( 'Start Growing with 35% Off', 'email-subscribers' ),
				'maxbuttonLink' => 'https://www.icegram.com/?buy-now=404335&qty=1&coupon=BJAPXJXS&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=march-2026',
				'probuttonLink' => 'https://www.icegram.com/?buy-now=39043&qty=1&coupon=BJAPXJXS&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=march-2026',
				'smallTextLower' => __( 'Your best price ends in 48 hours.', 'email-subscribers' ),
			);
		} elseif ( $days_since_activation >= 2 && $days_since_activation <= 8 ) {
			// Offer 2: Days 3-9 (week) - 25% off
			$days_left = 8 - $days_since_activation; // Days remaining until final day offer
			$config = array(
				'enabled' => true,
				'discount' => 25,
				'daysLeft' => max( 0, $days_left ),
				'title' => __( '25% off this week only - applied automatically when you choose a plan below.', 'email-subscribers' ),
				'description' => __( 'Offer valid for 7 days from your first visit.', 'email-subscribers' ),
				'buttonText' => __( 'Start Growing with 25% Off', 'email-subscribers' ),
				'maxbuttonLink' => 'https://www.icegram.com/?buy-now=404335&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=march-2026',
				'probuttonLink' => 'https://www.icegram.com/?buy-now=39043&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=march-2026',
			);
		} elseif ( $days_since_activation == 9 ) {
			// Offer 3: Day 10 only - 35% off (Final Day)
			$config = array(
				'enabled' => true,
				'discount' => 35,				
				'daysLeft' => 0, // Last day				
				// 'smallTextUpper' => '',
				'title' => __( 'This is your last chance to get 35% off. After today, the discount drops to 25% permanently.', 'email-subscribers' ),
				'description' => __( 'This offers expires tonight.', 'email-subscribers' ),
				'buttonText' => __( 'Unlock 35% Savings', 'email-subscribers' ),
				'maxbuttonLink' => 'https://www.icegram.com/?buy-now=404335&qty=1&coupon=BJAPXJXS&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=march-2026',
				'probuttonLink' => 'https://www.icegram.com/?buy-now=39043&qty=1&coupon=BJAPXJXS&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=march-2026',
				'smallTextLower' => __( 'This offer won’t come back.', 'email-subscribers' ),
			);
		} else {
			// After day 10 - 25% off
			$config = $default_offer;
		}

		return $config;
	}

	public static function es_show_pricing() {
		$utm_medium  = apply_filters( 'ig_es_pricing_page_utm_medium', 'in_app_pricing' );
		$allowedtags = ig_es_allowed_html_tags_in_esc();

		$pro_url = 'https://www.icegram.com/?buy-now=39043&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=' . esc_attr( $utm_medium ) . '&utm_campaign=pro';
		$max_url = 'https://www.icegram.com/?buy-now=404335&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=' . esc_attr( $utm_medium ) . '&utm_campaign=max';
		
		$premium_links = array(
				'pro_url' => $pro_url,
				'max_url' => $max_url
			);
		$premium_links = apply_filters( 'ig_es_premium_links', $premium_links );
		?>
		<div id="root"></div>
		<?php
	}
}

new Email_Subscribers_Pricing();
