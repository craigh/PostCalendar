<?php
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

require_once ('modules/PostCalendar/global.php');
 
//=========================================================================
//  utility functions for postcalendar
//=========================================================================
function PostCalendarSmartySetup (&$smarty)
{
	$smarty->assign('USE_POPUPS', _SETTING_USE_POPUPS);
	$smarty->assign('USE_TOPICS', _SETTING_DISPLAY_TOPICS);
	$smarty->assign('USE_INT_DATES', _SETTING_USE_INT_DATES);
	$smarty->assign('OPEN_NEW_WINDOW', _SETTING_OPEN_NEW_WINDOW);
	$smarty->assign('EVENT_DATE_FORMAT', _SETTING_DATE_FORMAT);
	$smarty->assign('HIGHLIGHT_COLOR', _SETTING_DAY_HICOLOR);
	$smarty->assign('24HOUR_TIME', _SETTING_TIME_24HOUR);
	return true;
}
function pcDebugVar($in)
{
	echo '<pre>';
	if(is_array($in)) print_r($in);
	else echo $in;
	echo '</pre>';
}

function pcVarPrepForDisplay($s) 
{ 
	$s = nl2br(pnVarPrepForDisplay(postcalendar_removeScriptTags($s)));
	$s = preg_replace('/&amp;(#)?([0-9a-z]+);/i','&\\1\\2;',$s);
	return $s;
}

function pcVarPrepHTMLDisplay($s) 
{ 
	return pnVarPrepHTMLDisplay(postcalendar_removeScriptTags($s)); 
}

function pcGetTopicName($topicid)
{
	return DBUtil::selectFieldByID ('topics', 'topicname', $topicid, 'topicid');
}

function postcalendar_makeValidURL($s)
{
	if(empty($s)) 
		return '';

	if(!preg_match('|^http[s]?:\/\/|i',$s)) 
		$s = 'http://'.$s;

	return $s;
}

function postcalendar_removeScriptTags($in)
{
	return preg_replace("/<script.*?>(.*?)<\/script>/","",$in);
}

function postcalendar_getDate($format='%Y%m%d%H%M%S')
{
	$Date      = FormUtil::getPassedValue('Date');
	$jumpday   = FormUtil::getPassedValue('jumpday');
	$jumpmonth = FormUtil::getPassedValue('jumpmonth');
	$jumpyear  = FormUtil::getPassedValue('jumpyear');

//	if(!isset($Date)) 
	if(empty($Date)) 
	{
		// if we still don't have a date then calculate it
		$time = time();
		if (pnUserLoggedIn())  $time += (pnUserGetVar('timezone_offset') - pnConfigGetVar('timezone_offset')) * 3600;
		// check the jump menu
        	if(!isset($jumpday))   $jumpday = strftime('%d',$time);
        	if(!isset($jumpmonth)) $jumpmonth = strftime('%m',$time);
        	if(!isset($jumpyear))  $jumpyear = strftime('%Y',$time);
        	$Date = (int) "$jumpyear$jumpmonth$jumpday";
	}

	$y = substr($Date,0,4);
	$m = substr($Date,4,2);
	$d = substr($Date,6,2);
	return strftime($format,mktime(0,0,0,$m,$d,$y));
}

function postcalendar_today($format='%Y%m%d')
{	
	return DateUtil::getDatetime('',$format);
	/*
	$time = time();
	if (pnUserLoggedIn()) 
		$time += (pnUserGetVar('timezone_offset') - pnConfigGetVar('timezone_offset')) * 3600;

	return strftime($format,$time);
	*/
}
/**
 * postcalendar_adminapi_getmonthname()
 *
 * Returns the month name translated for the user's current language
 *
 * @param array $args['Date'] number of month to return
 * @return string month name in user's language
 */
function postcalendar_adminapi_getmonthname($args) 
{ 
	return postcalendar_userapi_getmonthname($args); 
}

