<?php
@define('__POSTCALENDAR__','PostCalendar');
/**
 *  SVN: $Id$
 *
 *  @package         PostCalendar 
 *  @lastmodified    $Date$ 
 *  @modifiedby      $Author$ 
 *  @HeadURL	       $HeadURL$ 
 *  @version         $Revision$ 
 *  
 *  PostCalendar::PostNuke Events Calendar Module
 *  Copyright (C) 2002  The PostCalendar Team
 *  http://postcalendar.tv
 *  Copyright (C) 2009  Sound Web Development
 *  Craig Heydenburg
 *  http://code.zikula.org/soundwebdevelopment/
 *  
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *  To read the license please read the docs/license.txt or visit
 *  http://www.gnu.org/copyleft/gpl.html
 *
 */


pnModAPILoad(__POSTCALENDAR__,'user');
require_once ('includes/HtmlUtil.class.php');

function postcalendar_user_main()
{
	// check the authorization
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) 
		return _POSTCALENDARNOAUTH;

	// get the date and go to the view function
	return postcalendar_user_view(array('Date'=>postcalendar_getDate()));
}


function postcalendar_user_upload()
{
	$output = new pnHTML();
	$action = pnModURL(PostCalendar, "user", "viewupload");
	$output->Text($action);
	$output->LineBreak(2);
	$output->UploadMode();
	$output->FormStart($action);
	$output->SetInputMode(_PNH_VERBATIMINPUT);
	$output->FormFile('icsupload', $size=35);
	$output->FormSubmit();
	$output->FormEnd();
	return $output->getOutput();
}


function postcalendar_user_splitdate($args)
{
	$splitdate = array();
	$splitdate['day'] = substr($args, 6, 2);	
	$splitdate['month'] = substr($args, 4, 2);	
	$splitdate['year'] = substr($args, 0, 4);	
	return $splitdate;
}


// The function is made for GMT+1 with DaySaveTime Set to enabled
function postcalendar_user_splittime($args)
{
	$splittime = array();
	$splittime['hour'] = substr($args, 0, 2);
	$splittime['hour'] < 10 ? $splittime['hour'] = "0".$splittime['hour'] : '' ;
	$splittime['minute'] = substr($args, 2, 2);
	$splittime['second'] = substr($args, 4, 2);
	return $splittime;
}


