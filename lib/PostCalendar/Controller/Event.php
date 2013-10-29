<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
use PostCalendar_Entity_CalendarEvent as CalendarEvent;

class PostCalendar_Controller_Event extends Zikula_AbstractController
{
    public function postInitialize()
    {
        $this->view->setCaching(false);
    }

    /**
     * This is a user form 'are you sure' display
     * to delete an event
     */
    public function delete()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD), LogUtil::getErrorMsgPermission());

        $eid    = $this->request->query->get('eid'); //  seems like this should be handled by the eventHandler
        $render = FormUtil::newForm('PostCalendar', $this);

        // get the event from the DB
        $event = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->find($eid)->getOldArray();
        $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', array('event' => $event));

        $render->assign('loaded_event', $event);
        return $render->execute('event/deleteeventconfirm.tpl', new PostCalendar_Form_Handler_EditHandler());
    }

    /**
     * edit an event
     */
    public function edit($args)
    {
        $args['eid'] = $this->request->query->get('eid');
        return $this->create($args);
    }
    /**
     * copy an event
     */
    public function copy($args)
    {
        $args['eid'] = $this->request->query->get('eid');
        return $this->create($args);
    }
    /**
     * @desc create an event
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
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD), LogUtil::getErrorMsgPermission());

        // these items come on brand new view of this function
        $func = $this->request->query->get('func', 'create');
        $date = $this->request->query->get('date');
        
        $date = PostCalendar_Util::getDate(array(
            'date' => $date));

        // these items come on submission of form
        $submitted_event = $this->request->request->get('postcalendar_events', NULL);
        // process checkboxes
        $submitted_event['alldayevent'] = (isset($submitted_event['alldayevent'])) ? $submitted_event['alldayevent'] : 0;
        $submitted_event['html_or_text'] = (isset($submitted_event['html_or_text'])) ? $submitted_event['html_or_text'] : 0;
        $submitted_event['recurrtype'] = (isset($submitted_event['recurrtype'])) ? $submitted_event['recurrtype'] : CalendarEvent::RECURRTYPE_NONE;
        $submitted_event['hasexceptions'] = (isset($submitted_event['hasexceptions'])) ? $submitted_event['hasexceptions'] : 0;

        $is_update       = $this->request->request->get('is_update', false);
        $form_action     = $this->request->request->get('form_action', NULL);

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
            // make DateTime objects from form data
            $submitted_event['eventStart'] = DateTime::createFromFormat("Y-m-d G:i", $submitted_event['eventstart_date'] . " " . $submitted_event['eventstart_time']);
            $submitted_event['eventEnd'] = DateTime::createFromFormat("Y-m-d G:i", $submitted_event['eventend_date'] . " " . $submitted_event['eventend_time']);
            $submitted_event['endDate'] = DateTime::createFromFormat('Y-m-d', $submitted_event['enddate']);
            unset($submitted_event['eventstart_date'], $submitted_event['eventstart_time'], $submitted_event['eventend_date'], $submitted_event['eventend_time'], $submitted_event['enddate']);
            
            $abort = ModUtil::apiFunc('PostCalendar', 'event', 'validateformdata', $submitted_event);
            // check hooked modules for validation
            $hook = new Zikula_ValidationHook('postcalendar.ui_hooks.events.validate_edit', new Zikula_Hook_ValidationProviders());
            $hookvalidators = $this->notifyHooks($hook)->getValidators();
            $abort = $abort || $hookvalidators->hasErrors() ? true : false;
            if ($hookvalidators->hasErrors()) {
                LogUtil::registerError($this->__('Error! Hooked content does not validate.'));
            }
        }

        if ($func == 'create') { // triggered on form_action=preview && on brand new load
            $eventdata = array();
            // wrap all the data into array for passing to save and preview functions
            if ((isset($submitted_event['data_loaded'])) && (!empty($submitted_event['data_loaded']))) {
                $eventdata = $submitted_event; // data loaded on preview and processing of new event, but not on initial pageload
            }
            $eventdata['is_update'] = $is_update;
            $eventdata['data_loaded'] = true;

        } else { // func=edit or func=copy (we are editing an existing event or copying an existing event)
            if ((isset($submitted_event['data_loaded'])) && ($submitted_event['data_loaded'])) {
                $eventdata = $submitted_event; // reloaded event when editing
            } else {
                // here were need to format the DB data to be able to load it into the form
                $eid = $args['eid'];
                $eventdata = $this->entityManager
                        ->getRepository('PostCalendar_Entity_CalendarEvent')
                        ->findOneBy(array('eid' => $eid))
                        ->getOldArray();
                $eventdata = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', array('event' => $eventdata));
            }
            // need to check each of these below to see if truly needed CAH 11/14/09
            $eventdata['date'] = $date->format('Y-m-d');
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
            $eventdata['informant'] = UserUtil::getVar('uid');
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
            foreach ($eventdata['categories'] as $name => $id) {
                $categories[$name] = CategoryUtil::getCategoryByID($id);
            }
            unset($eventdata['categories']);
            $eventdata['categories'] = $categories;
            // reformat category attributes
            foreach ($categories as $propName => $category) {
                foreach ($category['attributes'] as $attr) {
                    $eventdata['categories'][$propName]['attributes'][$attr->getName()] = $attr->getValue();
                }
            }
            // format the data for preview
            $eventdata = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', array('event' => $eventdata));
        } else {
            $eventdata['preview'] = "";
        }

        // Enter the event into the DB
        if ($form_action == 'save') {
            $this->checkCsrfToken();

            $eventdata = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayforDB', $eventdata);

            if (!$eid = ModUtil::apiFunc('PostCalendar', 'event', 'writeEvent', array(
                'eventdata' => $eventdata))) {
                LogUtil::registerError($this->__('Error! Submission failed.'));
            } else {
                $url = new Zikula_ModUrl('PostCalendar', 'user', 'display', ZLanguage::getLanguageCode(), array('viewtype' => 'event', 'eid' => $eid));
                $this->notifyHooks(new Zikula_ProcessHook('postcalendar.ui_hooks.events.process_edit', $eid, $url));
                $this->view->clear_cache();
                $dateFormat = $this->getVar('pcDateFormats');
                if ($is_update) {
                    LogUtil::registerStatus($this->__f('Done! Updated the event. (event date: %s)', $submitted_event['eventStart']->format($dateFormat['date'])));
                } else {
                    LogUtil::registerStatus($this->__f('Done! Submitted the event. (event date: %s)', $submitted_event['eventStart']->format($dateFormat['date'])));
                }
                if ((int)$eventdata['eventstatus'] === (int)CalendarEvent::QUEUED) {
                    LogUtil::registerStatus($this->__('The event has been queued for administrator approval.'));
                    ModUtil::apiFunc('PostCalendar', 'admin', 'notify', array(
                        'eid' => $eid,
                        'is_update' => $is_update)); //notify admin
                }
            }
            if ($addtrigger) {
                System::redirect(ModUtil::url('PostCalendar', 'event', 'create'));
            } else {
                System::redirect(ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->getVar('pcDefaultView'),
                    'date' => $submitted_event['eventStart']->format('Ymd'))));
            }
            return true;
        }

        $submitformelements = ModUtil::apiFunc('PostCalendar', 'event', 'buildSubmitForm', array(
            'eventdata' => $eventdata,
            'date' => $date,
            'func' => $func)); //sets defaults or builds selected values
        foreach ($submitformelements as $var => $val) {
            $this->view->assign($var, $val);
        }

        // assign function in case we were editing
        $this->view->assign('func', $func);

        $navBar = new PostCalendar_CalendarView_Navigation($this->view, $date, null, null, null, array(
            'filter' => false, 
            'jumpdate' => false, 
            'navbar' => true,
            'navbartype' => $this->getVar('pcNavBarType')));
        $this->view->assign('navBar', $navBar->render());
        
        return $this->view->fetch("event/submit.tpl");
    }
} // end class def