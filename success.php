<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('woocommerce_thankyou_laybuy', 'laybuy_success_function');

function laybuy_success_function()
{
	if(isset($_POST['RESULT']) && isset($_SESSION['success_return_url']) && isset($_SESSION['cancel_return_url']))
	{
		if(sanitize_text_field($_POST['RESULT']) == 'SUCCESS')
		{
			global $wpdb;
			$laybuy_settings = get_option('woocommerce_laybuy_settings');
			$order_status = $laybuy_settings['order_status'];
			$date = date('Y-m-d');
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
			$downpayment = isset($_POST['DOWNPAYMENT'])?sanitize_text_field($_POST['DOWNPAYMENT']):'';
			$months = isset($_POST['MONTHS'])?sanitize_text_field($_POST['MONTHS']):'';
			
			$dp_amount = isset($_POST['DOWNPAYMENT_AMOUNT'])?sanitize_text_field($_POST['DOWNPAYMENT_AMOUNT']):'';
			$payment_amount = isset($_POST['PAYMENT_AMOUNTS'])?sanitize_text_field($_POST['PAYMENT_AMOUNTS']):'';
			$f_due = isset($_POST['FIRST_PAYMENT_DUE'])?sanitize_text_field($_POST['FIRST_PAYMENT_DUE']):'';
			$l_due = isset($_POST['LAST_PAYMENT_DUE'])?sanitize_text_field($_POST['LAST_PAYMENT_DUE']):'';
			$first_payment_due = date('Y-m-d', strtotime(str_replace('/','-',$f_due)));
			$last_payment_due = date('Y-m-d', strtotime(str_replace('/','-',$l_due))); 
			
			$downpayment = explode('%', $downpayment);
			$downpayment = floatval($downpayment[0]);
			
			$report = laybuy_generate_report($downpayment,$amount,$months,$date,$dp_paypal_txn_id);
			
			$status = '3';
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
			
			laybuy_change_order_status ($order_id,$order_status);
			$wpdb->insert( $table_name, $data);
			
			$success = $_SESSION['success_return_url'];
			$cancel = $_SESSION['cancel_return_url'];
			
			/* unset previously stored values */
			unset($_SESSION['success_return_url']);
			unset($_SESSION['cancel_return_url']);
			
			if($result == 'SUCCESS'){
				$order = new WC_Order( $order_id );
				$order->reduce_order_stock();
				wp_redirect($success);
			}
			else 
			{
				wp_redirect($cancel);
			}
		}
	}
}
