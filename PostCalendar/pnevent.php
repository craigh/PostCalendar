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
//Loader::requireOnce('includes/pnForm.php');
//Loader::loadClass('Form');
include_once 'modules/PostCalendar/global.php';

/**
 * This is the event handler file
 **/
class postcalendar_event_editHandler extends pnFormHandler
{
    var $eid;

    function initialize(&$render)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return $render->pnFormSetErrorMsg(__('Sorry! Authorization has not been granted.', $dom));
        }

        $this->eid = FormUtil::getPassedValue('eid');

        return true;
    }

    function handleCommand(&$render, &$args)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $url = null;

        // Fetch event data from DB to confirm event exists
        $event = DBUtil::selectObjectByID('postcalendar_events', $this->eid, 'eid');
        if (count($event) == 0) {
            return $render->pnFormSetErrorMsg(__f('Error! There are no events with ID %s.', $this->eid, $dom));
        }

        if ($args['commandName'] == 'delete') {
            if ((SessionUtil::getVar('uid') != $event['informant']) and (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))) {
                return $render->pnFormSetErrorMsg(__('Sorry! You do not have authorization to delete this event.', $dom));
            }
            $result = DBUtil::deleteObjectByID('postcalendar_events', $this->eid, 'eid');
            if ($result === false) {
                return $render->pnFormSetErrorMsg(__("Error! An 'unidentified error' occurred.", $dom));
            }
            LogUtil::registerStatus(__('Done! The event was deleted.', $dom));
            pnModCallHooks('item', 'delete', $this->eid, array(
                'module' => 'PostCalendar'));

            $redir = pnModUrl('PostCalendar', 'user', 'view', array(
                'viewtype' => _SETTING_DEFAULT_VIEW));
            return $render->pnFormRedirect($redir);
        } else if ($args['commandName'] == 'cancel') {
            $url = pnModUrl('PostCalendar', 'user', 'view', array(
                'eid' => $this->eid,
                'viewtype' => 'details',
                'Date' => $event['Date']));
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
    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }
    $eid    = FormUtil::getPassedValue('eid'); //  seems like this should be handled by the eventHandler
    $render = FormUtil::newpnForm('PostCalendar');

    // get the event from the DB
    $event = DBUtil::selectObjectByID('postcalendar_events', $eid, 'eid');
    $event = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);

    $render->assign('loaded_event', $event);
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
 *  This form can be loaded in nine states:
 *  new event (first pass): no previous values, need defaults
 *      $func=new, data_loaded=false, form_action=NULL
 *  new event preview (subsequent pass): loaded form values refilled into form w/preview (also triggered if form does not validate - e.g. abort=true)
 *      $func=new, data_loaded=true, form_action=preview
 *  new event submit (subsequent pass): loaded form values - write to DB
 *      $func=new, data_loaded=true, form_action=save
 *  edit existing event (first pass): load existing values from DB and fill into form
 *      $func=edit, data_loaded=true, form_action=NULL
 *  edit event preview (subsequent pass): loaded form values refilled  into form w/preview (also triggered if form does not validate - e.g. abort=true)
 *      $func=edit, data_loaded=true, form_action=preview
 *  edit event save (subsequent pass): loaded form values - write to DB
 *      see same for 'new'
 *  copy existing event (first pass): load existing values from DB and fill into to form
 *      $func=copy, data_loaded=true, form_action=NULL
 *  copy becomes 'new' after first pass - see new event preview and new event submit above
 *
 * expected $args = 'eid'
 **/
