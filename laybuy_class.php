<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Lay-Buys Standard Payment Gateway
 *
 * Provides a Lay-Buys Standard Payment Gateway.
 *
 * @class 		WC_Laybuy
 * @package		plugins/lay_buy_gateway
 * @author 		CEDCOSS technologies private ltd
 */
class WC_Gateway_Laybuy extends WC_Payment_Gateway 
{
	/**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
	public function __construct() 
	{
		global $woocommerce;

        $this->id           = 'laybuy';
        $this->icon         = apply_filters( 'woocommerce_laybuy_icon', plugin_dir_url(__FILE__) . 'images/laybuy.png' );
        $this->has_fields   = false;
        $this->method_title = 'Lay-Buys';
        global $post;
        
        if ( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';
        
        // If the plugin version number is set, return it
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
        	$version=$plugin_folder[$plugin_file]['Version'];
        }

        if ( $version >= 2.2 )
        {
        	$order_status = wc_get_order_statuses();
        }
        else
        {
        	$terms = get_terms ( 'shop_order_status', array (
        			'hide_empty' => 0,
        			'orderby' => 'id'
        	) );
        	
        	for($i = 0; $i < sizeof ( $terms ); $i ++) {
        		$order_status [$terms [$i]->slug] = $terms [$i]->slug;
        		if ($terms [$i]->slug == 'pending') {
        			unset ( $order_status [$terms [$i]->slug] );
        		}
        	}
        }
        /* get woocommerce users */
        
        $roles = laybuy_get_all_roles();
        
        foreach ($roles as $role){
        	$all_roles[$role['name']] = $role['name'];
        }
        
        $all_roles['Guest'] = 'Guest';
            
        /* get woocommerce categories */
        
        $all_cat = get_terms('product_cat',array('hide_empty'=>0));

        if($all_cat){
	        foreach ($all_cat as $cat){
	        
	        	$cat_name[$cat->term_id] = $cat->name;
	        
	        }
        }
        else 
        {	
        	$cat_name = 'No categories';
        }
        // Load the settings.
        
        $this->init_form_fields($order_status,$all_roles,$cat_name);
        
        // Define user set variables
        
        $this->title 			  		= 	$this->get_option( 'title' );
        $this->min 				  		= 	$this->get_option( 'min' );
        $this->max                		= 	$this->get_option( 'max' );
        $this->laybuy_memb_id     		= 	$this->get_option( 'laybuy_memb_id' );
        $this->laybuy_gateway_url 		= 	$this->get_option( 'laybuy_gateway_url' );
        $this->description 		  		= 	$this->get_option( 'description' );
        $this->cron_frequency 		  		= 	$this->get_option( 'cron_frequency' );
        $this->cron_time 		  		= 	$this->get_option( 'cron_time' );
        $this->form_submission_method 	= 	$this->get_option( 'form_submission_method' ) == 'yes' ? true : false;
        $this->max_months         		= 	$this->get_option( 'max_months' );
        $this->laybuy_checkout_text_enabled     = 	$this->get_option( 'laybuy_checkout_text_enabled' );                $this->image_for_laybuy_gateway = $this->get_option('image_for_laybuy_gateway');
        // Actions
      
