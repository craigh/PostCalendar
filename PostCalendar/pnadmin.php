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
 *  PostCalendar::Zikula Events Calendar Module
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
//=========================================================================
//  Load the API Functions
//=========================================================================
pnModAPILoad(__POSTCALENDAR__,'admin');

/**
 * the main administration function
 * This function is the default function, and is called whenever the
 * module is initiated without defining arguments.  As such it can
 * be used for a number of things, but most commonly it either just
 * shows the module menu and returns or calls whatever the module
 * designer feels should be the default function (often this is the
 * view() function)
 */
function postcalendar_admin_main()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}
	return postcalendar_admin_modifyconfig();
}

function postcalendar_admin_modifyconfig($msg='',$showMenu=true)
{   
	unset($showMenu); //remove this eventually
	
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}
	
	$pnRender = pnRender::getInstance('PostCalendar');
	$pnRender->assign('msg', $msg);

	return $pnRender->fetch('admin/postcalendar_admin_modifyconfig.htm');
}

function postcalendar_admin_listapproved() { return postcalendar_admin_showlist('',_EVENT_APPROVED,'listapproved',_PC_APPROVED_ADMIN); }
function postcalendar_admin_listhidden() { return postcalendar_admin_showlist('',_EVENT_HIDDEN,'listhidden',_PC_HIDDEN_ADMIN); }
function postcalendar_admin_listqueued() { return postcalendar_admin_showlist('',_EVENT_QUEUED,'listqueued',_PC_QUEUED_ADMIN); }
function postcalendar_admin_showlist($e='',$type,$function,$title,$msg='')
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}

	$pnRender = pnRender::getInstance('PostCalendar');
	$pnRender->assign('e', $e);
	$pnRender->assign('msg', $msg);
	
	$offset_increment = _SETTING_HOW_MANY_EVENTS;
	if(empty($offset_increment)) $offset_increment = 15;

	$offset = FormUtil::getPassedValue('offset');
	$sort = FormUtil::getPassedValue('sort');
	$sdir = FormUtil::getPassedValue('sdir');
	if(!isset($sort)) $sort = 'time';
	if(!isset($sdir)) $sdir = 1;
	if(!isset($offset))  $offset = 0;

	$events = pnModAPIFunc('PostCalendar','admin','getAdminListEvents',
                           array ('type'             => $type,
                                  'sdir'             => $sdir,
                                  'sort'             => $sort,
                                  'offset'           => $offset,
                                  'offset_increment' => $offset_increment));

	$output = pnModAPIFunc('PostCalendar','admin','buildAdminList',
                            array('type'             => $type,
                                  'title'            => $title,
                                  'sdir'             => $sdir,
                                  'sort'             => $sort,
                                  'offset'           => $offset,
                                  'offset_increment' => $offset_increment,
                                  'function'         => $function,
                                  'events'           => $events));


	$pnRender->assign('output', $output);
	$pnRender->assign('title', $title);

	return $pnRender->fetch('admin/postcalendar_admin_showlist.htm');
}

function postcalendar_admin_adminevents()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}

    $output = '';
    $action      = FormUtil::getPassedValue('action');
    $pc_event_id = FormUtil::getPassedValue('pc_event_id');
    $thelist     = FormUtil::getPassedValue('thelist');
    
    if(!isset($pc_event_id)) {
        $e  = _PC_NO_EVENT_SELECTED;
        
		switch($thelist) {
            case 'listqueued' :
                $output .= postcalendar_admin_showlist($e,_EVENT_QUEUED,'showlist');
                break;
                
            case 'listhidden' :
                $output .= postcalendar_admin_showlist($e,_EVENT_HIDDEN,'showlist');
                break;
                
            case 'listapproved' :
                $output .= postcalendar_admin_showlist($e,_EVENT_APPROVED,'showlist');
                break;
        }
        return $output;     
    }
    
    // main menu
    $output = "";
    $function = '';
    switch ($action) {
        case _ADMIN_ACTION_APPROVE :
            $function = 'approveevents';
			$are_you_sure_text = _PC_APPROVE_ARE_YOU_SURE;
			break;
            
        case _ADMIN_ACTION_HIDE :
            $function = 'hideevents';
			$are_you_sure_text = _PC_HIDE_ARE_YOU_SURE;
			break;
            
        case _ADMIN_ACTION_DELETE :
            $function = 'deleteevents';
			$are_you_sure_text = _PC_DELETE_ARE_YOU_SURE;
			break;
    }
	
	if(!empty($function)) {
		$output .= '<form action="'.pnModUrl(__POSTCALENDAR__,'admin',$function).'" method="post">';
    	$output .= $are_you_sure_text.' ';
    	$output .= '<input type="submit" name="submit" value="'._PC_ADMIN_YES.'" />';
		$output .= '<br /><br />';
	}

	$pnRender = pnRender::getInstance('PostCalendar');
	PostCalendarSmartySetup($pnRender);

	if(is_array($pc_event_id)) {
		foreach($pc_event_id as $eid) {
			// get event info
			$eventitems = pnModAPIFunc(__POSTCALENDAR__,'admin','eventDetail',array('eid'=>$eid,'nopop'=>true));
			// build template and fetch:
			foreach ($eventitems as $var=>$val) {
				$pnRender->assign($var,$val);
			}
			$output .= $pnRender->fetch($eventitems['template']);
			$output .= '<input type="hidden" name="pc_eid[]" value="'.$eid.'" />';
		}
	} else {
		// get event info
		$eventitems = pnModAPIFunc(__POSTCALENDAR__,'admin','eventDetail',array('eid'=>$pc_event_id,'nopop'=>true));
		// build template and fetch:
		foreach ($eventitems as $var=>$val) {
			$pnRender->assign($var,$val);
		}
		$output .= $pnRender->fetch($eventitems['template']);
		$output .= '<input type="hidden" name="pc_eid[0]" value="'.$pc_event_id.'" />';
	}
	if(!empty($function)) {
		$output .= $are_you_sure_text.' ';
		$output .= '<input type="submit" name="submit" value="'._PC_ADMIN_YES.'" />';
		$output .= '</form>';
	}

	$pnRender->assign('output',$output);
	return $pnRender->fetch("admin/postcalendar_admin_eventrevue.htm");
}

