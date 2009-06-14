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

/**
 * the main administration function
 * This function is the default function, and is called whenever the
 * module is initiated without defining arguments.
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
	if(!isset($sdir)) { 
		$sdir = 1; //default true
	}	else {
		$sdir = $sdir ? 0 : 1; //if true change to false, if false change to true
	}
	if(!isset($offset))  $offset = 0;

	$events = pnModAPIFunc('PostCalendar','admin','getAdminListEvents',
                           array ('type'             => $type,
                                  'sdir'             => $sdir,
                                  'sort'             => $sort,
                                  'offset'           => $offset,
                                  'offset_increment' => $offset_increment));

	$pnRender->assign('title', $title);
	$pnRender->assign('function', $function);
	$pnRender->assign('events', $events);
	$pnRender->assign('title_sort_url', pnModUrl('PostCalendar','admin',$function, array('offset'=>$offset,'sort'=>'title','sdir'=>$sdir)));
	$pnRender->assign('time_sort_url', pnModUrl('PostCalendar','admin',$function, array('offset'=>$offset,'sort'=>'time','sdir'=>$sdir)));
	$pnRender->assign('formactions', array(
		        _ADMIN_ACTION_VIEW => _PC_ADMIN_ACTION_VIEW,
            _ADMIN_ACTION_APPROVE => _PC_ADMIN_ACTION_APPROVE,
            _ADMIN_ACTION_HIDE => _PC_ADMIN_ACTION_HIDE,
            _ADMIN_ACTION_DELETE => _PC_ADMIN_ACTION_DELETE));
	$pnRender->assign('actionselected', _ADMIN_ACTION_VIEW);
	if($offset > 1) {
		$prevlink = pnModUrl('PostCalendar','admin',$function,array('offset'=>$offset-$offset_increment,'sort'=>$sort,'sdir'=>$sdir));
	} else {
		$prevlink = false;
	}
	$pnRender->assign('prevlink', $prevlink);
	if(count($events) >= $offset_increment) {
		$nextlink = pnModUrl('PostCalendar','admin',$function,array('offset'=>$offset+$offset_increment,'sort'=>$sort,'sdir'=>$sdir));
	} else {
		$nextlink = flase;
	}
	$pnRender->assign('nextlink', $nextlink);
	$pnRender->assign('offset_increment', $offset_increment);

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
		$output .= '<form action="'.pnModUrl(__POSTCALENDAR__,'event',$function).'" method="post">';
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
	
	pnModAPIFunc('PostCalendar','admin','clearCache');
		
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

	pnModAPIFunc('PostCalendar','admin','clearCache');

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
	$clear = pnModAPIFunc('PostCalendar','admin','clearCache');
	if ($clear) return postcalendar_admin_modifyconfig(_PC_CACHE_CLEARED);
	return postcalendar_admin_modifyconfig(_PC_CACHE_NOTCLEARED);
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
/****************************************************
 * The functions below are moved to eventapi
 ****************************************************/
function postcalendar_admin_approveevents()
{
	return pnModFunc('PostCalendar', 'event', 'approve');
}
function postcalendar_admin_hideevents()
{
	return pnModFunc('PostCalendar', 'event', 'hide');
}

function postcalendar_admin_deleteevents()
{
	return pnModFunc('postcalendar', 'event', 'delete');
}
function postcalendar_admin_edit($args) {
	return pnModFunc('PostCalendar', 'event', 'new', $args);
}
function postcalendar_admin_submit($args)
{  
	return pnModFunc('PostCalendar', 'event', 'new', $args);
}
?>