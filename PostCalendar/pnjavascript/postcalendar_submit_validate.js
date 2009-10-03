var err_no_start = "Please enter a Start Time.";
var err_no_end = "Please enter an End Time.";
var err_end_before = "* temp_err_no_before * ";
var err_no_person = "Please select who to move.";
var err_no_participant = "You didn't choose any participants.\n\nCheck 'I will participate', and/or select participants.";
var err_thema_long = "Subject text is too long.\nReduce length to be 100 characters or less.";
var err_external_long = "External text is too long.\nReduce length to be 200 characters or less.";

function MoveOption (MoveFrom, MoveTo) {
    //tested on:
    // - Win2k: MSIE5.5, MSIE6, NN6.2.1, NN6.2.2
    // - Mac: NN4.7/MacOS9.0.4 and MSIE5/MacOS9.0.4
    // - others: Mozilla0.9.8/FreeBSD4.5, Mozilla snapshot 2002040410 on Linux 2.4.18 
    var name;
    var ID;
    var i;
    var SelectFrom = eval('window.document.nd.elements[\"'+MoveFrom+'[]\"]');
    var SelectTo = eval('window.document.nd.elements[\"'+MoveTo+'[]\"]');
    var SelectedIndex = SelectFrom.options.selectedIndex;

    if (SelectedIndex == -1) {
	alert(err_no_person);
    }
    else {
	for (i=0; i<SelectFrom.options.length; i++) {
	    if(SelectFrom.options[i].selected) {
		name = SelectFrom.options[i].text;
		ID = SelectFrom.options[i].value;
		SelectFrom.options[i] = null;
		SelectTo.options[SelectTo.options.length]=new Option (name,ID);
		i--;
	    }
	}

	//begin of sorting stuff, it could be very slooow
	//needs JS1.1 and higher
	var sorting = new Array();		
	for (i=0; i<SelectTo.options.length; i++) {
	    sorting[i] = SelectTo.options[i].text+"###"+SelectTo.options[i].value;
	}
	sorted = sorting.sort();
	for (i=0; i<SelectTo.options.length; i++) {
	    var tmp = sorted[i].split("###");
	    name = tmp[0];
	    ID = tmp[1];
	    SelectTo.options[i]=new Option (name,ID);
	}
	// end of sorting stuff
    }
    return;
}

function selectItems() {
    var to_form = eval('document.nd.elements[\"participants[]\"]');
    for(i=0; i < to_form.length; i++) {
	to_form.options[i].selected = true;
    }
    if(i==0 && document.nd.i_will.checked == false) {
	alert(err_no_participant);
	return false;
    }
    return true;
}

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