function postcalendar_admin_approveevents()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}

	$pc_eid = FormUtil::getPassedValue('pc_eid');
    $approve_list = '';
    foreach($pc_eid as $eid) {
        if(!empty($approve_list)) { $approve_list .= ','; }
        $approve_list .= $eid;
    }
    
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $events_table = $pntable['postcalendar_events'];
    $events_column = &$pntable['postcalendar_events_column'];
    
    $sql = "UPDATE $events_table
            SET $events_column[eventstatus] = "._EVENT_APPROVED."
            WHERE $events_column[eid] IN ($approve_list)";

    $dbconn->Execute($sql);
    if ($dbconn->ErrorNo() != 0) { 
		$msg = _PC_ADMIN_EVENT_ERROR; 
	} else { 
		$msg = _PC_ADMIN_EVENTS_APPROVED; 
	}
    
	postcalendar_admin_clearCache();
	return postcalendar_admin_showlist('',_EVENT_APPROVED,'listapproved',_PC_APPROVED_ADMIN,$msg);
}

function postcalendar_admin_hideevents()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}
	
	$pc_eid = FormUtil::getPassedValue('pc_eid');
    $output = "";
    $event_list = '';
    foreach($pc_eid as $eid) {
        if(!empty($event_list)) { $event_list .= ','; }
        $event_list .= $eid;
    }
    
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $events_table = $pntable['postcalendar_events'];
    $events_column = &$pntable['postcalendar_events_column'];
    
    $sql = "UPDATE $events_table
            SET $events_column[eventstatus] = "._EVENT_HIDDEN."
            WHERE $events_column[eid] IN ($event_list)";

    $dbconn->Execute($sql);
    if ($dbconn->ErrorNo() != 0) {
        $msg = _PC_ADMIN_EVENT_ERROR;
    } else {
        $msg = _PC_ADMIN_EVENTS_HIDDEN;
    }
    
	postcalendar_admin_clearCache();

	return postcalendar_admin_showlist('',_EVENT_APPROVED,'listapproved',_PC_APPROVED_ADMIN,$msg);
}

