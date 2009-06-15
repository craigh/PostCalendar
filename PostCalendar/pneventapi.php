<?php
require_once ('modules/PostCalendar/global.php');

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

/**
 * This is the event handler api
 **/

/**
 *  postcalendar_eventapi_queryEvents //new name
 *  Returns an array containing the event's information (plural or singular?)
 *  @params array(key=>value)
 *  @params string key eventstatus
 *  @params int value -1 == hidden ; 0 == queued ; 1 == approved
 *  @return array $events[][]
 */
function postcalendar_eventapi_queryEvents($args)
{   
	//echo "pcQuerydebug<br>";
	//pcDebugVar ($args);
	$end = '0000-00-00';
	extract($args);

	//CAH we should not be get form values in an API function
	$pc_username = FormUtil::getPassedValue('pc_username');
	$topic       = FormUtil::getPassedValue('pc_topic');
	$category    = FormUtil::getPassedValue('pc_category');
	$userid      = pnUserGetVar('uid');

	if(!empty($pc_username) && (strtolower($pc_username) != 'anonymous')) {
		if($pc_username=='__PC_ALL__') {
			$ruserid = -1;
		} else {
			$ruserid = pnUserGetIDFromName(strtolower($pc_username));
    	}
    }

	if(!isset($eventstatus) || ((int)$eventstatus < -1 || (int)$eventstatus > 1)) $eventstatus = 1;

	if(!isset($start)) $start = Date_Calc::dateNow('%Y-%m-%d'); 
	list($sy,$sm,$sd) = explode('-',$start);

	$where = "WHERE pc_eventstatus=$eventstatus 
						AND (pc_endDate>='$start' OR (pc_endDate='0000-00-00' AND pc_recurrtype<>'0') OR pc_eventDate>='$start')
						AND pc_eventDate<='$end' ";

	if(isset($ruserid)) {
		// get all events for the specified username
		if($ruserid == -1) {
			$where .= "AND (pc_sharing = '" . SHARING_BUSY . "' ";
			$where .= "OR pc_sharing = '" . SHARING_PUBLIC . "') ";
		} else {
			// v4b TS start - always see the records of the logged in user too | disabled on 2004-10-18
			$where .= "AND pc_aid = $ruserid ";
			//$where .= "AND (pc_aid = $ruserid OR pc_aid = $userid) ";
		}
	} else if (!pnUserLoggedIn()) {
		// get all events for anonymous users
		$where .= "AND (pc_sharing = '" . SHARING_GLOBAL . "' ";
		$where .= "OR pc_sharing = '" . SHARING_HIDEDESC . "') ";
	} else {
		// get all events for logged in user plus global events
		$where .= "AND (pc_aid = $userid OR pc_sharing = '" . SHARING_GLOBAL . "' OR pc_sharing = '" . SHARING_HIDEDESC . "') ";
	}


	// Start Search functionality 
	if(!empty($s_keywords)) $where .= "AND ($s_keywords) ";
	if(!empty($s_category)) $where .= "AND ($s_category) ";
	if(!empty($s_topic))    $where .= "AND ($s_topic) ";
	if(!empty($category))   $where .= "AND (tbl.pc_catid = '".pnVarPrepForStore($category)."') ";
	if(!empty($topic))	    $where .= "AND (tbl.pc_topic = '".pnVarPrepForStore($topic)."') ";
	// End Search functionality 

	$sort .= "ORDER BY pc_meeting_id";

		// FIXME !!!
	$joinInfo = array ();
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catname',
				'object_field_name'   =>  'catname',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catdesc',
				'object_field_name'   =>  'catdesc',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catcolor',
				'object_field_name'   =>  'catcolor',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');

	$events = DBUtil::selectExpandedObjectArray ('postcalendar_events', $joinInfo, $where, $sort);
	$topicNames = DBUtil::selectFieldArray ('topics', 'topicname', '', '', false, 'topicid');
    
	// added temp_meeting_id
	$old_m_id = "NULL";
	$ak = array_keys ($events);
	foreach ($ak as $key) {
		$new_m_id = $key['meeting_id'];
		if ( ($old_m_id) && ($old_m_id != "NULL") && ($new_m_id > 0) && ($old_m_id == $new_m_id) ) {
			$old_m_id = $new_m_id;
			unset ($events[$key]);
		}
		$events[$key] = pnModAPIFunc('PostCalendar','event','fixEventDetails',$events[$key]);
	}
	return $events;
}

/**
 * I believe this function returns an array of events sorted by date
 *
 *
 **/
