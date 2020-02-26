/**
 * @author Anton Shulga
 * Description: For frontend
 */
jQuery.noConflict();

jQuery(document).ready(function ($) {

    $(".wss-number-fields").keypress(function(evt) {
        evt.preventDefault();
    });

    jQuery('#wss_start_date').datepicker({
        numberOfMonths: 1,
        minDate: '0',
        dateFormat: 'yy-m-d',
        beforeShowDay: $.datepicker.noWeekends
    });

    $('input[name="wss_billing_period"]').on('change', function(){
    	if($(this).val() == 'week') {
    		$('#available_days_delivery').css(
                {
                    'display':'-webkit-flex',
                    'display':'flex'
                }
            );
    	} else {
    		$('#available_days_delivery').hide();
    	}
    });
});