function postcalendar_admin_deleteevents()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}

	$pc_eid = FormUtil::getPassedValue('pc_eid');
    $output = "";
    $event_list = '';
    foreach($pc_eid as $eid) {
        if(!empty($event_list)) { $event_list .= ','; }
        $event_list .= $eid;
    }
    
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $events_table = $pntable['postcalendar_events'];
    $events_column = &$pntable['postcalendar_events_column'];
    
    $sql = "DELETE FROM $events_table WHERE $events_column[eid] IN ($event_list)";

    $dbconn->Execute($sql);
    if ($dbconn->ErrorNo() != 0) {
	    print $dbconn->ErrorMsg();
	    print $sql;
        $msg = _PC_ADMIN_EVENT_ERROR;
    } else {
        $msg = _PC_ADMIN_EVENTS_DELETED;
    }
    
	postcalendar_admin_clearCache();

	return postcalendar_admin_showlist('',_EVENT_APPROVED,'listapproved',_PC_APPROVED_ADMIN,$msg);
}
// V4B SB INCOMING VALUES FROM THE SUBMIT FORM
function postcalendar_admin_edit($args) { return postcalendar_admin_submit($args); }
function postcalendar_admin_submit($args)
{   
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}
    
	pnModAPILoad(__POSTCALENDAR__,'user');
	$output = "";
	
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
		$event_startmonth       = '';
		$event_startmonth       = substr($event_meetingdate_start, 5, 2);
		$event_startday         = '';
		$event_startday         = substr($event_meetingdate_start, 8, 2);
		$event_startyear        = '';
		$event_startyear        = substr($event_meetingdate_start, 0, 4); // V4B SB END
	}
	else
	{
		$event_startmonth       = '';
		$event_startmonth       = substr($event_meetingdate_start, 4, 2);
		$event_startday         = '';
		$event_startday         = substr($event_meetingdate_start, 6, 2);
		$event_startyear        = '';
		$event_startyear        = substr($event_meetingdate_start, 0, 4); // V4B SB END
	}

	$event_starttimeh       = FormUtil::getPassedValue('event_starttimeh');
	$event_starttimem       = FormUtil::getPassedValue('event_starttimem');
	$event_startampm        = FormUtil::getPassedValue('event_startampm');
	
	// event end information
	$event_meetingdate_end  = FormUtil::getPassedValue('meetingdate_end'); // V4B SB START
	if(strchr($event_meetingdate_end, '-'))
	{
		$event_endmonth         = '';
		$event_endmonth         = substr($event_meetingdate_end, 5, 2);
		$event_endday           = '';
		$event_endday           = substr($event_meetingdate_end, 8, 2);
		$event_endyear          = '';
		$event_endyear          = substr($event_meetingdate_end, 0, 4); // V4B SB END
	}
	else
	{
		$event_endmonth         = '';
		$event_endmonth         = substr($event_meetingdate_end, 4, 2);
		$event_endday           = '';
		$event_endday           = substr($event_meetingdate_end, 6, 2);
		$event_endyear          = '';
		$event_endyear          = substr($event_meetingdate_end, 0, 4); // V4B SB END
	}
	$event_endtype          = FormUtil::getPassedValue('event_endtype');
	$event_dur_hours        = FormUtil::getPassedValue('event_dur_hours');
	$event_dur_minutes      = FormUtil::getPassedValue('event_dur_minutes');
	$event_duration         = (60*60*$event_dur_hours) + (60*$event_dur_minutes);
	$event_allday           = FormUtil::getPassedValue('event_allday');
	
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
	
	// event repeating data
	$event_repeat 		= FormUtil::getPassedValue('event_repeat');
	$event_repeat_freq  = FormUtil::getPassedValue('event_repeat_freq');
	$event_repeat_freq_type = FormUtil::getPassedValue('event_repeat_freq_type');
	$event_repeat_on_num = FormUtil::getPassedValue('event_repeat_on_num');
	$event_repeat_on_day = FormUtil::getPassedValue('event_repeat_on_day');
	$event_repeat_on_freq = FormUtil::getPassedValue('event_repeat_on_freq');
	$event_recurrspec = serialize(compact('event_repeat_freq', 'event_repeat_freq_type', 'event_repeat_on_num',
                                          'event_repeat_on_day', 'event_repeat_on_freq'));
	
	$pc_html_or_text = FormUtil::getPassedValue('pc_html_or_text');
	$form_action = FormUtil::getPassedValue('form_action');
    $pc_event_id = FormUtil::getPassedValue('pc_event_id');
	$data_loaded = FormUtil::getPassedValue('data_loaded');
    $is_update   = FormUtil::getPassedValue('is_update');
    $authid      = FormUtil::getPassedValue('authid');
	
	if(pnUserLoggedIn()) { $uname = pnUserGetVar('uname'); } 
    else { $uname = pnConfigGetVar('anonymous'); }
    if(!isset($event_repeat)) { $event_repeat = 0; }
    
	// lets wrap all the data into array for passing to submit and preview functions
	if(!isset($pc_event_id) || empty($pc_event_id) || $data_loaded) {
		$eventdata = compact('event_subject','event_desc','event_sharing','event_category','event_topic',
		'event_startmonth','event_startday','event_startyear','event_starttimeh','event_starttimem','event_startampm',
		'event_endmonth','event_endday','event_endyear','event_endtype','event_dur_hours','event_dur_minutes',
		'event_duration','event_allday','event_location','event_street1','event_street2','event_city','event_state',
		'event_postal','event_location_info','event_contname','event_conttel','event_contemail',
		'event_website','event_fee','event_repeat','event_repeat_freq','event_repeat_freq_type',
		'event_repeat_on_num','event_repeat_on_day','event_repeat_on_freq','event_recurrspec','uname',
		'Date','year','month','day','pc_html_or_text');
		$eventdata['is_update'] = $is_update;
		$eventdata['pc_event_id'] = $pc_event_id;
		$eventdata['data_loaded'] = true;
	} else {
		$event = postcalendar_userapi_pcGetEventDetails($pc_event_id);
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
    $modinfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
    //$categories = pnModAPIFunc(__POSTCALENDAR__,'user','getCategories'); // V4B RNG unused
	
	//================================================================
	//	ERROR CHECKING
	//================================================================
    // $required_vars = array('event_subject','event_desc'); V4B SB
    $required_vars = array('event_subject');
    // $required_name = array(_PC_EVENT_TITLE,_PC_EVENT_DESC); V4B SB
    $required_name = array(_PC_EVENT_TITLE);
    $error_msg = '';
    $reqCount = count($required_vars);
    for ($r=0; $r<$reqCount; $r++) {
        if(empty($$required_vars[$r]) || !preg_match('/\S/i',$$required_vars[$r])) {
            $error_msg .= '<b>' . $required_name[$r] . '</b> ' . _PC_SUBMIT_ERROR4 . '<br />';
        }
    }
    unset($reqCount);
	// check repeating frequencies
	if($event_repeat == REPEAT) {
		if(!isset($event_repeat_freq) ||  $event_repeat_freq < 1 || empty($event_repeat_freq)) {
			$error_msg .= _PC_SUBMIT_ERROR5 . '<br />';
        } elseif(!is_numeric($event_repeat_freq)) {
			$error_msg .= _PC_SUBMIT_ERROR6 . '<br />';
        }
	} elseif($event_repeat == REPEAT_ON) {
		if(!isset($event_repeat_on_freq) || $event_repeat_on_freq < 1 || empty($event_repeat_on_freq)) {
			$error_msg .= _PC_SUBMIT_ERROR5 . '<br />';
        } elseif(!is_numeric($event_repeat_on_freq)) {
			$error_msg .= _PC_SUBMIT_ERROR6 . '<br />';
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
        $error_msg .= _PC_SUBMIT_ERROR1 . '<br />';
    }
    if(!checkdate($event_startmonth,$event_startday,$event_startyear)) {
        $error_msg .= _PC_SUBMIT_ERROR2 . '<br />';
    }
    if(!checkdate($event_endmonth,$event_endday,$event_endyear)) {
        $error_msg .= _PC_SUBMIT_ERROR3 . '<br />';
    }
    
	//================================================================
	//	Preview the event
	//================================================================
    if($form_action == 'preview') {
        if(!empty($error_msg)) {
            $preview = false;
            $output .= '<table border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td bgcolor="red">';
            $output .= '<table border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td bgcolor="pink">';
                $output .= '<center><b>' . _PC_SUBMIT_ERROR . '</b></center>'; 
                $output .= '<br />';
                $output .= $error_msg;
            $output .= '</td></td></table>';
            $output .= '</td></td></table>';
            $output .= '<br /><br />';
        } else {
            $output .= pnModAPIFunc(__POSTCALENDAR__,'user','eventPreview',$eventdata);
			$output .= '<br />';
        }
    }
    
	//================================================================
	//	Enter the event into the DB
	//================================================================
	if($form_action == 'commit') {
		//if (!pnSecConfirmAuthKey()) { return(_NO_DIRECT_ACCESS); }
		if(!empty($error_msg)) {
            $preview = false;
            $output .= '<table border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td bgcolor="red">';
            $output .= '<table border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td bgcolor="pink">';
                $output .= '<center><b>'._PC_SUBMIT_ERROR.'</b></center>'; 
                $output .= '<br />';
                $output .= $error_msg;
            $output .= '</td></td></table>';
            $output .= '</td></td></table>';
            $output .= '<br /><br />';
        } else
        {	
	    // V4B TS start - save the start date, before the vars are cleared (needed for the redirect on success)
	    $url_date = $event_startyear.$event_startmonth.$event_startday;

            if (!pnModAPIFunc(__POSTCALENDAR__,'admin','submitEvent',$eventdata)) {
        		$output .= '<center><div style="padding:5px; border:1px solid red; background-color: pink;">';		
				$output .= "<b>"._PC_EVENT_SUBMISSION_FAILED."</b>";		
				$output .= '</div></center><br />';	
				$output .= '<br />';
        	} else {
						postcalendar_admin_clearCache();

				$output .= '<center><div style="padding:5px; border:1px solid green; background-color: lightgreen;">';		
				if($is_update) {
					$output .= "<b>"._PC_EVENT_EDIT_SUCCESS."</b>";		
				} else {
					$output .= "<b>"._PC_EVENT_SUBMISSION_SUCCESS."</b>";		
				}
				$output .= '</div></center><br />';	
				$output .= '<br />';
        		// clear the form vars
        		$event_subject=$event_desc=$event_sharing=$event_category=$event_topic=
				$event_startmonth=$event_startday=$event_startyear=$event_starttimeh=$event_starttimem=$event_startampm=
				$event_endmonth=$event_endday=$event_endyear=$event_endtype=$event_dur_hours=$event_dur_minutes=
				$event_duration=$event_allday=$event_location=$event_street1=$event_street2=$event_city=$event_state=
				$event_postal=$event_location_info=$event_contname=$event_conttel=$event_contemail=
				$event_website=$event_fee=$event_repeat=$event_repeat_freq=$event_repeat_freq_type=
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
				'event_website','event_fee','event_repeat','event_repeat_freq','event_repeat_freq_type',
				'event_repeat_on_num','event_repeat_on_day','event_repeat_on_freq','event_recurrspec','uname',
				'Date','year','month','day','pc_html_or_text','is_update','pc_event_id');
			}

			// V4B RNG Start
			pnRedirect(pnModURL('PostCalendar', 'user', 'view',array('viewtype'=>'month','Date'=>$url_date)));
			return true;
			// V4B RNG End

        }
	}

    $output .= pnModAPIFunc('PostCalendar','admin','buildSubmitForm',$eventdata);
	return $output;
}