function postcalendar_eventapi_getEvents($args)
{   
	//echo "pcGetdebug<br>";
	//pcDebugVar($args);
	
	$s_keywords = $s_category = $s_topic = '';
	extract($args);
	$date = postcalendar_getDate();
	$cy = substr($date,0,4);
	$cm = substr($date,4,2);
	$cd = substr($date,6,2);

	if(isset($start) && isset($end)) {
		// parse start date
		list($sm,$sd,$sy) = explode('/',$start);
		// parse end date
		list($em,$ed,$ey) = explode('/',$end);
	
		$s = (int) "$sy$sm$sd";
		if($s > $date) {
			$cy = $sy;
			$cm = $sm;
			$cd = $sd;
		}
		$start_date = Date_Calc::dateFormat($sd,$sm,$sy,'%Y-%m-%d');
		$end_date = Date_Calc::dateFormat($ed,$em,$ey,'%Y-%m-%d');
	} else {
		$sm = $em = $cm;
		$sd = $ed = $cd;
		$sy = $cy;
		$ey = $cy+2;
		$start_date = $sy.'-'.$sm.'-'.$sd;
		$end_date = $ey.'-'.$em.'-'.$ed;
	}
	if(!isset($events)) {
		if(!isset($s_keywords)) $s_keywords = '';
		$a = array('start'=>$start_date,'end'=>$end_date,'s_keywords'=>$s_keywords,'s_category'=>$s_category,'s_topic'=>$s_topic);
		$events = pnModAPIFunc('PostCalendar','event','queryEvents',$a);
	}

	//==============================================================
	//  Here we build an array consisting of the date ranges
	//  specific to the current view.  This array is then
	//  used to build the calendar display.
	//==============================================================
	$days = array();
	$sday = Date_Calc::dateToDays($sd,$sm,$sy);
	$eday = Date_Calc::dateToDays($ed,$em,$ey);
	for($cday = $sday; $cday <= $eday; $cday++) {
		$d = Date_Calc::daysToDate($cday,'%d');
		$m = Date_Calc::daysToDate($cday,'%m');
		$y = Date_Calc::daysToDate($cday,'%Y');
		$store_date = Date_Calc::dateFormat($d,$m,$y,'%Y-%m-%d');
		$days[$store_date] = array();
	}
	
	//echo "GetEvents Line 729<br>";
    //$users = pnUserGetAll();
	//$nuke_users = array();
	
	//foreach($users as $user) {
    //    $nuke_users[strtolower($user['uname'])] = $user['uid'];
	//}
	//unset($users);
	
	foreach($events as $event) {
		// get the name of the topic
		$topicname = pcGetTopicName($event['topic']);
		// get the user id of event's author
		//$cuserid = @$nuke_users[strtolower($event['uname'])];
		// CAH mod 4/12/09
		$cuserid = pnUserGetIDFromName(strtolower($event['uname']));

		// check the current event's permissions
		// the user does not have permission to view this event
		// if any of the following evaluate as false
		if(!pnSecAuthAction(0, 'PostCalendar::Event', "$event[title]::$event[eid]", ACCESS_OVERVIEW)) {
			continue;
		} elseif(!pnSecAuthAction(0, 'PostCalendar::Category', "$event[catname]::$event[catid]", ACCESS_OVERVIEW)) {
			continue;
		} elseif(!pnSecAuthAction(0, 'PostCalendar::User', "$event[uname]::$cuserid", ACCESS_OVERVIEW)) {
			continue;
		} elseif(!pnSecAuthAction(0, 'PostCalendar::Topic', "$topicname::$event[topic]", ACCESS_OVERVIEW)) {
			continue;
		}
		// parse the event start date
		list($esY,$esM,$esD) = explode('-',$event['eventDate']);
		// grab the recurring specs for the event
		$event_recurrspec = @unserialize($event['recurrspec']);
		// determine the stop date for this event
		if($event['endDate'] == '0000-00-00') {
			$stop = $end_date; //CAH enddate has no value here (maybe passed as arg?)
		} else {
			$stop = $event['endDate'];
		}

		switch($event['recurrtype']) {
			//==============================================================
			//  Events that do not repeat only have a startday
			//==============================================================
			case NO_REPEAT :
				if(isset($days[$event['eventDate']])) {
					array_push($days[$event['eventDate']],$event); //CAH this line has no meaning. it seems backward and pushes the same value
				}
				break;
			//==============================================================
			//  Find events that repeat at a certain frequency
			//  Every,Every Other,Every Third,Every Fourth
			//  Day,Week,Month,Year,MWF,TR,M-F,SS
			//==============================================================   
			case REPEAT :
				$rfreq = $event_recurrspec['event_repeat_freq'];
				$rtype = $event_recurrspec['event_repeat_freq_type'];
				// we should bring the event up to date to make this a tad bit faster
				// any ideas on how to do that, exactly??? dateToDays probably.
				$nm = $esM; $ny = $esY; $nd = $esD; 
				$occurance = Date_Calc::dateFormat($nd,$nm,$ny,'%Y-%m-%d');
				while($occurance < $start_date) {
					$occurance = __increment($nd,$nm,$ny,$rfreq,$rtype);
					list($ny,$nm,$nd) = explode('-',$occurance);
				}
				while($occurance <= $stop) {
					if(isset($days[$occurance])) { array_push($days[$occurance],$event); }
					$occurance = __increment($nd,$nm,$ny,$rfreq,$rtype);
					list($ny,$nm,$nd) = explode('-',$occurance);
				}
				break;
			//==============================================================
			//  Find events that repeat on certain parameters
			//  On 1st,2nd,3rd,4th,Last
			//  Sun,Mon,Tue,Wed,Thu,Fri,Sat
			//  Every N Months
			//==============================================================     
			case REPEAT_ON :
				$rfreq = $event_recurrspec['event_repeat_on_freq'];
				$rnum  = $event_recurrspec['event_repeat_on_num'];
				$rday  = $event_recurrspec['event_repeat_on_day'];
				//==============================================================
				//  Populate - Enter data into the event array
				//==============================================================
				$nm = $esM; $ny = $esY; $nd = $esD;
				// make us current
				while($ny < $cy) {
					$occurance = date('Y-m-d',mktime(0,0,0,$nm+$rfreq,$nd,$ny));
					list($ny,$nm,$nd) = explode('-',$occurance);
				}
				// populate the event array
				while($ny <= $cy) {
					$dnum = $rnum; // get day event repeats on
					do {
						$occurance = Date_Calc::NWeekdayOfMonth($dnum--,$rday,$nm,$ny,$format="%Y-%m-%d");
					} while($occurance === -1);
					if(isset($days[$occurance]) && $occurance <= $stop) { array_push($days[$occurance],$event); }
					$occurance = date('Y-m-d',mktime(0,0,0,$nm+$rfreq,$nd,$ny));
					list($ny,$nm,$nd) = explode('-',$occurance);
				}
				break;
		} // <- end of switch($event['recurrtype'])
	} // <- end of foreach($events as $event)
	return $days;
}
/**
 *	postcalendar_eventapi_writeEvent()
 *	write an event to the DB
 *	@param $args array of event data
 *	@return bool true on success : false on failure;
 */
function postcalendar_eventapi_writeEvent($args) 
{
	$event_for_userid = $_POST['event_for_userid']; // gets the value out of the event_for_userid dropdown

	extract($args); unset($args);
    list($dbconn) = pnDBGetConn();
	$pntable = pnDBGetTables();

	define('PC_ACCESS_ADMIN', pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW));
    
	// determine if the event is to be published immediately or not
	if( (bool) _SETTING_DIRECT_SUBMIT || (bool) PC_ACCESS_ADMIN || ($event_sharing != SHARING_GLOBAL) ) 
		$event_status = _EVENT_APPROVED;
	else 
		$event_status = _EVENT_QUEUED;
    
	// set up some vars for the insert statement
	$startDate = $event_startyear.'-'.$event_startmonth.'-'.$event_startday;
	if($event_endtype == 1) 
		$endDate = $event_endyear.'-'.$event_endmonth.'-'.$event_endday;
	else 
		$endDate = '0000-00-00';
    
	if(!isset($event_allday)) 
		$event_allday = 0;

	if((bool)_SETTING_TIME_24HOUR) 
		$startTime = $event_starttimeh.':'.$event_starttimem.':00';
	else 
	if($event_startampm == _AM_VAL) 
		$event_starttimeh = $event_starttimeh == 12 ? '00' : $event_starttimeh;
	else 
		$event_starttimeh = $event_starttimeh != 12 ? $event_starttimeh+=12 : $event_starttimeh;

	$startTime = sprintf('%02d',$event_starttimeh).':'.sprintf('%02d',$event_starttimem).':00';
    
	// get rid of variables we no longer need to save memory
	unset($event_startyear,$event_startmonth,$event_startday,$event_endyear,$event_endmonth,
	      $event_endday,$event_starttimeh,$event_starttimem);
		
        $event_userid = $event_for_userid;
    
	if($pc_html_or_text == 'html') 
		$event_desc = ':html:'.$event_desc;
	else 
		$event_desc = ':text:'.$event_desc;

	if(($event_desc==':text:')||($event_desc==':html:'))
		$event_desc .= 'n/a';
	
	
	// V4B RNG Start, added event_for_userid, pc_event_id
	list($event_subject,$event_desc,$event_topic,$startDate,$endDate,$event_repeat,
		$startTime,$event_allday,$event_category,$event_location_info,$event_conttel,
		$event_contname,$event_contemail,$event_website,$event_fee,$event_status,
		$event_recurrspec,$event_duration,$event_sharing,$event_userid,$event_for_userid,
		 $pc_event_id) = 
		 	@pnVarPrepForStore($event_subject,$event_desc,$event_topic,$startDate,$endDate,
		 		$event_repeat,$startTime,$event_allday,$event_category,
		 		$event_location_info,$event_conttel,$event_contname,$event_contemail,
				$event_website,$event_fee,$event_status,$event_recurrspec,$event_duration,
				$event_sharing,$event_userid,$event_for_userid,$pc_event_id);

	// V4B SB Start defining pc_meeting_id
	// v4b TS moved some line, added NULL if no participant
	if($_POST['participants'])
	{
		$participants = $_POST['participants'];
		list($dbconn) = pnDBGetConn();
		$prefix = pnConfigGetVar('prefix');
		$table = $prefix . '_postcalendar_events';

		$sql_v4b = "select max(pc_meeting_id) as max_meeting_id from $table";
		$sql_v4b_result = $dbconn->Execute($sql_v4b);
		$pc_meeting_id = $sql_v4b_result->fields[0] + 1;
	} 
	else 
		$pc_meeting_id = 0;

	if(!in_array($event_for_userid, $participants))
		$participants[] = $event_for_userid;   
	// V4B SB End 
	
	if(!isset($is_update)) 
		$is_update = false; 

	if(!$is_update)
		$pc_event_id = $dbconn->GenId($pntable['postcalendar_events']);

	// v4b TS start - build an array of users for mail notification
	$pc_mail_users = array ();
    
    foreach($participants as $part) // V4B SB LOOP to insert events for every participant
	{
		if($is_update) 
		{
			$sql = "UPDATE $pntable[postcalendar_events] SET 
					pc_title = '$event_subject',
					pc_hometext = '$event_desc',
					pc_topic = '$event_topic',
					pc_eventDate = '$startDate',
					pc_endDate = '$endDate',
					pc_recurrtype = '$event_repeat',
					pc_startTime = '$startTime',
					pc_alldayevent = '$event_allday',
					pc_catid = '$event_category',
					pc_location = '$event_location_info',
					pc_conttel = '$event_conttel',
					pc_contname = '$event_contname',
					pc_contemail = '$event_contemail',
					pc_website = '$event_website',
					pc_fee = '$event_fee',
					pc_eventstatus = '$event_status',
					pc_recurrspec = '$event_recurrspec',
					pc_duration = '$event_duration',
					pc_sharing = '$event_sharing',
					pc_aid = '$part' 
				WHERE pc_eid = '$pc_event_id'";
		} 
		else 
		{
			$pc_event_id = $dbconn->GenId($pntable['postcalendar_events']);
			$sql = "INSERT INTO $pntable[postcalendar_events] (
					pc_eid, pc_title, pc_time, pc_hometext, pc_topic, pc_informant,
					pc_eventDate, pc_endDate, pc_recurrtype, pc_startTime, pc_alldayevent,
					pc_catid, pc_location, pc_conttel, pc_contname, pc_contemail,
					pc_website, pc_fee, pc_eventstatus, pc_recurrspec, pc_duration,
					pc_sharing, pc_aid, pc_meeting_id)
				VALUES (
					'$pc_event_id', '$event_subject', NOW(), '$event_desc', '$event_topic', '$uname',
					'$startDate', '$endDate', '$event_repeat', '$startTime', '$event_allday',
					'$event_category', '$event_location_info', '$event_conttel', '$event_contname', '$event_contemail',
					'$event_website', '$event_fee', '$event_status', '$event_recurrspec', '$event_duration',
					'$event_sharing', '$part', '$pc_meeting_id')";
            
		}
		$result = $dbconn->Execute($sql);
		if($result === false) 
		{
			$dbconn->ErrorMsg();
			return false;
		}
        
