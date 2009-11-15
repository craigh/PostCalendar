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
        if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) return $render->pnFormSetErrorMsg(__('Sorry! Authorization has not been granted.', $dom));

        $this->eid = FormUtil::getPassedValue('eid');

        return true;
    }

    function handleCommand(&$render, &$args)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $url = null;

        // Fetch event data
        $event = pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $this->eid);
        if (count($event) == 0) return $render->pnFormSetErrorMsg(__f('Error! There are no events with ID %s.',$this->eid, $dom));

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
                return $render->pnFormSetErrorMsg(__('Sorry! You do not have authorization to delete this event.', $dom));
            }
            $result = pnModAPIFunc('PostCalendar', 'event', 'deleteevent', array('eid' => $this->eid));
            if ($result === false) return $render->pnFormSetErrorMsg(__("Error! An 'unidentified error' occurred.", $dom));

            $redir = pnModUrl('PostCalendar', 'user', 'view', array('viewtype' => _SETTING_DEFAULT_VIEW));
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
    $eid = FormUtil::getPassedValue('eid'); //  seems like this should be handled by the eventHandler
    $render = FormUtil::newpnForm('PostCalendar');
    $eventdetails = pnModAPIFunc('PostCalendar', 'event', 'eventDetail', array('eid'=>$eid, 'Date' => ''));
    $render->assign('eventdetails', $eventdetails['A_EVENT']);
    return $render->pnFormExecute('event/postcalendar_event_deleteeventconfirm.htm', new postcalendar_event_editHandler());
}

/**
 * submit an event
 */
