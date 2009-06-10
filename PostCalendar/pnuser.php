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

Loader::requireOnce('includes/pnForm.php');
//don't think I'll need the next two lines anymore...
pnModAPILoad(__POSTCALENDAR__,'user');
require_once ('includes/HtmlUtil.class.php');

function postcalendar_user_main()
{
	// check the authorization
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) {
		return LogUtil::registerPermissionError();
	}

	// get the date and go to the view function
	return postcalendar_user_view(array('Date'=>postcalendar_getDate()));
}

/******************************************/

class postcalendar_user_fileuploadHandler extends pnFormHandler
{
	function initialize(&$render)
	{
		if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD))
			return $render->pnFormSetErrorMsg(_NOTAUTHORIZED);

		return true;
	}

    // The handleCommand() function is called by the framework to notify your handler that the
    // user did something that caused a command to be sent to your handler. You should check
    // which command it was and then react upon it.
    // Remember the "&"-ampersand in &$render otherwise your code wont work!
    function handleCommand(&$render, $args)
    {
				echo "got to the handleCommand";
        if ($args['commandName'] == 'submit')
        {
            // Do forms validation. This call forces the framework to check all validators on the page
            // to validate their input. If anyone fails then pnFormIsValid() returns false, and so
            // should your command event also do.
         //   if (!$render->pnFormIsValid())
         //       return false;
						echo "YOU ARE HERE";

            $data = $render->pnFormGetValues();
						pcDebugVar($data);

            $result = pnModAPIFunc('PostCalendar', 'user', 'processupload',
                                   array('icsupload' => $data['icsupload']));
            if ($result <> true)
                return $render->pnFormSetErrorMsg(_PC_COULDNOTPROCESSFILEUPLOAD);

            $url = pnModUrl('PostCalendar', 'user', 'view', array('viewtype' => pnModGetVar('PostCalendar','pcDefaultView')));

            return $render->pnFormRedirect($url);
        }
        else if ($args['commandName'] == 'cancel')
        {
            $redir = pnModUrl('PostCalendar', 'user', 'view', array('viewtype' => pnModGetVar('PostCalendar','pcDefaultView')));
            return $render->pnFormRedirect($redir);
        }
				echo "no command found";
        $data = $render->pnFormGetValues();
				pcDebugVar($data);
        return true;
    }
}

/******************************************/


function postcalendar_user_upload()
{
	$render = & FormUtil::newpnForm('PostCalendar');
	return $render->pnFormExecute('user/postcalendar_user_fileupload.htm', new postcalendar_user_fileuploadHandler());
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

/**
 * view items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 */
function postcalendar_user_view()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) {
		return LogUtil::registerPermissionError();
	}
    
    // get the vars that were passed in
    $Date      = FormUtil::getPassedValue ('Date');
    //$print     = FormUtil::getPassedValue ('print');
    $viewtype  = FormUtil::getPassedValue ('viewtype');
    $jumpday   = FormUtil::getPassedValue ('jumpday');
    $jumpmonth = FormUtil::getPassedValue ('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue ('jumpyear');
    
    $Date = postcalendar_getDate();
    if(!isset($viewtype))   $viewtype = _SETTING_DEFAULT_VIEW;
    //return postcalendar_user_display(array('viewtype'=>$viewtype,'Date'=>$Date,'print'=>$print)) . postcalendar_footer();
    return postcalendar_user_display(array('viewtype'=>$viewtype,'Date'=>$Date)) . postcalendar_footer();
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
	$pc_category = FormUtil::getPassedValue ('pc_category');
	$pc_topic    = FormUtil::getPassedValue ('pc_topic');
	$pc_username = FormUtil::getPassedValue ('pc_username');

	extract($args);
	if(empty($Date) && empty($viewtype)) {
		return false;
	}

	$uid = pnUserGetVar('uid');
	$theme = pnUserGetTheme();
	$cacheid = md5($Date.$viewtype._SETTING_TEMPLATE.$eid.$uid.'u'.$pc_username.$theme.'c'.$category.'t'.$topic);

	switch ($viewtype) 
	{
		case 'details':
			if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_READ)) {
				return LogUtil::registerPermissionError();
			}
      $out = pnModAPIFunc('PostCalendar','user','eventDetail', 
                             array('eid'=>$eid, 'Date'=>$Date, 'cacheid'=>$cacheid));

			if($out === false) { 
       	pnRedirect(pnModURL(__POSTCALENDAR__,'user'));
			}
			// build template and fetch:
			$tpl = pnRender::getInstance('PostCalendar');
			PostCalendarSmartySetup($tpl);
			if($tpl->is_cached($out['template'],$cacheid)) {
				// use cached version
				return $tpl->fetch($out['template'], $cacheid);
			} else {
				foreach ($out as $var=>$val) {
					$tpl->assign($var,$val);
				}
				return $tpl->fetch($out['template']);
			}
			break;

 		default :
			if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) {
				return LogUtil::registerPermissionError();
			}
			//now function just returns an array of information to pass to template 5/9/09 CAH
			$out = pnModAPIFunc('PostCalendar','user','buildView', 
													array('Date'=>$Date, 'viewtype'=>$viewtype, 'cacheid'=>$cacheid));
			// build template and fetch:
			$tpl = pnRender::getInstance('PostCalendar');
			PostCalendarSmartySetup($tpl);
			if($tpl->is_cached($out['template'],$cacheid)) {
				// use cached version
				return $tpl->fetch($out['template'], $cacheid);
			} else {
				foreach ($out as $var=>$val) {
					$tpl->assign($var,$val);
				}
				return $tpl->fetch($out['template']);
    	} // end if/else
			break;
	} // end switch
}
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
		$event = postcalendar_userapi_pcGetEventDetails($this->eid);
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
			if($uname != $event['informant']) {
				return $render->pnFormSetErrorMsg(_PC_CAN_NOT_DELETE);
			}
			$result = pnModAPIFunc('PostCalendar', 'user', 'deleteevents',
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
function postcalendar_user_delete()
{
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
		return LogUtil::registerPermissionError();
	}
	$eid = FormUtil::getPassedValue('eid');
	$render = FormUtil::newpnForm('PostCalendar');
	$eventdetails = pnModAPIFunc('PostCalendar','user','eventDetail',array('eid'=>$eid,'cacheid'=>'','Date'=>''));
	$render->assign('eventdetails', $eventdetails['A_EVENT']);
	return $render->pnFormExecute('user/postcalendar_user_deleteeventconfirm.htm', new postcalendar_event_editHandler());
}