function postcalendar_admin_resetDefaults()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}
	
	// remove all the PostCalendar variables from the DB
	pnModDelVar(__POSTCALENDAR__, 'pcTime24Hours');
	pnModDelVar(__POSTCALENDAR__, 'pcEventsOpenInNewWindow');
	pnModDelVar(__POSTCALENDAR__, 'pcUseInternationalDates');
	pnModDelVar(__POSTCALENDAR__, 'pcFirstDayOfWeek');
	pnModDelVar(__POSTCALENDAR__, 'pcDayHighlightColor');
	pnModDelVar(__POSTCALENDAR__, 'pcUsePopups');
	pnModDelVar(__POSTCALENDAR__, 'pcDisplayTopics');
	pnModDelVar(__POSTCALENDAR__, 'pcAllowDirectSubmit');
	pnModDelVar(__POSTCALENDAR__, 'pcListHowManyEvents');
	pnModDelVar(__POSTCALENDAR__, 'pcTimeIncrement');
	pnModDelVar(__POSTCALENDAR__, 'pcAllowSiteWide');
	pnModDelVar(__POSTCALENDAR__, 'pcAllowUserCalendar');
	pnModDelVar(__POSTCALENDAR__, 'pcEventDateFormat');
	pnModDelVar(__POSTCALENDAR__, 'pcTemplate');
	pnModDelVar(__POSTCALENDAR__, 'pcRepeating');
	pnModDelVar(__POSTCALENDAR__, 'pcMeeting');
	pnModDelVar(__POSTCALENDAR__, 'pcAddressbook');
	pnModDelVar(__POSTCALENDAR__, 'pcUseCache');
	pnModDelVar(__POSTCALENDAR__, 'pcCacheLifetime');
	pnModDelVar(__POSTCALENDAR__, 'pcDefaultView');
	pnModDelVar(__POSTCALENDAR__, 'pcNotifyAdmin');
	pnModDelVar(__POSTCALENDAR__, 'pcNotifyEmail');
	
	// PostCalendar Default Settings
	pnModSetVar(__POSTCALENDAR__, 'pcTime24Hours',  '0');
	pnModSetVar(__POSTCALENDAR__, 'pcEventsOpenInNewWindow','0');
	pnModSetVar(__POSTCALENDAR__, 'pcUseInternationalDates','0');
	pnModSetVar(__POSTCALENDAR__, 'pcFirstDayOfWeek',   '0');
	pnModSetVar(__POSTCALENDAR__, 'pcDayHighlightColor','#FF0000');
	pnModSetVar(__POSTCALENDAR__, 'pcUsePopups','1');
	pnModSetVar(__POSTCALENDAR__, 'pcDisplayTopics','0');
	pnModSetVar(__POSTCALENDAR__, 'pcAllowDirectSubmit','0');
	pnModSetVar(__POSTCALENDAR__, 'pcListHowManyEvents','15');
	pnModSetVar(__POSTCALENDAR__, 'pcTimeIncrement','15');
	pnModSetVar(__POSTCALENDAR__, 'pcAllowSiteWide','0');
	pnModSetVar(__POSTCALENDAR__, 'pcAllowUserCalendar','1');
	pnModSetVar(__POSTCALENDAR__, 'pcEventDateFormat','%Y-%m-%d');
	pnModSetVar(__POSTCALENDAR__, 'pcRepeating', '0');
	pnModSetVar(__POSTCALENDAR__, 'pcMeeting',  '0');
	pnModSetVar(__POSTCALENDAR__, 'pcAddressbook', '1');
	pnModSetVar(__POSTCALENDAR__, 'pcUseCache', '1');
	pnModSetVar(__POSTCALENDAR__, 'pcCacheLifetime', '3600');
	pnModSetVar(__POSTCALENDAR__, 'pcDefaultView', 'month');
	pnModSetVar(__POSTCALENDAR__, 'pcNotifyAdmin', '0');
	pnModSetVar(__POSTCALENDAR__, 'pcNotifyEmail', pnConfigGetVar('adminmail'));
	
	postcalendar_admin_clearCache();
		
	return postcalendar_admin_modifyconfig(_PC_UPDATED_DEFAULTS);
}

