<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$url = 'https://lay-buys.com/vtmob/deal5.php';

$buyer_email = isset($_POST['buyer_email'])?sanitize_text_field($_POST['buyer_email']):null;
$lb_amount = isset($_POST['lb_amount'])?sanitize_text_field($_POST['lb_amount']):null;
$lb_amount = explode(' ',$lb_amount);
$lb_amount = $lb_amount[1];
$payment_type = isset($settings['payment_type'])?sanitize_text_field($settings['payment_type']):null;
$dp_percent = isset($_POST['dp_percent'])?sanitize_text_field($_POST['dp_percent']):null;
$installments = isset($_POST['installments'])?sanitize_text_field($_POST['installments']):null;

if($lb_opt){
	
	$data ='';
	$data .= "mid=".$member_id."&";
	$data .= "eml=".$buyer_email."&";
	$data .= "prc=".$lb_amount."&";
	$data .= "curr=".get_woocommerce_currency()."&";
	$data .= "pp=".$payment_type."&";
	$data .= "pplan=".$payment_type."&";
	$data .= "init=".$dp_percent."&";
	$data .= "mnth=".$installments."&";
	$data .= "convrate=1"."&";
	$data .= "id=".$row->id."-".$row->custom."&";
	$data .= "RETURNURL=".get_permalink( get_option('woocommerce_myaccount_page_id') )."?revision=true&";
	$data .= "CANCELURL=".get_permalink( get_option('woocommerce_myaccount_page_id') )."?revision=false";
	
}

else {
	
	$data ='';
	$data .= "mid=".$member_id."&";
	$data .= "eml=".$buyer_email."&";
	$data .= "prc=".$lb_amount."&";
	$data .= "curr=".get_woocommerce_currency()."&";
	$data .= "pp=".$payment_type."&";
	$data .= "pplan=".$payment_type."&";
	$data .= "init=100"."&";
	$data .= "mnth=0"."&";
	$data .= "convrate=1"."&";
	$data .= "id=".$row->id."-".$row->custom."&";
	$data .= "RETURNURL=".get_permalink( get_option('woocommerce_myaccount_page_id') )."?revision=true&";
	$data .= "CANCELURL=".get_permalink( get_option('woocommerce_myaccount_page_id') )."?revision=false";
}


$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  /* use this to suppress output */
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); /* tell cURL to graciously accept an SSL certificate */
$result = curl_exec ($ch);
curl_close ($ch);


/**
 * update the status of transaction from pending to requested revise 
 */

if ($result == "success" ){
	
	global $wpdb;
	$revision_reports = $wpdb->prefix . "laybuy_revision_reports";
		
	laybuy_update_txn_status('5',$row->custom,$row->id);
	
	$data = array(
			'email' => $buyer_email,
			'amount' => $lb_amount,
			'pp' => $payment_type,
			'p_plan' => $payment_type,
			'dp' => $dp_percent,
			'month' => $installments,
			'merchant_ref_no' => $row->id,
			'request_date' => date('Y-m-d'),
			'custom' =>$row->custom
			);
	$wpdb->insert( $revision_reports, $data);
	$revision_info = array('order_id'=>$row->custom,'payment type'=>$payment_type);
	$info = print_r($revision_info,true);
	laybuy_write_log('the following order was revised',$info);
	$_SESSION['mail_sent'] = 'yes';
	
	$location = get_admin_url().'admin.php?page=lay-buy-instalment-reports&txn_id='.$row->id;
	wp_redirect( $location );
	exit;
	
}else 	$_SESSION['mail_sent'] = 'no';
?>