/**
 * postcalendar_userapi_getmonthname()
 *
 * Returns the month name translated for the user's current language
 *
 * @param array $args['Date'] date to return month name of
 * @return string month name in user's language
 */
function postcalendar_userapi_getmonthname($args)
{   
	extract($args); 
	unset($args);

	if(!isset($Date)) 
		return false; 

	$month_name = array('01' => _CALJAN, '02' => _CALFEB, '03' => _CALMAR,
			    '04' => _CALAPR, '05' => _CALMAY, '06' => _CALJUN,
			    '07' => _CALJUL, '08' => _CALAUG, '09' => _CALSEP,
			    '10' => _CALOCT, '11' => _CALNOV, '12' => _CALDEC);
	return $month_name[date('m',$Date)];
}
/**
 *  Returns an array of form data for FormSelectMultiple
 */
function postcalendar_adminapi_buildTimeSelect($args) 
{ 
	return postcalendar_userapi_buildTimeSelect($args); 
}

function postcalendar_userapi_buildTimeSelect($args) 
{   
	$inc = _SETTING_TIME_INCREMENT;
	extract($args); unset($args);
	$output = array('h'=>array(),'m'=>array(),'ap'=>1);

	if((bool)_SETTING_TIME_24HOUR) 
	{
		$start=0; 
		$end=23; 
	}
	else 
	{ 
		$start=1; 
		$end=12;
		// $hselected = $hselected > 12 ? $hselected-=12 : $hselected; 
		if ($hselected > 12)
		{
			$hselected = $hselected - 12;
			$output['ap'] = 2; //PM
		}
	}
    
	for($c=0,$h=$start; $h<=$end; $h++,$c++) 
	{
	$hour = sprintf('%02d',$h);
	$output['h'][$c]['id']         = pnVarPrepForStore($h);
	$output['h'][$c]['selected']   = $hselected == $hour;
	$output['h'][$c]['name']       = pnVarPrepForDisplay($hour);
	}
    
	for($c=0,$m=0; $m<=(60-$inc);$m+=$inc,$c++) 
	{
		$min = sprintf('%02d',$m);
		$output['m'][$c]['id']         = pnVarPrepForStore($m);
		$output['m'][$c]['selected']   = $mselected == $min;
		$output['m'][$c]['name']       = pnVarPrepForDisplay($min);
	}

	return $output;
}

/**
 *  Returns an array of form data for FormSelectMultiple
 */
function postcalendar_adminapi_buildMonthSelect($args) { return postcalendar_userapi_buildMonthSelect($args); }
function postcalendar_userapi_buildMonthSelect($args) 
{
    extract($args); unset($args);
    if(!isset($pc_month)) { $pc_month = Date_Calc::getMonth(); } 
    // create the return object to be inserted into the form
    $output = array();
    if(!isset($selected)) $selected = '';
    for ($c=0,$i=1;$i<=12;$i++,$c++) {
        if ($selected)              { $sel = $selected == $i ? true : false; }
        elseif ($i == $pc_month)    { $sel = true; } 
        else                        { $sel = false; }
        $output[$c]['id']       = sprintf('%02d',$i);
        $output[$c]['selected'] = $sel;
        $output[$c]['name']     = postcalendar_userapi_getmonthname(array('Date'=>mktime(0,0,0,$i,15)));
    }
    return $output;
}

/**
 *  Returns an array of form data for FormSelectMultiple
 */
function postcalendar_adminapi_buildDaySelect($args) { return postcalendar_userapi_buildDaySelect($args); }
function postcalendar_userapi_buildDaySelect($args) 
{   
    extract($args); unset($args);
    if(!isset($pc_day)) { $pc_day = Date_Calc::getDay(); }
    // create the return object to be inserted into the form
    $output = array();
    if(!isset($selected)) $selected = '';
    for($c=0,$i=1; $i<=31; $i++,$c++) {   
        if ($selected)          { $sel = $selected == $i ? true : false; }
        elseif ($i == $pc_day)  { $sel = true; } 
        else                    { $sel = false; }
        $output[$c]['id']       = sprintf('%02d',$i);
        $output[$c]['selected'] = $sel;
        $output[$c]['name']     = sprintf('%02d',$i);
    }
    return $output;
}

