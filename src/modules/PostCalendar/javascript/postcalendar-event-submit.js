jQuery(document).ready(function(){
    // hide or show timepiker fields based on alldayevent choice
    jQuery('#postcalendar_events_alldayevent').click(function(){
        if (jQuery('#postcalendar_events_alldayevent').is(':checked')) {
            jQuery('#eventstart_time_display').hide("slow");
            jQuery('#eventend_time_display').hide("slow");
        } else {
            jQuery('#eventstart_time_display').show("slow");
            jQuery('#eventend_time_display').show("slow");
        }
    });
    // hide or show repetition options
    jQuery('#postcalendar_events_repeats').click(function(){
        if (jQuery('#postcalendar_events_repeats').is(':checked')) {
            jQuery('#postcalendar_repetitionsettings').show("slow");
        } else {
            jQuery('#postcalendar_repetitionsettings').hide("slow");
        }
    });
    // hide or show allowed html based on text type
    jQuery('#postcalendar_events_htmlortext').click(function(){
        if (jQuery('#postcalendar_events_htmlortext').is(':checked')) {
            jQuery('#html_warning').show("slow");
        } else {
            jQuery('#html_warning').hide("slow");
        }
    });
    // hide or show location fields
    jQuery('#postcalendar_events_haslocation').click(function(){
        if (jQuery('#postcalendar_events_haslocation').is(':checked')) {
            jQuery('#postcalendar_events_haslocation_display').show("slow");
        } else {
            jQuery('#postcalendar_events_haslocation_display').hide("slow");
        }
    });
    // hide or show contact fields
    jQuery('#postcalendar_events_hascontact').click(function(){
        if (jQuery('#postcalendar_events_hascontact').is(':checked')) {
            jQuery('#postcalendar_events_hascontact_display').show("slow");
        } else {
            jQuery('#postcalendar_events_hascontact_display').hide("slow");
        }
    });
});