<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$laybuy_response = $wpdb->prefix . "laybuy_response";
$latest_reports = $wpdb->prefix . "laybuy_latest_reports";

/**
 * fetch the installment reports
 */

function laybuy_fetch_report($profile_ids,$member_id,$url)
{
	$data ='';
	$data .= "mid=".$member_id."&";
	$data .= "profileIds=".$profile_ids;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); /* use this to suppress output */
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); /* tell cURL to graciously accept an SSL certificate */
	$result = curl_exec ($ch);
	curl_close ($ch);
	
	$result = json_decode($result);
	
	laybuy_write_log('fetched reports','no new updates');
	laybuy_save_latest_reports($result);
}

/**
 * function to save fetched updates
 * @param $result
 */
function laybuy_save_latest_reports($result)
{
	if ( ! function_exists( 'get_plugins' ) )
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
	$plugin_folder = get_plugins( '/' . 'woocommerce' );
	$plugin_file = 'woocommerce.php';
	
	// If the plugin version number is set, return it
	if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) 
	{
		$version=$plugin_folder[$plugin_file]['Version'];
	}
	
	//echo $version;
	
	if ( $version >= 2.2 )
	{
		$order_statuses = wc_get_order_statuses();
		foreach($order_statuses as $key=>$order_status)
		{
			if($key == 'wc-on-hold')
			{
				$orders_status['onhold'] = $key;
			}
			if($key == 'wc-processing')
			{
				$orders_status['processing'] = $key;
			}
			if($key == 'wc-cancelled')
			{
				$orders_status['cancelled'] = $key;
			}
			if($key == 'wc-completed')
			{
				$orders_status['completed'] = $key;
			}
		}
			
	}
	else
	{
		$terms = get_terms ( 'shop_order_status', array (
				'hide_empty' => 0,
				'orderby' => 'id'
		) );
			
		for($i = 0; $i < sizeof ( $terms ); $i ++) 
		{
			$order_statuses [$terms [$i]->slug] = $terms [$i]->slug;
			if ($terms [$i]->slug == 'pending') 
			{
				unset ( $order_statuses [$terms [$i]->slug] );
			}
		}
	
		foreach($order_statuses as $key=>$order_status)
		{
			if($key == 'on-hold')
			{
				$orders_status['onhold'] = $key;
			}
			if($key == 'completed')
			{
				$orders_status['completed'] = $key;
			}
				
			if($key == 'processing')
			{
				$orders_status['processing'] = $key;
			}
			if($key == 'cancelled')
			{
				$orders_status['cancelled'] = $key;
			}
		}
	
	}
	
	$fetched = 0;
	
	if($result && count($result)>0)
	{
		foreach($result as $layBuyRefId=>$reports)
		{
			if(isset($result->$layBuyRefId->status))
			{
				$status = $result->$layBuyRefId->status;
				if($result->$layBuyRefId->status==1)
				{
					$txn = $reports->report;
					$txnid = $txn[0]->txnID;
					$my_order = laybuy_order_change($txnid);
					$order = new WC_Order($my_order);
					if($order->post_status != $orders_status['completed'])
					{
						laybuy_change_order_status ($my_order,$orders_status['processing']);
					}
				}
				
				$newStr = '<table>';
				$newStr .= '<thead><tr><th colspan="2">Instalment</th><th>Date</th><th>PayPal Transaction ID</th><th>Status</th></tr></thead>';
				
				$report = $reports->report;
				
				$row = laybuy_get_transaction_by_refId($layBuyRefId);
				$i=0;
				/* if transaction exists then process it */
				if($row)
				{
					$orderId = $row[0]->custom;
					$paypalProfileId = $row[0]->paypal_pro_id;
					$months = (int)$row[0]->months;
					$payment_amount = $row[0]->payment_amount;
					$report_log = print_r($report,true);
					$text = 'fetched reports';
					
					$pending_flag = false;
					
					laybuy_write_log($text,$report_log);
					
					$nextPaymentStatus = "Pending";
					
					$size=0;
					
					foreach($report as $month=>$transaction)
					{
						$size++;
						
						$transaction->paymentDate = date('Y-m-d h:i:s', strtotime(str_replace('/','-',$transaction->paymentDate)));
						$date = date('M d, Y',strtotime($transaction->paymentDate));
						if($transaction->type == 'd')
						{
							$start_date = $date;
							$newStr .= '<tbody style="width:275px;"><tr><td> DP: </td><td> '.get_woocommerce_currency_symbol().$transaction->amount.'</td>'.
									'<td>'.$date.'</td>'.
									'<td>'.$transaction->txnID.'</td>'.
									'<td class="status"> '.$transaction->paymentStatus.'</td></tr>';
							
							continue;
						}
						elseif($transaction->type == 'p')
						{
							$i++;
							$pending_flag = true;
							$txn_amount = $transaction->amount;
							$newStr .= '<tr>';
							$newStr .= '<td> Month '.$month.': </td><td> '.get_woocommerce_currency_symbol().$transaction->amount.' </td>';
							$newStr .= '<td>'.$date.' </td>';
							$txnID = $transaction->txnID;
							$newStr .= '<td> '.$txnID.' </td>';
							$newStr .= '<td class="status"> '.$transaction->paymentStatus.' </td></tr>';
							
							$nextPaymentStatus = $transaction->paymentStatus;
					
						}
											
					}
					
					/* to check wether a pending row is fetched or not */
					
					if($pending_flag)
						$startIndex = $month+1;
					else
						$startIndex = $month+1;
					
					if($month<=$months)
					{
						$tod=time();
						$isLeap = 0;
						$isLeap = Date('L',$tod);
						
						if($isLeap) $dim=array(31,31,29,31,30,31,30,31,31,30,31,30,31);
						else $dim=array(31,31,28,31,30,31,30,31,31,30,31,30,31);
						
						$day=Date('d',$tod);
						$mth=Date('m',$tod);
						$yr=Date('Y',$tod);
						$nextPaymentStatus = "Pending";
						
						for ($month=$startIndex; $month<=$months; $month++) 
						{
							if (++$mth>12) 
							{
								$mth='01';
								$yr++;
							}
							
							$m=1+$mth-1;
							$d=min($day,$dim[$m]);
							$even = '';
						
							$date = date('M d, Y',strtotime("$date + 1 months"));
						
							$newStr .= '<tr>';
							$newStr .= '<td> Month '.$month.': </td><td> '.get_woocommerce_currency_symbol().$payment_amount.' </td>';
									
							$nextPaymentDate = date("M d, Y", strtotime($date));
							$newStr .= '<td> '.$nextPaymentDate.' </td>';
							$newStr .= '<td></td>';
							$newStr .= '<td> '.$nextPaymentStatus.' </td></tr>';
									
						}
					}
					$startIndex = $i+2;
					$newStr.= '</tbody></table>'; 
					
					switch($status)
					{
						case -1: 
							/* Cancel */
							
							laybuy_update_transaction($row[0]->id,array('status'=>-1,'report'=>$newStr,'report_size'=>$startIndex));
							laybuy_change_order_status($orderId,$orders_status['cancelled']);
							$fetched++;
						
						break;
						case 0: /* Processing */
							
							laybuy_update_transaction($row[0]->id,array('status'=>3,'report'=>$newStr,'report_size'=>$startIndex));
							
							laybuy_change_order_status($orderId,$orders_status['onhold']);
							//change_order_status($orderId,'processing');
							
							$fetched++;
							break;
						case 1: 
							/* Completed */
							laybuy_update_transaction($row[0]->id,array('status'=>1,'report'=>$newStr,'report_size'=>$startIndex));
							/* set order status to be completed in woocommerce orders record */
							$order = new WC_Order($orderId);
							
							if($order->post_status != $orders_status['completed'])
							{
								laybuy_change_order_status ($orderId,$orders_status['processing']);
							}
							
							//change_order_status($orderId,'processing');
							$fetched++;
						
						break;
					}
				}
				$i++;
			}
		}
		if($fetched)
		{
			$_SESSION['fetched_rows_count'] = $fetched;
		}
		else
		{
		   $_SESSION['fetched_rows_count'] = '0';
		}	
	} 
	else 
	{
		$_SESSION['fetched_rows_count'] = '0';
	}
}


