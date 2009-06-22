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

function postcalendar_user_getDate($format='%Y%m%d%H%M%S')
{
	return postcalendar_getDate($format);
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

function pc_notify($eid,$is_update) // send an email to admin on new event submission
{
	if(!(bool)_SETTING_NOTIFY_ADMIN) 
		return true;

	//need to put a test in here for if the admin submitted the event, if not, probably don't send email.

	$modinfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
	$modversion = pnVarPrepForOS($modinfo['version']);

	$pnRender = pnRender::getInstance('PostCalendar');
	$pnRender->assign('is_update', $is_update);
	$pnRender->assign('modversion', $modversion);
	$pnRender->assign('eid', $eid);
	$pnRender->assign('link', pnModURL('PostCalendar','admin','adminevents',array('pc_event_id'=>$eid,'action'=>_ADMIN_ACTION_VIEW));
	$message = $pnRender->fetch('email/postcalendar_email_adminnotify.htm');

	$messagesent = pnModAPIFunc('Mailer', 'user', 'sendmessage', array('toaddress' => _SETTING_NOTIFY_EMAIL, 'subject' => _PC_NOTIFY_SUBJECT, 'body' => $message, 'html' => true));
		  
	if ($messagesent) {
		LogUtil::registerStatus('Admin notify email sent');
		return true;
	} else {
		LogUtil::registerError('Admin notify email not sent');
		return false;
	}
}

function postcalendar_footer()
{   
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