function postcalendar_admin_updateconfig()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}
	$pcTime24Hours           = FormUtil::getPassedValue ('pcTime24Hours', 0);
	$pcEventsOpenInNewWindow = FormUtil::getPassedValue ('pcEventsOpenInNewWindow', 0);
	$pcUseInternationalDates  = FormUtil::getPassedValue ('pcUseInternationalDates', 0);
	$pcFirstDayOfWeek= FormUtil::getPassedValue ('pcFirstDayOfWeek', 0);
	$pcDayHighlightColor = FormUtil::getPassedValue ('pcDayHighlightColor', '$ff0000');
	$pcUsePopups = FormUtil::getPassedValue ('pcUsePopups', 0);
	$pcAllowDirectSubmit = FormUtil::getPassedValue ('pcAllowDirectSubmit', 0);
	$pcListHowManyEvents = FormUtil::getPassedValue ('pcListHowManyEvents', 15);
	$pcDisplayTopics = FormUtil::getPassedValue ('pcDisplayTopics', 0);
	$pcEventDateFormat   = FormUtil::getPassedValue ('pcEventDateFormat', '%Y-%m-%d');
	//$pcTemplate  = FormUtil::getPassedValue ('pcTemplate', 'default');
	$pcRepeating = FormUtil::getPassedValue ('pcRepeating', 0);
	$pcMeeting   = FormUtil::getPassedValue ('pcMeeting', 0);
	$pcAddressbook   = FormUtil::getPassedValue ('pcAddressbook', 0);
	$pcAllowSiteWide = FormUtil::getPassedValue ('pcAllowSiteWide', 0);
	$pcAllowUserCalendar = FormUtil::getPassedValue ('pcAllowUserCalendar', 0);
	$pcTimeIncrement = FormUtil::getPassedValue ('pcTimeIncrement', 15);
	$pcUseCache  = FormUtil::getPassedValue ('pcUseCache', 0);
	$pcCacheLifetime = FormUtil::getPassedValue ('pcCacheLifetime', 3600);
	$pcDefaultView   = FormUtil::getPassedValue ('pcDefaultView', 'month');
	$pcNotifyAdmin   = FormUtil::getPassedValue ('pcNotifyAdmin', 0);
	$pcNotifyEmail   = FormUtil::getPassedValue ('pcNotifyEmail', pnConfigGetVar('adminmail'));
	// v4b TS end
	   
	// make sure we enter something into the DB   
	// delete the old vars - we're doing this because Zikula variable 
	// handling sometimes has old values in the $GLOBALS we need to clear
	pnModDelVar(__POSTCALENDAR__, 'pcTime24Hours');
	pnModDelVar(__POSTCALENDAR__, 'pcEventsOpenInNewWindow');
	pnModDelVar(__POSTCALENDAR__, 'pcUseInternationalDates');
	pnModDelVar(__POSTCALENDAR__, 'pcFirstDayOfWeek');
	pnModDelVar(__POSTCALENDAR__, 'pcDayHighlightColor');
	pnModDelVar(__POSTCALENDAR__, 'pcUsePopups');
	pnModDelVar(__POSTCALENDAR__, 'pcAllowDirectSubmit');
	pnModDelVar(__POSTCALENDAR__, 'pcListHowManyEvents');
	pnModDelVar(__POSTCALENDAR__, 'pcDisplayTopics');
	pnModDelVar(__POSTCALENDAR__, 'pcEventDateFormat');
	pnModDelVar(__POSTCALENDAR__, 'pcTemplate');
	pnModDelVar(__POSTCALENDAR__, 'pcRepeating');// v4b TS
	pnModDelVar(__POSTCALENDAR__, 'pcMeeting');  // v4b TS
	pnModDelVar(__POSTCALENDAR__, 'pcAddressbook');  // v4b TS
	pnModDelVar(__POSTCALENDAR__, 'pcAllowSiteWide');
	pnModDelVar(__POSTCALENDAR__, 'pcAllowUserCalendar');
	pnModDelVar(__POSTCALENDAR__, 'pcTimeIncrement');
	pnModDelVar(__POSTCALENDAR__, 'pcDefaultView');
	pnModDelVar(__POSTCALENDAR__, 'pcUseCache');
	pnModDelVar(__POSTCALENDAR__, 'pcCacheLifetime');
	pnModDelVar(__POSTCALENDAR__, 'pcNotifyAdmin');
	pnModDelVar(__POSTCALENDAR__, 'pcNotifyEmail');
		
	// set the new variables
	pnModSetVar(__POSTCALENDAR__, 'pcTime24Hours',   $pcTime24Hours);
	pnModSetVar(__POSTCALENDAR__, 'pcEventsOpenInNewWindow', $pcEventsOpenInNewWindow);
	pnModSetVar(__POSTCALENDAR__, 'pcUseInternationalDates', $pcUseInternationalDates);
	pnModSetVar(__POSTCALENDAR__, 'pcFirstDayOfWeek',$pcFirstDayOfWeek);
	pnModSetVar(__POSTCALENDAR__, 'pcDayHighlightColor', $pcDayHighlightColor);
	pnModSetVar(__POSTCALENDAR__, 'pcUsePopups', $pcUsePopups);
	pnModSetVar(__POSTCALENDAR__, 'pcAllowDirectSubmit', $pcAllowDirectSubmit);
	pnModSetVar(__POSTCALENDAR__, 'pcListHowManyEvents', $pcListHowManyEvents);
	pnModSetVar(__POSTCALENDAR__, 'pcDisplayTopics', $pcDisplayTopics);
	pnModSetVar(__POSTCALENDAR__, 'pcEventDateFormat',   $pcEventDateFormat);
	pnModSetVar(__POSTCALENDAR__, 'pcTemplate',  $pcTemplate);
	pnModSetVar(__POSTCALENDAR__, 'pcRepeating', $pcRepeating);   // v4b TS
	pnModSetVar(__POSTCALENDAR__, 'pcMeeting',   $pcMeeting); // v4b TS
	pnModSetVar(__POSTCALENDAR__, 'pcAddressbook',   $pcAddressbook); // v4b TS
	pnModSetVar(__POSTCALENDAR__, 'pcAllowSiteWide', $pcAllowSiteWide);
	pnModSetVar(__POSTCALENDAR__, 'pcAllowUserCalendar', $pcAllowUserCalendar);
	pnModSetVar(__POSTCALENDAR__, 'pcTimeIncrement', $pcTimeIncrement);
	pnModSetVar(__POSTCALENDAR__, 'pcDefaultView',   $pcDefaultView);
	pnModSetVar(__POSTCALENDAR__, 'pcUseCache',	 $pcUseCache);
	pnModSetVar(__POSTCALENDAR__, 'pcCacheLifetime', $pcCacheLifetime);
	pnModSetVar(__POSTCALENDAR__, 'pcNotifyAdmin',   $pcNotifyAdmin);
	pnModSetVar(__POSTCALENDAR__, 'pcNotifyEmail',   $pcNotifyEmail);

	postcalendar_admin_clearCache();

	return postcalendar_admin_modifyconfig(_PC_UPDATED);
}