// v4b TS start - build an array of users for mail notification
		if (( pnUserGetVar('uname', $part) != $uname ) && (!$is_update)) 
		{
			$pc_mail_users[] = $part;
			$pc_mail_events[] = $dbconn->PO_Insert_ID($pntable['postcalendar_events'],'pc_eid');
		}
	}    // V4B SB Foreach End
    
	if((bool)$is_update)
		$eid = $pc_event_id;
	else 
		$eid = $dbconn->PO_Insert_ID($pntable['postcalendar_events'],'pc_eid');

	pc_notify($eid,$is_update);
    
	// v4b TS start - mail information for participants
	if (!$is_update) 
	{
		// prepare the values for putput
		@list($pc_dur_hours,$dmin) = @explode('.',($event_duration/60/60));
		$pc_dur_minutes = substr(sprintf('%.2f','.' . 60*($dmin/100)),2,2);
		$display_type = substr($event_desc,0,6);

		if($display_type == ':text:') 
			$pc_description = substr($event_desc,6);
		elseif($display_type == ':html:') 
			$pc_description = substr($event_desc,6);

		list($x,$y,$z) = explode('-',$startDate);
		list($a,$b,$c) = explode('-',$startTime);
		$time = mktime($a,$b,$c,$y,$z,$x);
		$pc_start_time = strftime('%H:%M', $time);
        
		for ($i=0; $i < count($pc_mail_users); $i++) 
		{
			// build the url, get the authors name
			$pc_eid = $pc_mail_events[$i];
			$pc_URL = pnModURL ('PostCalendar', 'user', 'view', array('viewtype'=>'details','eid'=>$pc_eid));
			//$pc_author = pnUserGetVar('name', $event_for_userid);
			$pc_author = $uname;
        
			// process mail file to generate mail text
			$currentlang = pnVarPrepForOS (pnUserGetLang());
			$mailfile = "modules/PostCalendar/pnlang/$currentlang/mails/mail_meeting_notification.php";    

			if (file_exists($mailfile)) 
			{
				$fhandle     = fopen($mailfile, "r");
				$mailtext    = fread($fhandle, filesize($mailfile));
				fclose($fhandle);
				$mailtext    = '$mailtext = "' . $mailtext . '";';
				eval ($mailtext);

				// mail the users
				$extraHeader = "From: noreply"; 
				$email    = pnUserGetVar('email', $pc_mail_users[$i]);
				$subject  = _PC_MEETING_MAIL_TITLE . ": $event_subject";

				if ($email)
					pnMail ($email, $subject, $mailtext, $extraHeader);
            		}
        	}
    	}
    
	// v4b TS end - mail information for participants
	return true;
}

/**
 *	postcalendar_eventapi_buildSubmitForm()
 *	create event submit form
 */
