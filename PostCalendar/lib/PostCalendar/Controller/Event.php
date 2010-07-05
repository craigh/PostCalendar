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

class PostCalendar_Controller_Event extends Zikula_Controller
{
    /**
     * This is a user form 'are you sure' display
     * to delete an event
     */
    public function delete()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
        $eid    = FormUtil::getPassedValue('eid'); //  seems like this should be handled by the eventHandler
        $this->renderer = FormUtil::newForm('PostCalendar');
    
        // get the event from the DB
        $event = DBUtil::selectObjectByID('postcalendar_events', $eid, 'eid');
        $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);
    
        $this->renderer->assign('loaded_event', $event);
        return $this->renderer->execute('event/deleteeventconfirm.tpl', new PostCalendar_Form_Handler_editHandler());
    }
    
    /**
     * edit an event
     */
    public function edit($args)
    {
        $args['eid'] = FormUtil::getPassedValue('eid');
        return $this->create($args);
    }
    /**
     * copy an event
     */
    public function copy($args)
    {
        $args['eid'] = FormUtil::getPassedValue('eid');
        return $this->create($args);
    }
    /**
     * @function create
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
    public function create($args)
    {
        // We need at least ADD permission to submit an event
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
    
        // these items come on brand new view of this function
        $func = FormUtil::getPassedValue('func', 'create');
        $Date = FormUtil::getPassedValue('Date'); //typically formatted YYYYMMDD or YYYYMMDD000000
        // format to '%Y%m%d%H%M%S'
        $Date = ModUtil::apiFunc('PostCalendar', 'user', 'getDate', array(
            'Date' => $Date));
    
        // these items come on submission of form
        $submitted_event = FormUtil::getPassedValue('postcalendar_events', NULL);
        $is_update       = FormUtil::getPassedValue('is_update', false);
        $form_action     = FormUtil::getPassedValue('form_action', NULL);
        $authid          = FormUtil::getPassedValue('authid');
    
        // compensate for translation of input values
        if (isset($form_action)) {
            $formactionarraymap = array(
                $this->__('Save')         => 'save',
                $this->__('Save and Add') => 'save and add',
                $this->__('Preview')      => 'preview');
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
            $abort = ModUtil::apiFunc('PostCalendar', 'event', 'validateformdata', $submitted_event);
            // alo correct locations data if importing from locations module
            $submitted_event = ModUtil::apiFunc('PostCalendar', 'event', 'correctlocationdata', $submitted_event);
        }
    
        if ($func == 'create') { // triggered on form_action=preview && on brand new load
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
                $eventdata = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $eventdata);
            }
            // need to check each of these below to see if truly needed CAH 11/14/09
            $eventdata['Date'] = $Date;
            $eventdata['is_update'] = true;
            $eventdata['data_loaded'] = true;
        }
    
        if ($func == 'copy') {
            // reset some default values that are different from 'edit'
            $form_action = '';
            $func = "create"; // change function so data is processed as 'new' in subsequent pass
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
            $eventdata = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayforDB', $eventdata);
            // reformat the category information
            foreach ($eventdata['__CATEGORIES__'] as $name => $id) {
                $categories[$name] = CategoryUtil::getCategoryByID($id);
            }
            unset($eventdata['__CATEGORIES__']);
            $eventdata['__CATEGORIES__'] = $categories;
            // format the data for preview
            $eventdata = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $eventdata);
        } else {
            $eventdata['preview'] = "";
        }
    
        // Enter the event into the DB
        if ($form_action == 'save') {
            $sdate = strtotime($submitted_event['eventDate']);
            if (!SecurityUtil::confirmAuthKey()) {
                return LogUtil::registerAuthidError(ModUtil::url('postcalendar', 'admin', 'main'));
            }
    
            $eventdata = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayforDB', $eventdata);
    
            if (!$eid = ModUtil::apiFunc('PostCalendar', 'event', 'writeEvent', array(
                'eventdata' => $eventdata))) {
                LogUtil::registerError($this->__('Error! Submission failed.'));
            } else {
                ModUtil::apiFunc('PostCalendar', 'admin', 'clearCache');
                $presentation_date = DateUtil::strftime(_SETTING_DATE_FORMAT, $sdate);
                if ($is_update) {
                    LogUtil::registerStatus($this->__f('Done! Updated the event. (event date: %s)', $presentation_date));
                } else {
                    LogUtil::registerStatus($this->__f('Done! Submitted the event. (event date: %s)', $presentation_date));
                }
                if ((int) $eventdata['eventstatus'] === (int) _EVENT_QUEUED) {
                    LogUtil::registerStatus($this->__('The event has been queued for administrator approval.'));
                    ModUtil::apiFunc('PostCalendar', 'admin', 'notify', array(
                        'eid' => $eid,
                        'is_update' => $is_update)); //notify admin
                }
                // format startdate for redirect on success
                $url_date = strftime('%Y%m%d', $sdate);
            }
            if ($addtrigger) {
                System::redirect(ModUtil::url('PostCalendar', 'event', 'create'));
            } else {
                System::redirect(ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => _SETTING_DEFAULT_VIEW,
                    'Date' => $url_date)));
            }
            return true;
        }
    
        $submitformelements = ModUtil::apiFunc('PostCalendar', 'event', 'buildSubmitForm', array(
            'eventdata' => $eventdata,
            'Date' => $Date)); //sets defaults or builds selected values
        foreach ($submitformelements as $var => $val) {
            $this->renderer->assign($var, $val);
        }
    
        // assign some basic settings
        $this->renderer->assign('EVENT_DATE_FORMAT', _SETTING_DATE_FORMAT);
        $this->renderer->assign('24HOUR_TIME', _SETTING_TIME_24HOUR);
    
        // assign function in case we were editing
        $this->renderer->assign('func', $func);
    
        return $this->renderer->fetch("event/submit.tpl");
    }
} // end class def