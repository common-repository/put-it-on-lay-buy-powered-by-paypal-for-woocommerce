<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$my_orders = $wpdb->get_results( "SELECT * FROM $laybuy_response WHERE id='$my_order_id'");foreach ($my_orders as $row){if(isset($_GET['order_id'])){	$param = '?order_id='.$row->custom;}else $param = '';
?>
<div class="lb_center">
<h2><?php echo 'View Transaction Details';?></h2><p><a class="button" href="<?php echo get_site_url(); ?>/<?php echo $page_name.$param;?>">&larr; Return To Report</a></p>
		<h4><?php echo 'Reference Information';?></h4>

		<table class="customer_orders shop_table my_account_orders">
		
			<tbody id="the-list">
				
				<tr>
				<td>PayPal Profile ID</td>
				<td><?php echo $row->paypal_pro_id;?></td>
				</tr>
				<tr>
				<td>Lay-Buy Reference ID</td>
				<td><?php echo $row->laybuy_ref_no;?></td>
				</tr>
				<tr>
				<td>Order ID</td>
				<td><?php echo $row->custom;?></td>
				</tr>
			
			</tbody>
			
		</table>
		
	<h4><?php echo 'Payment Plan'; ?></h4>
	<?php
	$start_date = date("Y-m-d", strtotime($row->date));
		
	?>
	<table class="customer_orders shop_table my_account_orders">

		<tbody>
			
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
			<td>First Payment Due</td>
			<td><?php echo date("M d,Y", strtotime($row->first_payment_due));?></td>
			</tr>
			<tr>
			<td>Last Payment Due</td>
			<td><?php echo date("M d,Y", strtotime($row->last_payment_due));?></td>
			</tr>
			<tr>
			<td>Payment Record</td>
			<td id="payment_record">
				
			<?php echo $row->report;?>
				
			</td>
			</tr>
		
		</tbody>
	</table>
	<h4><?php echo 'Customer Information';?></h4>
	<table class="customer_orders shop_table my_account_orders">
		
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
<?php 
}/** * woocommerce_after_main_content hook * * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content) */do_action('woocommerce_after_main_content');/** * woocommerce_sidebar hook * * @hooked woocommerce_get_sidebar - 10*/do_action('woocommerce_sidebar');get_footer();
exit;