function postcalendar_admin_categories($msg='',$e='')
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}

	$pnRender = pnRender::getInstance('PostCalendar');
	$pnRender->assign('e', $e);
	$pnRender->assign('msg', $msg);

	$cats = pnModAPIFunc(__POSTCALENDAR__,'admin','getCategories');
	$pnRender->assign('cats', $cats);

	return $pnRender->fetch('admin/postcalendar_admin_categories.htm');
}

function postcalendar_admin_categoriesConfirm()
{   
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}
	
	$pnRender = pnRender::getInstance('PostCalendar');

	$id       = FormUtil::getPassedValue ('id');
	$del      = FormUtil::getPassedValue ('del');
	$name     = FormUtil::getPassedValue ('name');
	$desc     = FormUtil::getPassedValue ('desc');
	$color    = FormUtil::getPassedValue ('color');
	$newname  = FormUtil::getPassedValue ('newname');
	$newdesc  = FormUtil::getPassedValue ('newdesc');
	$newcolor = FormUtil::getPassedValue ('newcolor');

	if(is_array($del)) {                                                
		$dels = implode(',',$del);
		$delText = _PC_DELETE_CATS . $dels .'.';
		$pnRender->assign('delText', $delText);
		$pnRender->assign('dels', $dels);
	}
	$pnRender->assign('id', serialize($id));
	if (!empty($del)) $pnRender->assign('del', serialize($del));
	$pnRender->assign('name', serialize($name));
	$pnRender->assign('desc', serialize($desc));
	$pnRender->assign('color', serialize($color));
	$pnRender->assign('newname', $newname);
	$pnRender->assign('newdesc', $newdesc);
	$pnRender->assign('newcolor', $newcolor);
        
	return $pnRender->fetch('admin/postcalendar_admin_categoriesconfirm.htm');
}