/**
 * submit an event
 */
function postcalendar_user_edit($args) {return postcalendar_user_submit($args); }
function postcalendar_user_submit($args)
{   
    // We need at least ADD permission to submit an event
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
		return LogUtil::registerPermissionError();
	}
	
	$output = new pnHTML();
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    
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
    //$pc_event_id = FormUtil::getPassedValue('pc_event_id');
    $pc_event_id = FormUtil::getPassedValue('eid');
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
				$tpl = pnRender::getInstance('PostCalendar'); //smartysetup not needed
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
	if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) {
		return LogUtil::registerPermissionError();
	}
	
    $tpl = pnRender::getInstance('PostCalendar');
		PostCalendarSmartySetup($tpl);
		/* Trim as needed */
			$func  = FormUtil::getPassedValue('func');
			$template_view = FormUtil::getPassedValue('tplview');
			if (!$template_view) $template_view = 'month'; 
			$tpl->assign('FUNCTION', $func);
			$tpl->assign('TPL_VIEW', $template_view);
		/* end */

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
/*
    $template_name = _SETTING_TEMPLATE;
    if(!isset($template_name)) {
    	$template_name = 'default';
    }
*/
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
	/*
	$pcTheme = pnModGetVar(__POSTCALENDAR__,'pcTemplate');
	if(!$pcTheme)
	    $pcTheme='default';
	return $pageSetup . $tpl->fetch("$pcTheme/search.html");
	*/
	return $pageSetup . $tpl->fetch("search.html");
}
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

  //pnModAPILoad ('PostCalendar', 'user');
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
    //return postcalendar_icalapi_export_ical ($sevents);
		return pnModAPIFunc ('PostCalendar', 'ical', 'export_ical', ($sevents));
  else
    //return postcalendar_user_export_rss ($sevents, $start, $end);
		return pnModFunc ('PostCalendar', 'user', 'export_rss', array ($sevents, $start, $end));
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

    //$tpl_contact = new pnRender();
	$tpl_contact = pnRender::getInstance('PostCalendar');
	PostCalendarSmartySetup($tpl_contact);
		/* Trim as needed */
			$func  = FormUtil::getPassedValue('func');
			$template_view = FormUtil::getPassedValue('tplview');
			if (!$template_view) $template_view = 'month'; 
			$tpl_contact->assign('FUNCTION', $func);
			$tpl_contact->assign('TPL_VIEW', $template_view);
		/* end */

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
/*
    $pcTheme = pnModGetVar(__POSTCALENDAR__,'pcTemplate');
    if(!$pcTheme) $pcTheme='default';
    $output = $tpl_contact->fetch("$pcTheme/findContact.html");
*/
    $output = $tpl_contact->fetch("findContact.html");
    echo $output;

    return true;
}

?>