jQuery(document).ready(function(){
    jQuery('#postcalendar_events_alldayevent').click(function(){
        if (jQuery('#postcalendar_events_alldayevent').is(':checked')) {
            jQuery('#eventstart_time').hide();
            jQuery('#eventend_time').hide();
        } else {
            jQuery('#eventstart_time').show();
            jQuery('#eventend_time').show();
        }
    });
    jQuery('#postcalendar_events_repeats').click(function(){
        if (jQuery('#postcalendar_events_repeats').is(':checked')) {
            jQuery('#postcalendar_repetitionsettings').show("slow");
        } else {
            jQuery('#postcalendar_repetitionsettings').hide("slow");
        }
    });
    jQuery('#postcalendar_events_htmlortext').click(function(){
        if (jQuery('#postcalendar_events_htmlortext').is(':checked')) {
            jQuery('#html_warning').show("slow");
        } else {
            jQuery('#html_warning').hide("slow");
        }
    });
    jQuery('#postcalendar_events_haslocation').click(function(){
        if (jQuery('#postcalendar_events_haslocation').is(':checked')) {
            jQuery('#postcalendar_events_haslocation_display').show("slow");
        } else {
            jQuery('#postcalendar_events_haslocation_display').hide("slow");
        }
    });
    jQuery('#postcalendar_events_hascontact').click(function(){
        if (jQuery('#postcalendar_events_hascontact').is(':checked')) {
            jQuery('#postcalendar_events_hascontact_display').show("slow");
        } else {
            jQuery('#postcalendar_events_hascontact_display').hide("slow");
        }
    });
});
function updateFields(inputElementObj, dateText)
{
    var fieldName = jQuery(inputElementObj).attr('id');
    var date = new Date(dateText);
    var datePlusOne = new Date(dateText);
    datePlusOne.setDate(datePlusOne.getDate()+1);
    var eventStartTime = jQuery("#eventstart_time").datepicker("getDate");
    var eventEndTime = jQuery("#eventend_time").datepicker("getDate");
    
    switch(fieldName)
    {
        case 'eventstart_display':
            jQuery("#eventend_display").datepicker('option', 'minDate', date);
            break;
        case 'eventend_display':
            jQuery("#eventstart_display").datepicker('option', 'maxDate', date);
            jQuery("#repeat_enddate_display").datepicker('option', 'minDate', datePlusOne);
            break;
        case 'repeat_enddate_display':
            // do nothing
            break;
        case 'eventstart_time':
            // apparently cannot use straight == comparison of Date objects...
            if (jQuery.datepicker.formatDate('yymmdd', jQuery("#eventstart_display").datepicker("getDate")) == jQuery.datepicker.formatDate('yymmdd', jQuery("#eventend_display").datepicker("getDate"))) {
                if (eventStartTime > eventEndTime) {
                    jQuery("#eventend_time").datepicker("setDate", jQuery("#eventstart_time").datepicker("getDate"));
                }
            }
            break;
        case 'eventend_time':
            // apparently cannot use straight == comparison of Date objects...
            if (jQuery.datepicker.formatDate('yymmdd', jQuery("#eventstart_display").datepicker("getDate")) == jQuery.datepicker.formatDate('yymmdd', jQuery("#eventend_display").datepicker("getDate"))) {
                if (eventStartTime > eventEndTime) {
                    jQuery("#eventstart_time").datepicker("setDate", jQuery("#eventend_time").datepicker("getDate"));
                }
            }
            break;
    }
}