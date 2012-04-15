var filterActive = new Array();

jQuery(document).ready(function() {
    jQuery(function() {
        jQuery('#pcnav_buttonbar').buttonset()
        // this extra bit removes any linebreaks/spaces/tabs between code causing
        // abherant behavior - http://forum.jquery.com/topic/radio-buttonset-scaling
        .contents().filter(function() {
            return this.nodeType == 3;
        }).remove();
    });
    jQuery('.pcnav_button').click(function() {
        window.location = jQuery('#pcnav_url_' + jQuery(this).attr('value')).attr("value");
    });
    jQuery('#pcnav_datepicker_button').click(function() {
        jQuery('#pcnav_datepicker').datepicker('show');
        // prevent form submission
        return false;
    });
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
    jQuery('#pcnav_filterpicker_button').click(function() {
        jQuery('#pcnav_filterpicker_dialog').dialog('open');
        return false;
    });
    jQuery('#pcnav_filterpicker').click(function() {
        jQuery('#pcnav_filterpicker_dialog').dialog('open');
    });
    jQuery(document).click(function(e){
        if (!jQuery(e.target).parents().filter('.ui-dialog').length) {
            // close your dialog
            jQuery('#pcnav_filterpicker_dialog').dialog('close');
        }
    });
    jQuery('.pccategories_selector').click(function() {
        var catid = jQuery(this).attr('id').split("_").pop();
        jQuery('.pccategories_' + catid).toggle();
        if (jQuery('.pccategories_' + catid).is(':hidden')) {
            if (filterActive.indexOf('c' + catid) == -1) {
                filterActive.push('c' + catid);
            }
        } else {
            var c = filterActive.indexOf('c' + catid);
            if (c != -1) {
                filterActive.splice(c, 1);
            }
        }
        checkActiveState();
        jQuery(this).show();
    });
    jQuery('.pccategories_selector').toggle(function() {
        jQuery(this).fadeTo(0, 0.3);
    }, function() {
        jQuery(this).fadeTo(0, 1);
    });
    jQuery('.pcvisibility_selector').click(function() {
        var vizid = jQuery(this).attr('id');
        jQuery('.' + vizid).toggle();
        if (jQuery('.' + vizid).is(':hidden')) {
            if (filterActive.indexOf('v' + vizid) == -1) {
                filterActive.push('v' + vizid);
            }
        } else {
            var i = filterActive.indexOf('v' + vizid);
            if (i != -1) {
                filterActive.splice(i, 1);
            }
        }
        checkActiveState();
        jQuery(this).show();
    });
    jQuery('.pcvisibility_selector').toggle(function() {
        jQuery(this).fadeTo(0, 0.3);
    }, function() {
        jQuery(this).fadeTo(0, 1);
    });
});


function checkActiveState() {
    switch (filterActive.length) {
        case 0:
            jQuery('#pcnav_filterpicker')
                .css('background-color', '#EEEEEE')
                .css('border-color', '#CCCCCC')
                .attr('value', Zikula.__('inactive','module_postcalendar_js'));
            break;
        default:
            jQuery('#pcnav_filterpicker')
                .css('background-color', '#DFF2BF')
                .css('border-color', '#99CC99')
                .attr('value', Zikula.__('active','module_postcalendar_js'));
            break;

    }
}