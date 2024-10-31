<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;

$limit = isset($_GET['filter1']) ? absint( $_GET['filter1']) : 10; // number of rows in page

$date = isset($_GET['date'])?$_GET['date']:'';

$date = date('Y-m-d',strtotime($date));
$date = trim($date);

$order = isset($_GET['custom'])?$_GET['custom']:'';
$amount = isset($_GET['amount'])?$_GET['amount']:'';
$dp = isset($_GET['downpayment'])?$_GET['downpayment']:'';
$months = isset($_GET['months'])?$_GET['months']:'';
$dp_amount = isset($_GET['dp_amount'])?$_GET['dp_amount']:'';
$p_amount = isset($_GET['payment_amount'])?$_GET['payment_amount']:'';
$f_due = isset($_GET['first_payment_due'])?$_GET['first_payment_due']:'';
$f_due = date('Y-m-d',strtotime($f_due));
$f_due = trim($f_due);
$l_due = isset($_GET['last_payment_due'])?$_GET['last_payment_due']:'';
$l_due = date('Y-m-d',strtotime($l_due));
$l_due = trim($l_due);
$lb_status = isset($_GET['status'])?$_GET['status']:'';

$sql_string = '';
//$sql_string .="SELECT * FROM $laybuy_response ";

if($date!='1970-01-01') $sql_string .=" WHERE date= '$date'";

if($order) $sql_string .=" WHERE custom= '$order'";

if($amount) $sql_string .=" WHERE amount= '$amount'";

if($dp) $sql_string .=" WHERE downpayment= '$dp'";

if($months) $sql_string .=" WHERE months= '$months'";

if($dp_amount) $sql_string .=" WHERE dp_amount= '$dp_amount'";

if($p_amount) $sql_string .=" WHERE payment_amount= '$p_amount'";

if($f_due!='1970-01-01') $sql_string .=" WHERE first_payment_due= '$f_due' ";

if($l_due!='1970-01-01') $sql_string .=" WHERE last_payment_due= '$l_due' ";

if($lb_status!=='') $sql_string .=" WHERE status= $lb_status";

$sql_string = laybuy_replace_skip($sql_string, 'WHERE', 'AND');

$offset = ( $pagenum - 1 ) * $limit;
$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM $laybuy_response ".$sql_string );
$num_of_pages = ceil( $total / $limit );

/* query for filter */


$sql_string .=" ORDER BY date DESC LIMIT $offset, $limit";

$sql_string = laybuy_replace_skip($sql_string, 'WHERE', 'AND');

$transaction = $wpdb->get_results( "SELECT * FROM $laybuy_response ".$sql_string );//echo $wpdb->last_query;

if($total == 0){
	$message = "Record not found. ";
	laybuy_show_admin_notice($message);	
}
$page_links = paginate_links( array(
		'base' => add_query_arg( 'pagenum', '%#%' ),
		'format' => '',
		'prev_text' => __( '&laquo;', 'aag' ),
		'next_text' => __( '&raquo;', 'aag' ),
		'total' => $num_of_pages,
		'current' => $pagenum
) );