function postcalendar_event_new($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // We need at least ADD permission to submit an event
    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // these items come on brand new view of this function
    $func = FormUtil::getPassedValue('func', 'new');
    $Date = FormUtil::getPassedValue('Date'); //typically formatted YYYYMMDD or YYYYMMDD000000
    // format to '%Y%m%d%H%M%S'
    $Date = pnModAPIFunc('PostCalendar', 'user', 'getDate', array(
        'Date' => $Date));

    // these items come on submission of form
    $submitted_event = FormUtil::getPassedValue('postcalendar_events', NULL);
    $is_update       = FormUtil::getPassedValue('is_update', false);
    $form_action     = FormUtil::getPassedValue('form_action', NULL);
    $authid          = FormUtil::getPassedValue('authid');

    // compensate for translation of input values
    if (isset($form_action)) {
        $formactionarraymap = array(
            __('Save', $dom) => 'save',
            __('Save and Add', $dom) => 'save and add',
            __('Preview', $dom) => 'preview');
        $form_action = $formactionarraymap[$form_action];
    }

    $addtrigger = false;
    if ($form_action == 'save and add') {
        $form_action = 'save';
        $addtrigger = true;
    }

    // VALIDATE form data if form action is preview or save
    $abort = false;
    if (($form_action == 'preview') || ($form_action == 'save')) {
        $abort = pnModAPIFunc('PostCalendar', 'event', 'validateformdata', $submitted_event);
    }

    if ($func == 'new') { // triggered on form_action=preview && on brand new load
        $eventdata = array();
        // wrap all the data into array for passing to save and preview functions
        if ($submitted_event['data_loaded']) {
            $eventdata = $submitted_event; // data loaded on preview and processing of new event, but not on initial pageload
        }
        $eventdata['is_update'] = $is_update;
        $eventdata['data_loaded'] = true;

    } else { // func=edit or func=copy (we are editing an existing event or copying an existing event)
        if ($submitted_event['data_loaded']) {
            $eventdata = $submitted_event; // reloaded event when editing
        } else {
            // here were need to format the DB data to be able to load it into the form
            $eid = $args['eid'];
            $eventdata = DBUtil::selectObjectByID('postcalendar_events', $eid, 'eid');
            $eventdata = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $eventdata);
        }
        // need to check each of these below to see if truly needed CAH 11/14/09
        $eventdata['Date'] = $Date;
        $eventdata['is_update'] = true;
        $eventdata['data_loaded'] = true;
    }

    if ($func == 'copy') {
        // reset some default values that are different from 'edit'
        $form_action = '';
        $func = "new"; // change function so data is processed as 'new' in subsequent pass
        unset($args['eid']);
        unset($eventdata['eid']);
        $eventdata['is_update'] = false;
        $eventdata['informant'] = SessionUtil::getVar('uid');
    }

    if ($abort) {
        $form_action = 'preview'; // data not sufficient for save. force preview and correct.
    }


    // Preview the event
    if ($form_action == 'preview') {
        $eventdata['preview'] = true;
        // format the data for editing
        $eventdata = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayforDB', $eventdata);
        // reformat the category information
        Loader::loadClass('CategoryUtil');
        foreach ($eventdata['__CATEGORIES__'] as $name => $id) {
            $categories[$name] = CategoryUtil::getCategoryByID($id);
        }
        unset($eventdata['__CATEGORIES__']);
        $eventdata['__CATEGORIES__'] = $categories;
        // format the data for preview
        $eventdata = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $eventdata);
    } else {
        $eventdata['preview'] = "";
    }

    // Enter the event into the DB
    if ($form_action == 'save') {
        $sdate = strtotime($submitted_event['eventDate']);
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(pnModURL('postcalendar', 'admin', 'main'));
        }

        $eventdata = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayforDB', $eventdata);

        if (!$eid = pnModAPIFunc('PostCalendar', 'event', 'writeEvent', array(
            'eventdata' => $eventdata))) {
            LogUtil::registerError(__('Error! Submission failed.', $dom));
        } else {
            pnModAPIFunc('PostCalendar', 'admin', 'clearCache');
            $presentation_date = DateUtil::strftime(_SETTING_DATE_FORMAT, $sdate);
            if ($is_update) {
                LogUtil::registerStatus(__f('Done! Updated the event. (event date: %s)', $presentation_date, $dom));
            } else {
                LogUtil::registerStatus(__f('Done! Submitted the event. (event date: %s)', $presentation_date, $dom));
            }
            if ((int) $eventdata['eventstatus'] === (int) _EVENT_QUEUED) {
                LogUtil::registerStatus(__('The event has been queued for administrator approval.', $dom));
                pnModAPIFunc('PostCalendar', 'admin', 'notify', array(
                    'eid' => $eid,
                    'is_update' => $is_update)); //notify admin
            }
            // format startdate for redirect on success
            $url_date = strftime('%Y%m%d', $sdate);
        }
        if ($addtrigger) {
            pnRedirect(pnModURL('PostCalendar', 'event', 'new'));
        } else {
            pnRedirect(pnModURL('PostCalendar', 'user', 'view', array(
                'viewtype' => _SETTING_DEFAULT_VIEW,
                'Date' => $url_date)));
        }
        return true;
    }

    $submitformelements = pnModAPIFunc('PostCalendar', 'event', 'buildSubmitForm', array(
        'eventdata' => $eventdata,
        'Date' => $Date)); //sets defaults or builds selected values
    $tpl = pnRender::getInstance('PostCalendar', false); // Turn off template caching here
    foreach ($submitformelements as $var => $val) {
        $tpl->assign($var, $val);
    }

    // assign some basic settings
    $tpl->assign('EVENT_DATE_FORMAT', _SETTING_DATE_FORMAT);
    $tpl->assign('24HOUR_TIME', _SETTING_TIME_24HOUR);

    // assign function in case we were editing
    $tpl->assign('func', $func);

    return $tpl->fetch("event/postcalendar_event_submit.htm");
}
