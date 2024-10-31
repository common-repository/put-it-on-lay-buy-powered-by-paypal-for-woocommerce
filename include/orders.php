<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$laybuy_settings = get_option('woocommerce_laybuy_settings');
$member_id = $laybuy_settings['laybuy_memb_id'];
$fetch_report_url = $laybuy_settings['report_settings'];
$profile_ids = $row->paypal_pro_id;
if($row->status == 3)
{
	laybuy_fetch_report($profile_ids,$member_id,$fetch_report_url);
}

if(isset($_GET['pagenum']))
{
	$page_num = $_GET['pagenum'];
}
else
{
	$page_num = 1;
}

$revise = isset($_GET['revise'])?$_GET['revise']:false;
$laybuy_settings = get_option('woocommerce_laybuy_settings');
$member_id = $laybuy_settings['laybuy_memb_id'];


if(isset($_POST['save_resend_plan'])){
	
	
	if($_POST['payment_type'] == 1)
	{
		$lb_opt = sanitize_text_field($_POST['payment_type']);
	}
	else 
	{
		$bn_opt = sanitize_text_field($_POST['payment_type']);
	}	
	
	$id = $row->id;
	$settings['installments'] = sanitize_text_field($_POST['installments']);
	$settings['dp_percent'] = sanitize_text_field($_POST['dp_percent']);
	$settings['payment_type'] = sanitize_text_field($_POST['payment_type']);
	$settings['save_revised_plan'] = 'yes';
	update_option( "laybuy_revision_settings_$id", $settings, '', 'yes' );
	
	$url = 'https://lay-buys.com/vtmob/deal5.php';
	
	$buyer_email = isset($_POST['buyer_email'])?sanitize_text_field($_POST['buyer_email']):null;
	$lb_amount = isset($_POST['lb_amount'])?sanitize_text_field($_POST['lb_amount']):null;
	$lb_amount = explode(' ',$lb_amount);
	$lb_amount = $lb_amount[1];
	$payment_type = isset($settings['payment_type'])?$settings['payment_type']:null;
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
	
	if ($result == "success" )
	{
	$revision_info = array('order_id'=>$row->custom,'payment type'=>$payment_type);
	$info = print_r($revision_info,true);
	laybuy_write_log('the following order was revised',$info);
	$_SESSION['mail_sent'] = 'yes';
	
	}
	else
	{
		$_SESSION['mail_sent'] = 'no';
	}
}

if ($revise){
	
	include 'revise.php';	
	exit;
}

/* display admin niotice after revising transaction */
if(isset($_SESSION['mail_sent'])){
	if($_SESSION['mail_sent'] == 'yes'){
	
		$message = "Request has been saved and email was sent to ".$row->email." for order #".$row->custom;
		laybuy_show_admin_notice($message);
		
	}else if($_SESSION['mail_sent'] == 'no'){
	
		$message = "Unable to save and mail the revised instalment plan.";
		laybuy_show_admin_notice($message);
	}
}
unset($_SESSION['mail_sent']);

/* display admin niotice after cancelling transaction */

if(isset($_SESSION['txn_canceled'])){
	if($_SESSION['txn_canceled'] == 'yes'){
	
		$message = "The transaction has been cancelled successfully.";
		laybuy_show_admin_notice($message);
		
	}else if($_SESSION['txn_canceled'] == 'no'){
	
		$message = "Unable to cancel the transaction.";
		laybuy_show_admin_notice($message);
	}
}
unset($_SESSION['txn_canceled']);