function laybuy_order_change($txnid)
{
	global $wpdb;
	$laybuy_response = $wpdb->prefix . "laybuy_response";
	$query =  "SELECT custom FROM $laybuy_response WHERE dp_paypal_txn_id = '$txnid'";
	$my_order = $wpdb->get_var($query);
	
	return $my_order;
	
}


/**
 *function to get transaction by laybuy reference id
 */
function laybuy_get_transaction_by_refId($layBuyRefId)
{
	
	global $wpdb;
	$laybuy_response = $wpdb->prefix . "laybuy_response";
	$row = $wpdb->get_results( "SELECT * FROM $laybuy_response WHERE status = '3' AND laybuy_ref_no = '$layBuyRefId' ");
	return $row;
}

/**
 * get pending laybuy values
 */

function laybuy_get_values($index)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "laybuy_response";
	$profile_ids = $wpdb->get_results( "SELECT $index FROM $table_name WHERE status='3'" );
	
	$p_ids = array();
	
	foreach ($profile_ids as $id)
	{
		$p_ids[] = $id->$index;
	}
	$p_ids = implode(',', $p_ids);
	return $p_ids;
}

/**
 * get pending amount
 */

function laybuy_get_remaining_amount($order_id)
{
	global $wpdb;
	$laybuy_response = $wpdb->prefix . "laybuy_response";
		
	$row = $wpdb->get_results( "SELECT amount,dp_amount,payment_amount,report_size,months FROM $laybuy_response WHERE status='3' AND custom=".$order_id );
	
	$total_paid = ($row[0]->dp_amount) + ( (($row[0]->report_size)-2)* $row[0]->payment_amount);
	
	$total_amount = $row[0]->amount;
	
	return number_format(($total_amount-$total_paid),2,'.','');
}