/**
 *  Returns an array of form data for FormSelectMultiple
 */
function postcalendar_adminapi_buildYearSelect($args) { return postcalendar_userapi_buildYearSelect($args); }
function postcalendar_userapi_buildYearSelect($args) 
{   
    extract($args); unset($args);
    if(!isset($pc_year)) { $pc_year = date('Y'); }
    // create the return object to be inserted into the form
    $output = array();
    // we want the list to contain 10 years before today and 30 years after
    // maybe this will eventually become a user defined value
    $pc_start_year = date('Y') - 10;
    $pc_end_year = date('Y') + 30;
    if(!isset($selected)) $selected = '';
    for($c=0,$i=$pc_start_year; $i<=$pc_end_year; $i++,$c++) {   
        if ($selected)          { $sel = $selected == $i ? true : false; } 
        elseif ($i == $pc_year) { $sel = true; } 
        else                    { $sel = false; }
        $output[$c]['id']       = sprintf('%04d',$i);
        $output[$c]['selected'] = $sel;
        $output[$c]['name']     = sprintf('%04d',$i);
    }
    return $output;
}

function postcalendar_adminapi_getCategories() { return postcalendar_userapi_getCategories(); }
function postcalendar_userapi_getCategories()
{
	return DBUtil::selectObjectArray ('postcalendar_categories', '', 'catname');
}

function postcalendar_adminapi_getTopics() { return postcalendar_userapi_getTopics(); }
function postcalendar_userapi_getTopics()
{
	$permFilter = array();
	$permFilter[] = array('realm' => 0,
	                      'component_left'   => 'PostCalendar',
	                      'component_middle' => '',
	                      'component_right'  => 'Topic',
	                      'instance_left'    => 'topicid',
	                      'instance_middle'  => '',
	                      'instance_right'   => 'topicname',
	                      'level'            => ACCESS_OVERVIEW);

	return DBUtil::selectObjectArray ('topics', '', 'topictext', -1, -1, '', $permFilter);
}

function pc_notify($eid,$is_update)
{
	if(!(bool)_SETTING_NOTIFY_ADMIN) 
		return true; 
	
	$subject = _PC_NOTIFY_SUBJECT;
	
	if((bool)$is_update) 
		$message = _PC_NOTIFY_UPDATE_MSG;
	else 
		$message = _PC_NOTIFY_NEW_MSG;
	
	$modinfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
	$modversion = pnVarPrepForOS($modinfo['version']);
	unset($modinfo);
	
	$message .= pnModURL(__POSTCALENDAR__,'admin','adminevents',array('pc_event_id'=>$eid,'action'=>_ADMIN_ACTION_VIEW));
	$message .= "\n\n\n\n";
	$message .= "----\n";
	$message .= "PostCalendar $modversion\n";
	$message .= "http://www.postcalendar.tv";
	
	mail(_SETTING_NOTIFY_EMAIL,$subject,$message,
		  "From: " . _SETTING_NOTIFY_EMAIL . "\r\n"
		  ."X-Mailer: PHP/" . phpversion() . "\r\n"
		  ."X-Mailer: PostCalendar/$modversion" );
		  
	return true;
}
/** HERE **/

function postcalendar_footer()
{   
	// lets get the module's information
	//$modinfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
	//$footer = "<p align=\"right\"><a href=\"http://www.postcalendar.tv\">PostCalendar v$modinfo[version]</a></p>";
	//return $footer;
	return '';
}

