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
 * edit an event
 */
function postcalendar_event_edit($args)
{
    $args['eid'] = FormUtil::getPassedValue('eid');
    return postcalendar_event_new($args);
}
/**
 * copy an event
 */
function postcalendar_event_copy($args)
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

    extract($args);

    // these items come on brand new view of this function
    $func      = FormUtil::getPassedValue('func');
    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');
    $Date      = FormUtil::getPassedValue('Date');

    $Date  = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));

    // these items come on submission of form
    $submitted_event  = FormUtil::getPassedValue('postcalendar_events');
    $is_update        = FormUtil::getPassedValue('is_update');
    $form_action      = FormUtil::getPassedValue('form_action');
    $authid           = FormUtil::getPassedValue('authid');

    if (substr($submitted_event['endDate']['year'], 0, 4) == '0000') {
        $submitted_event['endDate'] = $submitted_event['eventDate'];
    }
    // reformat times from form to 'real' 24-hour format
    $submitted_event['duration'] = (60 * 60 * $submitted_event['duration']['Hour']) + (60 * $submitted_event['duration']['Minute']);
    if ((bool) !_SETTING_TIME_24HOUR) {
        if ($submitted_event['startTime']['Meridian'] == "am") {
            $submitted_event['startTime']['Hour'] = $submitted_event['startTime']['Hour'] == 12 ? '00' : $submitted_event['startTime']['Hour'];
        } else {
            $submitted_event['startTime']['Hour'] = $submitted_event['startTime']['Hour'] != 12 ? $submitted_event['startTime']['Hour'] += 12 : $submitted_event['startTime']['Hour'];
        }
    }
    $startTime = sprintf('%02d', $submitted_event['startTime']['Hour']) .':'. sprintf('%02d', $submitted_event['startTime']['Minute']) .':00';
    unset($submitted_event['startTime']);
    $submitted_event['startTime'] = $startTime;
    // not sure this check is needed here...
    if (pnUserLoggedIn()) {
        $submitted_event['informant'] = pnUserGetVar('uname');
    } else {
        $submitted_event['informant'] = pnConfigGetVar('anonymous');
    }
    /******* END REFORMAT SUBMITTED EVENT FOR ... *********/

    if ($func == 'new') { // triggered on form_action=preview && on brand new load
        // here we need generate default data for the form
        // wrap all the data into array for passing to commit and preview functions
        if ($submitted_event['data_loaded']) $eventdata = $submitted_event; // data loaded on preview and processing of new event, but not on initial pageload
        $eventdata['is_update'] = $is_update;
        $eventdata['data_loaded'] = true;

    } else { // we are editing an existing event or copying an existing event (func=edit or func=copy)
        if ($submitted_event['data_loaded']) {
            $eventdata = $submitted_event; // reloaded event when editing
            $eventdata['location_info'] = $eventdata['location'];
        } else {
            $eventdata = pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $args['eid']);
            // here were need to format the DB data to be able to load it into the form
            $eventdata['location_info'] = unserialize($eventdata['location']);
            $eventdata['repeat'] = unserialize($eventdata['recurrspec']) ;
            $eventdata['endtype'] = $eventdata['endDate'] == '0000-00-00' ? '0' : '1';
        }
        if (($uname != $eventdata['informant']) and (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN))) {
            return LogUtil::registerError(__('Sorry! You do not have authorization to edit this event.', $dom));
        }
        // need to check each of these below to see if truly needed CAH 11/14/09
        $eventdata['Date'] = $Date;
        $eventdata['is_update'] = true;
        $eventdata['data_loaded'] = true;
    }

    if ($func == 'copy') {
        // reset some default values that are different from 'edit'
        $form_action = '';
        unset($eid);
        unset($eventdata['eid']);
        $eventdata['is_update'] = false;
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
        $sdate = strtotime($submitted_event['startDate']);
        $edate = strtotime($submitted_event['endDate']);
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

    // Preview the event
    if ($form_action == 'preview') $eventdata['preview'] = true;

    //================================================================
    // Enter the event into the DB
    //================================================================
    if ($form_action == 'commit') {
        if (!SecurityUtil::confirmAuthKey()) return LogUtil::registerAuthidError(pnModURL('postcalendar', 'admin', 'main'));

        if (!$eid = pnModAPIFunc('PostCalendar', 'event', 'writeEvent', compact('eventdata','Date'))) {
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
