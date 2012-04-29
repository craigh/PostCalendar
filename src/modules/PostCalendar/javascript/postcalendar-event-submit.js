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
    // hide or show recur exceptions
    jQuery('#postcalendar_events_hasexceptions').click(function(){
        if (jQuery('#postcalendar_events_hasexceptions').is(':checked')) {
            jQuery('#postcalendar_exceptions').show("slow");
        } else {
            jQuery('#postcalendar_exceptions').hide("slow");
        }
    });
    // add new exception form field on click
    jQuery('.addexception').click(function() {
        var parent = jQuery(this).parent();
        // rename the first storage element to hold arrays
        parent.find('input:hidden').attr('name', 'postcalendar_events[recurexceptionstorage][]');        
        // compute the id number
        var exceptionIndex = parent.attr('id').split("-").pop();
        exceptionIndex++;
        // clone the parent container and change the ids and names
        var newChild = parent.clone(true)
            // find display element and rename
            .find('input.hasDatepicker')
            .attr('name', 'postcalendar_events[recurexceptiondisplay_' + exceptionIndex + ']')
            .attr('id', 'recurexceptiondisplay_' + exceptionIndex)
            // find storage element rename
            .end().find('input.hasDatepicker+input')
            .attr('name', 'postcalendar_events[recurexceptionstorage][]')
            .attr('id', 'recurexceptionstorage_' + exceptionIndex)
            .end()
            // re-id container
            .attr('id', 'postcalendar_exceptions-' + exceptionIndex);
        // insert the new container
        parent.after(newChild);
        // reinitialize cloned datepicker
        var oldDateInput = parent.find('input:text');
        var newDateInput = newChild.find('input:text');
        newDateInput.siblings('.ui-datepicker-trigger,.ui-datepicker-apply').remove();
        newDateInput
            .removeClass('hasDatepicker')
            .removeData('datepicker')
            .unbind()
            .datepicker({
                // copy old settings to new instance
                autoSize: oldDateInput.datepicker('option', 'autoSize'),
                altField: '#recurexceptionstorage_' + exceptionIndex,
                altFormat: oldDateInput.datepicker('option', 'altFormat'),
                minDate: oldDateInput.datepicker('option', 'minDate'),
                dateFormat: oldDateInput.datepicker('option', 'dateFormat'),
                defaultDate: oldDateInput.datepicker('option', 'defaultDate')
            });
        // remove add button
        jQuery(this).remove();
        // return false so button doesn't process form
        return false;
    });
    jQuery('.deleteexception').click(function() {
        // remove the entire container
        jQuery(this).parent().remove();
        // return false so button doesn't process form
        return false;
    });
});