function postcalendar_admin_categoriesUpdate()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}

	$id				= FormUtil::getPassedValue ('id');
	$del			= FormUtil::getPassedValue ('del');
	$dels			= FormUtil::getPassedValue ('dels');
	$name			= FormUtil::getPassedValue ('name');
	$desc			= FormUtil::getPassedValue ('desc');
	$color		= FormUtil::getPassedValue ('color');
	$newname	= FormUtil::getPassedValue ('newname');
	$newdesc	= FormUtil::getPassedValue ('newdesc');
	$newcolor	= FormUtil::getPassedValue ('newcolor');
	   
	$id			= unserialize($id);
	$del		= unserialize($del);
	$name		= unserialize($name);
	$desc		= unserialize($desc);
	$color	= unserialize($color);

	$modID = $modName = $modDesc = $modColor = array();

	//determine categories to update (not the ones to delete)
	if(isset($id)) {
		foreach($id as $k=>$i) {
			$found = false;
			if(count($del)) {
				foreach($del as $d) {
					if($i == $d) {
						$found = true;
						break;
					}
				}  
			} 
			if(!$found) {
				array_push($modID,$i);
				array_push($modName,$name[$k]);
				array_push($modDesc,$desc[$k]);
				array_push($modColor,$color[$k]);
			}
		}
	}

	//update categories
	$e =  $msg = '';
	$obj = array();
	foreach($modID as $k=>$id) {
		$obj['catid']= $id;
		$obj['catname']  = $modName[$k];
		$obj['catdesc']  = $modDesc[$k];
		$obj['catcolor'] = $modColor[$k];
		$res = DBUtil::updateObject ($obj, 'postcalendar_categories', '', 'catid');
		if (!$res) $e .= 'UPDATE FAILED';
	}

	// delete categories
	if (isset($dels) && $dels) {
		$res = DBUtil::deleteObjectsFromKeyArray (array_flip($del), 'postcalendar_categories', 'catid');
		if (!$res) $e .= 'DELETE FAILED';
	}

	// add category
	if(isset($newname)) {
		$obj['catid']= '';
		$obj['catname']  = $newname;
		$obj['catdesc']  = $newdesc;
		$obj['catcolor'] = $newcolor;
		$res = DBUtil::insertObject ($obj, 'postcalendar_categories', false, 'catid');
		if (!$res) $e .= 'INSERT FAILED';
	}

	if (empty($e)) $msg = 'DONE';
	return postcalendar_admin_categories($msg,$e);
}

