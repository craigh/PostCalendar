var filterActive = new Array();

jQuery(document).ready(function() {
    // adjust length of datepicker input field on pageload (if it exists)
    if (jQuery('#pcnav_datepicker').length > 0) {
        var tlength = jQuery('#pcnav_datepicker').val().length;
        tlength = .65 * tlength; // may need to be adjusted based on fontsize ?
        jQuery('#pcnav_datepicker').css('width', tlength + 'em');
    }
    // create the buttonset
    jQuery(function() {
        jQuery('#pcnav_buttonbar').buttonset()
        // this extra bit removes any linebreaks/spaces/tabs between code causing
        // abherant behavior - http://forum.jquery.com/topic/radio-buttonset-scaling
        .contents().filter(function() {
            return this.nodeType == 3;
        }).remove();
    });
    jQuery('#pcnav').contents().filter(function() {
        // see note above - removing spaces, etc.
        return this.nodeType == 3;
    }).remove();
    // redirect on buttonclick
    jQuery('.pcnav_button').click(function() {
        // prevent redirect on ical button click
        if (jQuery(this).attr('id') != 'pcnav_ical') {
            // else redirect
            window.location = jQuery('#pcnav_url_' + jQuery(this).attr('value')).attr("value");
        }
    });
    // create the user button
    jQuery('#pcnav_usercalendar_button').button({
        icons: {
            secondary: "ui-icon-triangle-1-s"
        }
    });
    // create the usercalendar dialog
    jQuery('#pcnav_usercalendar_dialog').dialog({
        autoOpen: false,
        width: 200,
        resizable: false,
        draggable: false,
        position: {
            my: 'left top',
            at: 'left bottom',
            of: jQuery('#pcnav_usercalendar_button')
        }
    });
    // open the usercalendar dialog on button click
    jQuery('#pcnav_usercalendar_button').click(function() {
        jQuery('#pcnav_usercalendar_dialog').css('display', 'inherit');
        jQuery('#pcnav_usercalendar_dialog').dialog('open');
        return false;
    });
    // close the usercalendar dialog when clicking outside dialog
    jQuery(document).click(function(e){
        if (!jQuery(e.target).parents().filter('.ui-dialog').length) {
            // close the dialog
            jQuery('#pcnav_usercalendar_dialog').dialog('close');
        }
    });
    // submit the form based on usercalendar selection
    jQuery('.pcusercalendar_selector').click(function() {
        var id = jQuery(this).attr('id').split("_").pop();
        if (id == 'GLOBAL') {
            id = '';
        }
        jQuery('#userfilter').attr('value', id);
        // submit the form
        jQuery('#pcnav-form').submit();
    });
    // open the datepicker on button click
    jQuery('#pcnav_datepicker_button').click(function() {
        jQuery('#pcnav_datepicker').datepicker('show');
        // prevent form submission
        return false;
    });
    // open dialog on ical button click
    jQuery('#pcnav_ical').click(function() {
        jQuery('#pcnav_ical_dialog').css('display', 'inherit');
        jQuery('#pcnav_ical_dialog').dialog('open');
        return false;
    });
    // create the ical feed dialog
    jQuery('#pcnav_ical_dialog').dialog({
        autoOpen: false,
        resizable: false,
        draggable: false,
        modal: true
    });
    // create the filterpicker dialog
    jQuery('#pcnav_filterpicker_dialog').dialog({
        autoOpen: false,
        width: 200,
        resizable: false,
        draggable: false,
        position: {
            my: 'left top',
            at: 'left bottom',
            of: jQuery('#pcnav_filterpicker')
        }
    });
    // open the filterpicker dialog on button click
    jQuery('#pcnav_filterpicker_button').click(function() {
        jQuery('#pcnav_filterpicker_dialog').css('display', 'inherit');
        jQuery('#pcnav_filterpicker_dialog').dialog('open');
        return false;
    });
    // open the filterpicker dialog on field click
    // not working atm
    jQuery('#pcnav_filterpicker').click(function() {
        jQuery('#pcnav_filterpicker_dialog').dialog('open');
    });
    // close the filterpicker dialog when clicking outside dialog
    jQuery(document).click(function(e){
        if (!jQuery(e.target).parents().filter('.ui-dialog').length) {
            // close the dialog
            jQuery('#pcnav_filterpicker_dialog').dialog('close');
        }
    });
    // manage category items based on filterpicker selection
    jQuery('.pccategories_selector').click(function() {
        var catid = jQuery(this).attr('id').split("_").pop();
        if (jQuery(this).css('opacity') < 1) {
            jQuery('.pccategories_' + catid).show();
            jQuery(this).fadeTo(0, 1);
            var c = filterActive.indexOf('c' + catid);
            if (c != -1) {
                filterActive.splice(c, 1);
            }
        } else {
            jQuery('.pccategories_' + catid).hide();
            jQuery(this).fadeTo(0, 0.3);
            if (filterActive.indexOf('c' + catid) == -1) {
                filterActive.push('c' + catid);
            }
        }
        checkActiveState();
    });
    // manage visibility items based on filterpicker selection
    jQuery('.pcvisibility_selector').click(function() {
        var vizid = jQuery(this).attr('id');
        if (jQuery(this).css('opacity') < 1) {
            jQuery('.' + vizid).show();
            jQuery(this).fadeTo(0, 1);
            var i = filterActive.indexOf('v' + vizid);
            if (i != -1) {
                filterActive.splice(i, 1);
            }
        } else {
            jQuery('.' + vizid).hide();
            jQuery(this).fadeTo(0, 0.3);
            if (filterActive.indexOf('v' + vizid) == -1) {
                filterActive.push('v' + vizid);
            }
        }
        checkActiveState();
    });
});

// display active/inactive based on filterpicker selections
function checkActiveState() {
    if (filterActive.length == 0) {
        jQuery('#pcnav_filterpicker')
            .css('background-color', '#EEEEEE')
            .css('border-color', '#CCCCCC')
            .attr('value', pcActiveStateInactive);
    } else {
        jQuery('#pcnav_filterpicker')
            .css('background-color', '#DFF2BF')
            .css('border-color', '#99CC99')
            .attr('value', pcActiveStateActive);
    }
}