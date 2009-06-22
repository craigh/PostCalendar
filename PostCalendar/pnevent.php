<?php
/**
 *  SVN: $Id$
 *
 *  @package         PostCalendar 
 *  @lastmodified    $Date$ 
 *  @modifiedby      $Author: craigh $ 
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
Loader::requireOnce('includes/pnForm.php');
require_once ('modules/PostCalendar/global.php');


/**
 * This is the event handler file
 **/
class postcalendar_event_editHandler extends pnFormHandler
{
	var $eid;

	function initialize(&$render)
	{
		if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD))
			return $render->pnFormSetErrorMsg(_NOTAUTHORIZED);

		$this->eid = FormUtil::getPassedValue('eid');

		return true;
	}

	function handleCommand(&$render, &$args)
	{
		$url = null;

		// Fetch event data 
		$event = pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $this->eid);
		if (count($event) == 0)
			return $render->pnFormSetErrorMsg(_NOEVENTSFROMID);

		if ($args['commandName'] == 'update')
		{
			/*
			if (!$render->pnFormIsValid())
				return false;

			$recipeData = $render->pnFormGetValues();
			$recipeData['id'] = $this->recipeId;

			$result = pnModAPIFunc('howtopnforms', 'recipe', 'update',
								   array('recipe' => $recipeData));
			if ($result === false)
				return $render->pnFormSetErrorMsg(howtopnformsErrorAPIGet());

			$url = pnModUrl('howtopnforms', 'recipe', 'view',
							array('rid' => $this->recipeId));
			*/
		}
		else if ($args['commandName'] == 'delete')
		{
			$uname = pnUserGetVar('uname');
			if(($uname != $event['informant']) AND (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN))){
				return $render->pnFormSetErrorMsg(_PC_CAN_NOT_DELETE);
			}
			$result = pnModAPIFunc('PostCalendar', 'event', 'deleteevent',
								   array('eid' => $this->eid));
			if ($result === false)
				return $render->pnFormSetErrorMsg(_PC_ADMIN_EVENT_ERROR);

			$redir = pnModUrl('PostCalendar', 'user', 'view', array('viewtype' => pnModGetVar('PostCalendar','pcDefaultView')));
			return $render->pnFormRedirect($redir);
		}
		else if ($args['commandName'] == 'cancel')
		{
			$url = pnModUrl('PostCalendar', 'user', 'view',
							array('eid' => $this->eid, 'viewtype' => 'details', 'Date' => $event['Date']));
		}

		if ($url != null)
		{
			/*pnModAPIFunc('PageLock', 'user', 'releaseLock',
						 array('lockName' => "HowtoPnFormsRecipe{$this->recipeId}")); */
			return $render->pnFormRedirect($url);
		}

		return true;
	}
}

/*
	This is a user form 'are you sure' display
	to delete an event
*/
function postcalendar_event_delete()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
		return LogUtil::registerPermissionError();
	}
	$render = FormUtil::newpnForm('PostCalendar');
	$eventdetails = pnModAPIFunc('PostCalendar','event','eventDetail',array('eid'=>$eid,'cacheid'=>'','Date'=>''));
	$render->assign('eventdetails', $eventdetails['A_EVENT']);
	return $render->pnFormExecute('event/postcalendar_event_deleteeventconfirm.htm', new postcalendar_event_editHandler());
}

/**
 * submit an event
 */
