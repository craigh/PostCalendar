<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
Loader::requireOnce('includes/pnForm.php');
require_once dirname(__FILE__) . '/global.php';

/**
 * This is the event handler file
 **/
class postcalendar_event_editHandler extends pnFormHandler
{
    var $eid;

    function initialize(&$render)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) return $render->pnFormSetErrorMsg(__('You are not authorized.', $dom));

        $this->eid = FormUtil::getPassedValue('eid');

        return true;
    }

    function handleCommand(&$render, &$args)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $url = null;

        // Fetch event data
        $event = pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $this->eid);
        if (count($event) == 0) return $render->pnFormSetErrorMsg(__('There are no events with id '.$this->eid.'.', $dom));

        if ($args['commandName'] == 'update') {
            /*
            if (!$render->pnFormIsValid())
                return false;

            $recipeData = $render->pnFormGetValues();
            $recipeData['id'] = $this->recipeId;

            $result = pnModAPIFunc('howtopnforms', 'recipe', 'update', array('recipe' => $recipeData));
            if ($result === false)
                return $render->pnFormSetErrorMsg(howtopnformsErrorAPIGet());

            $url = pnModUrl('howtopnforms', 'recipe', 'view', array('rid' => $this->recipeId));
            */
        } else if ($args['commandName'] == 'delete') {
            $uname = pnUserGetVar('uname');
            if (($uname != $event['informant']) and (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN))) {
                return $render->pnFormSetErrorMsg(__('You are not allowed to delete this event', $dom));
            }
            $result = pnModAPIFunc('PostCalendar', 'event', 'deleteevent', array('eid' => $this->eid));
            if ($result === false) return $render->pnFormSetErrorMsg(__('There was an error while processing your request.', $dom));

            $redir = pnModUrl('PostCalendar', 'user', 'view', array('viewtype' => pnModGetVar('PostCalendar', 'pcDefaultView')));
            return $render->pnFormRedirect($redir);
        } else if ($args['commandName'] == 'cancel') {
            $url = pnModUrl('PostCalendar', 'user', 'view', array('eid' => $this->eid, 'viewtype' => 'details', 'Date' => $event['Date']));
        }

        if ($url != null) {
            /*pnModAPIFunc('PageLock', 'user', 'releaseLock', array('lockName' => "HowtoPnFormsRecipe{$this->recipeId}")); */
            return $render->pnFormRedirect($url);
        }

        return true;
    }
}

/**
 * This is a user form 'are you sure' display
 * to delete an event
 */
function postcalendar_event_delete()
{
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }
    $render = FormUtil::newpnForm('PostCalendar');
    $eventdetails = pnModAPIFunc('PostCalendar', 'event', 'eventDetail', array('eid' => $eid, 'cacheid' => '', 'Date' => ''));
    $render->assign('eventdetails', $eventdetails['A_EVENT']);
    return $render->pnFormExecute('event/postcalendar_event_deleteeventconfirm.htm', new postcalendar_event_editHandler());
}

/**
 * submit an event
 */
function postcalendar_event_edit($args)
{
    return postcalendar_event_new($args);
}
/**
 * @function    postcalendar_event_new
 *
 * @Description    This function is used to generate a form for a new event
 *                 and edit an existing event
 *                 and preview a nearly submitted event
 *                 and copy an existing event
 *                 and process a submitted event
 */