/**
 * get revised downpayment
 

function revised($dp_percent,$order_id){
	
	$pending = get_remaining_amount($order_id);
	
	$new[] = calc_amount($dp_percent,$pending,$months);
	
	return number_format($new_dp[0][0],2,'.','');
}
*/
/**
 * get revised payment amount
 */

function laybuy_revised_amount($dp_percent,$order_id,$months)
{
	$new_amount = laybuy_get_remaining_amount($order_id);
	
	$new_dp_and_installment[] = laybuy_calc_amount($dp_percent,$new_amount,$months);
	
	return $new_dp_and_installment;
}

/**
 * update transaction after fetching reports or revising plan
 */


function laybuy_update_transaction($id,$txn_values)
{
	global $wpdb;
	$laybuy_response = $wpdb->prefix . "laybuy_response";
	
	$wpdb->update(
			$laybuy_response,
			$txn_values,
			array( 'id' => $id )
			);
}


/**
 * returns status of the transaction
 */
 
function laybuy_status($status){

	if ($status == -1) {
		return 'Cancelled';
	} elseif ($status == 0) {
		return 'Processing';
	} elseif ($status == 1) {
		return 'Completed';
	} elseif ($status == 3) {
		return 'Pending';
	}elseif ($status == 4) {
		return 'Revised';
	}elseif ($status == 5) {
		return 'Revision Requested';
	}
	
}

/**
 * returns status value of the transaction
 */

function laybuy_status_value($status){

	if ($status == 'cancelled') {
		return '-1';
	} elseif ($status == 'processing') {
		return '0';
	} elseif ($status == 'completed') {
		return '1';
	} elseif ($status == 'pending') {
		return '3';
	}elseif ($status == 'failed') {
		return '6';
	}elseif ($status == 'on-hold') {
		return '7';
	}elseif ($status == 'refunded') {
		return '8';
	}

}

/**
 * cancel a transaction
*/

function laybuy_cancel_transaction($paypal_pro_id,$order_id,$id,$status){
	
	global $wpdb;
	$laybuy_response = $wpdb->prefix . "laybuy_response";
	
	$laybuy_settings = get_option('woocommerce_laybuy_settings');
	$member_id = $laybuy_settings['laybuy_memb_id'];
	
	$url = 'https://lay-buys.com/vtmob/deal5cancel.php';
	$data ='';
	$data .= "mid=".$member_id."&";
	$data .= "paypal_profile_id=".$paypal_pro_id;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  /* use this to suppress output */
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); /* tell cURL to graciously accept an SSL certificate */
	$result = curl_exec ($ch);
	curl_close ($ch);
	
	if ($result == "success" ){
		
		laybuy_write_log('the following order was cancelled',$order_id);
		laybuy_update_txn_status($status,$order_id,$id);
		
		$report = $wpdb->get_results( "SELECT report FROM $laybuy_response WHERE custom = $order_id AND id = $id AND paypal_pro_id = '$paypal_pro_id'");
			
		foreach ($report as $text){
		
			
			$string = $text->report;
			$patterns[0] = '/quick/';
			$matched = preg_match("/\bpending\b/i", $string);
			
			if($matched){
				$string = str_replace("Pending", "Cancelled", $string);
				
				$string = str_replace("pending", "Cancelled", $string);
				
				global $wpdb;
				$laybuy_response = $wpdb->prefix . "laybuy_response";
					
				$wpdb->update(
						$laybuy_response,
						array('report' => $string),
						array( 'paypal_pro_id' => $paypal_pro_id )
				);
				
			}
			
		}
	$_SESSION['txn_canceled'] = 'yes';
	}else $_SESSION['txn_canceled'] = 'no';
}