function postcalendar_event_edit($args) {return postcalendar_event_new($args); }
function postcalendar_event_new($args)
{   
    // We need at least ADD permission to submit an event
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
		return LogUtil::registerPermissionError();
	}
    
	pnModAPILoad('PostCalendar','user');
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
	if(strchr($event_meetingdate_start, '-'))	{
		$event_startmonth 	= substr($event_meetingdate_start, 5, 2);
		$event_startday 	= substr($event_meetingdate_start, 8, 2);
		$event_startyear 	= substr($event_meetingdate_start, 0, 4); // V4B SB END
	}	else {
		$event_startmonth 	= substr($event_meetingdate_start, 4, 2);
		$event_startday 	= substr($event_meetingdate_start, 6, 2);
		$event_startyear 	= substr($event_meetingdate_start, 0, 4); // V4B SB END
	}

	$event_starttimeh	= FormUtil::getPassedValue('event_starttimeHour');
	$event_starttimem 	= FormUtil::getPassedValue('event_starttimeMinute');
	$event_startMer 	= FormUtil::getPassedValue('event_starttimeMeridian');
	$event_startampm 	= ($event_startMer == "am" ? 1 : 2); // reformat to old way
	
	// event end information
	$event_meetingdate_end 	= FormUtil::getPassedValue('meetingdate_end'); // V4B SB START
	if(strchr($event_meetingdate_end, '-'))	{
		$event_endmonth 	= substr($event_meetingdate_end, 5, 2);
		$event_endday 		= substr($event_meetingdate_end, 8, 2);
		$event_endyear 		= substr($event_meetingdate_end, 0, 4); 
	}	else {
		$event_endmonth 	= substr($event_meetingdate_end, 4, 2);
		$event_endday 		= substr($event_meetingdate_end, 6, 2);
		$event_endyear 		= substr($event_meetingdate_end, 0, 4);
	}
	if($event_endyear == '0000') {
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
	$event_fee  			= FormUtil::getPassedValue('event_fee');
	$event_contact 		= FormUtil::getPassedValue('event_contact');
    
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
    //$pc_event_id = FormUtil::getPassedValue('pc_event_id');
	$pc_event_id = FormUtil::getPassedValue('eid');
	$data_loaded = FormUtil::getPassedValue('data_loaded');
	$is_update   = FormUtil::getPassedValue('is_update');
	$authid      = FormUtil::getPassedValue('authid');
	$event_for_userid = FormUtil::getPassedValue('event_for_userid'); 
	$event_participants = FormUtil::getPassedValue('participants'); 

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
		$eventdata['event_for_userid'] = $event_for_userid;

		$event_participants = FormUtil::getPassedValue('participants'); 
	} else {
		$event = pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $pc_event_id);
		if(($uname != $event['informant']) AND (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN))){
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

		$eventdata['event_for_userid'] = $event_for_userid;
		$eventdata['meeting_id'] = $event['meeting_id']; 
		$eventdata['participants'] = $event_participants; 
	}
	
    if($form_action == 'copy') 
    {
        $form_action = '';
        unset($pc_event_id);
        $eventdata['pc_event_id'] = '';
        $eventdata['is_update'] = false;
        $eventdata['data_loaded'] = false;
    }
    
    $categories = pnModAPIFunc('PostCalendar','user','getCategories');

	//================================================================
	//	ERROR CHECKING
	//================================================================
    // $required_vars = array('event_subject','event_desc');
    $required_vars = array('event_subject');
    // $required_name = array(_PC_EVENT_TITLE,_PC_EVENT_DESC);
    $required_name = array(_PC_EVENT_TITLE);
    $error_msg = '';
    $reqCount = count($required_vars);
    for ($r=0; $r<$reqCount; $r++) {
        if(empty($$required_vars[$r]) || !preg_match('/\S/i',$$required_vars[$r])) {
						LogUtil::registerError('<b>' . $required_name[$r] . '</b> ' . _PC_SUBMIT_ERROR4 . '<br />');
        }
    }
    unset($reqCount);
	// check repeating frequencies
	if($event_repeat == REPEAT) {
		if(!isset($event_repeat_freq) ||  $event_repeat_freq < 1 || empty($event_repeat_freq)) {
			LogUtil::registerError(_PC_SUBMIT_ERROR5);
		} elseif(!is_numeric($event_repeat_freq)) {
			LogUtil::registerError(_PC_SUBMIT_ERROR6);
		}
	} elseif($event_repeat == REPEAT_ON) {
		if(!isset($event_repeat_on_freq) || $event_repeat_on_freq < 1 || empty($event_repeat_on_freq)) {
			LogUtil::registerError(_PC_SUBMIT_ERROR5);
		} elseif(!is_numeric($event_repeat_on_freq)) {
			LogUtil::registerError(_PC_SUBMIT_ERROR6);
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
			LogUtil::registerError(_PC_SUBMIT_ERROR1);
    }
    if(!checkdate($event_startmonth,$event_startday,$event_startyear)) {
			LogUtil::registerError(_PC_SUBMIT_ERROR2);
    }
    if(!checkdate($event_endmonth,$event_endday,$event_endyear)) {
			LogUtil::registerError(_PC_SUBMIT_ERROR3);
    }
    
	//================================================================
	//	Preview the event
	//================================================================
	if($form_action == 'preview') {
		if (!SecurityUtil :: confirmAuthKey())
			return LogUtil :: registerAuthidError(pnModURL('postcalendar', 'admin', 'main'));
		$output .= pnModAPIFunc('PostCalendar','user','eventPreview',$eventdata);
	}
    
	//================================================================
	//	Enter the event into the DB
	//================================================================
	if($form_action == 'commit') {
		if (!SecurityUtil :: confirmAuthKey())
			return LogUtil :: registerAuthidError(pnModURL('postcalendar', 'admin', 'main'));

		//save the start date, before the vars are cleared (needed for the redirect on success)
		$url_date = $event_startyear.$event_startmonth.$event_startday;

		if (!pnModAPIFunc('PostCalendar','event','writeEvent',$eventdata)) {
			LogUtil :: registerError(_PC_EVENT_SUBMISSION_FAILED);
		} else {
			pnModAPIFunc('PostCalendar','admin','clearCache');
			if($is_update) {
				LogUtil :: registerStatus(_PC_EVENT_EDIT_SUCCESS);		
			} else {
				LogUtil :: registerStatus(_PC_EVENT_SUBMISSION_SUCCESS);		
			}
     
      // save the start date, before the vars are cleared (needed for the redirect on success)
			$url_date = $event_startyear.$event_startmonth.$event_startday;
         
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
			//  wrap all the data into array for passing to submit and preview functions
			$eventdata = compact('event_subject','event_desc','event_sharing','event_category','event_topic',
				'event_startmonth','event_startday','event_startyear','event_starttimeh','event_starttimem','event_startampm',
				'event_endmonth','event_endday','event_endyear','event_endtype','event_dur_hours','event_dur_minutes',
				'event_duration','event_allday','event_location','event_street1','event_street2','event_city','event_state',
				'event_postal','event_location_info','event_contname','event_conttel','event_contemail',
				'event_website','event_fee','event_contact','event_repeat','event_repeat_freq','event_repeat_freq_type',
				'event_repeat_on_num','event_repeat_on_day','event_repeat_on_freq','event_recurrspec','uname',
				'Date','year','month','day','pc_html_or_text','is_update','pc_event_id');
		}

		pnRedirect(pnModURL('PostCalendar', 'user', 'view',array('viewtype'=>'month','Date'=>$url_date)));
		return true;
	}

	$output .= pnModAPIFunc('PostCalendar','event','buildSubmitForm',$eventdata);
	return $output;
}
?>