function postcalendar_admin_manualClearCache() {
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}
	$clear = postcalendar_admin_clearCache();
	if ($clear) return postcalendar_admin_modifyconfig(_PC_CACHE_CLEARED);
	return postcalendar_admin_modifyconfig(_PC_CACHE_NOTCLEARED);
}

function postcalendar_admin_clearCache()
{
	$pnRender = pnRender::getInstance('PostCalendar'); //	PostCalendarSmartySetup not needed
	$res = $pnRender->clear_all_cache();

	return $res;
}

function postcalendar_admin_testSystem()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		return LogUtil::registerPermissionError();
	}

	$modinfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
	$pcDir = pnVarPrepForOS($modinfo['directory']);
	$version = $modinfo['version'];
	unset($modinfo);
	
	$tpl = pnRender::getInstance('PostCalendar'); //	PostCalendarSmartySetup not needed
	$infos = array();
	
	if(phpversion() >= '4.1.0') {
		$__SERVER = $_SERVER;
		$__ENV    = $_ENV;
	} else {
		$__SERVER = $HTTP_SERVER_VARS;
		$__ENV    = $HTTP_ENV_VARS;
	}
	
	if(defined('_PN_VERSION_NUM')) {
		$pnVersion = _PN_VERSION_NUM;
	} else {
		$pnVersion = pnConfigGetVar('Version_Num');
	}
	
	array_push($infos, array('CMS Version', $pnVersion));
	array_push($infos, array('Sitename', pnConfigGetVar('sitename')));
	array_push($infos, array('url', pnGetBaseURL()));
	array_push($infos, array('PHP Version', phpversion()));
	if ((bool) ini_get('safe_mode')) {
	 	$safe_mode = "On";
	} else {
	 	$safe_mode = "Off";
	}
	array_push($infos, array('PHP safe_mode', $safe_mode));
	if ((bool) ini_get('safe_mode_gid')) {
	 	$safe_mode_gid = "On";
	} else {
	 	$safe_mode_gid = "Off";
	}
	array_push($infos, array('PHP safe_mode_gid', $safe_mode_gid));
	$base_dir = ini_get('open_basedir');
	if(!empty($base_dir)) {
		$open_basedir = "$base_dir";
	} else {
		$open_basedir = "NULL";
	}
	array_push($infos, array('PHP open_basedir', $open_basedir));
	array_push($infos, array('SAPI', php_sapi_name()));
	array_push($infos, array('OS', php_uname()));
	array_push($infos, array('WebServer', $__SERVER['SERVER_SOFTWARE']));
	array_push($infos, array('Module dir', "modules/$pcDir"));

	$modversion = array();
	include  "modules/$pcDir/pnversion.php";

	$error = '';
	if ($modversion['version'] != $version) {
  	$error  = '<br /><div style=\"color: red;\">';
		$error .= "new version $modversion[version] installed but not updated!";
		$error .= '</div>';
	}
	array_push($infos, array('Module version', $version . " $error"));
	array_push($infos, array('smarty version', $tpl->_version));
	array_push($infos, array('smarty location',  SMARTY_DIR));
	array_push($infos, array('smarty template dir', $tpl->template_dir));

	$info = $tpl->compile_dir;
	$error = '';
	if (!file_exists($tpl->compile_dir)) {
	  	$error .= " compile dir doesn't exist! [$tpl->compile_dir]<br />";
	} else {
	  	// dir exists -> check if it's writeable
		if (!is_writeable($tpl->compile_dir)) {
	 		$error .= " compile dir not writeable! [$tpl->compile_dir]<br />";
	  }
	}
	if (strlen($error) > 0) {
	  $info .= "<br /><div style=\"color: red;\">$error</div>";
	}
	array_push($infos, array('smarty compile dir',  $info));

	$info = $tpl->cache_dir;
	$error = "";
	if (!file_exists($tpl->cache_dir)) {
	  $error .= " cache dir doesn't exist! [$tpl->cache_dir]<br />";
	} else {
	  // dir exists -> check if it's writeable
	  if (!is_writeable($tpl->cache_dir)) {
	 		$error .= " cache dir not writeable! [$tpl->cache_dir]<br />";
	  }
	}
	if (strlen($error) > 0) {
	 	$info .= "<br /><div style=\"color: red;\">$error</div>";
	}
	array_push($infos, array('smarty cache dir',  $info));

	$tpl->assign('infos', $infos);
	return $tpl->fetch('admin/postcalendar_admin_systeminfo.htm');
}
?>