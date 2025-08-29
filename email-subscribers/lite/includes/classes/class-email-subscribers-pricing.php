<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Subscribers_Pricing {

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
