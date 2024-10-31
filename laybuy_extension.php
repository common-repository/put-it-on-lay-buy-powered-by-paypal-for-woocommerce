<?php
/*
Plugin Name: PUT IT ON LAY-BUY powered by PayPal for WooCommerce
Plugin URI: 
Author: Lay-Buy Financial Solutions Pty Ltd
Author URI: warrin@lay-buys.com
Description: Lay-Buy is an affordable payment plan option that allows you to pay-off a product or service via one down payment, with the balance paid over 1, 2 or 3 monthly instalments. Your purchase is delivered to you after the final instalment payment is completed.
Version: 2.1.17
*/

/*
 * laybuy_extension.php is the main file for 
 * 'PUT IT ON LAY-BUY powered by PayPal for WooCommerce' plugin
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hide all warnings
 */

error_reporting(0);

/**
 * start session if not already started
 */

if( !session_id() )
{
	session_start();
}

/**
 * include ajax functionality 
 */

include 'include/laybuy_ajax.php';
include 'success.php';
include 'include/revision_success.php';

function wpdocs_http_response_timeout( $timeout ) 
{	
	return 30; 
}

add_filter( 'http_response_timeout', 'wpdocs_http_response_timeout' );

add_action('plugins_loaded', 'woocommerce_gateway_laybuy_init', 0);

function woocommerce_gateway_laybuy_init() 
{
	/**
	 * check if WC_Payment_Gateway class exists or not
	 */	
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
	 * Localisation
	 */	

	load_plugin_textdomain('wc-gateway-laybuy', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
	/**
	 * Gateway class
	 */

	require_once 'laybuy_class.php';
	
	/**
     * Add the Gateway to WooCommerce
	 **/	

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_laybuy' );	

	function woocommerce_add_gateway_laybuy($methods) 
	{
		$methods[] = 'WC_Gateway_laybuy';
		return $methods;
	}

	include 'enqueue_scripts.php';	
	include 'include/callback_functions.php';

	/**
	 *  url for 'fetch updates' cron job 
	 */
	
	if(isset($_GET['cron_action']))
	{
		$action = $_GET['cron_action'];
		if ($action == 'fetch_updates')
		{
			laybuy_cronjob_function();
		}
	}
} 

register_activation_hook(__FILE__, 'laybuy_cronjob_activation');

function laybuy_cronjob_activation() 
{
	$laybuy_settings = get_option('woocommerce_laybuy_settings');
	$timestamp = $laybuy_settings['cron_time'];
	$recurrence = $laybuy_settings['cron_frequency'];
	if($timestamp == 'time()'):
		$timestamp = time();
	endif;
	wp_schedule_event($timestamp, $recurrence, 'laybuycronjobactivationhook');
}

add_action('laybuycronjobactivationhook', 'laybuy_cronjob_function');

function laybuy_cronjob_function()
{
	$profile_ids = laybuy_get_values('paypal_pro_id');
	$laybuy_settings = get_option('woocommerce_laybuy_settings');
	$member_id = $laybuy_settings['laybuy_memb_id'];
	$fetch_report_url = $laybuy_settings['report_settings'];
	laybuy_fetch_report($profile_ids,$member_id,$fetch_report_url); /* fetch updates */
	exit;
}

register_deactivation_hook(__FILE__, 'laybuy_cronjob_deactivation');

function laybuy_cronjob_deactivation() 
{
	wp_clear_scheduled_hook('laybuycronjobactivationhook');
}

/**
 * register section in admin panel for instalment reports settings
 */

add_action('admin_menu', 'register_laybuy_submenu_page');

function register_laybuy_submenu_page() 
{
	add_submenu_page( 'woocommerce', 'Lay-Buys Instalment Reports', 'Lay-Buys Instalment Reports', 'manage_options', 'lay-buy-instalment-reports', 'laybuy_report_page_callback' );
}

function laybuy_report_page_callback() 
{
	global $woocommerce;
	include 'include/installment_report.php';
}

include_once dirname( __FILE__ ) . '/include/on_lb_activation.php';
register_activation_hook( __FILE__,  'laybuy_response_table' );
register_activation_hook( __FILE__,  'laybuy_revision_reports' );
register_activation_hook( __FILE__, 'laybuy_add_pages' );
register_deactivation_hook(__FILE__, 'laybuy_remove_pages');

/**
 * include custom template for customer's 'lay-buys installment report' page
 */

add_filter( 'template_include', 'customer_report_template');

function customer_report_template($template)
{
	$laybuy_page_settings= get_option('laybuy_pages');
	$page_id = $laybuy_page_settings['pages']['Laybuy Installment Report'];
	if(is_page($page_id))
	{
		$new_template = dirname( __FILE__ ). '/templates/customer_laybuy_reports.php';
		$template =  $new_template;
	}
	return $template;
}

function laybuy_show_view_button($actions, $order)
{
	$order_status = $order->post_status;
	$payment_type = get_post_meta( $order->id, '_payment_method', true );

	if($payment_type == 'laybuy' && $order_status == 'wc-pending')
	{
		unset( $actions['pay'] );
		unset( $actions['cancel'] );
	}
	return $actions;
}

add_filter( 'woocommerce_my_account_my_orders_actions','laybuy_show_view_button', 10, 2);


function add_start()
{
	ob_start();
}
add_action('init', 'add_start');
?>