function postcalendar_smarty_pc_sort_day($params, &$smarty)
{
	extract($params);

  	if (empty($var)) 
	{
        	$smarty->trigger_error("sort_array: missing 'var' parameter");
        	return;
    	}

	if (!in_array('value', array_keys($params))) 
	{
		$smarty->trigger_error("sort_array: missing 'value' parameter");
		return;
	}
	
	if (!in_array('order', array_keys($params))) 
		$order = 'asc';
	
	if (!in_array('inc', array_keys($params))) 
		$inc = '15';
	
	if (!in_array('start', array_keys($params))) 
	{
        	$sh = '08';
		$sm = '00';
	} 
	else 
	{
		list($sh,$sm) = explode(':',$start);
	}
	
	if (!in_array('end', array_keys($params))) 
	{
		$eh = '21';
		$em = '00';
	} 
	else 
	{
		list($eh,$em) = explode(':',$end);
	}
	
	if(strtolower($order) == 'asc') 
		$function = 'sort_byTimeA';

	if(strtolower($order) == 'desc') 
		$function = 'sort_byTimeD';
	
	foreach($value as $events) 
	{
		usort($events,$function);
		$newArray = $events;
	}
	
	// here we want to create an intelligent array of
	// columns and rows to build a nice day view
	$ch = $sh; $cm = $sm;
	while("$ch:$cm" <= "$eh:$em") 
	{
		$hours["$ch:$cm"] = array();
		$cm += $inc;
		if($cm >= 60) 
		{
			$cm = '00';
			$ch = sprintf('%02d',$ch+1);
		}
	}
	
	$alldayevents = array();
	foreach($newArray as $event) 
	{
		list($sh,$sm,$ss) = explode(':',$event['startTime']);
		$eh = sprintf('%02d',$sh + $event['duration_hours']);
		$em = sprintf('%02d',$sm + $event['duration_minutes']);
		
		if($event['alldayevent']) 
		{
			// we need an entire column . save till later
			$alldayevents[] = $event;
		} 
		else 
		{
			//find open time slots - avoid overlapping
			$needed = array();
			$ch = $sh; $cm = $sm;
			//what times do we need?
			while("$ch:$cm" < "$eh:$em") 
			{
				$needed[] = "$ch:$cm";
				$cm += $inc;
				if($cm >= 60) 
				{
					$cm = '00';
					$ch = sprintf('%02d',$ch+1);
				}
			}
			$i = 0;
			foreach($needed as $time) 
			{
				if($i==0) 
				{
					$hours[$time][] = $event;
					$key = count($hours[$time])-1;
				} 
				else 
				{
					$hours[$time][$key] = 'continued';
				}
				$i++;
			}
		}
	}
	$smarty->assign_by_ref($var,$hours);
}

function sort_byCategoryA($a,$b) 
{
	if($a['catname'] < $b['catname']) return -1;
	elseif($a['catname'] > $b['catname']) return 1;
}
function sort_byCategoryD($a,$b) 
{
	if($a['catname'] < $b['catname']) return 1;
	elseif($a['catname'] > $b['catname']) return -1;
}
function sort_byTitleA($a,$b) 
{
	if($a['title'] < $b['title']) return -1;
	elseif($a['title'] > $b['title']) return 1;
}
function sort_byTitleD($a,$b) 
{
	if($a['title'] < $b['title']) return 1;
	elseif($a['title'] > $b['title']) return -1;
}
function sort_byTimeA($a,$b) 
{
	if($a['startTime'] < $b['startTime']) return -1;
	elseif($a['startTime'] > $b['startTime']) return 1;
}
function sort_byTimeD($a,$b) 
{
	if($a['startTime'] < $b['startTime']) return 1;
	elseif($a['startTime'] > $b['startTime']) return -1;
}
/**
 *	pc_clean
 *	@param s string text to clean
 *	@return string cleaned up text
 */
