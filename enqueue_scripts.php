<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$extension_url = plugins_url('laybuy_extension.php', __FILE__);

/**
 * function to enqueue laybuy scripts
 */
add_action('wp_enqueue_scripts', 'laybuy_enq_checkout_script');

function laybuy_enq_checkout_script(){
	global $extension_url;
	
	$laybuy_settings = get_option('woocommerce_laybuy_settings');
	$max_months = $laybuy_settings['max_months'];
	$min = $laybuy_settings['min'];
	$max = $laybuy_settings['max'];	
	$laybuy_checkout_text_enabled = $laybuy_settings['laybuy_checkout_text_enabled'];
	$currency_position = get_option('woocommerce_currency_pos');
	
	//check wether jquery is already included or not
	if(!wp_script_is('jquery', 'queue')){

		wp_enqueue_script('jquery');

	}
	
	if(isset($_GET['order_id'])){
		
		$order_id = $_GET['order_id'];
		$order = new WC_Order( $order_id );
		$order_total = $order->order_total;
		
	}else $order_total = 0;

	wp_enqueue_style('laybuy-style', plugins_url('css/laybuy.css', __FILE__) );
	wp_enqueue_script( 'checkout_script' , plugin_dir_url(__FILE__) . 'js/function.js');
	wp_localize_script('checkout_script', 'plugin_val', array( 'laybuy_checkout_text_enabled' => $laybuy_checkout_text_enabled, 'extension_url' => $extension_url,'max_months'=>$max_months,'max' => $max,'min' => $min,'base_url' => get_site_url(),'order_total'=>$order_total,'currency' => get_woocommerce_currency_symbol(), 'currency_position' => $currency_position));
}

add_action( 'admin_init', 'laybuy_enq_admin_scripts' );

function laybuy_enq_admin_scripts() {

	global $extension_url;
	if(!wp_script_is('jquery', 'queue')){
	
		wp_enqueue_script('jquery');
	
	}
	if(!wp_script_is('jquery-ui-core', 'queue')){
	
		wp_enqueue_script('jquery-ui-core');
	
	}
	if(!wp_script_is('jquery-ui-datepicker', 'queue')){
	
		wp_enqueue_script('jquery-ui-datepicker');
	}
		wp_enqueue_style('thickbox');				wp_enqueue_script('media-upload');		wp_enqueue_script('thickbox');	
	wp_enqueue_script( 'date_picker', plugins_url('js/datepicker.js', __FILE__) );
	wp_enqueue_script( 'admin_script', plugins_url('js/laybuy_admin.js', __FILE__) );
	wp_enqueue_style('jquery-style', plugins_url('css/jquery-ui.css', __FILE__));
	wp_localize_script('admin_script', 'plugin_url', array( 'extension_url' => $extension_url,'currency' => get_woocommerce_currency_symbol()));
}

