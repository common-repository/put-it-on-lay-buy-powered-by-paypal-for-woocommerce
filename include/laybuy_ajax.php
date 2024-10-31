<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * set the selected options in laybuy form
 */
if(isset($_POST['percent']) || isset($_POST['months'])){

	$percent = sanitize_text_field($_POST['percent']);
	$months = sanitize_text_field($_POST['months']);

	$_SESSION['percent'] = $percent;
	$_SESSION['months'] = $months;

	exit;

}

/**
 * reset payment plan
 */
if(isset($_POST['reset_action'])){

	if(isset($_POST['installments']) && isset($_POST['dp_percent'])){
		$settings = get_option( "laybuy_revision_settings" );
		$settings['payment_type'] = '1';
		$settings['installments'] = sanitize_text_field($_POST['installments']);
		$settings['dp_percent'] = sanitize_text_field($_POST['dp_percent']);
		update_option( "laybuy_revision_settings", $settings, '', 'yes' );
		echo json_encode(array(
				'payment_type'=>$settings['payment_type'],
				'installments'=>$settings['installments'],
				'dp_percent'=>$settings['dp_percent']
		));
	}
	exit;

}

if(isset($_POST['order_action'])){
	
	$page_settings = get_option('laybuy_pages');
	
	$page_name = get_post($page_settings['pages']['Laybuy Installment Report'])->post_name;
	
	if(isset($_POST['order_id'])){
		
		$id = sanitize_text_field($_POST['order_id']);
		
		$laybuy_response = $wpdb->prefix . "laybuy_response";
		$transaction = $wpdb->get_results( "SELECT * FROM $laybuy_response");
		
		foreach ($transaction as $row){
			$orders[] = $row->custom;
		}
		
		$result=array_intersect($id,$orders);
		
		foreach ($result as $value){
			
			if(isset($page_name)){
				
				$url[] = get_site_url().'/'.$page_name.'?order_id='.$value;
				$id[] = $value;	
			}
			
		}
		
		
		echo json_encode(array('lb_anchor' => $url,'lb_id' => $id));	
	}
	exit;
}