jQuery(document).ready(function() {
    jQuery(function() {
        jQuery('#pcnav-buttonbar').buttonset()
        // this extra bit removes any linebreaks/spaces/tabs between code causing
        // abherant behavior - http://forum.jquery.com/topic/radio-buttonset-scaling
        .contents().filter(function() {
            return this.nodeType == 3;
        }).remove();
    });
    jQuery('.pcnav-button').click(function() {
        window.location = jQuery('#pcnav_url_' + jQuery(this).attr('value')).attr("value");
    });
    jQuery('#pcnav_datepicker_button').click(function() {
        jQuery('#pcnav_datepicker').datepicker('show');
        // prevent form submission
        return false;
    });
    jQuery('#pccategorypicker').dialog({
        autoOpen: false,
        width: 200,
        resizable: false,
        draggable: false,
        position: {
            my: 'left top',
            at: 'left bottom',
            of: jQuery('#pcnav_categoryfilter')
        }
    });
    jQuery('#pcnav_categoryfilter_button').click(function() {
        jQuery('#pccategorypicker').dialog('open');
        return false;
    });
    jQuery(document).click(function(e){
        if (!jQuery(e.target).parents().filter('.ui-dialog').length) {
            // close your dialog
            jQuery('#pccategorypicker').dialog('close');
        }
    });
    jQuery('.pccategories_selector').click(function() {
        var catid = jQuery(this).attr('id').split("_").pop();
        jQuery('.pccategories_' + catid).toggle();
        jQuery(this).show();
    });
    jQuery('.pccategories_selector').toggle(function() {
        jQuery(this).fadeTo(0, 0.3);
    }, function() {
        jQuery(this).fadeTo(0, 1);
    });
});