function postcalendar_user_viewupload()
{
	$dbname = pnConfigGetVar('dbname');
	list($dbconn) = pnDBGetConn();
	$pntable = pnDBGetTables();
	$cat_table = $pntable['postcalendar_categories'];
	$event_table = $pntable['postcalendar_events'];
	require_once('includes/debug.php');

	$vevent  = array();
	$vevent_save_data  = array();
	$counter = 0;
	
	$fp = fopen($_FILES['icsupload']['tmp_name'], "r");
	while(!feof($fp))
	{
		if(preg_match('(BEGIN:VCALENDAR)', $fileline, $result))
	        {
			$write = 1;
		}

		if((preg_match('(BEGIN:VEVENT)', $fileline, $result))&&($write==1))
		{
			$write=2;
		}
		
		$fileline = fgets($fp);
		if((preg_match('(SUMMARY:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['title']  		=  substr($fileline, $start);
		}
		if((preg_match('(DESCRIPTION:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['description']	=  trim(substr($fileline, $start, -5));
		}
		if((preg_match('(\s+Contact:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$vevent[$counter]['contact']   		=  substr($fileline, $start, -3);
		}
		if((preg_match('(\s+Phone:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$vevent[$counter]['phone']   		=  substr($fileline, $start, -3);
		}
		if((preg_match('(\s+Email:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$vevent[$counter]['email']   		=  substr($fileline, $start, -3);
		}
		if((preg_match('(\s+URL:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+9;
			$vevent[$counter]['url']   		=  substr($fileline, $start, -3);
		}
		if((preg_match('(ALLDAY:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['allday']  		=  substr($fileline, $start);
		}
		if((preg_match('(TOPIC:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['topic']  		=  substr($fileline, $start);
		}
		if((preg_match('(FEE:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['fee']  		=  substr($fileline, $start);
		}
		if((preg_match('(\s+Location:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$vevent[$counter]['location']  		=  substr($fileline, $start, -3);
		}
		if((preg_match('(\s+City,\s+ST\s+ZIP:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$help			 		=  substr($fileline, $start, -3);
			$citystop				=  strpos($help, ",");
			$vevent[$counter]['city']  		=  substr($help, 0, $citystop);
			$statezip				=  trim(substr($help, $citystop+1));
			$statestop				=  strpos($statezip, " ");
			$vevent[$counter]['state']		=  substr($statezip, 0, $statestop); 
			$vevent[$counter]['zip']		=  substr($statezip, $statestop+1);
		}
		if((preg_match('(CATEGORIES:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$category		  		=  trim(substr($fileline, $start));
			$cat_id = DBUtil::selectFieldByID ('postcalendar_categories', 'catid', $category, 'catname');
			if(!$cat_id)
				$cat_id = 1;
			$vevent[$counter]['cat_id'] = $cat_id;
		}
		if(preg_match('(DTSTAMP:)', $fileline, $result))
	        {
			$stampstart			= strpos($fileline, ":")+1;
			$vevent[$counter]['stdate']	= postcalendar_user_splitdate(substr($fileline, $stampstart, 8));
			$vevent[$counter]['sttime']	= postcalendar_user_splittime(substr($fileline, $stampstart+9, 6));
		}
		if(preg_match('(DTSTART:)', $fileline, $result))
	        {
			$datestart			  = strpos($fileline, ":")+1;
			$vevent[$counter]['startdate'] = postcalendar_user_splitdate(substr($fileline, $datestart, 8));
			$vevent[$counter]['starttime'] = postcalendar_user_splittime(substr($fileline, $datestart+9, 6));

		}
		if(preg_match('(DTEND:)', $fileline, $result))
	        {
			$dateend			= strpos($fileline, ":")+1;
			$vevent[$counter]['enddate']	= postcalendar_user_splitdate(substr($fileline, $dateend, 8));
			$vevent[$counter]['endtime']	= postcalendar_user_splittime(substr($fileline, $dateend+9, 6));
		}

		$event_repeat_data = array();
		$event_repeat_data['event_reqeat_freq'] 	= "1";
		$event_repeat_data['event_reqeat_freq_type'] 	= "0";
		$event_repeat_data['event_reqeat_on_num'] 	= "1";
		$event_repeat_data['event_reqeat_on_day'] 	= "0";
		$event_repeat_data['event_reqeat_on_freq'] 	= "1";
		
		$event_location_data = array();
		$event_location_data['event_location']  = $vevent[$counter]['location'];
		$event_location_data['event_street1']   = $vevent[$counter]['street1'];
		$event_location_data['event_street2']   = $vevent[$counter]['street2'];
		$event_location_data['event_city']	= $vevent[$counter]['city'];
		$event_location_data['event_state']	= $vevent[$counter]['state'];
		$event_location_data['event_postal']	= $vevent[$counter]['zip'];
		
		if(preg_match('(END:VEVENT)', $fileline, $result))
	        {
			$vevent[$counter]['pc_recurrspec']	= serialize($event_repeat_data);
			$vevent[$counter]['pc_location']	= serialize($event_location_data);
			$write = 1;
			$counter++;
		}
		if(preg_match('(END:VCALENDAR)', $fileline, $result))
	        {
			$write = 0;
		}
	}
	foreach($vevent as $ve)
	{
		$duration = NULL;
		$ve['endtime']['second'] < $ve['starttime']['second'] ? $ve['endtime']['minute']++ : '';
		$ve['endtime']['minute'] < $ve['starttime']['minute'] ? $ve['endtime']['hour']++ : '';
		$duration = 3600*($ve['endtime']['hour']-$ve['starttime']['hour'])
	       		    + 60*($ve['endtime']['minute']-$ve['starttime']['minute'])
			    +    ($ve['endtime']['second']-$ve['starttime']['second']);
		$pc_aid = pnSessionGetVar('uid');
		$pc_informant = pnUserGetVar('name', $pc_aid);
		
		$sql  = "SELECT pc_meeting_id FROM $event_table ORDER BY pc_meeting_id DESC LIMIT 1";
		$res  = DBUtil::executeSQL ($sql, false, false);
		$emid = $res->fields[0];
		
		$pc_endDate   = $ve['enddate']['year']."-".$ve['enddate']['month']."-".$ve['enddate']['day']; 
		$pc_endTime   = $ve['endtime']['hour'].":".$ve['endtime']['minute'].":".$ve['endtime']['second'];
		$pc_eventDate = $ve['startdate']['year']."-".$ve['startdate']['month']."-".$ve['startdate']['day']; 
		$pc_startTime = $ve['starttime']['hour'].":".$ve['starttime']['minute'].":".$ve['starttime']['second'];
		$pc_timestamp = $ve['stdate']['year']."-".$ve['stdate']['month']."-".$ve['stdate']['day']." ".$ve['sttime']['hour'].":".$ve['sttime']['minute'].":".$ve['sttime']['second'];


		
		$where = " WHERE pc_catid     = $ve[cat_id] 
			   AND   pc_aid       = '$pc_aid'
			   AND   pc_title     = '$ve[title]'
			   AND   pc_hometext  = ':text:$ve[description]'
			   AND   pc_eventDate = '$pc_eventDate' 
			   AND   pc_duration  = $duration
			   AND   pc_startTime = '$pc_startTime'";
		$event = DBUtil::selectObject ('postcalendar_events', $where);
		if (!$event)
		{
			$obj = array ();
			$obj['catid']      = $ve['cat_id'];
			$obj['aid']         = $pc_aid;
			$obj['title']       = $ve['title'];
			$obj['time']        = $pc_timestamp;
			$obj['hometext']    = ":text:$ve[description]";
			$obj['topic']       = $ve['topic'];
			$obj['informant']   = $pc_informant;
			$obj['eventDate']   = $pc_eventDate;
			$obj['endDate']     = $pc_endDate;
			$obj['duration']    = $duration;
			$obj['recurrtype']  = 0;
			$obj['recurrspec']  = $ve['pc_recurrspec'];
			$obj['recurrfreq']  = 0;
			$obj['startTime']   = $pc_eventDate;
			$obj['endTime']     = $pc_endDate;
			$obj['alldayevent'] = $ve['allday'];
			$obj['location']    = $ve['pc_location'];
			$obj['conttel']     = $ve['phone'];
			$obj['contname']    = $ve['contact'];
			$obj['contemail']   = $ve['email'];
			$obj['website']     = $ve['url'];
			$obj['fee']         =  $ve['fee'];
			$obj['eventstatus'] = 1;
			$obj['sharing']     = 1;
			$obj['language']    = NULL;
			$obj['meeting_id']  = $emid;
			$result = DBUtil::insertObject ($obj, 'postcalendar_events');
		}
	}

	$tpl = pcRender::getInstance('PostCalendar');        $tpl->clear_all_cache();
        $tpl->clear_compiled_tpl();
	
	return postcalendar_user_main();
}


/**
 * view items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 */
function postcalendar_user_view()
{
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) { return _POSTCALENDARNOAUTH; }
    
    // get the vars that were passed in
    $Date      = FormUtil::getPassedValue ('Date');
    $print     = FormUtil::getPassedValue ('print');
    $viewtype  = FormUtil::getPassedValue ('viewtype');
    $jumpday   = FormUtil::getPassedValue ('jumpday');
    $jumpmonth = FormUtil::getPassedValue ('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue ('jumpyear');
    
    $Date = postcalendar_getDate();
    if(!isset($viewtype))   $viewtype = _SETTING_DEFAULT_VIEW;
    return postcalendar_user_display(array('viewtype'=>$viewtype,'Date'=>$Date,'print'=>$print)) . postcalendar_footer();
}

/**
 * display item
 * This is a standard function to provide detailed information on a single item
 * available from the module.
 */
function postcalendar_user_display($args)
{
    $eid         = FormUtil::getPassedValue ('eid');
    $Date        = FormUtil::getPassedValue ('Date');
    $print       = FormUtil::getPassedValue ('print');
    $pc_category = FormUtil::getPassedValue ('pc_category');
    $pc_topic    = FormUtil::getPassedValue ('pc_topic');
    $pc_username = FormUtil::getPassedValue ('pc_username');
    
    extract($args);
    if(empty($Date) && empty($viewtype)) {
        return false;
    }

    $uid = pnUserGetVar('uid');
    $theme = pnUserGetTheme();
    $cacheid = md5($Date.$viewtype._SETTING_TEMPLATE.$eid.$print.$uid.'u'.$pc_username.$theme.'c'.$category.'t'.$topic);
	
    switch ($viewtype) 
    {
        case 'details':
            if (!(bool)PC_ACCESS_READ) {
                return _POSTCALENDARNOAUTH;
            }
            $event = pnModAPIFunc('PostCalendar','user','eventDetail', 
                                  array('eid'=>$eid, 'Date'=>$Date, 'print'=>$print, 'cacheid'=>$cacheid));

            if($event === false) { 
                pnRedirect(pnModURL(__POSTCALENDAR__,'user'));
            }
            $out  = "\n\n<!-- START user_display -->\n\n";
            $out .= $event;
            $out .= "\n\n<!-- END user_display -->\n\n";
            break;

       default :
            if (!(bool)PC_ACCESS_OVERVIEW) {
        	return _POSTCALENDARNOAUTH;
            }
            $out  = "\n\n<!-- START user_display -->\n\n";
            $out .= pnModAPIFunc('PostCalendar','user','buildView', 
                                  array('Date'=>$Date, 'viewtype'=>$viewtype, 'cacheid'=>$cacheid));
            $out .= "\n\n<!-- END user_display -->\n\n";
            break;
    }
    // Return the output that has been generated by this function
    return $out;
}

function postcalendar_user_delete()
{
    if(!(bool)PC_ACCESS_ADD) {
        return _POSTCALENDARNOAUTH;
    }
	
	$output = new pnHTML();
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    
	$uname = pnUserGetVar('uname');
    $action = FormUtil::getPassedValue('action');
    $pc_event_id = FormUtil::getPassedValue('pc_event_id');
    $event = postcalendar_userapi_pcGetEventDetails($pc_event_id);
	if($uname != $event['informant']) {
		return _PC_CAN_NOT_DELETE;
	}
	unset($event);
	
    $output->FormStart(pnModUrl(__POSTCALENDAR__,'user','deleteevents'));
    // V4B RNG Start
    $output->FormHidden('pc_eid',$pc_event_id);
    // V4B RNG End
    $output->Text(_PC_DELETE_ARE_YOU_SURE.' ');
    $output->FormSubmit(_PC_ADMIN_YES);
    $output->Linebreak(2);
    // V4B RNG Start: Form is closed in the following block
    // Form is closed in the following block
    $output->Text(pnModAPIFunc(__POSTCALENDAR__,'user','eventDetail',array('eid'=>$pc_event_id,'cacheid'=>'','print'=>0,'Date'=>'')));
    $output->Linebreak(2);
    // Re-open form here ...
    $output->FormStart(pnModUrl(__POSTCALENDAR__,'user','deleteevents'));
    // V4B RNG End
    $output->FormHidden('pc_eid',$pc_event_id);
	$output->Text(_PC_DELETE_ARE_YOU_SURE.' ');
    $output->FormSubmit(_PC_ADMIN_YES);
    $output->FormEnd();
    
	return $output->GetOutput();
}
function postcalendar_user_deleteevents()
{
    if(!(bool)PC_ACCESS_ADD) {
        return _POSTCALENDARNOAUTH;
    }

	$uname = pnUserGetVar('uname');
	$pc_eid = FormUtil::getPassedValue('pc_eid');
	$event = postcalendar_userapi_pcGetEventDetails($pc_eid);
	if($uname != $event['informant']) {
		return _PC_CAN_NOT_DELETE;
	}
	unset($event);
	
    $output = new pnHTML();
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $events_table = $pntable['postcalendar_events'];
    $events_column = &$pntable['postcalendar_events_column'];
    
    $sql = "DELETE FROM $events_table WHERE $events_column[eid] = '$pc_eid'";

    $dbconn->Execute($sql);
    if ($dbconn->ErrorNo() != 0) {
        $output->Text(_PC_ADMIN_EVENT_ERROR);
    } else {
        $output->Text(_PC_ADMIN_EVENTS_DELETED);
    }
    
	// clear the template cache
	$tpl = pcRender::getInstance('PostCalendar');	$tpl->clear_all_cache();
    return $output->GetOutput(); 
}

/**
 * submit an event
 */
function postcalendar_user_edit($args) {return postcalendar_user_submit($args); }
function postcalendar_user_submit($args)
{   
    // We need at least ADD permission to submit an event
    if (!(bool)PC_ACCESS_ADD) {
        return _POSTCALENDARNOAUTH;
    }
	
	$output = new pnHTML();
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    
	// get the theme globals :: is there a better way to do this?
    pnThemeLoad(pnUserGetTheme());
    global $bgcolor1, $bgcolor2, $bgcolor3, $bgcolor4, $bgcolor5, $textcolor1, $textcolor2;
	extract($args);

	$Date = postcalendar_getDate();
	$year   = substr($Date,0,4);
    $month  = substr($Date,4,2);
    $day    = substr($Date,6,2);
    
	// basic event information
	$event_subject  	= FormUtil::getPassedValue('event_subject');
	$event_desc 		= FormUtil::getPassedValue('event_desc');
	$event_sharing  	= FormUtil::getPassedValue('event_sharing');
	$event_category 	= FormUtil::getPassedValue('event_category');
	$event_topic 		= FormUtil::getPassedValue('event_topic');
	
	// event start information
	$event_meetingdate_start = FormUtil::getPassedValue('meetingdate_start'); // V4B SB START
	if(strchr($event_meetingdate_start, '-'))
	{
		$event_startmonth 	= substr($event_meetingdate_start, 5, 2);
		$event_startday 	= substr($event_meetingdate_start, 8, 2);
		$event_startyear 	= substr($event_meetingdate_start, 0, 4); // V4B SB END
	}
	else
	{
		$event_startmonth 	= substr($event_meetingdate_start, 4, 2);
		$event_startday 	= substr($event_meetingdate_start, 6, 2);
		$event_startyear 	= substr($event_meetingdate_start, 0, 4); // V4B SB END
	}

	$event_starttimeh	= FormUtil::getPassedValue('event_starttimeh');
	$event_starttimem 	= FormUtil::getPassedValue('event_starttimem');
	$event_startampm 	= FormUtil::getPassedValue('event_startampm');
	
	// event end information
	$event_meetingdate_end 	= FormUtil::getPassedValue('meetingdate_end'); // V4B SB START
	if(strchr($event_meetingdate_end, '-'))
	{
		$event_endmonth 	= substr($event_meetingdate_end, 5, 2);
		$event_endday 		= substr($event_meetingdate_end, 8, 2);
		$event_endyear 		= substr($event_meetingdate_end, 0, 4); 
	}
	else
	{
		$event_endmonth 	= substr($event_meetingdate_end, 4, 2);
		$event_endday 		= substr($event_meetingdate_end, 6, 2);
		$event_endyear 		= substr($event_meetingdate_end, 0, 4);
	}
	if($event_endyear == '0000')
	{
		$event_endmonth 	= $event_startmonth;
		$event_endday 		= $event_startday;
		$event_endyear 		= $event_startyear; // V4B SB END
	}
	$event_endtype  	= FormUtil::getPassedValue('event_endtype');
	$event_dur_hours 	= FormUtil::getPassedValue('event_dur_hours');
	$event_dur_minutes  	= FormUtil::getPassedValue('event_dur_minutes');
	$event_duration 	= (60*60*$event_dur_hours) + (60*$event_dur_minutes);
	$event_allday 		= FormUtil::getPassedValue('event_allday');
	
	// location data
	$event_location 	= FormUtil::getPassedValue('event_location');
	$event_street1  	= FormUtil::getPassedValue('event_street1');
	$event_street2  	= FormUtil::getPassedValue('event_street2');
	$event_city 		= FormUtil::getPassedValue('event_city');
	$event_state 		= FormUtil::getPassedValue('event_state');
	$event_postal 		= FormUtil::getPassedValue('event_postal');
	$event_location_info = serialize(compact('event_location', 'event_street1', 'event_street2',
                                             'event_city', 'event_state', 'event_postal'));
	// contact data
	$event_contname 	= FormUtil::getPassedValue('event_contname');
	$event_conttel  	= FormUtil::getPassedValue('event_conttel');
	$event_contemail 	= FormUtil::getPassedValue('event_contemail');
	$event_website  	= FormUtil::getPassedValue('event_website');
	$event_fee  		= FormUtil::getPassedValue('event_fee');
    // v4b TS start
    $event_contact 		= FormUtil::getPassedValue('event_contact');
    
    // v4b TS end
    
    
	
	// event repeating data
	$event_repeat 		= FormUtil::getPassedValue('event_repeat');
	$event_repeat_freq  = FormUtil::getPassedValue('event_repeat_freq');
	$event_repeat_freq_type = FormUtil::getPassedValue('event_repeat_freq_type');
	$event_repeat_on_num = FormUtil::getPassedValue('event_repeat_on_num');
	$event_repeat_on_day = FormUtil::getPassedValue('event_repeat_on_day');
	$event_repeat_on_freq = FormUtil::getPassedValue('event_repeat_on_freq');
	$event_recurrspec = serialize(compact('event_repeat_freq', 'event_repeat_freq_type', 'event_repeat_on_num',
                                          'event_repeat_on_day', 'event_repeat_on_freq'));
	
	$form_action = FormUtil::getPassedValue('form_action');
	$pc_html_or_text = FormUtil::getPassedValue('pc_html_or_text');
    $pc_event_id = FormUtil::getPassedValue('pc_event_id');
	$data_loaded = FormUtil::getPassedValue('data_loaded');
    $is_update   = FormUtil::getPassedValue('is_update');
	$authid      = FormUtil::getPassedValue('authid');
	// V4B RNG Start
	$event_for_userid = FormUtil::getPassedValue('event_for_userid'); 
	// V4B RNG End
	// V4B SB Start
	$event_participants = FormUtil::getPassedValue('participants'); 
	// V4B SB End

	if(pnUserLoggedIn()) { $uname = pnUserGetVar('uname'); } 
    else { $uname = pnConfigGetVar('anonymous'); }
    if(!isset($event_repeat)) { $event_repeat = 0; }
    
	if(!isset($pc_event_id) || empty($pc_event_id) || $data_loaded) {
		// lets wrap all the data into array for passing to submit and preview functions
		$eventdata = compact('event_subject','event_desc','event_sharing','event_category','event_topic',
		'event_startmonth','event_startday','event_startyear','event_starttimeh','event_starttimem','event_startampm',
		'event_endmonth','event_endday','event_endyear','event_endtype','event_dur_hours','event_dur_minutes',
		'event_duration','event_allday','event_location','event_street1','event_street2','event_city','event_state',
		'event_postal','event_location_info','event_contname','event_conttel','event_contemail',
		'event_website','event_fee','event_contact','event_repeat','event_repeat_freq','event_repeat_freq_type',
		'event_repeat_on_num','event_repeat_on_day','event_repeat_on_freq','event_recurrspec','uname',
		'Date','year','month','day','pc_html_or_text');
		$eventdata['is_update'] = $is_update;
		$eventdata['pc_event_id'] = $pc_event_id;
		$eventdata['data_loaded'] = true;
		// V4B RNG Start
                $eventdata['event_for_userid'] = $event_for_userid;
		// V4B RNG End
		// V4B SB Start
		$event_participants = FormUtil::getPassedValue('participants'); 
		// V4B SB End
	} else {
		$event = postcalendar_userapi_pcGetEventDetails($pc_event_id);
		if($uname != $event['informant']) {
			return _PC_CAN_NOT_EDIT;
		}
		$eventdata['event_subject'] = $event['title'];
		$eventdata['event_desc'] = $event['hometext'];
		$eventdata['event_sharing'] = $event['sharing'];
		$eventdata['event_category'] = $event['catid'];
		$eventdata['event_topic'] = $event['topic'];
		$eventdata['event_startmonth'] = substr($event['eventDate'],5,2);
		$eventdata['event_startday'] = substr($event['eventDate'],8,2);
		$eventdata['event_startyear'] = substr($event['eventDate'],0,4);
		$eventdata['event_starttimeh'] = substr($event['startTime'],0,2);
		$eventdata['event_starttimem'] = substr($event['startTime'],3,2);
		$eventdata['event_startampm'] = $eventdata['event_starttimeh'] < 12 ? _PC_AM : _PC_PM ;
		$eventdata['event_endmonth'] = substr($event['endDate'],5,2);
		$eventdata['event_endday'] = substr($event['endDate'],8,2);
		$eventdata['event_endyear'] = substr($event['endDate'],0,4);
		$eventdata['event_endtype'] = $event['endDate'] == '0000-00-00' ? '0' : '1' ;
		$eventdata['event_dur_hours'] = $event['duration_hours'];
		$eventdata['event_dur_minutes'] = $event['duration_minutes'];
		$eventdata['event_duration'] = $event['duration'];
		$eventdata['event_allday'] = $event['alldayevent'];
		$loc_data = unserialize($event['location']);
		$eventdata['event_location'] = $loc_data['event_location'];
		$eventdata['event_street1'] = $loc_data['event_street1'];
		$eventdata['event_street2'] = $loc_data['event_street2'];
		$eventdata['event_city'] = $loc_data['event_city'];
		$eventdata['event_state'] = $loc_data['event_state'];
		$eventdata['event_postal'] = $loc_data['event_postal'];
		$eventdata['event_location_info'] = $loc_data;
		$eventdata['event_contname'] = $event['contname'];
		$eventdata['event_conttel'] = $event['conttel'];
		$eventdata['event_contemail'] = $event['contemail'];
		$eventdata['event_website'] = $event['website'];
		$eventdata['event_fee'] = $event['fee'];
        $eventdata['event_contact'] = $event['event_contact'];
		$eventdata['event_repeat'] = $event['recurrtype'];
		$rspecs = unserialize($event['recurrspec']);
		$eventdata['event_repeat_freq'] = $rspecs['event_repeat_freq'];
		$eventdata['event_repeat_freq_type'] = $rspecs['event_repeat_freq_type'];
		$eventdata['event_repeat_on_num'] = $rspecs['event_repeat_on_num'];
		$eventdata['event_repeat_on_day'] = $rspecs['event_repeat_on_day'];
		$eventdata['event_repeat_on_freq'] = $rspecs['event_repeat_on_freq'];
		$eventdata['event_recurrspec'] = $rspecs;
		$eventdata['uname'] = $uname;
		$eventdata['Date'] = $Date;
		$eventdata['year'] = $year;
		$eventdata['month'] = $month;
		$eventdata['day'] = $day;
		$eventdata['is_update'] = true;
		$eventdata['pc_event_id'] = $pc_event_id;
		$eventdata['data_loaded'] = true;
		$eventdata['pc_html_or_text'] = $pc_html_or_text;
		// V4B RNG Start
                $eventdata['event_for_userid'] = $event_for_userid;
		// V4B RNG End
		// V4B SB Start
		$eventdata['participants'] = $event_participants; 
		// V4B SB End
	}
	
	// v4b TS start
    if($form_action == 'copy') 
    {
        $form_action = '';
        unset($pc_event_id);
        $eventdata['pc_event_id'] = '';
        $eventdata['is_update'] = false;
        $eventdata['data_loaded'] = false;
    }
    // v4b TS end
    
    // lets get the module's information
    //$modinfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
    $categories = pnModAPIFunc(__POSTCALENDAR__,'user','getCategories');
	$output->tabindex=1;
	
	
	//================================================================
	//	ERROR CHECKING
	//================================================================
    // $required_vars = array('event_subject','event_desc');
    $required_vars = array('event_subject');
    // $required_name = array(_PC_EVENT_TITLE,_PC_EVENT_DESC);
    $required_name = array(_PC_EVENT_TITLE);
    $error_msg = '';
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $reqCount = count($required_vars);
    for ($r=0; $r<$reqCount; $r++) {
        if(empty($$required_vars[$r]) || !preg_match('/\S/i',$$required_vars[$r])) {
            $error_msg .= $output->Text('<b>'.$required_name[$r].'</b> '._PC_SUBMIT_ERROR4);
            $error_msg .= $output->Linebreak(); 
        }
    }
	unset($reqCount);
	// check repeating frequencies
	if($event_repeat == REPEAT) {
		if(!isset($event_repeat_freq) ||  $event_repeat_freq < 1 || empty($event_repeat_freq)) {
			$error_msg .= $output->Text(_PC_SUBMIT_ERROR5);
        	$error_msg .= $output->Linebreak(); 
		} elseif(!is_numeric($event_repeat_freq)) {
			$error_msg .= $output->Text(_PC_SUBMIT_ERROR6);
        	$error_msg .= $output->Linebreak();
		}
	} elseif($event_repeat == REPEAT_ON) {
		if(!isset($event_repeat_on_freq) || $event_repeat_on_freq < 1 || empty($event_repeat_on_freq)) {
			$error_msg .= $output->Text(_PC_SUBMIT_ERROR5);
        	$error_msg .= $output->Linebreak(); 
		} elseif(!is_numeric($event_repeat_on_freq)) {
			$error_msg .= $output->Text(_PC_SUBMIT_ERROR6);
        	$error_msg .= $output->Linebreak();
		}
	}
	// check date validity
    if(_SETTING_TIME_24HOUR) {
        $startTime = $event_starttimeh.':'.$event_starttimem;
        $endTime =   $event_endtimeh.':'.$event_endtimem;
    } else {
        if($event_startampm == _AM_VAL) {
            $event_starttimeh = $event_starttimeh == 12 ? '00' : $event_starttimeh;
        } else {
            $event_starttimeh =  $event_starttimeh != 12 ? $event_starttimeh+=12 : $event_starttimeh;
        }
        $startTime = $event_starttimeh.':'.$event_starttimem;
    }
    $sdate = strtotime($event_startyear.'-'.$event_startmonth.'-'.$event_startday);
    $edate = strtotime($event_endyear.'-'.$event_endmonth.'-'.$event_endday);
    $tdate = strtotime(date('Y-m-d'));
    if($edate < $sdate && $event_endtype == 1) {
        $error_msg .= $output->Text(_PC_SUBMIT_ERROR1);
        $error_msg .= $output->Linebreak(); 
    }
    if(!checkdate($event_startmonth,$event_startday,$event_startyear)) {
        $error_msg .= $output->Text(_PC_SUBMIT_ERROR2);
        $error_msg .= $output->Linebreak(); 
    }
    if(!checkdate($event_endmonth,$event_endday,$event_endyear)) {
        $error_msg .= $output->Text(_PC_SUBMIT_ERROR3); 
        $error_msg .= $output->Linebreak();
    }
	$output->SetOutputMode(_PNH_KEEPOUTPUT);
	
	if($form_action == 'preview') {
        //================================================================
		//	Preview the event
		//================================================================
		// check authid
        if (!pnSecConfirmAuthKey()) { return(_NO_DIRECT_ACCESS); }
        if(!empty($error_msg)) {
            $preview = false;
            $output->Text('<table border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td bgcolor="red">');
            $output->Text('<table border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td bgcolor="pink">');
                $output->Text('<center><b>'._PC_SUBMIT_ERROR.'</b></center>'); 
                $output->Linebreak();
                $output->Text($error_msg);
            $output->Text('</td></td></table>');
            $output->Text('</td></td></table>');
            $output->Linebreak(2);
        } else {
            $output->Text(pnModAPIFunc(__POSTCALENDAR__,'user','eventPreview',$eventdata));
			$output->Linebreak();
        }
    } elseif($form_action == 'commit') {
		//================================================================
		//	Enter the event into the DB
		//================================================================
		if (!pnSecConfirmAuthKey()) { return(_NO_DIRECT_ACCESS); }
		if(!empty($error_msg)) {
            $preview = false;
            $output->Text('<table border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td bgcolor="red">');
            $output->Text('<table border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td bgcolor="pink">');
                $output->Text('<center><b>'._PC_SUBMIT_ERROR.'</b></center>'); 
                $output->Linebreak();
                $output->Text($error_msg);
            $output->Text('</td></td></table>');
            $output->Text('</td></td></table>');
            $output->Linebreak(2);
        } else {
            if (!pnModAPIFunc(__POSTCALENDAR__,'user','submitEvent',$eventdata)) {
        		$output->Text('<center><div style="padding:5px; border:1px solid red; background-color: pink;">');		
				$output->Text("<b>"._PC_EVENT_SUBMISSION_FAILED."</b>");		
				$output->Text('</div></center><br />');	
				$output->Linebreak();
        		$output->Text($dbconn->ErrorMsg());
    		} else {
        		// clear the Render cache
				$tpl = pcRender::getInstance('PostCalendar');
				$tpl->clear_all_cache();
				$output->Text('<center><div style="padding:5px; border:1px solid green; background-color: lightgreen;">');		
				if($is_update) {
					$output->Text("<b>"._PC_EVENT_EDIT_SUCCESS."</b>");		
				} else {
					$output->Text("<b>"._PC_EVENT_SUBMISSION_SUCCESS."</b>");		
				}
				$output->Text('</div></center><br />');	
				$output->Linebreak();
                
                // v4b TS start - save the start date, before the vars are cleared (needed for the redirect on success)
                $url_date = $event_startyear.$event_startmonth.$event_startday;
                // v4b TS end
                
        		// clear the form vars
        		$event_subject=$event_desc=$event_sharing=$event_category=$event_topic=
				$event_startmonth=$event_startday=$event_startyear=$event_starttimeh=$event_starttimem=$event_startampm=
				$event_endmonth=$event_endday=$event_endyear=$event_endtype=$event_dur_hours=$event_dur_minutes=
				$event_duration=$event_allday=$event_location=$event_street1=$event_street2=$event_city=$event_state=
				$event_postal=$event_location_info=$event_contname=$event_conttel=$event_contemail=
				$event_website=$event_fee=$event_contact=$event_repeat=$event_repeat_freq=$event_repeat_freq_type=
				$event_repeat_on_num=$event_repeat_on_day=$event_repeat_on_freq=$event_recurrspec=$uname=
				$Date=$year=$month=$day=$pc_html_or_text=null;
				$is_update = false;
				$pc_event_id = 0;
				// lets wrap all the data into array for passing to submit and preview functions
				$eventdata = compact('event_subject','event_desc','event_sharing','event_category','event_topic',
				'event_startmonth','event_startday','event_startyear','event_starttimeh','event_starttimem','event_startampm',
				'event_endmonth','event_endday','event_endyear','event_endtype','event_dur_hours','event_dur_minutes',
				'event_duration','event_allday','event_location','event_street1','event_street2','event_city','event_state',
				'event_postal','event_location_info','event_contname','event_conttel','event_contemail',
				'event_website','event_fee','event_contact','event_repeat','event_repeat_freq','event_repeat_freq_type',
				'event_repeat_on_num','event_repeat_on_day','event_repeat_on_freq','event_recurrspec','uname',
				'Date','year','month','day','pc_html_or_text','is_update','pc_event_id');
                
                // v4b TS start - redirect to month view, when everything worked as expected
                //pnRedirect(pnModURL('PostCalendar', 'user', 'view',array('tplview'=>'default','viewtype'=>'month','Date'=>$url_date)));
                //return true;
                // v4b TS end
			}
        }
	}

    $output->Text(pnModAPIFunc('PostCalendar','user','buildSubmitForm',$eventdata));
    return $output->GetOutput();
}

/**
 * search events
 */
function postcalendar_user_search()
{   
    // We need at least ADD permission to submit an event
    if (!(bool)PC_ACCESS_OVERVIEW) {
        return _POSTCALENDARNOAUTH;
    }
	
    $tpl = pcRender::getInstance('PostCalendar');

    $k = FormUtil::getPassedValue('pc_keywords');
    $k_andor = FormUtil::getPassedValue('pc_keywords_andor');
	$pc_category = FormUtil::getPassedValue('pc_category');
	$pc_topic = FormUtil::getPassedValue('pc_topic');
	$submit = FormUtil::getPassedValue('submit');
	
	$categories = pnModAPIFunc(__POSTCALENDAR__, 'user', 'getCategories');
	$cat_options = '';
	foreach($categories as $category) {
		$cat_options .= "<option value='".$category[catid]."'>".$category[catname]."</option>";
	}
	$tpl->assign('CATEGORY_OPTIONS',$cat_options);
	
	if(_SETTING_DISPLAY_TOPICS) {
		$topics = pnModAPIFunc(__POSTCALENDAR__,'user','getTopics');
		$top_options = '';
		foreach($topics as $topic) {
			$top_options .= "<option value='".$topic['topicid']."'>".$topic['topictext']."</option>";
		}
		$tpl->assign('TOPIC_OPTIONS',$top_options);
	}
	//=================================================================
    //  Find out what Template we're using    
	//=================================================================
    $template_name = _SETTING_TEMPLATE;
    if(!isset($template_name)) {
    	$template_name = 'default';
    }
	//=================================================================
    //  Output the search form
	//=================================================================
	$thisformaction=pnModURL(__POSTCALENDAR__,'user','search');
	$thisformaction = DataUtil::formatForDisplay($thisformaction);
	$tpl->assign('FORM_ACTION',$thisformaction);
	//=================================================================
    //  Perform the search if we have data
	//=================================================================
	if(!empty($submit)) {
		$sqlKeywords = '';
		$keywords = explode(' ',$k);
		// build our search query
		foreach($keywords as $word) {
			if(!empty($sqlKeywords)) $sqlKeywords .= " $k_andor ";
			$sqlKeywords .= '(';
			$sqlKeywords .= "pc_title LIKE '%$word%' OR ";
			$sqlKeywords .= "pc_hometext LIKE '%$word%' OR ";
			$sqlKeywords .= "pc_location LIKE '%$word%'";
			$sqlKeywords .= ') ';
		}
		
		if(!empty($pc_category)) {
			$s_category = "pc_catid = '$pc_category'";
		}
		
		if(!empty($pc_topic)) {
			$s_topic = "pc_topic = '$pc_topic'";
		}
		
		$searchargs = array();
		if(!empty($sqlKeywords)) $searchargs['s_keywords'] = $sqlKeywords;
		if(!empty($s_category)) $searchargs['s_category'] = $s_category;
		if(!empty($s_topic)) $searchargs['s_topic'] = $s_topic;
		
		$eventsByDate =& postcalendar_userapi_pcGetEvents($searchargs);
		$tpl->assign('SEARCH_PERFORMED',true);
		$tpl->assign('A_EVENTS',$eventsByDate);
	}
	$tpl->caching = false;
	$pageSetup = pnModAPIFunc(__POSTCALENDAR__,'user','pageSetup');
	$pcTheme = pnModGetVar(__POSTCALENDAR__,'pcTemplate');
	if(!$pcTheme)
	    $pcTheme='default';
	return $pageSetup . $tpl->fetch("$pcTheme/search.html");
}


###############################################################################
###############################################################################
##
##      ical.php
##
##      icalendar export of PostCalendar data
##
##      Author:         Eric Germann
##      Version:        0.1
##      Date:           01-03-04
##      Contact:        ekgermann at cctec.com
##
##      ical.php is an icalendar export library for PostCalendar.  Put it
##      in the root of your PostNuke site.  The parameters are as follows:
##
##      date            one day export formatted as MM/DD/YYYY (i.e. 01/04/2004)
##      start           beginning day export formatted as above
##      end             ending dayexport formatted as above
##      eid             a specific PostCalendar Event ID (use with date to get a
##                      specific event on a specific day.
##      type            inline (default) or attach.  Outlook 2000 can only do one
##                      event inline.  However, you can save multiple events and
##                      import the file and it will work.  This will override
##                      that behavior.
##      debug           set to 1 to turn on debugging, which shows the ICS file
##                      as HTML, not an x-vCalendar type.
##      category        allows you to match a specific PostCalendar Event Category
##
###############################################################################
###############################################################################
function postcalendar_user_export ()
{
  # control whether debug and extendedinfo flags are allowed
  $debugallowed = 0;
  $extendedinfoallowed = 1;

  $date     = FormUtil::getPassedValue('date');
  $start    = FormUtil::getPassedValue('start');
  $end      = FormUtil::getPassedValue('end');
  $eid      = FormUtil::getPassedValue('eid');
  $format   = FormUtil::getPassedValue('format');
  $debug    = FormUtil::getPassedValue('debug');
  $category = FormUtil::getPassedValue('category');
  $etype    = FormUtil::getPassedValue('etype', 'ical');

  # Clean up the dates and force the format to be correct
  if ($start == '') { 
    $start = date ("m/d/Y"); 
  }
  else { 
    $start = date ("m/d/Y", strtotime ($start)); 
  }

  if ($end == '') { 
    $end = date("m/d/Y", (time() + 30*60*60*24)); 
  }
  else { 
    $end = date ("m/d/Y", strtotime ($end)); 
  }

  if ($date != "") 
  { 
    $start = date ("m/d/Y", strtotime ($date)); 
    $end = $start; 
  }

  if (!$debug)
  {
      $filename = mktime () . ($etype == 'ical' ? '.ics' : '.xml');
      header ("Content-Type: text/calendar");
      if (($format == "") || ($format == "inline"))
      {
          header ("Content-Disposition: inline; filename=$filename");
      }
      else
      {
          header ("Content-Disposition: attachment; filename=$filename");
      }
  }

  if ($debug) 
  { 
    echo ("<PRE>"); 
  }

  pnModAPILoad ('PostCalendar', 'user');
  $events = pnModAPIFunc ('PostCalendar', 'user', 'pcGetEvents', array ('start' => $start, 'end' => $end));

  # sort the events by start date and time, sevent has the sorted list
  $sevents = array ();
  foreach ($events as $cdate => $event)
  {
      # $event has event array for $cdate day
      # sort the event array and store back in $sevent with $cdate as the index
      usort ($event, "eventdatecmp");
      $sevents [$cdate] = array ();
      $sevents [$cdate] = $event;
  }
  reset ($sevents);

  if ($debug && $debugallowed) { echo "<P><HR WIDTH=100%><P>Original Events are <P>"; prayer ($events); }; 
  if ($debug && $debugallowed) { echo "<P><HR WIDTH=100%><P>Sorted Events are <P>\r\n"; prayer ($sevents); };

  if ($etype == 'ical')
    return postcalendar_user_export_ical ($sevents);
  else
    return postcalendar_user_export_rss ($sevents, $start, $end);
}


function postcalendar_user_export_ical ($sevents)
{
  $eid      = FormUtil::getPassedValue('eid');
  $category = FormUtil::getPassedValue('category');
  $sitename = getenv ('SERVER_NAME');

  echo "BEGIN:VCALENDAR\n";
  echo "VERSION:2.0\n";
  echo "METHOD:PUBLISH\n";
  echo "PRODID:-//CCTec/PostCalendar 4.0.0 iCal export//EN\n";

  foreach ($sevents as $cdate => $event)
  {
      # $cdate has the events actual date
      # $event has the event array for $cdate day
      foreach ($event as $item)
      {
          # Allow a selection by unique eventid and/or category
          if (($item['eid'] == $eid || $eid == "") &&
              ($item['catname'] == $category || $category == ""))
          {
              # slurp out the fields to make it more convenient
              $starttime        = $item['startTime'];
              $duration         = $item['duration'];
              $title            = $item['title'];
              $summary          = $item['title'];
              $description      = html_entity_decode(strip_tags(substr($item['hometext'],6)));
              $evcategory       = $item['catname'];
              $location         = $item['event_location'];
              $uid              = $item['eid'] . "--" .  strtotime ($item['time']) . "@$sitename";
              $url              = $item['website'];
              $peid             = $item['eid'];
              $allday           = $item['alldayevent'];
              $fee              = $item['fee'];
              $topic            = $item['topic'];
       
              # this block of code cleans up encodings such as &#113; in the
              # email addresses.  These were escaped on store by postcalendar
              # and I'm too lazy to figure out a regexp to fix it.
              # it builds two arrays with search and replace and then calls
              # str_replace once to translate everything over.
              $email = $item ['contemail'];
              for ($i=1; $i<=127; $i++)
              {
                  $srch [$i] = sprintf ("&#%03.3d;", $i);
                  $repl [$i] = chr ($i);
              }
              $item ['contemail'] = str_replace ($srch, $repl, $item ['contemail']);
              $email = str_replace ($srch, $repl, $email);
              $organizer = $email;
       
              # indent the original description so VEVENT doesn't blow up on DESCRIPTION
              $description = preg_replace ('!^!m', str_repeat (' ', 2), $description);

              # Build the event description text.
              $evtdesc = $description . "\N\n" .
                "  For more information:\N\n" .
                "  Contact: " . $item['contname'] . "\N\n" .
                "  Phone: " . $item['conttel'] . "\N\n" .
                "  Email: " . $email . "\N\n" .
                "  URL: " . $item['website'] . "\N\n";
              if ($item['event_location'])
                  $eventdesc .= "  Location: " . $item['event_location'] . "\N\n";
              if ($item['event_street1'])
                  $eventdesc .= "  Street Addr 1: " . $item['event_street1'] . "\N\n";
              if ($item['event_street2'])
                  $eventdesc .= "  Street Addr 2: " . $item['event_street2'] . "\N\n";
              if ($item['event_city'])
                  $eventdesc .= "  City, ST ZIP: " . $item['event_city'] . "," . $item['event_state'] . " " . $item['event_postal'] . "\N\n";

              # Build the ALTREP line as a link to the actual calendar
              $args = array();
              $args['Date'] = date ("Ymd", strtotime ($cdate));
              $args['viewtype'] = 'details';
              $args['eid'] = $peid;
              $url = pnModURL ('PostCalendar', 'user', 'view', $args);

              # output the vCard/iCal VEVENT object
              echo "BEGIN:VEVENT\n";
              if ($organizer <> "")
              {
                  echo "ORGANIZER:MAILTO:$organizer\n";
                  echo "CONTACT:MAILTO:$organizer\n";
              }
              if ($url <> "") 
              {
                 echo "URL:$url\n"; 
              }

              echo "SUMMARY:$summary\n";
              echo "DESCRIPTION:$evtdesc\n";
              echo "TZ:-5\r\n";
              echo "CATEGORIES:$evcategory\n";
              echo "LOCATION:$location\n";
              echo "TRANSP:OPAQUE\n";
              echo "CLASS:CONFIDENTIAL\n";
              echo "DTSTAMP:" . gmdate ("Ymd") . "T" . gmdate ("His") . "Z\n";
              echo "ALLDAY:" . $allday."\n";
              echo "FEE:" . $fee."\n";
              echo "TOPIC:" . $topic."\n";
              # format up the date/time into ical format for output
              # build the normal date/time string ...
              $evtstr = $cdate . " ". $item['startTime'];
              # convert it to unix time ...
              $evttime = strtotime ($evtstr);
              # add duration to get the end time ...
              $evtend = $evttime + $duration;

              # format it for output
              $startdate = gmdate ("Ymd", $evttime) . "T" . gmdate ("His", $evttime) ."Z";
              echo "DTSTART:$startdate\n";

              $enddate = gmdate ("Ymd", $evtend) . "T" . gmdate ("His", $evtend) . "Z";
              echo "DTEND:$enddate\n";

              # bury a serialized php structure in the COMMENT field.
              if (($extendedinfo == 1) && ($extendedinfoallowed == 1))
              {
                  $extinfo['url']               = $url;
                  $extinfo['date']              = gmdate ("Ymd", $evttime);
                  $extinfo['eid']               = $peid;
                  $extinfo['eventtime']         = $evttime;
                  $extinfo['icallink']          = "http://$sitename/modules/PostCalendar/ical.php?eid=$peid&date=" .  date ("Ymd", strtotime ($cdate));
                  $extinfo['evtstartunixtime']  = $evttime;
                  $extinfo['evtendunixtime']    = $evtend;

                  foreach ($item as $key => $data)
                  { 
                      $extinfo[$key] = $item[$key]; 
                  }

                  echo "COMMENT:" . serialize ($extinfo) . "\n";
              }

              echo "END:VEVENT\n";
          }
      }
  }
  echo "END:VCALENDAR\n";
  return true;
}


function postcalendar_user_export_rss ($sevents, $start, $end)
{
  $eid      = FormUtil::getPassedValue('eid');
  $category = FormUtil::getPassedValue('category');
  $sitename = getenv ('SERVER_NAME');

  require_once ('modules/PostCalendar/pnincludes/rssfeedcreator.php');
  $rss = new UniversalFeedCreator(); 
  $rss->useCached(); 
  $rss->title = "$sitename $start - $end Calendar";
  $rss->description = "$sitename $start - $end Calendar";
  $rss->descriptionTruncSize = 500;
  $rss->descriptionHtmlSyndicated = true;
  $rss->link = urlencode(pnModURL ('PostCalendar', 'user', 'main'));

  foreach ($sevents as $cdate => $event)
  {
      # $cdate has the events actual date
      # $event has the event array for $cdate day
      foreach ($event as $item)
      {
          # Allow a selection by unique eventid and/or category 
          if (($item['eid'] == $eid || $eid === '') &&
              ($item['catid'] == $category || $category === ''))
          {
          # slurp out the fields to make it more convenient
          $starttime   = $item ['startTime'];
          $duration    = $item ['duration'];
          $title       = $item ['title'];
          $summary     = htmlentities($item ['title']);
          $description = htmlentities(str_replace("<br />", "\n",substr($item ['hometext'],6))); 
          $evcategory  = $item ['catname'];
          $location    = htmlentities($item ['event_location']);
          $uid         = $item ['eid'] . "--" .  strtotime ($item ['time']) . "@$sitename"; 
          $url         = $item ['website'];
          $peid        = $item ['eid'];

          # this block of code cleans up encodings such as &#113; in the
          # email addresses.  It builds two arrays with search and replace and then calls 
          # str_replace once to translate everything over.
          $email = $item ['contemail'];
          for ($i = 1; $i <= 127; $i++)
          {
              $srch [$i] = sprintf ("&#%03.3d;", $i); 
              $repl [$i] = chr ($i);
          }

          $item ['contemail'] = str_replace ($srch, $repl, $item ['contemail']);
          $email = str_replace ($srch, $repl, $email);
          $organizer = $email; 
    
          # indent the original description
          $description = preg_replace ('!^!m', str_repeat (' ', 2), $description);

          # Build the item description text.
          $evtdesc = $description . "&lt;br /&gt; &lt;br /&gt;" . 
              "  &lt;br /&gt;&lt;b&gt;";
          if ($item['contname'])
              $evtdesc .= "  Contact: " . htmlentities($item ['contname']) . "&lt;br /&gt;";
          if ($item['conttel'])
              $evtdesc .= "  Phone: " . $item ['conttel'] . "&lt;br /&gt;";
          if ($email)
              $evtdesc .= "  Email: " . $email . "&lt;br /&gt;";
          if ($item['website'])
              $evtdesc .= "  URL: " . $item ['website'] . "&lt;br /&gt;";
          if ($item['event_location'])
              $evtdesc .= "  Location: " . htmlentities($item ['event_location']) . "&lt;br /&gt;";
          if ($item['event_street1'])
              $evtdesc .= "  Location: " . htmlentities($item ['event_street1']) . "&lt;br /&gt;";
          if ($item['event_street2'])
              $evtdesc .= "  Location: " . htmlentities($item ['event_street2']) . "&lt;br /&gt;";
          if ($item['event_city'])
              $evtdesc .= "  City, ST ZIP: " . htmlentities($item ['event_city']) . "," . $item ['event_state'] . " " . $item ['event_postal'] . "  &lt;br /&gt;";

          # Build the link to the actual calendar
          $args = array();
          $args['Date'] = date ("Ymd", strtotime ($cdate));
          $args['viewtype'] = 'details';
          $args['eid'] = $peid;
          $url = pnModURL ('PostCalendar', 'user', 'view', $args);

          # output the RSS item
          //echo "<item>\n";
          //echo "<guid>$altrep</guid>"; 
          //echo "<title>$summary - " . date ("F jS", strtotime ($cdate)) . " ($evcategory)</title>\n";
          //echo "<description>\n$evtdesc\n</description>\n"; 
          //echo "<category>$evcategory</category>";
          //echo "<link>$altrep</link>\n";
          //echo "</item>\n";

          $item = new FeedItem(); 
          $item->title                     = "$summary - " . date ("F jS", strtotime ($cdate));
          $item->link                      = $url;
          $item->description               = $evtdesc;
          $item->category                  = $evcategory;
          //$item->date                      = date ("l, F jS", strtotime ($cdate));
          $item->author                    = pnUserGetVar ('uname', $peid);
          $item->descriptionTruncSize      = 500;
          $item->descriptionHtmlSyndicated = true;
          $rss->addItem($item); 
          }
      }
  }

  $rss->saveFeed('RSS2.0', 'modules/PostCalendar/rsstmp.xml', true);
  return true;
}


###############################################################################

function eventdatecmp ($a, $b)
{
    if ($a [startTime] < $b [startTime]) return -1;
    elseif ($a [startTime] > $b [startTime]) return 1;
}

###############################################################################

function postcalendar_user_findContact ()
{

    //$tpl_contact = new pcRender();
	$tpl_contact = pcRender::getInstance('PostCalendar');

    $tpl_contact->caching = false;

    pnModDBInfoLoad ('v4bAddressBook');
    $cid = FormUtil::getPassedValue('cid');
    $bid = FormUtil::getPassedValue('bid');
    $contact_id = FormUtil::getPassedValue('contact_id');
    
    // v4bAddressBook compatability layer
    if ($cid)
        $company = DBUtil::selectObjectByID ('v4b_addressbook_company', $cid);
        
    if ($bid)
        $branch = DBUtil::selectObjectByID ('v4b_addressbook_company_branch', $bid);

    if ($contact_id)
        $contact = DBUtil::selectObjectByID ('v4b_addressbook_contact', $contact_id);
    // v4bAddressBook compatability layer

    $contact_phone = $contact['addr_phone1'];
    $contact_mail  = $contact['addr_email1'];
    $contact_www   = $contact['homepage'];
    
    $location = $company['name'];
    if ($branch['name'])
        $location .= " / ".$branch['name'];
    
    // assign the values   
    $tpl_contact->assign('cid',$cid);
    $tpl_contact->assign('bid',$bid);
    $tpl_contact->assign('contact_id',$contact_id);
    $tpl_contact->assign('contact',$contact);
    $tpl_contact->assign('location',$location);
    $tpl_contact->assign('contact_phone',$contact_phone);
    $tpl_contact->assign('contact_mail',$contact_mail);
    $tpl_contact->assign('contact_www',$contact_www);

    $pcTheme = pnModGetVar(__POSTCALENDAR__,'pcTemplate');
    if(!$pcTheme) $pcTheme='default';
    $output = $tpl_contact->fetch("$pcTheme/findContact.html");
    echo $output;

    return true;
}

?>