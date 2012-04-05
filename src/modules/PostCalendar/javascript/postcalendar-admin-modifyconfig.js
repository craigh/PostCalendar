jQuery(document).ready(function(){
    jQuery('#pcEventDateFormat').change(function(){
        if (jQuery('#pcEventDateFormat').attr('value') == "-1") {
            jQuery('#manuallySetDateFormats').show("slow");
        } else {
            jQuery('#manuallySetDateFormats').hide("slow");
        }
    });
});