       add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
       if ( !$this->is_valid_for_use() ) $this->enabled = false;
               
    }
	
	
	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 */
	public function admin_options() 
	{
	?>
	<h3><?php _e( 'Lay-Buys standard', 'woocommerce' ); ?></h3>
	<p><?php _e( 'Lay-Buy is an affordable payment plan option that allows you to pay-off a product or service via one down payment, with the balance paid over 1, 2 or 3 monthly instalments. Your purchase is delivered to you after the final instalment payment is completed.A 0.9% admin fee is payable to Lay-buy', 'woocommerce' ); ?></p>
	<?php if ( $this->is_valid_for_use() ) : ?>
	<table class="form-table">
		<?php
		// Generate the HTML For the settings form.
		$this->generate_settings_html();    			    			
		if(isset($this->image_for_laybuy_gateway) && ($this->image_for_laybuy_gateway != '')): ?>				
		<tr valign="top">
			<th class="titledesc" scope="row"><label for="">Selected image for
					Laybuy Gateway(From above setting) <br /> <small>To change image
						click above textbox for image</small>
			</label></th>
			<td class="forminp">
				<fieldset>
					<span id="lay_img_span"> <img
						src="<?php echo $this->image_for_laybuy_gateway; ?>" height="200"
						width="200" />
					</span> &nbsp;&nbsp;&nbsp;&nbsp; 
					<span> 
						<input type="checkbox" name="remove_laybuy_image" selected="false" onclick="javascript: document.getElementById('woocommerce_laybuy_image_for_laybuy_gateway').value = ''; document.getElementById('lay_img_span').innerHTML = ''; " />
						Check this checkbox to remove Laybuy Gateway image.
					</span>
				</fieldset>
			</td>
		</tr>			
		<?php endif; ?>				
	</table>
	<!--/.form-table-->
	
	<?php else : ?>
	<div class="inline error">
		<p>
			<strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'laybuy does not support your store currency.', 'woocommerce' ); ?>
		</p>
	</div>
	<?php
			endif;
	}
		
	/**
	 * Check if this gateway is enabled and available in the user's country
	 *
	 * @access public
	 * @return bool
	 */
	function is_valid_for_use() 
	{
		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_laybuy_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) ) ) return false;
	
		return true;
	}
		
	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields($order_status,$users,$categories) 
	{
		$this->form_fields = array(
				'enabled' => array(
						'title' => __( 'Enable/Disable', 'woocommerce' ),
						'type' => 'checkbox',
						'label' => __( 'Enable laybuy standard', 'woocommerce' ),
						'default' => 'yes'
				),
				'title' => array(
						'title' => __( 'Title', 'woocommerce' ),
						'type' => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
						'default' => 'PUT IT ON LAY-BUY powered by PayPal',
						
				),
				'description' => array(
						'title' => __( 'Description', 'woocommerce' ),
						'type' => 'textarea',
						'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
						'default' => __( 'Please select the instalment plan:', 'woocommerce' )
				),
				
				'order_status' => array(
						'type' => 'hidden',
						'default' => __( 'wc-on-hold', 'woocommerce' )
				),
				'laybuy_gateway_url' => array(
						'title' => __( 'Gateway URL', 'woocommerce' ),
						'type' => 'text',
						'description' => __( 'This is the url to post request to Lay-Buys.', 'woocommerce' ),
						'default' => __( 'http://lay-buys.com/gateway/', 'woocommerce' ),
					
				),
				
				'laybuy_memb_id' => array(
						'title' => __( 'Lay-Buys Membership Number', 'woocommerce' ),
						'type' => 'password',
						'description' => __( 'This is the Lay-Buys member ID.', 'woocommerce' ),
				),
				
				'min' => array(
						'title' => __( 'Minimum', 'woocommerce' ),
						'type' => 'select',
						'description' => 'Minimum deposit amount(default is 20%)',
						'options' => array(
								'10%' => '10%',
								'20%' => '20%',
								'30%' => '30%',
								'40%' => '40%',
								'50%' => '50%',
						),
						'default' => __( '20%', 'woocommerce' ),
				),
				
				'max' => array(
						'title' => __( 'Maximum', 'woocommerce' ),
						'type' => 'select',
						'description' => 'Maximum deposit amount(defaults to 50%)',
						'options' => array(
								'10%' => '10%',
								'20%' => '20%',
								'30%' => '30%',
								'40%' => '40%',
								'50%' => '50%',
						),
						'default' => __( '50%', 'woocommerce' )
						
				),
				
				'max_months' => array(
						'title' => __( 'Months', 'woocommerce' ),
						'type' => 'select',
						'description' => 'Maximum number of months to pay balance is 6 (defaults to 3)',
						'default' => __( '3', 'woocommerce' ),
						'options' => array('1' => '1',
										   '2' => '2',
										   '3' => '3',
										   '4' => '4',
										   '5' => '5',
										   '6' => '6',
									)
				),
				
				'total' => array(
						'title' => __( 'Total', 'woocommerce' ),
						'type' => 'number',
						'description' => __( 'The checkout total an order must reach before this payment method becomes active.', 'woocommerce' ),
						'default' => __( '', 'woocommerce' ),
						'custom_attributes' => array('min' => '1')
						
				),
				'users' => array(
						
						'title' =>  __( 'Allowed Customer Groups', 'woocommerce' ),
						'description' => 'The checkout customer must be in these customer groups before this payment method becomes active.',
						'type' => 'multiselect',
						'options' => $users,
						'default' => array(
											'Administrator'=>'Administrator'
											)	
				),
				'categories' => array(
				
						'title' =>  __( 'Allowed Categories', 'woocommerce' ),
						'description' => 'The checkout products of the orders must be in these categories before this payment method becomes active.',
						'type' => 'multiselect',
						'options' => $categories
				),
				'excluded_product_id' => array(
						'title' => __( 'Excluded Product IDs', 'woocommerce' ),		
						'type' => 'textarea',		
						'description' => __( 'Add product ids separated by comma(,) for which method will not be available.', 'woocommerce' ),		
						'default' => __( '', 'woocommerce' )
				),
				'report_settings' => array(
						'title' => __( 'Lay-Buys Instalment Report Settings (Api IP-Address)', 'woocommerce' ),		
						'type' => 'text',		
						'description' => __( 'By default it is "https://lay-buys.com/report/"', 'woocommerce' ),		
						'default' => __( 'https://lay-buys.com/report/', 'woocommerce' )
				),
				'cron_url' => array(
						'title' => __( 'Cron URL for fetching updates', 'woocommerce' ),		
						'description' => __( 'By default it is "'.get_site_url().'?cron_action=fetch_updates'.'"', 'woocommerce' )
				),	
	
				'cron_frequency' => array(
									'title' =>  __( 'Set Cronjob Frequency', 'woocommerce' ),
									'type' => 'select',
									'description' => __('No of time Cronjob is execute is depend on its Frequency.', 'woocommerce' ),
									'options' => array('hourly'=>'Hourly',
											'daily' => 'Daily',
											'twicedaily'=> 'Twice Daily'
									),
									'default' => array(
											'daily' => 'Daily'
								)
	
						),
	
				'cron_time' => array(
							'title' =>  __( 'Set Cronjob Time ', 'woocommerce' ),
							'type' => 'select',
							'description' => __('Cronjob is execute from set cron time. Here cron is start execution with the activation of the Extension.', 'woocommerce' ),
							'options' => array('time()'=>'Current Time',
									),
							'default' => array(
									'time()'=>'Current Time'
										)
							
					
					),
			
				'laybuy_checkout_text_enabled' => array(
							'title' => __( 'Lay-Buy Admin Fees', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Lay-Buy Admin Fees', 'woocommerce' ),
							'default' => 'yes',
							'description' => __('A 0.9% admin fee is payable to Lay-Buys. For more detail please login to merchant panel using your merchant credentials.', 'woocommerce')),
	
				'image_for_laybuy_gateway' => array(
							'title' => __( 'Enter an URL or upload an image for the gateway.', 'woocommerce' ),
							'type' => 'text',
							'label' => __( 'Enter an URL or upload an image for the gateway', 'woocommerce' ),
							'custom_attributes' => array('readonly' => 'readonly')
					   ),

				'image_button_for_laybuy_gateway' => array(
							'type' => 'button',
							'default' => 'UPLOAD',
							'description' => __('logo or image (max. 750x90) to appear on Lay-Buys page for your branding or white-labeling', 'woocommerce')
								
					)	
				
		);
	
	}
		
		
	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int $order_id
	 * @return array
	 */
	
	function process_payment( $order_id ) 
	{
		/* 	
		echo '<script>';		
		echo 'document.getElementById("customer_details").innerHTML = "please wait!";';
		echo 'var xyz = document.getElementsByName("checkout");
				xyz[0].style.display="none";            	
	           	var x = document.getElementsByClassName("woocommerce");
	           	var e = document.createElement("div");
				e.innerHTML = "<h3>Please wait while we reach paypal..<br/> Do not refresh page or press back button.</h3>";
	           	x[0].appendChild(e);
	           		';
			
		echo '</script>'; */
			
		$text = 'submitted laybuy form for following order id:';
		$data =  print_r($order_id,true);
		laybuy_write_log($text,$data);
				
		$order = new WC_Order( $order_id );
		$order_details = $order->order_custom_fields;
		
		$billing_email = $order->billing_email;
			
		$url = $this->laybuy_gateway_url;
		
		$max_months =  $this->max_months;
		$min = $this->min;
		
		$cancel_url = $order->get_cancel_order_url();
		$success_url = $this->get_return_url( $order );
		
		$_SESSION['cancel_return_url'] = $order->get_cancel_order_url();
		$_SESSION['success_return_url'] = $this->get_return_url( $order );
		
		$percent = isset($_SESSION['percent'])?$_SESSION['percent']:$min;
		$months = isset($_SESSION['months'])?$_SESSION['months']:$max_months;
		
		/* unset previously stored values */
		unset($_SESSION['percent']);
		unset($_SESSION['months']);
		
		$data="MEMBER=".$this->laybuy_memb_id."&RETURNURL=".$success_url."&CANCELURL=".$cancel_url."&AMOUNT=".$order->order_total."&CURRENCY=".get_woocommerce_currency()."&INIT=".$percent."&MIND=".$this->min."&MONTHS=".$months."&EMAIL=".$billing_email."&BYPASSLAYBUY=1&CUSTOM=".$order->id."&VERSION=0.2&IMAGE=".$this->image_for_laybuy_gateway;
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); /* tell cURL to graciously accept an SSL */	
	
		$response = curl_exec($ch);				
		$response=json_decode($response,true);		
		curl_close($ch);		
		if(!empty($response)&&$response['ACK']=='SUCCESS')		
		{		
			
			$path=$url.'?TOKEN='.$response['TOKEN'];
			return array(
					'result'   => 'success',
					'redirect' => $path
			);
			/* echo '<script>';
			echo 'window.location = "'.$path.'"';			
			echo '</script>';	 */	
		}		
		else		
		{			
			echo "Some Error Has Occured. Please try Again !!!!<br/><br/>";		
		}
		//die;
	}
}
?>