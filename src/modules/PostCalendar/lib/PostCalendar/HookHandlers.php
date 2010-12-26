<?php
/**
 * Copyright 2010 PostCalendar Team.
 *
 * @license LPGL v2+
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

class PostCalendar_HookHandlers extends Zikula_HookHandler
{
    /**
     * Zikula_View instance
     * @var object
     */
    private $view;

    /**
     * Post constructor hook.
     *
     * @return void
     */
    public function setup()
    {
        $this->view = Zikula_View::getInstance("PostCalendar");
    }

    /**
     * Display hook for view.
     *
     * Subject is the object being viewed that we're attaching to.
     * args[id] is the id of the object.
     * args[caller] the module who notified of this event.
     *
     * @param Zikula_Event $z_event
     *
     * @return void
     */
    public function ui_view(Zikula_Event $z_event)
    {
        // Security check
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_READ)) {
            return;
        }
        // get data from $event
        $module = isset($z_event['caller']) ? $z_event['caller'] : ModUtil::getName(); // default to active module
        $objectid = $z_event['id']; // id of hooked item

        if (!$objectid) {
            return;
        }
        
        ModUtil::dbInfoLoad('PostCalendar');
        $dbtable = DBUtil::getTables();
        $cols = $dbtable['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'
                  AND "   . $cols['hooked_objectid']   . " = '" . DataUtil::formatForStore($objectid) . "'";
        $pc_event = DBUtil::selectObject('postcalendar_events', $where, array('eid'));

        if (!$pc_event) {
            return;
        }

        $this->view->assign('eid', $pc_event['eid']);

        // add this response to the event stack
        $area = 'modulehook_area.postcalendar.event';
        $z_event->data[$area] = new Zikula_Response_DisplayHook($area, $this->view, 'hooks/view.tpl');
    }

     /**
     * Display hook for edit views.
     *
     * Subject is the object being created/edited that we're attaching to.
     * args[id] Is the ID of the subject.
     * args[caller] the module who notified of this event.
     *
     * @param Zikula_Event $z_event
     *
     * @return void
     */
    public function ui_edit(Zikula_Event $z_event)
    {
        // get data from $event
        $module = isset($z_event['caller']) ? $z_event['caller'] : ModUtil::getName(); // default to active module
        $objectid = $z_event['id']; // id of hooked item

        if (!$objectid) {
            $access_type = ACCESS_ADD;
        } else {
            $access_type = ACCESS_EDIT;
        }
        // special ACCESS case for users module new registration
        if ($module == "Users" && (isset($z_event['userregistration']) && $z_event['userregistration'])) {
            $access_type = ACCESS_READ;
        }
        
        // Security check
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', $access_type)) {
            return;
        }

        // if validation object does not exist, this is the first time display of the create/edit form.
        if (!$this->validation) {
            // either display an empty form,
            // or fill the form with existing data
            if (!$objectid) {
                // this is a create action so create a new empty object for editing
                $pceventid = 0;
                $selectedcategories = array();
            } else {
                // get the event
                // Get table info
                ModUtil::dbInfoLoad('PostCalendar');
                $dbtable = DBUtil::getTables();
                $cols = $dbtable['postcalendar_events_column'];
                // build where statement
                $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'
                          AND "   . $cols['hooked_objectid']   . " = '" . DataUtil::formatForStore($objectid) . "'";
                $pc_event = DBUtil::selectObject('postcalendar_events', $where);

                if ($pc_event) {
                    $selectedcategories = array();
                    foreach ($pc_event['__CATEGORIES__'] as $prop => $cats) {
                        $selectedcategories[$prop] = $cats['id'];
                    }
                    $pceventid = $pc_event['eid'];
                } else {
                    // no existing PC event associated with item
                    $pceventid = 0;
                    $selectedcategories = array();
                }
            }
        } else {
            // this is a re-entry because the form didn't validate.
            // We need to gather the input from the form and render display
            // get the input from the form (this was populated by the validation hook).
            $data = $this->validation->getObject();
        }

        $postcalendar_admincatselected = ModUtil::getVar($module, 'postcalendar_admincatselected');
        $postcalendar_optoverride = ModUtil::getVar($module, 'postcalendar_optoverride', false);

        if (($postcalendar_admincatselected['Main'] > 0) && (!$postcalendar_optoverride)) {
            $postcalendar_hide = true;
        } else {
            $postcalendar_hide = false;
        }
        $this->view->assign('postcalendar_hide', $postcalendar_hide);

        if ($postcalendar_admincatselected['Main'] == 0) {
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
            $this->view->assign('postcalendar_catregistry', $catregistry);
            $this->view->assign('postcalendar_selectedcategories', $selectedcategories);
        } else {
            $this->view->assign('postcalendar_admincatselected', serialize($postcalendar_admincatselected)); // value assigned by admin
        }
        $this->view->assign('postcalendar_optoverride', $postcalendar_optoverride);

        $this->view->assign('postcalendar_eid', $pceventid);

        // add this response to the event stack
        $area = 'modulehook_area.postcalendar.event';
        $z_event->data[$area] = new Zikula_Response_DisplayHook($area, $this->view, 'hooks/edit.tpl');
    }

    /**
     * Display hook for delete views.
     *
     * Subject is the object being created/edited that we're attaching to.
     * args[id] Is the ID of the subject.
     * args[caller] the module who notified of this event.
     *
     * @param Zikula_Event $z_event
     *
     * @return void
     */
    public function ui_delete(Zikula_Event $z_event)
    {
        // Security check
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            return;
        }

        // get data from $z_event
        $module = isset($z_event['caller']) ? $z_event['caller'] : ModUtil::getName(); // default to active module
        $objectid = $z_event['id']; // id of hooked item

        ModUtil::dbInfoLoad('PostCalendar');
        $dbtable = DBUtil::getTables();
        $cols = $dbtable['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'
                  AND "   . $cols['hooked_objectid']   . " = '" . DataUtil::formatForStore($objectid) . "'";
        $pc_event = DBUtil::selectObject('postcalendar_events', $where, array('eid'));

        $this->view->assign('eid', $pc_event['eid']);

        // add this response to the event stack
        $area = 'modulehook_area.postcalendar.event';
        $z_event->data[$area] = new Zikula_Response_DisplayHook($area, $this->view, 'hooks/delete.tpl');
    }

    /**
     * validation handler for validate.edit hook type.
     *
     * The property $z_event->data is an instance of Zikula_Collection_HookValidationProviders
     * Use the $z_event->data->set() method to log the validation response.
     *
     * This method populates this hookhandler object with a Zikula_Provider_HookValidation
     * so the information is available to the ui_edit method if validation fails,
     * and so the process_* can write the validated data to the database.
     *
     * @param Zikula_Event $z_event
     *
     * @return void
     */
    public function validate_edit(Zikula_Event $z_event)
    {
        // get data from post
        $data = FormUtil::getPassedValue('postcalendar', null, 'POST');

        // create a new hook validation object and assign it to $this->validation
        $this->validation = new Zikula_Provider_HookValidation('data', $data);

        // do the actual validation
        // for this example, the validation passes if our dummydata is a number between 1 and 9
        // otherwise the validation fais
//        if (!is_numeric($mhp_data['dummydata']) || ((int)$mhp_data['dummydata'] < 1 || (int)$mhp_data['dummydata'] > 9)) {
//            $this->validation->addError('dummydata', 'You must fill a number between 1 and 9.');
//        }

        $z_event->data->set('hookhandler.postcalendar.ui.edit', $this->validation);
    }

    /**
     * validation handler for validate.delete hook type.
     *
     * The property $z_event->data is an instance of Zikula_Collection_HookValidationProviders
     * Use the $z_event->data->set() method to log the validation response.
     *
     * This method populates this hookhandler object with a Zikula_Provider_HookValidation
     * so the information is available to the ui_edit method if validation fails,
     * and so the process_* can write the validated data to the database.
     *
     * @param Zikula_Event $z_event
     *
     * @return void
     */
    public function validate_delete(Zikula_Event $z_event)
    {
        // nothing to do here really, just return
        // if however i wanted to check for something, i would do it like the
        // validate_edit function!!! [make sure you check ui_edit and process_edit also]

        return;
    }

    /**
     * process edit hook handler.
     *
     * This should be executed only if the validation has succeeded.
     * This is used for both new and edit actions.  We can determine which
     * by the presence of an ID field or not.
     *
     * Subject is the object being created/edited that we're attaching to.
     * args[id] Is the ID of the subject.
     * args[caller] the module who notified of this event.
     *
     * @param Zikula_Event $z_event
     *
     * @return void
     */
    public function process_edit(Zikula_Event $z_event)
    {
        // check for validation here
        if (!$this->validation) {
            return;
        }

        $module = isset($z_event['caller']) ? $z_event['caller'] : ModUtil::getName(); // default to active module
        $objectid = $z_event['id']; // id of hooked item

        $hookinfo = $this->validation->getObject();
        $hookinfo = DataUtil::cleanVar($hookinfo);
        if (DataUtil::is_serialized($hookinfo['cats'], false)) {
            $hookinfo['cats'] = unserialize($hookinfo['cats']);
        }

        if ((!isset($hookinfo['optin'])) || (!$hookinfo['optin'])) {
            // check to see if event currently exists - delete if so
            if (!empty($hookinfo['eid'])) {
                DBUtil::deleteObjectByID('postcalendar_events', $hookinfo['eid'], 'eid');
                LogUtil::registerStatus($this->__("PostCalendar: Existing event deleted (opt out)."));
            } else {
                LogUtil::registerStatus($this->__("PostCalendar: News event not created (opt out)."));
            }
            return;
        }

        if (!$postCalendarEventInstance = $this->_getClassInstance($module)) {
            LogUtil::registerError($this->__f("PostCalendar: Could not create %s class instance.", $module));
        }
        if (is_callable(array($postCalendarEventInstance, 'makeEvent'))) {
            $args = array(
                'objectid' => $objectid);
            if ($postCalendarEventInstance->makeEvent($args)) {
                $postCalendarEventInstance->setHooked_objectid($objectid);
                $postCalendarEventInstance->set__CATEGORIES__($hookinfo['cats']);
                ModUtil::dbInfoLoad('PostCalendar');
                if (!empty($hookinfo['eid'])) {
                    // event already exists - just update
                    $postCalendarEventInstance->setEid($hookinfo['eid']);
                    $pc_event = $postCalendarEventInstance->toArray();
                    if (DBUtil::updateObject($pc_event, 'postcalendar_events', NULL, 'eid')) {
                        LogUtil::registerStatus($this->__("PostCalendar: Associated Calendar event updated."));
                        return true;
                    }
                } else {
                    // create a new event
                    $pc_event = $postCalendarEventInstance->toArray();
                    if (DBUtil::insertObject($pc_event, 'postcalendar_events', 'eid')) {
                        LogUtil::registerStatus($this->__("PostCalendar: Event created."));
                        return true;
                    }
                }
            } else {
                LogUtil::registerError($this->__("PostCalendar: Could not create event (method failed)."));
            }
        } else {
            LogUtil::registerError($this->__f("PostCalendar: Extended class for %s not found.", $module));
        }
        LogUtil::registerError($this->__('Error! Could not update the associated Calendar event.'));
    }

    /**
     * delete process hook handler.
     *
     * The subject should be the object that was deleted.
     * args[id] Is the is of the object
     * args[caller] is the name of who notified this event.
     *
     * @param Zikula_Event $z_event
     *
     * @return void
     */
    public function process_delete(Zikula_Event $z_event)
    {
        $module = isset($z_event['caller']) ? $z_event['caller'] : ModUtil::getName(); // default to active module
        $objectid = $z_event['id']; // id of hooked item

        // Get table info
        ModUtil::dbInfoLoad('PostCalendar');
        $table = DBUtil::getTables();
        $cols = $table['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'
                  AND "   . $cols['hooked_objectid']   . " = '" . DataUtil::formatForStore($objectid) . "'";

        //return (bool)DBUtil::deleteWhere('postcalendar_events', $where);
        // TODO THIS IS NOT DELETING THE ROW IN categories_mapobj table!!!! (it should!)
        if (!DBUtil::deleteObject(array(), 'postcalendar_events', $where, 'eid')) {
            return LogUtil::registerError($this->__('Error! Could not delete associated PostCalendar event.'));
        }

        LogUtil::registerStatus($this->__('Associated PostCalendar event also deleted.'));
    }

    /**
     * add config options to hooked module's module config
     *
     * @param Zikula_Event $z_event
     */
    public function config_ui_edit(Zikula_Event $z_event)
    {
        $module = isset($z_event['caller']) ? $z_event['caller'] : ModUtil::getName(); // default to active module
    
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $this->view->assign('postcalendar_catregistry', $catregistry);
    
        $this->view->assign('postcalendar_optoverride', ModUtil::getVar($module, 'postcalendar_optoverride', false));
        $this->view->assign('postcalendar_admincatselected', ModUtil::getVar($module, 'postcalendar_admincatselected'));

        // add this response to the event stack
        $area = 'modulehook_area.postcalendar.event';
        $z_event->data[$area] = new Zikula_Response_DisplayHook($area, $this->view, 'hooks/modifyconfig.tpl');
    }

    /**
     * process results of config_ui_edit
     * 
     * @param Zikula_Event $z_event
     */
    public function config_process_edit(Zikula_Event $z_event)
    {
        $hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST');
        if ((!isset($hookinfo['postcalendar_optoverride'])) || (empty($hookinfo['postcalendar_optoverride']))) {
            $hookinfo['postcalendar_optoverride'] = 0;
        }
        $module = isset($z_event['caller']) ? $z_event['caller'] : ModUtil::getName(); // default to active module
        ModUtil::setVars($module, $hookinfo);
        // ModVars: postcalendar_admincatselected, postcalendar_optoverride

        LogUtil::registerStatus($this->__("PostCalendar: module config updated."));
    }

    /**
     * Handle module uninstall event "installer.module.uninstalled".
     * Receives $modinfo as $args
     *
     * @param Zikula_Event $z_event
     *
     * @return void
     */
    public static function moduleDelete(Zikula_Event $z_event)
    {
        $module = $z_event['name'];

        // Get table info
        ModUtil::dbInfoLoad('PostCalendar');
        $dbtable = DBUtil::getTables();
        $cols = $dbtable['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'";

        if (DBUtil::deleteObject(array(), 'postcalendar_events', $where, 'eid')) {
            LogUtil::registerStatus($this->__('ALL associated PostCalendar events also deleted.'));
        }
        LogUtil::registerError($this->__('Error! Could not delete associated PostCalendar events.'));
    }

    /**
     * Find Class and instantiate
     *
     * @param string $module Module name
     * @return instantiated object of found class
     */
    private function _getClassInstance($module) {
        if (empty($module)) {
            return false;
        }

        $locations = array($module, 'PostCalendar'); // locations to search for the class
        foreach ($locations as $location) {
            $classname = $location . '_PostCalendarEvent_' . $module;
            if (class_exists($classname)) {
                $instance = new $classname($module);
                if ($instance instanceof PostCalendar_PostCalendarEvent_Base) {
                    return $instance;
                }
            }
        }
        return new PostCalendar_PostCalendarEvent_Generic($module);
    }
}