function postcalendar_eventapi_buildSubmitForm($args)
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
		return LogUtil::registerPermissionError();
	}

	extract($args); 
	unset($args);

	if (!$admin) $admin = false; //reset default value
	
	$output = new pnHTML();
	$output->SetInputMode(_PNH_VERBATIMINPUT);

	//$tpl = new pnRender();
	$tpl = pnRender::getInstance('PostCalendar');
	$tpl->caching = false;
	PostCalendarSmartySetup($tpl);

	/* $pcTheme = pnModGetVar('PostCalendar','pcTemplate');
	if(!$pcTheme) 
        	$pcTheme = 'default';
	*/
	// V4B RNG start
	//================================================================
	//        build the username filter pulldown
	//================================================================
	if(true)
	{
		$event_for_userid = (int)DBUtil::selectFieldByID ('postcalendar_events', 'aid', $pc_event_id, 'eid');

		$optstart     = "<select name=\"event_for_userid\">";
		$optbody      = "";
		$optuser      = "";
		$optend       = '</select>';

		$uid   = pnUserGetVar('uid');
		$uname = pnUserGetVar('uname');
		$idsel = ($event_for_userid ? $event_for_userid : $uid);
		$namesel = "";

		@define('_PC_FORM_USERNAME',true);

		$users = DBUtil::selectObjectArray ('users', '', 'uname');
		foreach ($users as $user)
		{
			if ($idsel==$user['uid'])
				$optuser = "<option value=\"$user[uid]\" selected>$user[uname]</option>";
			else
				$optbody .= "<option value=\"$user[uid]\">$user[uname]</option>";
		}

		$useroptions = $optstart . $optuser . $optbody . $optend;
		$tpl->assign('UserSelector', $useroptions);
	}
    // v4b TS start

	$endDate = $event_endyear.$event_endmonth.$event_endday;
	$today = postcalendar_getDate();
	if(($endDate == '')||($endDate == '00000000'))
	{
		$endvalue = substr($today, 6, 2).'-';
		$endvalue .= substr($today, 4, 2).'-';
		$endvalue .= substr($today, 0, 4);
		// V4B RNG: build other date format for JS cal
		$endDate = substr($today, 0, 4) .'-'. substr($today, 4, 2) . '-' . substr($today, 6, 2);
	}
	else
	{
		$endvalue = substr($endDate, 6, 2).'-';
		$endvalue .= substr($endDate, 4, 2).'-';
		$endvalue .= substr($endDate, 0, 4);
		// V4B RNG: build other date format for JS cal
		$endDate = substr($endDate, 0, 4) .'-'. substr($endDate, 4, 2) . '-' . substr($endDate, 6, 2);
	}
	$tpl->assign('endvalue', $endvalue);	
    $tpl->assign('endDate', $endDate);

	$startdate = $event_startyear.$event_startmonth.$event_startday;
	$today = postcalendar_getDate();
	if($startdate == '')
	{
		$startvalue = substr($today, 6, 2).'-';
		$startvalue .= substr($today, 4, 2).'-';
		$startvalue .= substr($today, 0, 4);
		// V4B RNG: build other date format for JS cal
		$startdate = substr($today, 0, 4) .'-'. substr($today, 4, 2) . '-' . substr($today, 6, 2);
	}
	else
	{
		$startvalue = substr($startdate, 6, 2).'-';
		$startvalue .= substr($startdate, 4, 2).'-';
		$startvalue .= substr($startdate, 0, 4);
		// V4B RNG: build other date format for JS cal
		$startdate = substr($startdate, 0, 4) .'-'. substr($startdate, 4, 2) . '-' . substr($startdate, 6, 2);
	}
	$tpl->assign('startvalue', $startvalue);	
    $tpl->assign('startdate', $startdate);
    
    // v4b TS end

	// V4B SB END // JAVASCRIPT CALENDAR

	// V4B SB Start // Selectboxes for the participants
	//
	//================================================================
	//	build the userlist select box
	//================================================================
	if(true)
	{
		$ca = array();
		$ca['uid'] = 'uid';
		$ca['uname'] = 'uname';
	        $users = DBUtil::selectObjectArray ('users', '', '', -1, -1, 'uid', null, $ca);

		$useroptions  = "<select name=\"tn[]\" multiple size=\"5\">";
		foreach($users as $user) 
			$useroptions .= "<option value=\"".$user['uid']."\">".$user['uname']."</option>";
		$useroptions .= '</select>';
		$tpl->assign('UserListSelector', $useroptions);
	}

	//================================================================
	//	build the participants select box
	//================================================================
	if($event['meeting_id'])
	{
	        $where     = 'WHERE pc_meeting_id=' . pnVarPrepForStore ($event['meeting_id']);
	        $attendees = DBUtil::selectFieldArray ('postcalendar_events', 'aid', $where);
		foreach($attendees as $user) 
			$useroptions .= "<option value=\"".$user['uid']."\">".$user['uname']."</option>";

                $participants = array();
		foreach($attendees as $uid) 
                    $participants[$uid] = $users[$uid]['uname'];

		$useroptions  = "<select name=\"tn[]\" multiple size=\"5\">";
		foreach($participants as $k=>$v) 
			$useroptions .= "<option value=\"$k\">$v</option>";
		$useroptions .= '</select>';
		$tpl->assign('ParticipantSelector', $useroptions);
	}
	// V4B RNG end


	//=================================================================
	//  Setup the correct config file path for the templates
	//=================================================================
	//$modinfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
	//$modir = pnVarPrepForOS($modinfo['directory']);
	//$modname = $modinfo['displayname'];
	$all_categories = pnModAPIFunc('PostCalendar','user','getCategories');
	//unset($modinfo);
	//$tpl->config_dir = "modules/$modir/pntemplates/$pcTheme/config/";

	//=================================================================
	//  PARSE MAIN
	//=================================================================
	//$tpl->assign('TPL_NAME',$pcTheme);
	$tpl->assign('VIEW_TYPE',''); // E_ALL Fix
	$tpl->assign('FUNCTION',FormUtil::getPassedValue('func'));
	$tpl->assign('ModuleName', $modname);
	$tpl->assign('ModuleDirectory', $modir);
	$tpl->assign('category',$all_categories);
	$tpl->assign('NewEventHeader',          _PC_NEW_EVENT_HEADER);
	$tpl->assign('EventTitle',              _PC_EVENT_TITLE);
	$tpl->assign('Required',                _PC_REQUIRED);
	$tpl->assign('DateTimeTitle',           _PC_DATE_TIME);
	$tpl->assign('AlldayEventTitle',        _PC_ALLDAY_EVENT);
	$tpl->assign('TimedEventTitle',         _PC_TIMED_EVENT);
	$tpl->assign('TimedDurationTitle',      _PC_TIMED_DURATION);
	$tpl->assign('TimedDurationHoursTitle', _PC_TIMED_DURATION_HOURS);
	$tpl->assign('TimedDurationMinutesTitle',_PC_TIMED_DURATION_MINUTES);
	$tpl->assign('EventDescTitle',          _PC_EVENT_DESC);
    	
	//=================================================================
	//  PARSE INPUT_EVENT_TITLE
	//=================================================================
	$tpl->assign('InputEventTitle', 'event_subject');
	$tpl->assign('ValueEventTitle', pnVarPrepForDisplay($event_subject));
    
	//=================================================================
	//  PARSE SELECT_DATE_TIME
	//=================================================================
	$output->SetOutputMode(_PNH_RETURNOUTPUT);
	$output->SetOutputMode(_PNH_KEEPOUTPUT);
	$tpl->assign('InputAllday', 'event_allday');
	$tpl->assign('ValueAllday', '1');
	$tpl->assign('SelectedAllday', $event_allday==1 ? 'checked':'');
	$tpl->assign('InputTimed', 'event_allday');
	$tpl->assign('ValueTimed', '0');
	$tpl->assign('SelectedTimed', $event_allday==0 ? 'checked':'');
    
	//=================================================================
	//  PARSE SELECT_END_DATE_TIME
	//=================================================================
	$output->SetOutputMode(_PNH_RETURNOUTPUT);
	if(_SETTING_USE_INT_DATES) 
	{
		$sel_data = pnModAPIFunc('PostCalendar','user','buildDaySelect',array('pc_day'=>$day,'selected'=>$event_endday));
		$formdata = $output->FormSelectMultiple('event_endday', $sel_data);
		$sel_data = pnModAPIFunc('PostCalendar','user','buildMonthSelect',array('pc_month'=>$month,'selected'=>$event_endmonth));
		$formdata .= $output->FormSelectMultiple('event_endmonth', $sel_data);
	} 
	else 
	{
		$sel_data = pnModAPIFunc('PostCalendar','user','buildMonthSelect',array('pc_month'=>$month,'selected'=>$event_endmonth));
		$formdata = $output->FormSelectMultiple('event_endmonth', $sel_data);
		$sel_data = pnModAPIFunc('PostCalendar','user','buildDaySelect',array('pc_day'=>$day,'selected'=>$event_endday));
		$formdata .= $output->FormSelectMultiple('event_endday', $sel_data);
	}
	$sel_data = pnModAPIFunc('PostCalendar','user','buildYearSelect',array('pc_year'=>$year,'selected'=>$event_endyear));
	$formdata .= $output->FormSelectMultiple('event_endyear', $sel_data);
	$output->SetOutputMode(_PNH_KEEPOUTPUT);
	$tpl->assign('SelectEndDate', $formdata);
    
	//=================================================================
	//  PARSE SELECT_TIMED_EVENT
	//=================================================================

	/* // V4B RNG Start V4B SB START keep the default starttime of 0 hours so an allday event appears at the beginning of an eventlist.
	if (!$event_starttimeh)
		$event_starttimeh = 9;  // provide a reasonable default rather than 0 hours
	// V4B RNG End V4B SB END */

	$stimes = pnModAPIFunc('PostCalendar','user','buildTimeSelect',array('hselected'=>$event_starttimeh,'mselected'=>$event_starttimem));
    
	$output->SetOutputMode(_PNH_RETURNOUTPUT);
	$timed_hours = $output->FormSelectMultiple('event_starttimeh', $stimes['h']);
	$timed_minutes = $output->FormSelectMultiple('event_starttimem', $stimes['m']);
	if(!_SETTING_TIME_24HOUR) 
	{
		$ampm = array();
		$ampm[0]['id']          = pnVarPrepForStore(_AM_VAL);
		$ampm[0]['selected']    = $stimes['ap'] == _AM_VAL;
		$ampm[0]['name']        = pnVarPrepForDisplay(_PC_AM);
		$ampm[1]['id']          = pnVarPrepForStore(_PM_VAL);
		$ampm[1]['selected']    = $stimes['ap'] == _PM_VAL;
		$ampm[1]['name']        = pnVarPrepForDisplay(_PC_PM);
		$timed_ampm = $output->FormSelectMultiple('event_startampm', $ampm);
	} 
	else 
		$timed_ampm = '';

	$output->SetOutputMode(_PNH_KEEPOUTPUT);
    
	$tpl->assign('SelectTimedHours', $timed_hours);
	$tpl->assign('SelectTimedMinutes', $timed_minutes);
	$tpl->assign('SelectTimedAMPM', $timed_ampm);
    
	//=================================================================
	//  PARSE SELECT_DURATION
	//=================================================================

	// V4B RNG Start
	if (!$event_dur_hours)
		$event_dur_hours = 1;  // provide a reasonable default rather than 0 hours
	// V4B RNG End

	for($i=0; $i<=24; $i+=1) 
		$TimedDurationHours[$i] = array('value'=>$i,
						'selected'=>($event_dur_hours==$i ? 'selected':''),
						'name'=>sprintf('%02d',$i));

	$tpl->assign('TimedDurationHours',$TimedDurationHours);
	$tpl->assign('InputTimedDurationHours', 'event_dur_hours');
    
	for($i=0; $i<60; $i+=_SETTING_TIME_INCREMENT) 
		$TimedDurationMinutes[$i] = array('value'=>$i,
						  'selected'=>($event_dur_minutes==$i ? 'selected':''),
						  'name'=>sprintf('%02d',$i));

	$tpl->assign('TimedDurationMinutes',$TimedDurationMinutes);
	$tpl->assign('InputTimedDurationMinutes', 'event_dur_minutes');
    
	//=================================================================
	//  PARSE INPUT_EVENT_DESC
	//=================================================================
	$tpl->assign('InputEventDesc', 'event_desc');
	if(empty($pc_html_or_text)) 
	{
		$display_type = substr($event_desc,0,6);
		if($display_type == ':text:') 
		{
			$pc_html_or_text = 'text';
			$event_desc = substr($event_desc,6);
		} 
		elseif($display_type == ':html:') 
		{
			$pc_html_or_text = 'html';
			$event_desc = substr($event_desc,6);
		} 
		else 
			$pc_html_or_text = 'text';

		unset($display_type);
	}

	$tpl->assign('ValueEventDesc', pnVarPrepForDisplay($event_desc));
    	
	$eventHTMLorText  = "<select name=\"pc_html_or_text\">";
	if($pc_html_or_text == 'text') 
		$eventHTMLorText .= "<option value=\"text\" selected=\"selected\">"._PC_SUBMIT_TEXT."</option>";
	else 
		$eventHTMLorText .= "<option value=\"text\">"._PC_SUBMIT_TEXT."</option>";

	if($pc_html_or_text == 'html') 
		$eventHTMLorText .= "<option value=\"html\" selected=\"selected\">"._PC_SUBMIT_HTML."</option>";
	else 
		$eventHTMLorText .= "<option value=\"html\">"._PC_SUBMIT_HTML."</option>";

	$eventHTMLorText .= "</select>";
	$tpl->assign('EventHTMLorText',$eventHTMLorText);

    //=================================================================
    //  PARSE select_event_topic_block
    //=================================================================
    $tpl->assign('displayTopics',_SETTING_DISPLAY_TOPICS);
    if((bool)_SETTING_DISPLAY_TOPICS) {
        $a_topics = pnModAPIFunc('PostCalendar', 'user', 'getTopics');
		$topics = array();
		foreach($a_topics as $topic) {
			array_push($topics,array('value'=>$topic['topicid'],
                                     'selected'=>($topic['topicid']==$event_topic ? 'selected':''),
                                     'name'=>$topic['topictext']));
		}
		unset($a_topics);
        // only show this if we have topics to show
		if(count($topics) > 0) {
			$tpl->assign('topics',$topics);
        	$tpl->assign('EventTopicTitle', _PC_EVENT_TOPIC);
        	$tpl->assign('InputEventTopic', 'event_topic');
		}
	}
    
	//=================================================================
	//  PARSE select_event_type_block
	//=================================================================
	$categories = array();
	foreach($all_categories as $category) 
	{
		// FIXME !!!!!
		$categories[] = array('value'    => $category['catid'],
				      'selected' => ($category['catid']==$event_category ? 'selected' : ''),
				      'name'     => $category['catname'],
				      'color'    => $category['catcolor'],
				      'desc'     => $category['catdesc']);
	}


	// only show this if we have categories to show
	// you should ALWAYS have at least one valid category
	if(count($categories) > 0) 
	{
		$tpl->assign('categories',$categories);
		$tpl->assign('EventCategoriesTitle', _PC_EVENT_CATEGORY);
		$tpl->assign('InputEventCategory', 'event_category');
	} 

	//=================================================================
	//  PARSE event_sharing_block
	//=================================================================
	$data = array();
	if(_SETTING_ALLOW_USER_CAL) 
	{
		array_push($data,array(SHARING_PRIVATE,_PC_SHARE_PRIVATE));
		array_push($data,array(SHARING_PUBLIC,_PC_SHARE_PUBLIC));
		array_push($data,array(SHARING_BUSY,_PC_SHARE_SHOWBUSY));
	}

	if(pnSecAuthAction(0,'PostCalendar::', '::', ACCESS_ADMIN) || _SETTING_ALLOW_GLOBAL || !_SETTING_ALLOW_USER_CAL) 
	{
    		array_push($data,array(SHARING_GLOBAL,_PC_SHARE_GLOBAL));
        	array_push($data,array(SHARING_HIDEDESC,_PC_SHARE_HIDEDESC));
	}

	// V4B RNG Start
	if (!isset($event_sharing))
		$event_sharing = 1;
	// V4B RNG End

	$sharing = array();
	foreach($data as $cell) 
        	array_push($sharing,array('value'=>$cell[0],
					  'selected'=>((int) $event_sharing == $cell[0] ? 'selected' : ''),
					  'name'=>$cell[1]));

	$tpl->assign('sharing',$sharing);
	$tpl->assign('EventSharingTitle', _PC_SHARING);
	$tpl->assign('InputEventSharing','event_sharing');
	//=================================================================
	//  location information
	//=================================================================
	$tpl->assign('EventLocationTitle',  _PC_EVENT_LOCATION);
	$tpl->assign('InputLocation',       'event_location');
	$tpl->assign('ValueLocation',       pnVarPrepForDisplay($event_location));
	$tpl->assign('EventStreetTitle',    _PC_EVENT_STREET);
	$tpl->assign('InputStreet1',        'event_street1');
	$tpl->assign('ValueStreet1',        pnVarPrepForDisplay($event_street1));
	$tpl->assign('InputStreet2',        'event_street2');
	$tpl->assign('ValueStreet2',        pnVarPrepForDisplay($event_street2));
	$tpl->assign('EventCityTitle',      _PC_EVENT_CITY);
	$tpl->assign('InputCity',           'event_city');
	$tpl->assign('ValueCity',           pnVarPrepForDisplay($event_city));
	$tpl->assign('EventStateTitle',     _PC_EVENT_STATE);
	$tpl->assign('InputState',          'event_state');
	$tpl->assign('ValueState',          pnVarPrepForDisplay($event_state));
	$tpl->assign('EventPostalTitle',    _PC_EVENT_POSTAL);
	$tpl->assign('InputPostal',         'event_postal');
	$tpl->assign('ValuePostal',         pnVarPrepForDisplay($event_postal));
	//=================================================================
	//  contact information
	//=================================================================
	$tpl->assign('EventContactTitle',   _PC_EVENT_CONTACT);
	$tpl->assign('InputContact',        'event_contname');
	$tpl->assign('ValueContact',        pnVarPrepForDisplay($event_contname));
	$tpl->assign('EventPhoneTitle',     _PC_EVENT_PHONE);
	$tpl->assign('InputPhone',          'event_conttel');
	$tpl->assign('ValuePhone',          pnVarPrepForDisplay($event_conttel));
	$tpl->assign('EventEmailTitle',     _PC_EVENT_EMAIL);
	$tpl->assign('InputEmail',          'event_contemail');
	$tpl->assign('ValueEmail',          pnVarPrepForDisplay($event_contemail));
	$tpl->assign('EventWebsiteTitle',   _PC_EVENT_WEBSITE);
	$tpl->assign('InputWebsite',        'event_website');
	$tpl->assign('ValueWebsite',        pnVarPrepForDisplay($event_website));
	$tpl->assign('EventFeeTitle',       _PC_EVENT_FEE);
	$tpl->assign('InputFee',            'event_fee');
	$tpl->assign('ValueFee',            pnVarPrepForDisplay($event_fee));
	//=================================================================
	//  Repeating Information
	//=================================================================
	$tpl->assign('RepeatingHeader',     _PC_REPEATING_HEADER);
	$tpl->assign('NoRepeatTitle',       _PC_NO_REPEAT);
	$tpl->assign('RepeatTitle',         _PC_REPEAT);
	$tpl->assign('RepeatOnTitle',       _PC_REPEAT_ON);
	$tpl->assign('OfTheMonthTitle',     _PC_OF_THE_MONTH);
	$tpl->assign('EndDateTitle',        _PC_END_DATE);
	$tpl->assign('NoEndDateTitle',      _PC_NO_END);
	$tpl->assign('InputNoRepeat', 'event_repeat');
	$tpl->assign('ValueNoRepeat', '0');
	$tpl->assign('SelectedNoRepeat', (int) $event_repeat==0 ? 'checked':'');
	$tpl->assign('InputRepeat', 'event_repeat');
	$tpl->assign('ValueRepeat', '1');
	$tpl->assign('SelectedRepeat', (int) $event_repeat==1 ? 'checked':'');
    
	unset($in); 
	$in = array(_PC_EVERY,_PC_EVERY_OTHER,_PC_EVERY_THIRD,_PC_EVERY_FOURTH);
	$keys = array(REPEAT_EVERY,REPEAT_EVERY_OTHER,REPEAT_EVERY_THIRD,REPEAT_EVERY_FOURTH);
	$repeat_freq = array();
	foreach($in as $k=>$v) 
        	array_push($repeat_freq,array('value'=>$keys[$k],
						'selected'=>($keys[$k]==$event_repeat_freq?'selected':''),
						'name'=>$v));

	$tpl->assign('InputRepeatFreq','event_repeat_freq');
	if(empty($event_repeat_freq) || $event_repeat_freq < 1) $event_repeat_freq = 1;
		$tpl->assign('InputRepeatFreqVal',$event_repeat_freq);
	$tpl->assign('repeat_freq',$repeat_freq);
    
	unset($in); 
	$in = array(_PC_EVERY_DAY,_PC_EVERY_WEEK,_PC_EVERY_MONTH,_PC_EVERY_YEAR);
	$keys = array(REPEAT_EVERY_DAY,REPEAT_EVERY_WEEK,REPEAT_EVERY_MONTH,REPEAT_EVERY_YEAR);
	$repeat_freq_type = array();
	foreach($in as $k=>$v) 
		array_push($repeat_freq_type,array('value'=>$keys[$k],
						   'selected'=>($keys[$k]==$event_repeat_freq_type?'selected':''),
						   'name'=>$v));

	$tpl->assign('InputRepeatFreqType','event_repeat_freq_type');
	$tpl->assign('repeat_freq_type',$repeat_freq_type);
    
	$tpl->assign('InputRepeatOn', 'event_repeat');
	$tpl->assign('ValueRepeatOn', '2');
	$tpl->assign('SelectedRepeatOn', (int) $event_repeat==2 ? 'checked':'');
    
	unset($in); 
	$in = array(_PC_EVERY_1ST,_PC_EVERY_2ND,_PC_EVERY_3RD,_PC_EVERY_4TH,_PC_EVERY_LAST);
	$keys = array(REPEAT_ON_1ST,REPEAT_ON_2ND,REPEAT_ON_3RD,REPEAT_ON_4TH,REPEAT_ON_LAST);
	$repeat_on_num = array();
	foreach($in as $k=>$v) 
		array_push($repeat_on_num,array('value'=>$keys[$k],
						'selected'=>($keys[$k]==$event_repeat_on_num?'selected':''),
						'name'=>$v));

	$tpl->assign('InputRepeatOnNum', 'event_repeat_on_num');
	$tpl->assign('repeat_on_num',$repeat_on_num);
    
	unset($in); 
	$in = array(_PC_EVERY_SUN,_PC_EVERY_MON,_PC_EVERY_TUE,_PC_EVERY_WED,_PC_EVERY_THU,_PC_EVERY_FRI,_PC_EVERY_SAT);
	$keys = array(REPEAT_ON_SUN,REPEAT_ON_MON,REPEAT_ON_TUE,REPEAT_ON_WED,REPEAT_ON_THU,REPEAT_ON_FRI,REPEAT_ON_SAT);
	$repeat_on_day = array();
	foreach($in as $k=>$v) 
		array_push($repeat_on_day,array('value'=>$keys[$k],
						'selected'=>($keys[$k]==$event_repeat_on_day ? 'selected' : ''),
						'name'=>$v));

	$tpl->assign('InputRepeatOnDay', 'event_repeat_on_day');
	$tpl->assign('repeat_on_day',$repeat_on_day);
    
	unset($in); 
	$in = array(_PC_OF_EVERY_MONTH,_PC_OF_EVERY_2MONTH,_PC_OF_EVERY_3MONTH,_PC_OF_EVERY_4MONTH,_PC_OF_EVERY_6MONTH,_PC_OF_EVERY_YEAR);
	$keys = array(REPEAT_ON_MONTH,REPEAT_ON_2MONTH,REPEAT_ON_3MONTH,REPEAT_ON_4MONTH,REPEAT_ON_6MONTH,REPEAT_ON_YEAR);
	$repeat_on_freq = array();
	foreach($in as $k=>$v) 
		array_push($repeat_on_freq,array('value'=>$keys[$k],
						 'selected'=>($keys[$k] == $event_repeat_on_freq ? 'selected' : ''),
						 'name'=>$v));

	$tpl->assign('InputRepeatOnFreq', 'event_repeat_on_freq');
	if(empty($event_repeat_on_freq) || $event_repeat_on_freq < 1) 
		$event_repeat_on_freq = 1;
	$tpl->assign('InputRepeatOnFreqVal', $event_repeat_on_freq);
	$tpl->assign('repeat_on_freq',$repeat_on_freq);
	$tpl->assign('MonthsTitle',_PC_MONTHS);
    
	//=================================================================
	//  PARSE INPUT_END_DATE
	//=================================================================
	$tpl->assign('InputEndOn', 'event_endtype');
	$tpl->assign('ValueEndOn', '1');
	$tpl->assign('SelectedEndOn', (int) $event_endtype==1 ? 'checked':'');
	//=================================================================
	//  PARSE INPUT_NO_END
	//=================================================================
	$tpl->assign('InputNoEnd', 'event_endtype');
	$tpl->assign('ValueNoEnd', '0');
	$tpl->assign('SelectedNoEnd', (int) $event_endtype==0 ? 'checked':'');
    
	$output->SetOutputMode(_PNH_RETURNOUTPUT);
	$authkey = $output->FormHidden('authid',pnSecGenAuthKey());
	$output->SetOutputMode(_PNH_KEEPOUTPUT);
    
	$form_hidden = "<input type=\"hidden\" name=\"is_update\" value=\"$is_update\" />";
	$form_hidden .= "<input type=\"hidden\" name=\"pc_event_id\" value=\"$pc_event_id\" />";
	if(isset($data_loaded)) 
	{
		$form_hidden .= "<input type=\"hidden\" name=\"data_loaded\" value=\"$data_loaded\" />";
		$tpl->assign('FormHidden',$form_hidden);
	}
	$form_submit = '<select name="form_action">
			<option value="preview">'._PC_EVENT_PREVIEW.'</option>
			<option value="commit" selected>'._PC_EVENT_SUBMIT.'</option>
			</select>'.$authkey.'<input type="submit" name="_submit" value="go" onclick="return selectItems();">' ; // V4B SB added Javascript call to Button
	$tpl->assign('FormSubmit',$form_submit);

	// do not cache this page
	$output->Text($tpl->fetch("event/postcalendar_event_submit.html"));
	$output->Text(postcalendar_footer());
	return $output->GetOutput();
}

