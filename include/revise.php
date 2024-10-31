<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if(isset($_GET['pagenum']))
{
	$page_num = $_GET['pagenum'];
}
else
{
	$page_num = 1;
}

$laybuy_settings = get_option('woocommerce_laybuy_settings');
$member_id = $laybuy_settings['laybuy_memb_id'];
$fetch_report_url = $laybuy_settings['report_settings'];
$profile_ids = $row->paypal_pro_id;
if($row->status == 3)
{
	laybuy_fetch_report($profile_ids,$member_id,$fetch_report_url);
}

$max_months = $laybuy_settings['max_months'];
$min = str_replace('%', '', $laybuy_settings['min']);
$max = str_replace('%', '', $laybuy_settings['max']);
$order_id = $row->custom;
$id = $row->id;
$start_date= date('Y-m-d');

$lb_opt = isset($_POST['lb_opt'])?sanitize_text_field($_POST['lb_opt']):null;
$bn_opt = isset($_POST['bn_opt'])?sanitize_text_field($_POST['bn_opt']):null;

$settings = get_option( "laybuy_revision_settings" );

if(isset($_POST['save_revised_plan'])){
	
		
	if($lb_opt){
		$settings['payment_type'] = $lb_opt;
	
		
	}else{
		$settings['payment_type'] = $bn_opt;
		
	}

	if(isset($_POST['installments'])){

		$settings['installments'] = sanitize_text_field($_POST['installments']);
	
	}else $settings['installments'] = $max_months;
	
	if(isset($_POST['dp_percent'])){

		$settings['dp_percent'] = sanitize_text_field($_POST['dp_percent']);
		
	}else $settings['dp_percent'] = $min;
	
	$settings['save_revised_plan'] = 'yes';
	update_option( "laybuy_revision_settings_$id", $settings, '', 'yes' );
	
	include 'save_revision.php';
	
} 

$settings = get_option( "laybuy_revision_settings_$id" );


