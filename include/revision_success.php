<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('woocommerce_before_my_account', 'laybuy_revision_function');

function laybuy_revision_function()
{
	if(isset($_GET['revision']))
	{
		if($_GET['revision'] == 'true')
		{		
			if($_POST['RESULT'] == 'SUCCESS')
			{
				$laybuy_settings = get_option('woocommerce_laybuy_settings');
				$order_status = $laybuy_settings['order_status'];
				
				global $wpdb;
				
				
				$result = isset($_POST['RESULT'])?sanitize_text_field($_POST['RESULT']):'';
				$errormessage = isset($_POST['ErrorMessage'])?sanitize_text_field($_POST['ErrorMessage']):'';
				$order_id = isset($_POST['CUSTOM'])?sanitize_text_field($_POST['CUSTOM']):'';
				$firstname = isset($_POST['FIRSTNAME'])?sanitize_text_field($_POST['FIRSTNAME']):'';
				$lastname = isset($_POST['LASTNAME'])?sanitize_text_field($_POST['LASTNAME']):'';
				$address = isset($_POST['ADDRESS'])?sanitize_text_field($_POST['ADDRESS']):'';
				$suburb = isset($_POST['SUBURB'])?sanitize_text_field($_POST['SUBURB']):'';
				$state = isset($_POST['STATE'])?sanitize_text_field($_POST['STATE']):'';
				$country = isset($_POST['COUNTRY'])?sanitize_text_field($_POST['COUNTRY']):'';
				$postcode = isset($_POST['POSTCODE'])?sanitize_text_field($_POST['POSTCODE']):'';
				$email = isset($_POST['EMAIL'])?sanitize_text_field($_POST['EMAIL']):'';
				$amount = isset($_POST['AMOUNT'])?sanitize_text_field($_POST['AMOUNT']):'';
				$currency = isset($_POST['CURRENCY'])?sanitize_text_field($_POST['CURRENCY']):'';
				$paypal_pro_id = isset($_POST['PAYPAL_PROFILE_ID'])?sanitize_text_field($_POST['PAYPAL_PROFILE_ID']):'';
				$dp_paypal_txn_id =isset($_POST['DP_PAYPAL_TXN_ID'])?sanitize_text_field($_POST['DP_PAYPAL_TXN_ID']):'';
				$laybuy_ref_no = isset($_POST['LAYBUY_REF_NO'])?sanitize_text_field($_POST['LAYBUY_REF_NO']):'';
				$merchants_ref_no = isset($_POST['MERCHANTS_REF_NO'])?sanitize_text_field($_POST['MERCHANTS_REF_NO']):'';
				
				$date = date('Y-m-d');
				
				$revision_reports = $wpdb->prefix . "laybuy_revision_reports";
				$row = $wpdb->get_results( "SELECT * FROM $revision_reports WHERE merchant_ref_no = '$merchants_ref_no' AND custom = '$order_id'");
				
				/* if payment type is buynow */
				if( empty($_POST['DOWNPAYMENT_AMOUNT']) && ($row[0]->pp == '0') && ($row[0]->p_plan == '0') ){
						
					$downpayment = '100';
					$months = '0';
					$dp_amount = '0';
					$payment_amount = '0';
					$first_payment_due = date('Y-m-d');
					$last_payment_due = date('Y-m-d');
					$cur_date = date('M d, Y');
					$status = '1';
					$report = '';
					$report = '<table>';
					$report .= '<thead><tr><th colspan="2">Instalment</th><th>Date</th><th>PayPal Transaction ID</th><th>Status</th></tr></thead>';
					$report .= '<tbody><tr><td> DP: </td><td> '.get_woocommerce_currency_symbol().$amount.'</td>'.
								'<td>'.$cur_date.'</td>'.
								'<td>'.$dp_paypal_txn_id.'</td>'.
								'<td class="status">Completed</td></tr></tbody></table>';
					
					laybuy_change_order_status ($order_id,'processing');
				}
				/* if payment type is laybuy */
				else {
				
					$downpayment = isset($_POST['DOWNPAYMENT'])?sanitize_text_field($_POST['DOWNPAYMENT']):'';
				
					$downpayment = explode('%', $downpayment);
					$downpayment = floatval($downpayment[0]);
				
					$months = isset($_POST['MONTHS'])?sanitize_text_field($_POST['MONTHS']):'';
					$dp_amount = isset($_POST['DOWNPAYMENT_AMOUNT'])?sanitize_text_field($_POST['DOWNPAYMENT_AMOUNT']):'';
					$payment_amount = isset($_POST['PAYMENT_AMOUNTS'])?sanitize_text_field($_POST['PAYMENT_AMOUNTS']):'';
					$f_due = isset($_POST['FIRST_PAYMENT_DUE'])?sanitize_text_field($_POST['FIRST_PAYMENT_DUE']):'';
					$l_due = isset($_POST['LAST_PAYMENT_DUE'])?sanitize_text_field($_POST['LAST_PAYMENT_DUE']):'';
				
					$first_payment_due = date('Y-m-d', strtotime(str_replace('/','-',$f_due)));
					$last_payment_due = date('Y-m-d', strtotime(str_replace('/','-',$l_due)));
				
					$report = laybuy_generate_report($downpayment,$amount,$months,date('Y-m-d'),$dp_paypal_txn_id);
				
					$status = '3';
				
				}
				
				/*
				 * insert Lay-Buy response into response table
				*/
				$table_name = $wpdb->prefix . "laybuy_response";
				
				$data = array(
						'date' => $date,
						'result' => $result,
						'custom' => $order_id,
						'firstname' => $firstname,
						'lastname' => $lastname,
						'address' => $address,
						'suburb' => $suburb,
						'state' => $state,
						'country' => $country,
						'postcode' => $postcode,
						'email' => $email,
						'amount' => $amount,
						'currency' => $currency,
						'paypal_pro_id' => $paypal_pro_id,
						'dp_paypal_txn_id' => $dp_paypal_txn_id,
						'report' => $report,
						'laybuy_ref_no' => $laybuy_ref_no,
						'downpayment' => $downpayment,
						'months' => $months,
						'dp_amount' => $dp_amount,
						'payment_amount' => $payment_amount,
						'first_payment_due' => $first_payment_due,
						'last_payment_due' => $last_payment_due,
						'status' => $status,
						'report_size' => 2
				);
				
				$wpdb->insert( $table_name, $data);
				
				/*get values of the previous transaction*/
				
				$requested = $wpdb->get_results( "SELECT * FROM $table_name WHERE id=".$merchants_ref_no );
				
				foreach ($requested as $value){
					
					$paypal_pro_id = $value->paypal_pro_id;
					$order_id = $value->custom;
					
					/*cancel the previous transaction*/
					laybuy_cancel_transaction($paypal_pro_id,$order_id,$merchants_ref_no,'4');
					
				}
				
				$rev_success_url = get_permalink( get_option('woocommerce_myaccount_page_id') );
				wp_redirect($rev_success_url);
			}
		}
		if($_GET['revision'] == 'false')
		{
			$rev_cancel_url = get_permalink( get_option('woocommerce_myaccount_page_id') );
			wp_redirect($rev_cancel_url);
		}
	}
}