?>
<div style="margin: 20px 0 35px;">
	<h3>View Transaction Details</h3>
	<!-- <hr style="margin-bottom: 20px;"> -->
	<a style="text-decoration: none;" href="<?php echo get_admin_url(); ?>admin.php?page=lay-buy-instalment-reports&pagenum=<?php echo $page_num; ?>"><input type="button" style="float: right;margin-right: 5px;" class="button" value="Back" name="lb_back" id="lb_back"></a>
	
	<?php 
	/* if transaction status is pending (3) then only display cancel and revise options */
	if ($row->status == '3'){
	?>
	<form name="cancel_transaction" id="cancel_transaction" method="post">
	
	<input type="submit" style="float: right;margin-right: 5px;" value="Cancel Transaction" name="lb_cancel" id="lb_cancel" class="button">
	
	</form>
	
	<?php /* cancel transaction */
	
	if(isset($_POST['lb_cancel'])){
		
		/* cancel transaction in Lay-Buys */

		laybuy_cancel_transaction($row->paypal_pro_id,$row->custom,$row->id,'-1');
		
		/* cancel transaction WooCommerce orders */
		laybuy_change_order_status ($row->custom,'cancelled');
		$redirect_url = get_admin_url().'admin.php?page=lay-buy-instalment-reports&txn_id='.$row->id;
		
		wp_redirect($redirect_url);
		
		exit;
		
	}
	
	?>
	<a href="<?php echo $txn_url;?>&revise=true"><input type="button" style="float: right;margin-right: 5px;" value="Revise Instalment paln" name="lb_revise" id="lb_revise" class="button"></a>
	<?php }
	elseif($row->status == 5)
	{?>
	<form name="revise_plan" id="revise_plan" method="post">
	<?php 
		$results = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."laybuy_revision_reports` WHERE custom ='$row->custom' ORDER BY id DESC LIMIT 1" );
		$result = $results[0];
		//print_r($result);
		?>
	<input type="hidden" name="buyer_email"	value="<?php echo $result->email?>">
	<input type="hidden" name="lb_amount" value="<?php echo get_woocommerce_currency_symbol().' '.$result->amount?>">
	<input type="hidden" name="payment_type" value="<?php echo $result->pp?>">
	<input type="hidden" name="dp_percent"	value="<?php echo $result->dp?>">
	<input type="hidden" name="installments" value="<?php echo $result->month?>">
	<input type="submit" style="float: right;margin-right: 5px;" value="Resend Email" name="save_resend_plan" id="save_revised_plan" class="button">
	</form>
	<?php }	
	
	
	?>	
			<h4><?php echo 'Reference Information';?></h4>
	
			<table cellspacing="0" class="wp-list-table widefat fixed posts">
			
				<tbody id="the-list">
					
					<tr>
					<td>PayPal Profile ID</td>
					<td><?php echo $row->paypal_pro_id;?></td>
					</tr>
					<tr>
					<td>Lay-Buys Reference ID</td>
					<td><?php echo $row->laybuy_ref_no;?></td>
					</tr>
					<tr>
					<td>Order ID</td>
					<td>#<?php echo $row->custom;?></td>
					</tr>
				
				</tbody>
				
			</table>
			
		<h4><?php echo 'Payment Plan'; ?></h4>
		<?php
		$start_date = date("Y-m-d", strtotime($row->date));
			
		?>
		<table cellspacing="0" class="wp-list-table widefat fixed posts">
			
			<tbody id="the-list">
				
				<tr>
				<td>Status</td>
				<td><?php echo laybuy_status($row->status);?></td>
				</tr>
				<tr>
				<td>Amount</td>
				<td><?php echo get_woocommerce_currency_symbol().$row->amount;?></td>
				</tr>
				<tr>
				<td>Down Payment %</td>
				<td><?php echo $row->downpayment;?>%</td>
				</tr>
				<tr>
				<td>Months</td>
				<td><?php echo $row->months;?></td>
				</tr>
				<tr>
				<td>Downpayment Amount</td>
				<td><?php echo get_woocommerce_currency_symbol().$row->dp_amount;?></td>
				</tr>
				<tr>
				<td>Payment Amount</td>
				<td><?php echo get_woocommerce_currency_symbol().$row->payment_amount;?></td>
				</tr>
				<tr>
				<td>First Payment Due</td>
				<td><?php echo date("M d, Y", strtotime($row->first_payment_due));?></td>
				</tr>
				<tr>
				<td>Last Payment Due</td>
				<td><?php echo date("M d, Y", strtotime($row->last_payment_due));?></td>
				</tr>
				<tr>
				<td>Payment Record</td>
				<td>
					
				<?php //include 'installments_html.php';?>
				<?php echo $row->report;?>
					
				</td>
				</tr>
			
				</tbody>
				</table>
				<h4><?php echo 'Customer Information';?></h4>
				<table cellspacing="0" class="wp-list-table widefat fixed posts">
			
				<tbody id="the-list">
				
				<tr>
				<td>First Name</td>
				<td><?php echo $row->firstname;?></td>
				</tr>
				<tr>
				<td>Last Name</td>
				<td><?php echo $row->lastname;?></td>
				</tr>
				<tr>
				<td>Email</td>
				<td><?php echo $row->email;?></td>
				</tr>
				<tr>
				<td>Address</td>
				<td><?php echo $row->address;?></td>
				</tr>
				<tr>
				<td>Suburb</td>
				<td><?php echo $row->suburb;?></td>
				</tr>
				<tr>
				<td>State</td>
				<td><?php echo $row->state;?></td>
				</tr>
				<tr>
				<td>Country</td>
				<td><?php echo $row->country;?></td>
				</tr>
				<tr>
				<td>Postcode</td>
				<td><?php echo $row->postcode;?></td>
				</tr>
			
				</tbody>
				</table>
</div>
<?php exit;
