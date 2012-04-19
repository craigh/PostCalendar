function updateFields(inputElementObj, dateText)
{
    var fieldName = jQuery(inputElementObj).attr('id');
    var date = new Date(dateText);
    var datePlusOne = new Date(dateText);
    datePlusOne.setDate(datePlusOne.getDate()+1);
    var eventStartTime = jQuery("#eventstart_time_display").datepicker("getDate");
    var eventEndTime = jQuery("#eventend_time_display").datepicker("getDate");
    
    switch(fieldName)
    {
        case 'eventstart_display':
            jQuery("#eventend_display").datepicker('option', 'minDate', date);
            updateFields(jQuery("#eventend_time_display"), dateText);
            break;
        case 'eventend_display':
            jQuery("#eventstart_display").datepicker('option', 'maxDate', date);
            jQuery("#repeat_enddate_display").datepicker('option', 'minDate', datePlusOne);
            updateFields(jQuery("#eventstart_time_display"), dateText);
            break;
        case 'repeat_enddate_display':
            // do nothing
            break;
        case 'eventstart_time_display':
            jQuery("#eventstart_time").attr("value", pcFormatTime(eventStartTime));
            if (jQuery("#eventstart_display").datepicker("getDate").getTime() == jQuery("#eventend_display").datepicker("getDate").getTime()) {
                if (eventStartTime > eventEndTime) {
                    jQuery("#eventend_time_display").datepicker("setDate", eventStartTime);
                    jQuery("#eventend_time").attr("value", pcFormatTime(eventStartTime));
                }
            }
            break;
        case 'eventend_time_display':
            jQuery("#eventend_time").attr("value", pcFormatTime(eventEndTime));
            if (jQuery("#eventstart_display").datepicker("getDate").getTime() == jQuery("#eventend_display").datepicker("getDate").getTime()) {
                if (eventStartTime > eventEndTime) {
                    jQuery("#eventstart_time_display").datepicker("setDate", eventEndTime);
                    jQuery("#eventstart_time").attr("value", pcFormatTime(eventEndTime));
                }
            }
            break;
    }
}
function pcFormatTime(date)
{
    var m = date.getMinutes();
    var h = date.getHours();
    m = m + ''; // convert to string
    h = h + ''; // convert to string
    m = m.length == 1 ? "0" + m : m;
    h = h.length == 1 ? "0" + h : h;
    return h + ":" + m;
}