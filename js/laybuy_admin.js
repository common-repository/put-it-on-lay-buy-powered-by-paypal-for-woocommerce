/**
 * generate menu for revising plan
 */
function generate_plan(){
     	
    	amount = jQuery('input#lb_amount').val();
    	
       	amount = amount.replace(',',''); 
      
       	amount = amount.replace(plugin_url.currency,''); 

    	amount = amount.match(/\d+\.?\d*/g);
     	
    	amount = Number(amount);
     	
    	percent= parseInt(jQuery('select#dp_percent').val());
    	 
    	months = parseInt(jQuery('select#installments').val());
     		    	   	
    	dep = amount*percent/100;
     	
     	if(months !==0) installments = (amount-dep)/months; //payment amount
     	
     	installments = installments.toFixed(2);
     
    	dep=amount - installments * months; //dp amount

    	dep = dep.toFixed(2);

    	table = generate_table(dep,installments,months);
     	
    	jQuery('table.select_plan table').replaceWith(table);
     	
}

function  generate_table(downpayment,installments,months){
	 
	var test_date = new Date();
	
	yr = test_date.getFullYear();
	mth = test_date.getMonth();
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
	
	table = '';
 	table += '<table style="width: 275px"><thead><tr><th>Payment</th><th>Due Date</th><th>Amount</th></tr></thead><tbody>';
 	
 	table += '<tr><td>Down Payment</td><td>Today</td><td>'+plugin_url.currency+downpayment+'</td></tr>';
 	month_flag = 0;
 	for(var i=1;i<=months;i++){
 	
 		if (++mth>12) {
			mth='1';
			yr++;
		}
 		
 		m=1+mth-1;
		d=Math.min(day,dim[m]);
		even = '';
	
		date = '';
		date = month_name[mth+1]+' '+d+','+yr;
		table += '<tr><td>'+i+'</td><td>'+date+'</td><td>'+plugin_url.currency+installments+'</td></tr>';	
 	}
 	
 	table += '</tbody></table>';
 	return table;
	 
 }

jQuery(document).ready(function(){
	jQuery('#bn_opt').click(function(){
		jQuery('.bn_hide').hide();
    });
	jQuery('#lb_opt').click(function(){
		jQuery('.bn_hide').show();
    });
	 if(jQuery('#bn_opt').is(':checked')) { jQuery('.bn_hide').hide(); }	
	
	 jQuery('#woocommerce_laybuy_image_button_for_laybuy_gateway').click(function() {
			 
			 inputField = jQuery('#woocommerce_laybuy_image_for_laybuy_gateway');
	         tb_show('', 'media-upload.php?TB_iframe=true');
	         window.send_to_editor = function(html)
	         {
	            url = jQuery(html).attr('href');
	            inputField.val(url);
	            tb_remove();
	         };
	         return false;
		});		
	 
});

jQuery(document).ready(function(){
	
	jQuery( "#lb_opt" ).click(function() {
		
			jQuery('#bn_opt').removeAttr('checked');
	
	});
		
	
	jQuery( "#bn_opt" ).click(function() {
			
			jQuery('#lb_opt').removeAttr('checked');
		
	});
	
});	  

/**
 * function to reset plan
 * @param installments
 * @param dp_percent
 */
function reset_plan(installments,dp_percent){
	 $url = plugin_url.extension_url;
	 jQuery.post($url, { reset_action: 'reset', installments: installments,dp_percent: dp_percent }, function(data){
		 reset_values(data);
			},'json');
	
}
/** * reset values of revise form */
function reset_values(data){
	
	 if(jQuery('#bn_opt').is(':checked')) { 
		jQuery('.bn_hide').show();
		jQuery('#bn_opt').removeAttr('checked');
		jQuery('#lb_opt').attr('checked', true);
		
	 }else
		
	{
	var html='';
	
		html+='<input type="radio" checked="checked" value="1" name="lb_opt" id="lb_opt"> Lay-Buy<br>';
		html+='<input type="radio" value="0" name="bn_opt" id="bn_opt"> Buy-Now';
		jQuery('td#type').html(html);
		
		var select_percent = '';
		select_percent += '<option value="'+data.dp_percent+'" selected="">'+data.dp_percent+'%</option>';
		jQuery('select#dp_percent option:selected').replaceWith(select_percent);		if(data.installments == '1'){
		$var = 'month';		}else $var = 'months';			
		var select_installment = '';
		select_installment += '<option value="'+data.installments+'" selected="">'+data.installments+' '+$var+'</option>';
		jQuery('select#installments option:selected').replaceWith(select_installment);
		
		jQuery( "#bn_opt" ).click(function() {
			jQuery('#lb_opt').removeAttr('checked');
			jQuery('.bn_hide').hide();
		});
		
		jQuery( "#lb_opt" ).click(function() {
			jQuery('#bn_opt').removeAttr('checked');
			jQuery('.bn_hide').show();
		});	
		
	}
	 location.reload();
}

/**
 *  update url parameters for filters
 */
function updateURLParameter(url, param, paramVal)
{
    var TheAnchor = null;
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1];
    var temp = "";

    if (additionalURL) 
    {
        var tmpAnchor = additionalURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor = tmpAnchor[1];
        if(TheAnchor)
            additionalURL = TheParams;
        tempArray = additionalURL.split("&");

        for (i=0; i<tempArray.length; i++)
        {
            if(tempArray[i].split('=')[0] != param)
            {
                newAdditionalURL += temp + tempArray[i];
                temp = "&";
            }
        }        
    }
    else
    {
        var tmpAnchor = baseURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor  = tmpAnchor[1];

        if(TheParams)
            baseURL = TheParams;
    }

    if(TheAnchor)
        paramVal += "#" + TheAnchor;

    var rows_txt = temp + "" + param + "=" + paramVal;
    return baseURL + "?" + newAdditionalURL + rows_txt;
}/** *  remove "pagenum" parameter from url */
function removeParam(key, sourceURL) {    var rtn = sourceURL.split("?")[0],        param,        params_arr = [],        queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";    if (queryString !== "") {        params_arr = queryString.split("&");        for (var i = params_arr.length - 1; i >= 0; i -= 1) {            param = params_arr[i].split("=")[0];            if (param === key) {                params_arr.splice(i, 1);            }        }        rtn = rtn + "?" + params_arr.join("&");    }    return rtn;}
/**
 *  filters for laybuys installment reports admin page
 */
jQuery(document).ready(function() {
	 jQuery('.filter').change(function() {		 
		   	var newURL = updateURLParameter(window.location.href, this.id , jQuery(this).val());
		   	newURL = removeParam('pagenum', newURL);		   	window.location.replace(newURL);
		   	
	});
});
