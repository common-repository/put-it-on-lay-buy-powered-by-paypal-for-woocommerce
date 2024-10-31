/**
 * add menu to the checkout page for selecting laybuy payment plan
 */
var click_flag = 0;

jQuery(document).on('click', '#payment_method_laybuy', function () {
	
	setTimeout(function() {
		
		if(!document.getElementById('loaded-content')){
			click_flag = 0;
		}
		var chkout_text = '';			 	if(plugin_val.laybuy_checkout_text_enabled == 'yes'){	 		chkout_text = 'A 0.9% admin fee is payable to Lay-buy.';	 	}
		var html = '<div id="loaded-content">Lay-Buy is an affordable payment plan option that allows you to pay-off a product or service via one down payment, with the balance paid over 1, 2 or 3 monthly instalments. Your purchase is delivered to you after the final instalment payment is completed. '+chkout_text;
		
		if(click_flag == 0){
			jQuery('div.payment_method_laybuy p').before(html);
		}
		
		jQuery('div.payment_method_laybuy p').css('font-weight','bold');
	 	min = plugin_val.min;
	 	if(min=='')
	 	{
	 	  min = '20%';
	 	
	 	}
	 	
	 	max = plugin_val.max;
	 	if(max=='')
	 	{
	 	  max = '50%';
	 	}
	 	
	 	min = parseInt(min.replace('%',''));
	 	max = parseInt(max.replace('%',''));
	 	max_months = plugin_val.max_months;
	 	
	 	
	 	
	 	if(max_months == '')
	 	{
	 		max_months = '3';
	 	}
	 	max_months = parseInt(max_months);
	 	var html='';
	 	
	 	html+='<div class="plan"><span class="bind"><span style="float: left;">Initial Payment: </span><select id="percent"><option selected value="'+min+'">'+min+'%</option>';
	 	for(var j=min;j<=max;j+=10){
	 		if(j!=min){html+='<option value='+j+'>'+j+'%</option>'};
	 	}
	 	html+='</select></span><span class="bind">';
	 	
	 	html+='<span style="float: left; margin-left: 10px;">Months To Pay:</span><select id="months">';
	 	
	 	for(var k=1;k<=max_months;k++){
	 		if(k!=max_months){
	 			if(k==1){html+='<option value='+k+'>'+k+' month</option>';}
	 			else {html+='<option value='+k+'>'+k+' months</option>'}
	 			
	 		}
	 	}
	 	html+='<option selected value="'+max_months+'">'+max_months+' months</option>';
	 	html+='</select></span></div><div style="clear:both"></div>'; 
	 	/* alert(html); */
        if(click_flag == 0){
        	jQuery('div.payment_method_laybuy p').after(html);
		}
          
    
    generate_plan_options(click_flag);
   
	jQuery('select').change(function(){
	     	
	    	generate_plan_options(1);
	  	 
	     	jQuery.post(plugin_val.extension_url,{ percent: percent,months: months}, function(data){
	     	
	     	},'json');
	     	
	     });
	click_flag++;
	
	}, 700);
 });

  
 jQuery(document).ready(function () {
	 
	 	 	 
 if(jQuery('input#payment_method_laybuy').is(':checked')) {
	 jQuery('input#payment_method_laybuy').removeAttr('checked');
	 
 }
 
 jQuery('#place_order').click(function(){
	 
	 jQuery( "form.checkout" ).html('Wating for paypal..');
 });
 
 
 });
 
 /**
  *generate select options on checkout page
  */
 function generate_plan_options(flag){
	 
	 amount = jQuery('tr.total span.amount:first').text();
	if(amount == ''){
			amount = jQuery('tr.order-total span.amount:first').text();
	}

	if(amount == ''){
		amount = jQuery('td.product-total span.amount:first').text();
	}
	
 	amount = amount.replace(',','');
 	
 	amount = amount.match(/\d+\.?\d*/g);
	
	
	//amount = plugin_val.order_total;
		
 	months = parseInt(jQuery('select#months').val());
  	
 	percent = parseInt(jQuery('select#percent').val());
  	
	   	 	
 	dep = amount*percent/100;
 	
 	
 	if(months !==0) installments = (amount-dep)/months; //payment amount
 	
 	installments = installments.toFixed(2);
 	
 	
 	
 
	dep=amount - installments * months; //dp amount

	dep = dep.toFixed(2);
	
 	table = generate_table(dep,installments,months);
  	
 	if(flag == 0){
 		
 		jQuery('div.plan').after(table);
 		
	}else jQuery('div.payment_method_laybuy table').replaceWith(table);
  	flag++;
 }
 
flag_div = 0;
 function  generate_table(dep,installments,months){
	 
		var test_date = new Date();
		
		yr = test_date.getFullYear();
		mth = test_date.getMonth() + 1;
		day = test_date.getDate();
		
		var month_name=new Array(12);
		 month_name[1]="Jan";
		 month_name[2]="Feb";
		 month_name[3]="Mar";
		 month_name[4]="Apr";
		 month_name[5]="May";
		 month_name[6]="Jun";
		 month_name[7]="Jul";
		 month_name[8]="Aug";
		 month_name[9]="Sep";
		 month_name[10]="Oct";
		 month_name[11]="Nov";
		 month_name[12]="Dec";
		
		isLeap = new Date(test_date.getYear(), 1, 29).getMonth() == 1;
		
		if(isLeap){
			
			dim=new Array(31,31,29,31,30,31,30,31,31,30,31,30,31);
			
		}
		else dim=new Array(31,31,28,31,30,31,30,31,31,30,31,30,31);
		
		var currency_position = plugin_val.currency_position;
		
		if(currency_position == 'left')
		{
			var dp = plugin_val.currency+dep;
		}
		if(currency_position == 'left_space')
		{
			var dp = plugin_val.currency+' '+dep;
		}
		if(currency_position == 'right')
		{
			var dp = dep+plugin_val.currency;
		}
		if(currency_position == 'right_space')
		{
			var dp = dep+' '+plugin_val.currency;
		}
		
		table = '';
	 	table += '<table class="lb_table" border="1 solid black"><thead><tr><th class="lb_thead">Payment</th><th class="lb_thead">Due Date</th><th class="lb_thead">Amount</th></tr></thead><tbody>';
	 	
	 	table += '<tr><td class="lb_tr">Down Payment</td><td class="lb_td">Today</td><td class="lb_td">'+dp+'</td></tr>';
	 	month_flag = 0;
	 	for(var i=1;i<=months;i++){
	 	
	 		if(currency_position == 'left')
			{
				var install = plugin_val.currency+installments;
			}
			if(currency_position == 'left_space')
			{
				var install = plugin_val.currency+' '+installments;
			}
			if(currency_position == 'right')
			{
				var install = installments+plugin_val.currency;
			}
			if(currency_position == 'right_space')
			{
				var install  = installments+' '+plugin_val.currency;
			}
	 		
	 		if (++mth>12) {
				mth='1';
				yr++;
			}
	 		
	 		m=1+mth-1;
			d=Math.min(day,dim[m]);
			even = '';
		
			date = '';
			date = month_name[mth]+' '+d+', '+yr;
	 		table += '<tr><td class="lb_td">'+i+'</td><td class="lb_td">'+date+'</td><td class="lb_td">'+install+'</td></tr>';	
	 	}
	 	
	 	table += '</tbody></table>';
	 	if(flag_div==0){
	 		table += '<div class="lb_desc"> Your goods/services will be delivered once your final payment has been received</div>';
	 	}
	 	flag_div++;
	 	return table;
		 
	 }
 