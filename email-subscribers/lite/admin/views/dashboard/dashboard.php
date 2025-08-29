<?php
// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$audience_url              = admin_url( 'admin.php?page=es_subscribers' );
$new_contact_url           = admin_url( 'admin.php?page=es_subscribers&action=new' );
$new_broadcast_url         = admin_url( 'admin.php?page=es_campaigns#!/gallery?campaignType=newsletter' );
$new_post_notification_url = admin_url( 'admin.php?page=es_campaigns#!/gallery?campaignType=post_notification' );
$new_sequence_url          = admin_url( 'admin.php?page=es_sequence&action=new' );
$new_form_url              = admin_url( 'admin.php?page=es_forms&action=new' );
$form_url                  = admin_url( 'admin.php?page=es_forms' );
$new_list_url              = admin_url( 'admin.php?page=es_lists&action=new' );
$list_url				   = admin_url( 'admin.php?page=es_lists' );
$new_template_url          = admin_url( 'admin.php?page=es_campaigns#!/gallery?manageTemplates=yes' );
$icegram_pricing_url       = 'https://www.icegram.com/email-subscribers-pricing/';
$reports_url               = admin_url( 'admin.php?page=es_reports' );
$templates_url             = admin_url( 'edit.php?post_type=es_template' );
$settings_url              = admin_url( 'admin.php?page=es_settings' );
$facebook_url              = 'https://www.facebook.com/groups/2298909487017349/';
$import_url				   = admin_url( 'admin.php?page=es_subscribers&action=import' );
$campaign_url			   = admin_url( 'admin.php?page=es_campaigns' );


$topics = ES_Common::get_useful_articles();
$allowed_html_tags = ig_es_allowed_html_tags_in_esc(); 

?>
<div id="root"></div>