function postcalendar_event_edit($args)
{
    $args['eid'] = FormUtil::getPassedValue('eid');
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

    // these items come on brand new view of this function
    $func      = FormUtil::getPassedValue('func');
    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');
    $Date      = FormUtil::getPassedValue('Date');

    $Date  = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));
    $year  = substr($Date, 0, 4);
    $month = substr($Date, 4, 2);
    $day   = substr($Date, 6, 2);

    // these items come on submission of form
    $submitted_event  = FormUtil::getPassedValue('postcalendar_events');
    $is_update        = FormUtil::getPassedValue('is_update');
    $form_action      = FormUtil::getPassedValue('form_action');
    $authid           = FormUtil::getPassedValue('authid');
    $data_loaded      = FormUtil::getPassedValue('data_loaded');

    /******* REFORMAT SUBMITTED EVENT FOR DB WRITE *********/
    // convert event start information (YYYY-MM-DD)
    $submitted_event['eventDate'] = postcalendar_event_splitdate($submitted_event['eventDate']);
    $submitted_event['startTime']['Meridian'] = ($submitted_event['startTime']['Meridian'] == "am") ? _AM_VAL : _PM_VAL; // reformat to old way
    // convert event end information (YYYY-MM-DD)
    $submitted_event['endDate'] = postcalendar_event_splitdate($submitted_event['endDate']);
    if ($submitted_event['endDate']['year'] == '0000') {
        $submitted_event['endDate']['month'] = $submitted_event['eventDate']['month'];
        $submitted_event['endDate']['day']   = $submitted_event['eventDate']['day'];
        $submitted_event['endDate']['year']  = $submitted_event['eventDate']['year'];
        $submitted_event['endDate']['full']  = $submitted_event['eventDate']['full'];
    }
    $submitted_event['duration']['full'] = (60 * 60 * $submitted_event['duration']['Hour']) + (60 * $submitted_event['duration']['Minute']);
    if (pnUserLoggedIn()) {
        $submitted_event['informant'] = pnUserGetVar('uname');
    } else {
        $submitted_event['informant'] = pnConfigGetVar('anonymous');
    }
    /******* END REFORMAT SUBMITTED EVENT FOR  DB WRITE *********/


    if (!isset($args['eid']) || empty($args['eid']) || $data_loaded) { // this is a new event (possibly previewed)
        // wrap all the data into array for passing to commit and preview functions
        if ($data_loaded) $eventdata = $submitted_event; // data is only loaded if preview was selected
        $eventdata['is_update'] = $is_update;
        $eventdata['data_loaded'] = true;

    } else { // we are editing an existing event or copying an exisiting event
        $eventdata = pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $eid);
        if (($uname != $eventdata['informant']) and (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN))) {
            return LogUtil::registerError(__('Sorry! You do not have authorization to edit this event.', $dom));
        }

        // need to check each of these below to see if truly needed CAH 11/14/09
        $eventdata['endtype'] = $eventdata['endDate'] == '0000-00-00' ? '0' : '1';
        $eventdata['uname'] = $uname;
        $eventdata['Date'] = $Date;
        $eventdata['year'] = $year;
        $eventdata['month'] = $month;
        $eventdata['day'] = $day;
        $eventdata['is_update'] = true;
        $eventdata['data_loaded'] = true;
        $loc_data = unserialize($eventdata['location']);
        $rspecs = unserialize($eventdata['recurrspec']);
        $eventdata = array_merge($eventdata, $loc_data, $rspecs);
        $eventdata['location_info'] = $loc_data;
        $eventdata['recurrspec'] = $rspecs;
    }

    if ($form_action == 'copy') {
        $form_action = '';
        unset($eid);
        $eventdata['eid'] = '';
        $eventdata['is_update'] = false;
        $eventdata['data_loaded'] = false;
    }

    //================================================================
    // ERROR CHECKING IF ACTION IS PREVIEW OR COMMIT
    //================================================================
    $abort = false;
    if (($form_action == 'preview') OR ($form_action == 'commit')) {
        if (empty($submitted_event['title'])) {
            LogUtil::registerError(__(/*!This is the field name from pntemplates/event/postcalendar_event_submit.html:31*/"'Title' is a required field.", $dom).'<br />');
            $abort = true;
        }

        // check repeating frequencies
        if ($submitted_event['repeat']['repeatval'] == REPEAT) {
            if (!isset($submitted_event['repeat']['freq']) || $submitted_event['repeat']['freq'] < 1 || empty($submitted_event['repeat']['freq'])) {
                LogUtil::registerError(__('Error! The repetition frequency must be at least 1.', $dom));
                $abort = true;
            } elseif (!is_numeric($submitted_event['repeat']['freq'])) {
                LogUtil::registerError(__('Error! The repetition frequency must be an integer.', $dom));
                $abort = true;
            }
        } elseif ($submitted_event['repeat']['repeatval'] == REPEAT_ON) {
            if (!isset($submitted_event['repeat']['on_freq']) || $submitted_event['repeat']['on_freq'] < 1 || empty($submitted_event['repeat']['on_freq'])) {
                LogUtil::registerError(__('Error! The repetition frequency must be at least 1.', $dom));
                $abort = true;
            } elseif (!is_numeric($submitted_event['repeat']['on_freq'])) {
                LogUtil::registerError(__('Error! The repetition frequency must be an integer.', $dom));
                $abort = true;
            }
        }
        // check date validity
        if (!_SETTING_TIME_24HOUR) {
            if ($submitted_event['startTime']['Time_Meridian'] == _AM_VAL) {
                $submitted_event['startTime']['Time_Hour'] = ($submitted_event['startTime']['Time_Hour'] == 12) ? '00' : $submitted_event['startTime']['Time_Hour'];
            } else {
                $submitted_event['startTime']['Time_Hour'] = ($submitted_event['startTime']['Time_Hour'] != 12) ? $submitted_event['startTime']['Time_Hour'] += 12 : $submitted_event['startTime']['Time_Hour'];
            }
        }
        $submitted_event['startTime']['full'] = $submitted_event['startTime']['Time_Hour'].":".$submitted_event['startTime']['Time_Minute'];
        $submitted_event['endTime']['full']   = $submitted_event['endTime']['Time_Hour'].":".$submitted_event['endTime']['Time_Minute'];
        $sdate = strtotime($submitted_event['startDate']['full']);
        $edate = strtotime($submitted_event['endDate']['full']);
        $tdate = strtotime(date('Y-m-d'));

        if ($edate < $sdate && $submitted_event['endtype'] == 1) {
            LogUtil::registerError(__('Error! The selected start date falls after the selected end date.', $dom));
            $abort = true;
        }
        if (!checkdate($submitted_event['eventDate']['month'], $submitted_event['eventDate']['day'], $submitted_event['eventDate']['year'])) {
            LogUtil::registerError(__('Error! Invalid start date.', $dom));
            $abort = true;
        }
        if (!checkdate($submitted_event['endDate']['month'], $submitted_event['endDate']['day'], $submitted_event['endDate']['year'])) {
            LogUtil::registerError(__('Error! Invalid end date.', $dom));
            $abort = true;
        }
    } // end if form_action = preview/commit

    if ($abort) $form_action = 'preview'; // data not sufficient for commit. force preview and correct.
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

        if (!$eid = pnModAPIFunc('PostCalendar', 'event', 'writeEvent', compact('eventdata','Date','event_for_userid'))) {
            LogUtil::registerError(__('Error! Submission failed.', $dom));
        } else {
            pnModAPIFunc('PostCalendar', 'admin', 'clearCache');
            if ($is_update) {
                LogUtil::registerStatus(__('Done! Updated the event.', $dom));
            } else {
                LogUtil::registerStatus(__('Done! Submitted the event.', $dom));
            }
            if ((!_SETTING_DIRECT_SUBMIT) && (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN))) {
                 LogUtil::registerStatus(__('The event has been queued for administrator approval.', $dom));
            }

            pnModAPIFunc('PostCalendar','admin','notify',compact('eid','is_update')); //notify admin

            // save the start date, before the vars are cleared (needed for the redirect on success)
            $url_date = $submitted_event['startDate']['short'];
        }

        pnRedirect(pnModURL('PostCalendar', 'user', 'view', array('viewtype' => _SETTING_DEFAULT_VIEW, 'Date' => $url_date)));
        return true;
    }

    return pnModAPIFunc('PostCalendar', 'event', 'buildSubmitForm', compact('eventdata','Date','func'));
}


/**
 * postcalendar_event_splitdate
 *
 * @param $args string      expected to be a string of integers YYYYMMDD
 * @return array              date split with keys
 */
function postcalendar_event_splitdate($date)
{
    $splitdate          = array();
    $splitdate['full']  = $date;
    $date               = str_replace("-", "", $date); //remove '-' if present
    $date               = substr($date, 0, 8);
    $splitdate['short'] = $date;
    $splitdate['day']   = substr($date, 6, 2);
    $splitdate['month'] = substr($date, 4, 2);
    $splitdate['year']  = substr($date, 0, 4);
    return $splitdate;
}