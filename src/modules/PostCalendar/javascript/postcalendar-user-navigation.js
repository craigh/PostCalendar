jQuery(document).ready(function() {
    jQuery(function() {
        jQuery('#pcnav-buttonbar').buttonset()
        // this extra bit removes any linebreaks/spaces/tabs between code causing
        // abherant behavior - http://forum.jquery.com/topic/radio-buttonset-scaling
        .contents().filter(function() {
            return this.nodeType == 3;
        }).remove();
    });
    jQuery('.pcnav-button').click(function(){
        window.location = jQuery('#pcnav_url_' + jQuery(this).attr('value')).attr("value");
    });
    jQuery('#pcnav_datepicker_button').click(function() {
        jQuery('#pcnav_datepicker').datepicker('show');
        // prevent form submission
        return false;
    });
});