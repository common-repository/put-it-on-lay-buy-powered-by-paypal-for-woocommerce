<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

/* fetch data from laybuy_response table */

$laybuy_response = $wpdb->prefix . "laybuy_response";

$url = $_SERVER['REQUEST_URI'];

$laybuy_settings = get_option('woocommerce_laybuy_settings');
//print_r($laybuy_settings);

$member_id = $laybuy_settings['laybuy_memb_id'];
$fetch_report_url = $laybuy_settings['report_settings'];

if(isset($_POST['fetch_data']))
{

	$profile_ids = laybuy_get_values('paypal_pro_id'); /* get ',' seperated profile id's */
	laybuy_fetch_report($profile_ids,$member_id,$fetch_report_url); /* fetch updates */
	wp_redirect($_SERVER['HTTP_REFERER']);
	exit;
}

include 'lb_pagination.php';

foreach ($transaction as $row){
	
	if (isset($_GET['txn_id'])) {
		if ($_GET['txn_id'] == $row->id){			
		$txn_url = $_SERVER['REQUEST_URI'];
			include 'orders.php';
		}
	}
}

/* if rows are updated after fetching latest reports then show admin notices */

$lb_settings = get_option( "laybuy_revision_settings");


if(isset($_SESSION['fetched_rows_count'])){
	if($_SESSION['fetched_rows_count']){
	$message = "Fetched ".$_SESSION['fetched_rows_count']." report rows from 'https://lay-buys.com/report/'. ";
	laybuy_show_admin_notice($message);
}else if ($_SESSION['fetched_rows_count'] == '0'){
	$message = "No new updates.";
	laybuy_show_admin_notice($message);
}
}
unset($_SESSION['fetched_rows_count']);
echo '<h3 style="float: left;">Lay-Buys Instalment Reports</h3>';
?>

<form name="fetch" id ="fetch" action="<?php echo get_admin_url();?>admin.php?page=lay-buy-instalment-reports&noheader=true" method="post" style="float: right;margin: 20px 0 35px;">	
	<input type="submit" class ="button" style="float: right;margin: 0 0 35px;" name="fetch_data" id="fetch_data" value="Fetch Updates" >
</form>
<div style="clear:both;"></div>

<form method="get" id="sort" style="float:left;margin: -50px 0 35px;">
<?php include 'lb_filters.php';?>
</form>

<a href="<?php echo get_admin_url();?>admin.php?page=lay-buy-instalment-reports" class ="button" style="float:right;margin: -50px 0 35px;">Reset Filter</a>

<div style="clear:both;"></div>

<table cellspacing="0" class="wp-list-table widefat fixed posts">
<thead>
<tr>
<th>Created At</th>
<th>Order</th>
<th>Amount</th>
<th>Down Payment %</th>
<th>Months</th>
<th>Down Payment Amount</th>
<th>Payment Amount</th>
<th>First Payment Due</th>
<th>Last Payment Due</th>
<th>Status</th>
</tr>
</thead>

<thead>
<tr>
<th><input type="text" id="date" class="txn_date filter" name="filter2" value="<?php if($date!='1970-01-01') echo date("M d, Y", strtotime($date)); ?>" style="margin: 0;width: 85px;"/></th>
<th><input type="text" id="custom" class ="filter" name="filter3" value="<?php echo $order;?>" style="margin: 0;width: 85px;"/></th>
<th><input type="text" id="amount" class ="filter" name="filter4" value="<?php echo $amount;?>" style="margin: 0;width: 85px;"/></th>
<th><input type="text" id="downpayment" class ="filter" name="filter5" value="<?php echo $dp;?>" style="margin: 0;width: 85px;"/></th>
<th><input type="text" id="months" class ="filter" name="filter6" value="<?php echo $months;?>" style="margin: 0;width: 85px;"/></th>
<th><input type="text" id="dp_amount" class ="filter" name="filter7" value="<?php echo $dp_amount;?>" style="margin: 0;width: 85px;"/></th>
<th><input type="text" id="payment_amount" class ="filter" name="filter8" value="<?php echo $p_amount;?>" style="margin: 0;width: 85px;"/></th>
<th><input type="text" id="first_payment_due" class="txn_date filter" name="filter9" value="<?php if($f_due!='1970-01-01') echo date("M d, Y", strtotime($f_due));?>" style="margin: 0;width: 85px;"/></th>
<th><input type="text" id="last_payment_due" class="txn_date filter" name="filter10" value="<?php if($l_due!='1970-01-01') echo date("M d, Y", strtotime($l_due));?>" style="margin: 0;width: 85px;""/></th>

<th>

<select name="filter11" id="status" class ="filter" style="margin: 0;width: 158px;"/>
	
	<?php if(!empty($lb_status)){?>
		<option value="<?php echo $lb_status;?>" selected="selected"><?php echo laybuy_status($lb_status);?></option>
	<?php } else { ?>
		<option value="">Select</option>
	<?php } ?>
	<?php if($lb_status!=3){?><option value="3">Pending</option><?php }?>
	<?php if($lb_status!=1){?><option value="1">Completed</option><?php }?>
	<?php if($lb_status!=-1){?><option value="-1">Canceled</option><?php }?>
	<?php if($lb_status!=4){?><option value="4">Revised</option><?php }?>
    <?php if($lb_status!=5){?><option value="5">Revision Requested</option> <?php }?>

</select>

</th>
</tr>
</thead>


<tbody id="the-list">
<?php $location = $_SERVER['REQUEST_URI'];
if(isset($transaction)){
	foreach ($transaction as $row){ 
	//$location = get_admin_url().'admin.php?page=lay-buy-instalment-reports&txn_id='.$row->id;	?>
	<tr>
	<td><?php echo date("M d, Y", strtotime($row->date));?></td>
	<td><a href="<?php echo $location.'&txn_id='.$row->id;?>">Order #<?php echo $row->custom;?></a></td>
	<td><?php echo get_woocommerce_currency_symbol().$row->amount;?></td>
	<td><?php echo $row->downpayment;?>%</td>
	<td><?php echo $row->months;?></td>
	<td><?php echo get_woocommerce_currency_symbol().$row->dp_amount;?></td>
	<td><?php echo get_woocommerce_currency_symbol().$row->payment_amount;?></td>
	<td><?php echo date("M d, Y", strtotime($row->first_payment_due));?></td>
	<td><?php echo date("M d, Y", strtotime($row->last_payment_due));?></td>
	<td><?php echo laybuy_status($row->status);?></td>
	</tr>
	
	<?php  
		
	}
}

?>
</tbody>
</table>

<?php 

if ( $page_links ) {
	echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
}