function postcalendar_eventapi_fixEventDetails($event)
{
	// there has to be a more intelligent way to do this
	@list($event['duration_hours'],$dmin) = @explode('.',($event['duration']/60/60));
	$event['duration_minutes'] = substr(sprintf('%.2f','.' . 60*($dmin/100)),2,2);
	// ---
	
	$suid = pnUserGetVar('uid');
//	$euid = DBUtil::selectFieldByID ('users', 'uid', $event['uname'], 'uname');
	$euid = $event['aid'];
	
	// is this a public event to be shown as busy?
	if($event['sharing'] == SHARING_PRIVATE && $euid != $suid) 
	{
		// they are not supposed to see this
		return false;
	} 
	elseif($event['sharing'] == SHARING_BUSY && $euid != $suid) 
	{
		// make it not display any information
		$event['title']     = _USER_BUSY_TITLE;
		$event['hometext']  = _USER_BUSY_MESSAGE;
		$event['desc']      = _USER_BUSY_MESSAGE;

		$fields = array ('event_location', 'conttel', 'contname', 'contemail', 'website', 'fee', 
		                 'event_street1', 'event_street2', 'event_city', 'event_state', 'event_postal');
		foreach ($fields as $field)
		    $event[$field] = '';
	} 
	else
	{
		// FIXME: this entire thing should be a sub-array 
		$location = unserialize($event['location']);
		$event['event_location'] = $location['event_location'];
		$event['event_street1']  = $location['event_street1'];
		$event['event_street2']  = $location['event_street2'];
		$event['event_city']     = $location['event_city'];
		$event['event_state']    = $location['event_state'];
		$event['event_postal']   = $location['event_postal'];
		//$event['date']     = str_replace('-','',$Date);
	}

	return $event;
}