/**
 * function to update transaction status
 */
function laybuy_update_txn_status($status,$order_id,$id){
	
	global $wpdb;
	$laybuy_response = $wpdb->prefix . "laybuy_response";
		
	$wpdb->update(
			$laybuy_response,
			array('status' => $status),
			array( 'custom' => $order_id,'id' => $id )
	);
}


/**
 * calculate downpayment amount and payment amount
 */
function laybuy_calc_amount($downpayment,$amount,$months){
	
	
	$dep=$amount*$downpayment/100;
	
	if($months !=0) $rest=number_format(($amount-$dep)/$months,2,'.','');
	
	$dep=number_format($amount - $rest * $months,2,'.','');
		
	return array($dep,$rest);

}

/**
 * generates report for saving in corresponding transactions
 */
function laybuy_generate_report($downpayment,$amount,$months,$date,$dp_paypal_txn_id){
		
	$updated_amounts = laybuy_calc_amount($downpayment,$amount,$months);
	
	$downpaymnet_amount = $updated_amounts[0];
	$payment_amount = $updated_amounts[1];
		
	$report = '';
	$report = '<table>';
	$report .= '<thead><tr><th colspan="2">Instalment</th><th>Date</th><th>PayPal Transaction ID</th><th>Status</th></tr></thead>';
	$report .= '<tbody><tr><td> DP: </td><td> '.get_woocommerce_currency_symbol().$downpaymnet_amount.'</td>'.
			'<td>'.date("M d, Y",strtotime($date)).'</td>'.
			'<td>'.$dp_paypal_txn_id.'</td>'.
			'<td class="status">Completed</td></tr>';
			
	
	$tod=time();
	$isLeap = 0;
	$isLeap = Date('L',$tod);
	if($isLeap){
		
		$dim=array(31,31,29,31,30,31,30,31,31,30,31,30,31);}
	else
		
		$dim=array(31,31,28,31,30,31,30,31,31,30,31,30,31);
	
	$day=Date('d',$tod);
	$mth=Date('m',$tod);
	$yr=Date('Y',$tod);
	
	for ($e=1; $e<=$months; $e++) {
		if (++$mth>12) {
			$mth='01';
			$yr++;
		}
		$m=1+$mth-1;
		$d=min($day,$dim[$m]);
		$even = '';
	
		$date = '';
		$date = $d.'-'.$mth.'-'.$yr;
		
		
		
		$report .='<tr>'.
				'<td>Month'.$e.':</td>'.
				'<td>'.get_woocommerce_currency_symbol().$payment_amount.'</td>'.
				'<td>'.date("M d, Y",strtotime($date)).'</td>'.
				'<td></td>'.
				'<td>Pending</td>'.
				'</tr>';
	}
	
	$report .='</tbody></table>';
	return $report;
}

/**
 * writes log for every functionality
 * @param string $text
 * @param mixed $data
 */

function laybuy_write_log($text,$data){
	
	$date = date('Y-m-d h:i:s');
	error_log("date: $date\n"."message: $text\n"."data: $data\n"."\n******\n", 3, laybuy_lb_plugin_path().'/lb_log.log');
}


/**
 * removes unwanted query string
 */