?>
<div style="margin: 20px 0 35px;">
<h3>Edit Transaction Details</h3>

	<form name="revise_plan" id="revise_plan" method="post">
	<input type="submit" style="float: right;margin-right: 5px;" value="Save And Send Email" name="save_revised_plan" id="save_revised_plan" class="button">
	<a  href="<?php echo get_admin_url().'admin.php?page=lay-buy-instalment-reports&pagenum='.$page_num.'&txn_id='.$row->id;;?>"><input type="button" style="float: right;margin-right: 5px;" value="Back" name="lb_back" id="lb_back" class="button"></a>
	<input type="button" style="float: right;margin-right: 5px;" value="Reset" name="lb_reset" id="lb_reset" class="button" onclick="reset_plan(<?php echo $row->months;?>,<?php echo $row->downpayment;?>)">
	
	<h4><?php echo 'Reference Information';?></h4>
	
		<table cellspacing="0" class="wp-list-table widefat fixed posts">
			
			<tbody id="the-list">
				
				<tr>
				<td>PayPal Profile ID</td>
				<td><input type="text" value="<?php echo $row->paypal_pro_id;?>" style="width: 275px; padding: 2px;" readonly></td>
				</tr>
				<tr>
				<td>Lay-Buys Reference ID</td>
				<td><input type="text" value="<?php echo $row->laybuy_ref_no;?>" style="width: 275px; padding: 2px;" readonly></td>
				</tr>
				<tr>
				<td>Order ID</td>
				<td><input type="text" value="<?php echo $row->custom;?>" readonly style="width: 275px; padding: 2px;"></td>
				</tr>
			
			</tbody>
		</table>
	
	<h4><?php echo 'Payment Plan'; ?></h4>

		<table cellspacing="0" class="wp-list-table widefat fixed posts select_plan">
			
			<tbody id="the-list">
				
				<tr>
				<td>Total Amount</td>
				<td id="amount"><input type="text" name="lb_amount" id="lb_amount" value="<?php echo get_woocommerce_currency_symbol().' '.laybuy_get_remaining_amount($row->custom);?>" style="width: 275px; padding: 2px;" readonly></td>
				</tr>
				
				<tr>
				<td>Payment Type</td>
				<td id="type">
				<?php if (empty($settings)) {/* setting for the first time */?>	
					<input type="radio" id="lb_opt" name="lb_opt" value="1" checked> Lay-Buys<br>
					<input type="radio" id="bn_opt" name="bn_opt" value="0" > Buy-Now
				<?php }else {?>
					<input type="radio" id="lb_opt" name="lb_opt" value="1" <?php if($settings['payment_type']=='1'){echo 'checked';}?>> Lay-Buy<br>
					<input type="radio" id="bn_opt" name="bn_opt" value="0" <?php if($settings['payment_type']=='0'){echo 'checked';}?>> Buy-Now
				<?php }?>
				</td>
				</tr>
				
				<tr class="bn_hide">
				<td>Initial Payment</td>
				<td>
					<select onchange="generate_plan()" id="dp_percent" name="dp_percent" style="width: 275px; padding: 2px;">
					<?php if (empty($settings)) {/* setting for the first time */?>	
						<?php for ($i=$min;$i<=$max;$i+=10){?>
							<?php if($row->downpayment!=$i){?><option value="<?php echo $i;?>"><?php echo $i;?>%</option>
							<?php } else { ?> <option value="<?php echo $i;?>" selected><?php echo $i;?>%</option> <?php }?>
						<?php }?>
					<?php } else { /* when settings are already saved */?>
						<?php for ($i=$min;$i<=$max;$i+=10){?>
							<?php if($row->downpayment!=$i){?><option value="<?php echo $i;?>"><?php echo $i;?>%</option>
							<?php } else { ?> <option value="<?php echo $i;?>" selected><?php echo $i;?>%</option> <?php }?>
						<?php }?>
					<?php } ?>
					</select>
				</td>
				</tr>
				<tr class="bn_hide">
				<td>Months To Pay</td>
				<td>
					<select onchange="generate_plan()" id="installments" name="installments" style="width: 275px; padding: 2px;">
					<?php if (empty($settings)) {/* setting for the first time */?>		
						<?php for ($j=1;$j<=$max_months;$j++){?>
						
							<?php if($row->months!=$j){?><option value="<?php echo $j;?>"><?php echo $j.' '; if($j == 1){ echo 'month'; } else { echo 'months';}?></option>
							<?php } else { ?> <option value="<?php echo $j;?>" selected><?php echo $j.' '; if($j == 1){ echo 'month'; } else { echo 'months';}?></option> <?php }?>
						<?php } ?>
						<?php } else { ?>
							<?php for ($j=1;$j<=$max_months;$j++){?>
							
								<?php if($row->months!=$j){?><option value="<?php echo $j;?>"><?php echo $j.' '; if($j == 1){ echo 'month'; } else { echo 'months';}?></option>
								<?php } else { ?> <option value="<?php echo $j;?>" selected><?php echo $j.' '; if($j == 1){ echo 'month'; } else { echo 'months';}?></option> <?php }?>
							<?php } ?> 
						<?php }?>
					
					</select>
					</td>
					</tr>
					<tr class="bn_hide">
					<td>Preview</td>
					<td>
						<?php
						if (empty($settings)) {/* setting for the first time */

							  $revised_amount = laybuy_revised_amount($row->downpayment,$row->custom,$row->months);
												 
						 }else {/* if already set */

							 $revised_amount = laybuy_revised_amount($row->downpayment,$row->custom,$row->months);
													 					  	
						 }
						 $new_dp_amount = $revised_amount[0][0];
						 $new_installment = $revised_amount[0][1];
						?>
					<table style=" width: 295px; text-align: center;">
						<thead><tr><th style="text-align: center;">Payment</th><th style="text-align: center;">Due Date</th><th style="text-align: center;">Amount</th></tr></thead>
						<tbody>
							<tr>
								<td>Down Payment</td>
								<td>Today</td>
								<td><?php echo get_woocommerce_currency_symbol().$new_dp_amount;?>
								<input type="hidden" name="revised_dp" id="revised_dp" value="<?php echo $new_dp_amount;?>">
								</td>
							</tr>
							<?php 
							if(isset($settings['installments'])){
								$installments = $settings['installments'];
							}else {
								$installments = $row->months;
							}
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
								
								for ($e=1; $e<=$row->months; $e++) {
									if (++$mth>12) {
										$mth='01';
										$yr++;
									}
									$m=1+$mth-1;
									$d=min($day,$dim[$m]);
									$even = '';
								
									$date = '';
									$date = $d.'-'.$mth.'-'.$yr; ?>
							<tr>
								<td><?php echo $e;?></td>
								<td><?php echo date("M d, Y",strtotime($date));?></td>
								<td><?php echo get_woocommerce_currency_symbol().$new_installment;?><input type="hidden" id="revised_payment_amount" name="revised_payment_amount" value="<?php echo $new_installment;?>"></td>
							</tr>
							<?php } ?>					
						</tbody>
					</table>
				</td>
				</tr>
				<tr>
				<td>Email</td>
				<td><input type="text" name="buyer_email" id="buyer_email" value="<?php echo $row->email;?>" style="width: 275px; padding: 2px;" readonly></td>
				</tr>		
			</tbody>
		</table>
	</form>
</div>	