//function postcalendar_userapi_pcGetEventDetails($eid) 
function postcalendar_eventapi_getEventDetails($eid) 
{
    
	if(!isset($eid))
		return false;

        // FIXME !!!
	$joinInfo = array ();
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catname',
				'object_field_name'   =>  'catname',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catdesc',
				'object_field_name'   =>  'catdesc',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catcolor',
				'object_field_name'   =>  'catcolor',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');
	// FIXME!!!!!!
	//$joinInfo[] = array (   'join_table'          =>  'topics',
				//'join_field'          =>  'topictext',
				//'object_field_name'   =>  'topictext',
				//'compare_field_table' =>  'topicid',
				//'compare_field_join'  =>  'topic');
	$event = DBUtil::selectExpandedObjectByID ('postcalendar_events', $joinInfo, $eid, 'eid');
	//	$event = postcalendar_userapi_pcFixEventDetails ($event);
	$event = pnModAPIFunc('PostCalendar','event','fixEventDetails',$event);
	return $event;
}

/**
 *  postcalendar_userapi_eventDetail
 *  Creates the detailed event display and outputs html.  
 *  Accepts an array of key/value pairs
 *  @param int $eid the id of the event to display
 *  @return string html output 
 *  @access public               
 */
//function postcalendar_adminapi_eventDetail($args) { return postcalendar_userapi_eventDetail($args,true); }
//function postcalendar_userapi_eventDetail($args,$admin=false)
function postcalendar_eventapi_eventDetail($args)
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_READ)) {
		return LogUtil::registerPermissionError();
	}

	$popup = FormUtil::getPassedValue('popup');
	extract($args); 
	unset($args);

	if (!$admin) $admin = false;

	if(!isset($cacheid)) $cacheid = null;
	if(!isset($eid)) return false;
	if(!isset($nopop)) $nopop = false;
	$uid = pnUserGetVar('uid');

	//$tpl = pnRender::getInstance('PostCalendar');
	//PostCalendarSmartySetup($tpl);
		/* Trim as needed */
			$func  = FormUtil::getPassedValue('func');
			$template_view = FormUtil::getPassedValue('tplview');
			if (!$template_view) $template_view = 'month'; 
			$function_out['FUNCTION'] = $func;
			$function_out['TPL_VIEW'] = $template_view;
		/* end */
  
	if($admin) {
		$function_out['template'] = "admin/postcalendar_admin_view_event_details.htm";
		$function_out['Date'] = postcalendar_getDate();
	} else {
		$function_out['template'] = "user/postcalendar_user_view_event_details.html";
	}
	if ($popup == true) $function_out['template'] = "user/postcalendar_user_view_popup.html";
	
	// let's get the DB information
	//$event = postcalendar_userapi_pcGetEventDetails($eid);
	$event = pnModAPIFunc('PostCalendar','event','getEventDetails',$eid);
	// if the above is false, it's a private event for another user
	// we should not diplay this - so we just exit gracefully
	if($event === false) 
		return false; 
	
	// since recurrevents are dynamically calculcated, we need to change the date 
	// to ensure that the correct/current date is being displayed (rather than the 
	// date on which the recurring booking was executed).	
	if ($event['recurrtype'])
	{
		$y = substr ($Date, 0, 4);
		$m = substr ($Date, 4, 2);
		$d = substr ($Date, 6, 2);
		$event['eventDate'] = "$y-$m-$d";
	}
	
	// populate the template
	$display_type = substr($event['hometext'],0,6);
	if($display_type == ':text:') {
		$prepFunction = 'pcVarPrepForDisplay';
		$event['hometext'] = substr($event['hometext'],6);
	} elseif($display_type == ':html:') {
		$prepFunction = 'pcVarPrepHTMLDisplay';
		$event['hometext'] = substr($event['hometext'],6);
	} else {
		$prepFunction = 'pcVarPrepHTMLDisplay';
	}
	
	unset($display_type);
	// prep the vars for output
	$event['title']     = $prepFunction($event['title']); 
	$event['hometext']  = $prepFunction($event['hometext']);
	$event['desc']      = $event['hometext'];
	$event['conttel']   = $prepFunction($event['conttel']);
	$event['contname']  = $prepFunction($event['contname']);
	$event['contemail'] = $prepFunction($event['contemail']);
	$event['website']   = $prepFunction(postcalendar_makeValidURL($event['website']));
	$event['fee']       = $prepFunction($event['fee']);
	$event['location']  = $prepFunction($event['event_location']);
	$event['street1']   = $prepFunction($event['event_street1']);
	$event['street2']   = $prepFunction($event['event_street2']);
	$event['city']      = $prepFunction($event['event_city']);
	$event['state']     = $prepFunction($event['event_state']);
	$event['postal']    = $prepFunction($event['event_postal']);
	$function_out['A_EVENT'] = $event;
	
	if(!empty($event['location']) || !empty($event['street1']) ||
		!empty($event['street2']) || !empty($event['city']) ||
		!empty($event['state']) || !empty($event['postal'])) 
		$function_out['LOCATION_INFO'] = true;
	else 
		$function_out['LOCATION_INFO'] = false;
	
	if(!empty($event['contname']) || !empty($event['contemail']) ||
		!empty($event['conttel']) || !empty($event['website'])) 
		$function_out['CONTACT_INFO'] = true;
	else 
		$function_out['CONTACT_INFO'] = false;
	
	// determine meeting participants
	$participants = array();
	if ($event['meeting_id'])
	{
		$where     = 'WHERE pc_meeting_id=' . pnVarPrepForStore ($event['meeting_id']);
		$attendees = DBUtil::selectFieldArray ('postcalendar_events', 'aid', $where);
	
		// FIXME: do we need this here? Just to do a lookup? 
		$ca = array();
		$ca['uid'] = 'uid';
		$ca['uname'] = 'uname';
		$users = DBUtil::selectObjectArray ('users', '', '', -1, -1, 'uid', null, $ca);

		foreach ($attendees as $uid)
			$participants[] = $users[$uid]['uname'];

		sort ($participants);
	}
	$function_out['participants'] = $participants;


	//=================================================================
	//  populate the template $ADMIN_OPTIONS
	//=================================================================
	$target='';
	if(_SETTING_OPEN_NEW_WINDOW) 
		$target = 'target="csCalendar"';

	$admin_edit_url = $admin_delete_url = '';
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) 
	{
		$admin_edit_url     = pnModURL('PostCalendar','admin','submit',array('eid'=>$eid));
		//$admin_delete_url   = pnModURL('PostCalendar','admin','adminevents',array('action'=>_ACTION_DELETE,'eid'=>$eid));
		$admin_delete_url   = pnModURL('PostCalendar','user','delete',array('eid'=>$eid));
		$admin_copy_url     = pnModURL('PostCalendar','admin','submit',array('eid'=>$eid,'form_action'=>'copy'));
	}
	$user_edit_url = $user_delete_url = '';
	
	if(pnUserLoggedIn()) 
		$logged_in_uid = pnUserGetVar('uid');
	else 
		$logged_in_uid = 1;
	
	$can_edit = false;
	if ((pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD) && $logged_in_uid == $event['aid']) || 
	     pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN))
	{
		$user_edit_url     = pnModURL('PostCalendar','user','submit',array('eid'=>$eid));
		$user_delete_url   = pnModURL('PostCalendar','user','delete',array('eid'=>$eid));
		// v4b TS start 1 line
		$user_copy_url     = pnModURL('PostCalendar','user','submit',array('eid'=>$eid,'form_action'=>'copy'));
		$can_edit = true;
	}

	$function_out['ADMIN_TARGET'] = $target;
	$function_out['ADMIN_EDIT'] = $admin_edit_url;
	$function_out['ADMIN_DELETE'] = $admin_delete_url;
	// v4b TS start 2 lines
	$function_out['ADMIN_COPY'] = $admin_copy_url;
	$function_out['USER_COPY'] = $user_copy_url;

	$function_out['USER_TARGET'] = $target;
	$function_out['USER_EDIT'] = $user_edit_url;
	$function_out['USER_DELETE'] = $user_delete_url;
	$function_out['USER_CAN_EDIT'] = $can_edit;

	/*
	if($popup != 1) {    
		return $function_out;	
	} else {
		// this concept needs to be changed to simply use a different template if using a popup. CAH 5/9/09
		$theme = pnUserGetTheme();
		$function_out['raw1'] = "<html><head></head><body>\n";
	  //$tpl->display("view_event_details.html",$cacheid);
		
		$function_out['raw2'] .= postcalendar_footer();
		// V4B TS start ***  Hook code for displaying stuff for events in popup
		if ($_GET["type"] != "admin") {
			$hooks = pnModCallHooks('item', 'display', $eid, "index.php?module=PostCalendar&type=user&func=view&viewtype=details&eid=$eid&popup=1");
			$function_out['raw2'] .= $hooks;
		} 
		// V4B TS end ***  End of Hook code
		$function_out['raw2'] .= "\n</body></html>";
		//session_write_close();
		//exit;
		$function_out['displayaspopup'] = true;
		return $function_out;
	}
	*/
	return $function_out;
}
/**
 *	postcalendar_eventapi_update()
 *	expected args: eventarray=array([id]=>array([id]=>[idval],[colname]=>[newval],
 *		[id2]=>array([id2]=>[idval],[colname]=>[newval])
 *
 */
function postcalendar_eventapi_update($eventarray) {
	if (!is_array($eventarray)) return false;
	$res=DBUtil::updateObjectArray ($eventarray, 'postcalendar_events', 'eid');
	if ($res) { return true; } else { return false; }
}
/**
 *  postcalendar_eventapi_deleteevent
 *  This function deletes one event provided the event ID (eid)
 *	expected args: args=array(['eid']=>idval)
 *
 */
function postcalendar_eventapi_deleteevent($args)
{
	return DBUtil::deleteObjectByID('postcalendar_events', $args['eid'], 'eid');
}
/**
 *  postcalendar_eventapi_deleteeventarray
 *  This function deletes several events when provided an array of ids
 *	expected args: args=array([idval]=>val,[idval2]=>val,[idval3]=>val...)
 *  note the vals are not used. just the keys
 *
 */
function postcalendar_eventapi_deleteeventarray($args)
{
	if (!is_array($args)) return false;
	return DBUtil::deleteObjectsFromKeyArray ($args, 'postcalendar_events', 'eid');
}
?>