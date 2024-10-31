<?php
/**
 * The Template for displaying laybuy reports to customers.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$user_id = get_current_user_id();

if($user_id == 0)
{
	wp_redirect(get_permalink( get_option('woocommerce_myaccount_page_id')));
	exit;
}


get_header();


/**
 * woocommerce_before_main_content hook
*
* @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
* @removed woocommerce_breadcrumb hook- 20
*/
do_action('woocommerce_before_main_content');


global $woocommerce;

//$woocommerce->show_messages(); 

$user_id = get_current_user_id();

global $wpdb;

$orders = $wpdb->get_results( "select post_id from $wpdb->postmeta where meta_key = '_customer_user' and meta_value=$user_id", ARRAY_A );
$txn_id = array();

if(count($orders)>0){
	foreach ($orders as $order){
		$txn_id[] = isset($order['post_id'])?$order['post_id']:'';
	}
}
$txn_id = implode(',',$txn_id);

$laybuy_response = $wpdb->prefix . "laybuy_response";

$transaction = '';

//$url = $_SERVER['REQUEST_URI'];
$page_settings = get_option('laybuy_pages');
$page_name = get_post($page_settings['pages']['Laybuy Installment Report'])->post_name;
		
if (isset($_GET['txn_id'])) {
	$my_order_id = $_GET['txn_id'];
	$txn_url = $_SERVER['REQUEST_URI'];
	include 'my_orders.php';
}

global $post;
$page_id = $post->ID;
$id= isset($_GET['order_id'])?$_GET['order_id']:'';
/* change query string parameter */
if(isset($_GET['order_id'])){
$param = '?order_id='.$_GET['order_id'].'&txn_id';
}else $param  = '?txn_id';

$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;

$limit = 10; // number of rows per page
$offset = ( $pagenum - 1 ) * $limit;


?>
<div class="lb_center">

<h2><?php echo get_the_title($page_id);?></h2>
<a class="button" href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id'));?>" style="text-decoration: none;">
&larr; Return To Orders
</a>
<table class="order_details customer_report" style="margin-top:20px;float:right;">
<thead>
<tr>
<th>Created At</th>
<th>Order</th>
<th>Amount</th>
<th>Down Payment %</th>
<th>Months</th>
<th>First Payment Due</th>
<th>Last Payment Due</th>
<th>Status</th>
</tr>
</thead>
<tbody id="the-list">
<?php 
$transaction = $wpdb->get_results( "SELECT * FROM $laybuy_response  WHERE custom in ($txn_id) ORDER BY date DESC LIMIT $offset, $limit");
if($transaction){
	foreach ($transaction as $row){ 
?>
	<tr>
	<td><?php echo date("M d, Y", strtotime($row->date));?></td>
	<td><a href="<?php echo get_site_url(); ?>/<?php echo $page_name.$param?>=<?php echo $row->id;?>">Order #<?php echo $row->custom;?></a></td>
	<td><?php echo $row->amount;?></td>
	<td><?php echo $row->downpayment;?></td>
	<td><?php echo $row->months;?></td>
	<td><?php echo date("M d, Y", strtotime($row->first_payment_due));?></td>
	<td><?php echo date("M d, Y", strtotime($row->last_payment_due));?></td>
	<td><?php echo laybuy_status($row->status);?></td>
	</tr>
	
	<?php  
		
	}
}

?>
</tbody>
</table>
<?php 

$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM $laybuy_response WHERE custom in ($txn_id)" );

$num_of_pages = ceil( $total / $limit );

$page_links = paginate_links( array(

		'base' => add_query_arg( 'pagenum', '%#%' ),

		'format' => '',

		'prev_text' => __( '&laquo;', 'aag' ),

		'next_text' => __( '&raquo;', 'aag' ),

		'total' => $num_of_pages,

		'current' => $pagenum

) );

if ( $page_links ) {

	echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';

}
?>
</div>
<?php

/**
 * woocommerce_after_main_content hook
*
* @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
*/
do_action('woocommerce_after_main_content');

/**
* woocommerce_sidebar hook
*
* @hooked woocommerce_get_sidebar - 10
*/
do_action('woocommerce_sidebar');

get_footer();