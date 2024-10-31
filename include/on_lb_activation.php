<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * create wp_laybuy_response table on plugin activation
 */

function laybuy_response_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . "laybuy_response";

	$sql = "CREATE TABLE $table_name (

	id int(10) NOT NULL AUTO_INCREMENT,
	date date,
	result varchar(10),
	custom varchar(50),
	firstname varchar(50),
	lastname varchar(50),
	address varchar(100),
	suburb varchar(50),
	state varchar(50),
	country varchar(50),
	postcode varchar(50),
	email varchar(50),
	amount varchar(50),
	currency varchar(10),
	paypal_pro_id varchar(50),
	dp_paypal_txn_id varchar(50),
	report text,
	laybuy_ref_no varchar(50),
	downpayment varchar(50),
	months varchar(50),
	dp_amount varchar(50),
	payment_amount varchar(50),
	first_payment_due date,
	last_payment_due date,
	status varchar(20),
	report_size int(10),
	PRIMARY KEY (id,custom)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

/**
 * create wp_laybuy_revision_reports table on plugin activation
 */

function laybuy_revision_reports() {
	global $wpdb;
	$table_name = $wpdb->prefix . "laybuy_revision_reports";

	$sql = "CREATE TABLE $table_name (

	id int(10) NOT NULL AUTO_INCREMENT,
	custom varchar(10),
	email varchar(100),
	amount varchar(10),
	pp varchar(5),
	p_plan varchar(5),
	dp varchar(5),
	month varchar(5),
	merchant_ref_no varchar(10),
	request_date date,
	PRIMARY KEY (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}


/**
 * add lay-buys installment report page on theme activation
 */
function laybuy_add_pages(){

	$customer_reports = array(
			'post_author'    => 1,
			'post_name'      => 'laybuy_installment_report',
			'post_title'     => 'Laybuy Installment Report',
			'post_type'      => 'page',
			'post_status'    => 'publish',

	);

	$page_id = wp_insert_post($customer_reports);

	if($page_id) {
		$laybuy_pages['pages']['Laybuy Installment Report']=$page_id;
	}
	update_option('laybuy_pages', $laybuy_pages);
}

/**
 * remove lay-buys installment report page on theme de-activation
 */

function laybuy_remove_pages(){

	$laybuy_pages =  get_option('laybuy_pages');
	$page_id = $laybuy_pages['pages']['Laybuy Installment Report'];

	wp_delete_post($page_id);

}

/**
 * edit 'woocommerce_order_details_after_order_table' hook
 * for adding 'Put it on lay-buy' link on 'view order' page
 */

add_action( 'woocommerce_order_details_after_order_table', 'laybuy_order_table',40 );
function laybuy_order_table(){

	if (isset($_GET['order'])) {

		global $wpdb;
		$order_id = $_GET['order'];		
		$laybuy_response = $wpdb->prefix . "laybuy_response";
		$transaction = $wpdb->get_results( "SELECT * FROM $laybuy_response  WHERE custom = '$order_id'");

		if($transaction){

			$page_settings = get_option('laybuy_pages');
			$page_name = get_post($page_settings['pages']['Laybuy Installment Report'])->post_name;
			echo '<dl class="customer_details">				  <dt>Payment Method:</dt>				  <dd><a title="click to view report" href="'.get_site_url().'/'.$page_name.'?order_id='.$order_id.'">PUT IT ON LAY-BUY powered by PayPal</a></dd></dl>';
		}
	}
}