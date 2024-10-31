<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo 'View';
$query_string = laybuy_remove_querystring_var($_SERVER['QUERY_STRING'],"filter1");
$query_string = explode('&',$query_string);

foreach ($query_string as $string){
			 
	$query_val[] = explode('=',$string);
			 	
}
for($i=0;$i<sizeof($query_val);$i++){
			
?>
<input type="hidden" value="<?php echo $query_val[$i][1];?>" name="<?php echo $query_val[$i][0];?>">
<?php 
} 

$row_count = isset($_GET['filter1']) ? absint( $_GET['filter1']) : 10;
?>
<select name="filter1" id="row_count" onchange="this.form.submit()">
	
	<?php for($j=10;$j<=50;$j+=5){?>
	<?php if($j!=$row_count){?><option value="<?php echo $j;?>"><?php echo $j;?></option>
	<?php } else {?><option selected value="<?php echo $row_count;?>"><?php echo $row_count;?></option> <?php }?>
	<?php } ?>
</select>
	per page | total <?php echo $total; if($total==1){echo ' record';} else echo ' records';?> found