function postcalendar_event_new($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // We need at least ADD permission to submit an event
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    extract($args); //if there are args, does that mean were are editing an event?

    //not sure these three lines are needed with call to getDate here
    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');

    $Date  = FormUtil::getPassedValue('Date');
    $Date  = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));
    $year  = substr($Date, 0, 4);
    $month = substr($Date, 4, 2);
    $day   = substr($Date, 6, 2);

    // basic event information
    $event_subject  = FormUtil::getPassedValue('event_subject');
    $event_desc     = FormUtil::getPassedValue('event_desc');
    $event_sharing  = FormUtil::getPassedValue('event_sharing');
    $event_category = FormUtil::getPassedValue('event_category');
    $event_topic    = FormUtil::getPassedValue('event_topic');

    // event start information
    $event_meetingdate_start = FormUtil::getPassedValue('meetingdate_start');
    if (strchr($event_meetingdate_start, '-')) {
        $event_startmonth = substr($event_meetingdate_start, 5, 2);
        $event_startday   = substr($event_meetingdate_start, 8, 2);
        $event_startyear  = substr($event_meetingdate_start, 0, 4);
    } else {
        $event_startmonth = substr($event_meetingdate_start, 4, 2);
        $event_startday   = substr($event_meetingdate_start, 6, 2);
        $event_startyear  = substr($event_meetingdate_start, 0, 4);
    }

    $event_starttimeh = FormUtil::getPassedValue('event_starttimeHour');
    $event_starttimem = FormUtil::getPassedValue('event_starttimeMinute');
    $event_startMer   = FormUtil::getPassedValue('event_starttimeMeridian');
    $event_startampm  = ($event_startMer == "am" ? 1 : 2); // reformat to old way

    // event end information
    $event_meetingdate_end = FormUtil::getPassedValue('meetingdate_end');
    if (strchr($event_meetingdate_end, '-')) {
        $event_endmonth = substr($event_meetingdate_end, 5, 2);
        $event_endday   = substr($event_meetingdate_end, 8, 2);
        $event_endyear  = substr($event_meetingdate_end, 0, 4);
    } else {
        $event_endmonth = substr($event_meetingdate_end, 4, 2);
        $event_endday   = substr($event_meetingdate_end, 6, 2);
        $event_endyear  = substr($event_meetingdate_end, 0, 4);
    }
    if ($event_endyear == '0000') {
        $event_endmonth = $event_startmonth;
        $event_endday   = $event_startday;
        $event_endyear  = $event_startyear; // V4B SB END
    }
    $event_endtype     = FormUtil::getPassedValue('event_endtype'); //0 = no end daate
    $event_dur_hours   = FormUtil::getPassedValue('event_dur_Hour');
    $event_dur_minutes = FormUtil::getPassedValue('event_dur_Minute');
    $event_duration    = (60 * 60 * $event_dur_hours) + (60 * $event_dur_minutes);
    $event_allday      = FormUtil::getPassedValue('event_allday');

    // location data
    $event_location  = FormUtil::getPassedValue('event_location');
    $event_street1   = FormUtil::getPassedValue('event_street1');
    $event_street2   = FormUtil::getPassedValue('event_street2');
    $event_city      = FormUtil::getPassedValue('event_city');
    $event_state     = FormUtil::getPassedValue('event_state');
    $event_postal    = FormUtil::getPassedValue('event_postal');
    $event_location_info = compact('event_location', 'event_street1', 'event_street2', 'event_city', 'event_state', 'event_postal');
    foreach ($event_location_info as $key => $litmp) $event_location[$key] = DataUtil::formatForStore($litmp);
    $event_location_info = serialize($event_location_info);
    // contact data
    $event_contname  = FormUtil::getPassedValue('event_contname');
    $event_conttel   = FormUtil::getPassedValue('event_conttel');
    $event_contemail = FormUtil::getPassedValue('event_contemail');
    $event_website   = FormUtil::getPassedValue('event_website');
    $event_fee       = FormUtil::getPassedValue('event_fee');
    $event_contact   = FormUtil::getPassedValue('event_contact');

    // event repeating data
    $event_repeat           = FormUtil::getPassedValue('event_repeat');
    if (!isset($event_repeat)) $event_repeat = 0;
    $event_repeat_freq      = FormUtil::getPassedValue('event_repeat_freq');
    $event_repeat_freq_type = FormUtil::getPassedValue('event_repeat_freq_type');
    $event_repeat_on_num    = FormUtil::getPassedValue('event_repeat_on_num');
    $event_repeat_on_day    = FormUtil::getPassedValue('event_repeat_on_day');
    $event_repeat_on_freq   = FormUtil::getPassedValue('event_repeat_on_freq');
    $event_recurrspec = compact('event_repeat_freq', 'event_repeat_freq_type', 'event_repeat_on_num', 'event_repeat_on_day', 'event_repeat_on_freq');
    foreach ($event_recurrspec as $key => $rctmp) $event_recurrspec[$key] = DataUtil::formatForStore($rctmp);
    $event_recurrspec = serialize($event_recurrspec);

    $form_action        = FormUtil::getPassedValue('form_action');
    $pc_html_or_text    = FormUtil::getPassedValue('pc_html_or_text');
    $eid                = FormUtil::getPassedValue('eid');
    $data_loaded        = FormUtil::getPassedValue('data_loaded');
    $is_update          = FormUtil::getPassedValue('is_update');
    $authid             = FormUtil::getPassedValue('authid');
    $event_for_userid   = FormUtil::getPassedValue('event_for_userid');
    $event_participants = FormUtil::getPassedValue('participants');

    if (pnUserLoggedIn()) {
        $uname = pnUserGetVar('uname');
    } else {
        $uname = pnConfigGetVar('anonymous');
    }

    if (!isset($eid) || empty($eid) || $data_loaded) { // this is a new event
        // wrap all the data into array for passing to commit and preview functions
        $eventdata = compact('event_subject', 'event_desc', 'event_sharing',
            'event_category', 'event_topic', 'event_startmonth', 'event_startday', 'event_startyear',
            'event_starttimeh', 'event_starttimem', 'event_startampm', 'event_endmonth',
            'event_endday', 'event_endyear', 'event_endtype', 'event_dur_hours', 'event_dur_minutes',
            'event_duration', 'event_allday', 'event_location', 'event_street1', 'event_street2',
            'event_city', 'event_state', 'event_postal', 'event_location_info', 'event_contname',
            'event_conttel', 'event_contemail', 'event_website', 'event_fee', 'event_contact',
            'event_repeat', 'event_repeat_freq', 'event_repeat_freq_type', 'event_repeat_on_num',
            'event_repeat_on_day', 'event_repeat_on_freq', 'event_recurrspec', 'uname', 'Date', 'year',
            'month', 'day', 'pc_html_or_text');
        $eventdata['is_update'] = $is_update;
        $eventdata['eid'] = $eid;
        $eventdata['data_loaded'] = true;
        $eventdata['event_for_userid'] = $event_for_userid;

        $event_participants = FormUtil::getPassedValue('participants');
    } else { // we are editing an existing event or copying an exisiting event
        $event = pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $eid);
        if (($uname != $event['informant']) and (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN))) {
            return __('You are not allowed to edit this event', $dom); // need to change this to logutil
        }
        $eventdata['event_subject'] = $event['title'];
        $eventdata['event_desc'] = $event['hometext'];
        $eventdata['event_sharing'] = $event['sharing'];
        $eventdata['event_category'] = $event['catid'];
        $eventdata['event_topic'] = $event['topic'];
        $eventdata['event_startmonth'] = substr($event['eventDate'], 5, 2);
        $eventdata['event_startday'] = substr($event['eventDate'], 8, 2);
        $eventdata['event_startyear'] = substr($event['eventDate'], 0, 4);
        $eventdata['event_starttimeh'] = substr($event['startTime'], 0, 2);
        $eventdata['event_starttimem'] = substr($event['startTime'], 3, 2);
        $eventdata['event_startampm'] = $eventdata['event_starttimeh'] < 12 ? __('AM', $dom) : __('PM', $dom);
        $eventdata['event_endmonth'] = substr($event['endDate'], 5, 2);
        $eventdata['event_endday'] = substr($event['endDate'], 8, 2);
        $eventdata['event_endyear'] = substr($event['endDate'], 0, 4);
        $eventdata['event_endtype'] = $event['endDate'] == '0000-00-00' ? '0' : '1';
        $eventdata['event_dur_hours'] = $event['duration_hours'];
        $eventdata['event_dur_minutes'] = $event['duration_minutes'];
        $eventdata['event_duration'] = $event['duration'];
        $eventdata['event_allday'] = $event['alldayevent'];
        $eventdata['event_contname'] = $event['contname'];
        $eventdata['event_conttel'] = $event['conttel'];
        $eventdata['event_contemail'] = $event['contemail'];
        $eventdata['event_website'] = $event['website'];
        $eventdata['event_fee'] = $event['fee'];
        $eventdata['event_contact'] = $event['event_contact'];
        $eventdata['event_repeat'] = $event['recurrtype'];
        $eventdata['uname'] = $uname;
        $eventdata['Date'] = $Date;
        $eventdata['year'] = $year;
        $eventdata['month'] = $month;
        $eventdata['day'] = $day;
        $eventdata['is_update'] = true;
        $eventdata['eid'] = $eid;
        $eventdata['data_loaded'] = true;
        $eventdata['pc_html_or_text'] = $pc_html_or_text;

        $eventdata['event_for_userid'] = $event_for_userid;
        $eventdata['meeting_id'] = $event['meeting_id'];
        $eventdata['participants'] = $event_participants;

        $loc_data = unserialize($event['location']);
        $rspecs = unserialize($event['recurrspec']);
        $eventdata = array_merge($eventdata, $loc_data, $rspecs);
        $eventdata['event_location_info'] = $loc_data;
        $eventdata['event_recurrspec'] = $rspecs;
    }

    if ($form_action == 'copy') {
        $form_action = '';
        unset($eid);
        $eventdata['eid'] = '';
        $eventdata['is_update'] = false;
        $eventdata['data_loaded'] = false;
    }

    $categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');

    //================================================================
    // ERROR CHECKING IF ACTION IS PREVIEW OR COMMIT
    //================================================================
    if (($form_action == 'preview') OR ($form_action == 'commit')) {
        if (empty($event_subject)) LogUtil::registerError('<b>event subject</b>'.__('is a required field.', $dom).'<br />');
        // if this truly is empty and we are committing, it should abort!

        // check repeating frequencies
        if ($event_repeat == REPEAT) {
            if (!isset($event_repeat_freq) || $event_repeat_freq < 1 || empty($event_repeat_freq)) {
                LogUtil::registerError(__('Your repeating frequency must be at least 1.', $dom));
            } elseif (!is_numeric($event_repeat_freq)) {
                LogUtil::registerError(__('Your repeating frequency must be an integer.', $dom));
            }
        } elseif ($event_repeat == REPEAT_ON) {
            if (!isset($event_repeat_on_freq) || $event_repeat_on_freq < 1 || empty($event_repeat_on_freq)) {
                LogUtil::registerError(__('Your repeating frequency must be at least 1.', $dom));
            } elseif (!is_numeric($event_repeat_on_freq)) {
                LogUtil::registerError(__('Your repeating frequency must be an integer.', $dom));
            }
        }
        // check date validity
        if (_SETTING_TIME_24HOUR) {
            $startTime = $event_starttimeh . ':' . $event_starttimem;
            $endTime = $event_endtimeh . ':' . $event_endtimem;
        } else {
            if ($event_startampm == _AM_VAL) {
                $event_starttimeh = $event_starttimeh == 12 ? '00' : $event_starttimeh;
            } else {
                $event_starttimeh = $event_starttimeh != 12 ? $event_starttimeh += 12 : $event_starttimeh;
            }
            $startTime = $event_starttimeh . ':' . $event_starttimem;
        }
        $sdate = strtotime($event_startyear . '-' . $event_startmonth . '-' . $event_startday);
        $edate = strtotime($event_endyear . '-' . $event_endmonth . '-' . $event_endday);
        $tdate = strtotime(date('Y-m-d'));

        if ($edate < $sdate && $event_endtype == 1) {
            LogUtil::registerError(__('Your start date is greater than your end date', $dom));
        }
        if (!checkdate($event_startmonth, $event_startday, $event_startyear)) {
            LogUtil::registerError(__('Your start date is invalid', $dom));
        }
        if (!checkdate($event_endmonth, $event_endday, $event_endyear)) {
            LogUtil::registerError(__('Your end date is invalid', $dom));
        }
    } // end if form_action = preview/commit
    //================================================================
    // Preview the event
    //================================================================
    if ($form_action == 'preview') {
        if (!SecurityUtil::confirmAuthKey()) return LogUtil::registerAuthidError(pnModURL('postcalendar', 'admin', 'main'));
        $eventdata['preview'] = pnModAPIFunc('PostCalendar', 'user', 'eventPreview', $eventdata);
    }

    //================================================================
    // Enter the event into the DB
    //================================================================
    if ($form_action == 'commit') {
        if (!SecurityUtil::confirmAuthKey()) return LogUtil::registerAuthidError(pnModURL('postcalendar', 'admin', 'main'));

        if (!pnModAPIFunc('PostCalendar', 'event', 'writeEvent', $eventdata)) {
            LogUtil::registerError(__('Your submission failed.', $dom));
        } else {
            pnModAPIFunc('PostCalendar', 'admin', 'clearCache');
            if ($is_update) {
                LogUtil::registerStatus(__('Your event has been modified.', $dom));
            } else {
                LogUtil::registerStatus(__('Your event has been submitted.', $dom));
            }

            // save the start date, before the vars are cleared (needed for the redirect on success)
            $url_date = $event_startyear . $event_startmonth . $event_startday;
        }

        pnRedirect(pnModURL('PostCalendar', 'user', 'view', array('viewtype' => 'month', 'Date' => $url_date))); // change to default view or previous
        return true;
    }

    return pnModAPIFunc('PostCalendar', 'event', 'buildSubmitForm', $eventdata);
}