function laybuy_remove_querystring_var($query_string, $key) {
	
	$query_string = preg_replace('/(.*)(?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $query_string . '&');
	$query_string = substr($query_string, 0, -1);
		
	return $query_string;
		
}

/**
 *  function to replace extra 'WHERE' with 'AND' in sql query 
 */

function laybuy_replace_skip($str, $find, $replace, $skip = 1) {
	$cpos = 0;
	for($i = 0, $len = strlen($find);$i < $skip;++$i) {
		if(($pos = strpos(substr($str, $cpos), $find)) !== false) {
			$cpos += $pos + $len;
		}
	}
	return substr($str, 0, $cpos) . str_replace($find, $replace, substr($str, $cpos));
}

/**
 * update status of orders in woocommrce order record
 */
function laybuy_change_order_status ($order_id,$order_status) {
	
	$order = new WC_Order($order_id);
	$order->update_status( $order_status );
	return $order;

}

/**
 *  restrictions on laybuy payment gateway 
 */

add_filter('woocommerce_available_payment_gateways','laybuy_filter_gateways',1);

function laybuy_filter_gateways($gateways){
	
	$laybuy_settings = get_option('woocommerce_laybuy_settings');
	$unset = "no";
	
	global $woocommerce;
	
	$args = array( 'taxonomy' => 'product_cat' );
	$woo_terms = get_terms('product_cat',array('hide_empty'=>0));
	
	/* check for allowed categories */
	

	foreach ($woocommerce->cart->cart_contents as $key => $values ) {
		/* Get the terms, i.e. category list using the ID of the product */
		
		$id[] = $values['product_id'];
		$terms = get_the_terms( $values['product_id'], 'product_cat' );
		
		/* Because a product can have multiple categories, we need to iterate through the list of the products category for a match */
		if($woo_terms)
		{
			if($terms)
			{
				foreach ($terms as $term) 
				{
					$term_id[] = $term->term_id;
				}
				if($laybuy_settings['categories'])
				{
					$difference = array_intersect($term_id, $laybuy_settings['categories']);
				}
				else
				{
					$unset="yes";
				}	
				if(empty($difference))
				{
					$unset="yes";					
				}
			}
			else
			{
				$unset="yes";
			}	
		}
	}
	
	/* unset lay-buys on the basis of user groups */
	
	if ( is_user_logged_in() ) 
	{
		$user = new WP_User(  get_current_user_id( ) );
		$roles = laybuy_get_all_roles();
		foreach ($roles as $role)
		{
			$all_roles[$role['name']] = $role['name'];
		}
		
		if ( !empty( $user->roles ) && is_array( $user->roles ) ) 
		{
			foreach ( $user->roles as $role )
			{
				$role = ucfirst($role);
				if(in_array($role, $all_roles))
				{
					$current_user = $role;
				}
			}	
		}
	}
	else
	{
		$current_user = 'Guest';
	}
	
	
	if(
		!empty($current_user) && 
		!empty($laybuy_settings['users']) &&
		!empty($id))
	   {		
			if(is_array($laybuy_settings['users']))
			if(!in_array($current_user, $laybuy_settings['users'])){
				
				$unset="yes";
				
			}
			
			/* unset lay-buys payment gateway based on excluded product id's  */
			
			$excluded_product_id = explode(',',$laybuy_settings['excluded_product_id']);
			
			
			for($i=0;$i<sizeof($id);$i++){
				
				if(in_array($id[$i],$excluded_product_id)){
					
					$unset="yes";
				}
					
			}
			
			/* unset lay-buys payment gateway based on minimum total amount  */
			
			$currency_position = get_option('woocommerce_currency_pos');
			
			$order_total = explode(get_woocommerce_currency_symbol(),$woocommerce->cart->get_cart_total());
			
			if($currency_position == 'left' || $currency_position == 'left_space')
			{
				$order_total = trim($order_total[1]);
			}
			else
			{
				$order_total = trim($order_total[0]);
			}				
			
			$order_total = preg_replace("/[^0-9\.]/", '', $order_total);
			
			$min_total = floatval($laybuy_settings['total']);
			
			if( floatval($order_total) < $min_total)
			{  
				$unset="yes";
			}
		}
		
		else if(empty($laybuy_settings['categories']) || empty($laybuy_settings['users'])){
			
			$unset="yes";
			
		}

		if($unset == "yes"){
			unset($gateways['laybuy']);
		}
		
	return $gateways;
}

/**
 * function to gett all the user roles
 */

function laybuy_get_all_roles() {
	global $wp_roles;

	$all_roles = $wp_roles->roles;
	$editable_roles = apply_filters('editable_roles', $all_roles);

	return $editable_roles;
}

/**
 * function to show admin notices
 */
function laybuy_show_admin_notice($message){
?>	
<div class="updated" style="margin: 10px 0 35px;">
	<p><?php echo  $message; ?></p>
</div>
<?php 	
}

/**
 * function to remove breadcrumbs from laybuy installment reports page
 */

function laybuy_remove_breadcrumbs(){
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
}
add_action('init','laybuy_remove_breadcrumbs');

/**
 * gets the absolute path to laybuy plugin directory
 * @return string
 */
function laybuy_lb_plugin_path() {

	return untrailingslashit( plugin_dir_path( __FILE__ ) );
}		
