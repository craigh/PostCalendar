var err_no_start = "Please enter a Start Time.";
var err_no_end = "Please enter an End Time.";
var err_end_before = "* temp_err_no_before * ";
var err_external_long = "External text is too long.\nReduce length to be 200 characters or less.";

function check_form() {
    if (document.nd.day.checked == false) {
    	if (document.nd.appointment_start.value == 0) {
    	    alert (err_no_start);
    	    document.nd.appointment_start.focus();
    	    return false;
    	} else if (document.nd.appointment_end.value == 0) {
    	    alert (err_no_end);
    	    document.nd.appointment_end.focus();
    	    return false;
    	} else if (!document.nd.appointment_attribute[0].checked && (document.nd.appointment_end.value < document.nd.appointment_start.value)) {
    	    alert (err_end_before);
    	    document.nd.appointment_end.focus();
    	    return false;
    	}
    }

    if (document.nd.external.value.length > 200) {
		alert (err_external_long);
		return false;
    }

    return selectItems();
}