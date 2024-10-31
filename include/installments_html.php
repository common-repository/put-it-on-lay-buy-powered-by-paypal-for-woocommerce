<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table>
<colgroup>
    <col width="1">
<col width="1">
<col width="150">
<col width="252">
<col width="1">
</colgroup>
<thead>
<tr><th colspan="2">Installment</th><th>Date</th><th>PayPal Transaction ID</th><th>Status</th></tr></thead>
				
				<tbody>
					<tr>
					<td>DP:</td>
					<td><?php echo get_woocommerce_currency_symbol().$row->dp_amount;?></td>
					<td><?php echo date("d M, Y", strtotime($row->date));?></td>
					<td><?php echo $row->dp_paypal_txn_id;?></td>
					<td><?php if($row->dp_paypal_txn_id){echo 'Completed';}else {echo 'Pending';}?></td>
					
					</tr>
					<?php 
											
						$amount = floatval($row->amount);
						$months = floatval($row->months);
						$percent = floatval($row->downpayment);
						$downpayment = (($percent/100)*$amount);
						$unpaid = ($amount-$downpayment);
						$installments = round(($unpaid/$months),2);
						
						?>
					
					<?php for ($k=1;$k<=$months;$k++){ ?>
						<tr>
						<td>Month<?php echo ' '.$k;?>:</td>
						<td><?php echo get_woocommerce_currency_symbol().$installments;?></td>
						<td><?php echo date("d M, Y",strtotime($start_date." + $k months"));?></td>
						<td></td>
						<td><?php echo 'Pending'?></td>
						</tr>
					<?php }?>
				</tbody>		
</table>