function pc_clean($s)
{
	$display_type = substr($s,0,6);

	if($display_type == ':text:') 
		$s = substr($s,6);
	elseif($display_type == ':html:') 
		$s = substr($s,6);

	unset($display_type);
	$s = preg_replace('/[\r|\n]/i','',$s);
	$s = str_replace("'","\'",$s);
	$s = str_replace('"','&quot;',$s);
	// ok, now we need to break really long lines
	// we only want to break at spaces to allow for
	// correct interpretation of special characters
	$tmp = explode(' ',$s);
	return join("'+' ",$tmp);
}
/****************************************************
 * The functions below are moved to eventapi
 ****************************************************/
function postcalendar_userapi_submitEvent($args)
{
	return pnModAPIFunc('PostCalendar','event','writeEvent', $args);
}
function postcalendar_adminapi_submitEvent($args)
{
	return pnModAPIFunc('PostCalendar','event','writeEvent', $args);
}
function postcalendar_userapi_buildSubmitForm($args)
{
	return pnModAPIFunc('PostCalendar','event','buildSubmitForm', $args);
}
function postcalendar_adminapi_buildSubmitForm($args)
{
	$args['admin'] = true;
	return pnModAPIFunc('PostCalendar','event','buildSubmitForm', $args);
}
function postcalendar_userapi_pcFixEventDetails($args)
{
	return pnModAPIFunc('PostCalendar','event','fixEventDetails', $args);
}
function postcalendar_userapi_pcGetEventDetails($args)
{
	return pnModAPIFunc('PostCalendar','event','getEventDetails', $args);
}
function postcalendar_userapi_eventDetail($args)
{
	return pnModAPIFunc('PostCalendar','event','eventDetail', $args);
}
function postcalendar_adminapi_eventDetail($args)
{
	$args['admin'] = true;
	return pnModAPIFunc('PostCalendar','event','eventDetail', $args);
}
/****************************************************/

/**************************************
THE FOLLOWING FUNCTIONS ARE MOVED AND RENAMED

FROM USERAPI to EVENTAPI:
OLD: postcalendar_userapi_pcQueryEvents
NEW: postcalendar_eventapi_queryEvents

OLD: postcalendar_userapi_pcGetEvents
NEW: postcalendar_eventapi_getEvents

OLD: postcalendar_userapi_deleteevents
NEW: postcalendar_eventapi_deleteevent

FROM USER TO EVENT:
OLD: postcalendar_user_delete
NEW: postcalendar_event_delete

OLD: postcalendar_user_submit
NEW: postcalendar_event_new

OLD: postcalendar_user_edit
NEW: postcalendar_event_edit

FROM COMMON TO EVENTAPI:
OLD: postcalendar_userapi_submitEvent
OLD: postcalendar_adminapi_submitEvent
NEW: postcalendar_eventapi_writeEvent

OLD: postcalendar_userapi_buildSubmitForm
OLD: postcalendar_adminapi_buildSubmitForm
NEW: postcalendar_eventapi_buildSubmitForm

OLD: postcalendar_userapi_pcFixEventDetails
NEW: postcalendar_eventapi_fixEventDetails

OLD: postcalendar_userapi_pcGetEventDetails
NEW: postcalendar_eventapi_getEventDetails

OLD: postcalendar_userapi_eventDetail
OLD: postcalendar_adminapi_eventDetail
NEW: postcalendar_eventapi_eventDetail

FROM ADMIN TO EVENT:
OLD: postcalendar_admin_approveevents
OLD: postcalendar_event_approve

OLD: postcalendar_admin_hideevents
NEW: postcalendar_event_hide

OLD: postcalendar_admin_deleteevents
NEW: postcalendar_event_delete (also from USER)

OLD: postcalendar_admin_edit
NEW: postcalendar_event_new (also from USER)

OLD: postcalendar_admin_submit
NEW: postcalendar_event_new (also from USER)


***************************************/
?>