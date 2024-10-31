jQuery(document).ready(function() {
	
	if (jQuery.isFunction(jQuery.fn.datepicker)) {
	   jQuery('.txn_date').datepicker({
	      dateFormat : 'M d, yy'
	   });
	}
       
});