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

        $modname = $z_event->getSubject()->getName();
        $data['optin'] = isset($data['optin']) ? $data['optin'] : ModUtil::gevar($modname, 'postcalendar_optoverride');
        $data['cats'] = isset($data['optin']) ? $data['optin'] : ModUtil::gevar($modname, 'postcalendar_admincatselected');

        // create a new hook validation object and assign it to $this->validation
        $this->validation = new Zikula_Provider_HookValidation('data', $data);

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
        
        $dom = ZLanguage::getModuleDomain('PostCalendar');
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
                LogUtil::registerStatus(__("PostCalendar: Existing event deleted (opt out).", $dom));
            } else {
                LogUtil::registerStatus(__("PostCalendar: News event not created (opt out).", $dom));
            }
            return;
        }

        if (!$postCalendarEventInstance = $this->_getClassInstance($module)) {
            LogUtil::registerError(__f("PostCalendar: Could not create %s class instance.", $module, $dom));
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
                        LogUtil::registerStatus(__("PostCalendar: Associated Calendar event updated.", $dom));
                        return true;
                    }
                } else {
                    // create a new event
                    $pc_event = $postCalendarEventInstance->toArray();
                    if (DBUtil::insertObject($pc_event, 'postcalendar_events', 'eid')) {
                        LogUtil::registerStatus(__("PostCalendar: Event created.", $dom));
                        return true;
                    }
                }
            } else {
                LogUtil::registerError(__("PostCalendar: Could not create event (method failed).", $dom));
            }
        } else {
            LogUtil::registerError(__f("PostCalendar: Extended class for %s not found.", $module, $dom));
        }
        LogUtil::registerError(__('Error! Could not update the associated Calendar event.', $dom));
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
        $dom = ZLanguage::getModuleDomain('PostCalendar');

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
            return LogUtil::registerError(__('Error! Could not delete associated PostCalendar event.', $dom));
        }

        LogUtil::registerStatus(__('Associated PostCalendar event also deleted.', $dom));
    }

    /**
     * add hook config options to hooked module's module config
     *
     * @param Zikula_Event $z_event
     */
    public static function postcalendarhookconfig(Zikula_Event $z_event)
    {
        // check if this is for this handler
        $subject = $z_event->getSubject();
        if (!($z_event['method'] == 'postcalendarhookconfig' && strrpos(get_class($subject), '_Controller_Admin'))) {
           return;
        }
        $moduleName = $subject->getName();
        if (!SecurityUtil::checkPermission($moduleName.'::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
        $view = Zikula_View::getInstance('PostCalendar', false);

        $view->assign('ActiveModule', $moduleName);
        
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $view->assign('postcalendar_catregistry', $catregistry);
    
        $view->assign('postcalendar_optoverride', ModUtil::getVar($moduleName, 'postcalendar_optoverride', false));
        $view->assign('postcalendar_admincatselected', ModUtil::getVar($moduleName, 'postcalendar_admincatselected'));

        $z_event->setData($view->fetch('hooks/modifyconfig.tpl'));
        $z_event->setNotified();
    }

    /**
     * process results of postCalendarHookConfig
     * 
     * @param Zikula_Event $z_event
     */
    public static function postcalendarhookconfigprocess(Zikula_Event $z_event)
    {
        // check if this is for this handler
        $subject = $z_event->getSubject();
        if (!($z_event['method'] == 'postcalendarhookconfigprocess' && strrpos(get_class($subject), '_Controller_Admin'))) {
           return;
        }
        $moduleName = $subject->getName();
        if (!SecurityUtil::checkPermission($moduleName.'::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
        
        $hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST');
        if ((!isset($hookinfo['postcalendar_optoverride'])) || (empty($hookinfo['postcalendar_optoverride']))) {
            $hookinfo['postcalendar_optoverride'] = 0;
        }
        ModUtil::setVars($moduleName, $hookinfo);
        // ModVars: postcalendar_admincatselected, postcalendar_optoverride

        $dom = ZLanguage::getModuleDomain('PostCalendar');
        LogUtil::registerStatus(__("PostCalendar: Hook option settings updated.", $dom));

        $z_event->setData(true);
        $z_event->setNotified();
        return System::redirect(ModUtil::url($moduleName, 'admin', 'main'));
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
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        // Get table info
        ModUtil::dbInfoLoad('PostCalendar');
        $dbtable = DBUtil::getTables();
        $cols = $dbtable['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'";

        if (DBUtil::deleteObject(array(), 'postcalendar_events', $where, 'eid')) {
            LogUtil::registerStatus(__('ALL associated PostCalendar events also deleted.', $dom));
        }
        LogUtil::registerError(__('Error! Could not delete associated PostCalendar events.', $dom));
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

    /**
     * populate Services menu with hook option link
     *
     * @param Zikula_Event $event
     */
    public static function servicelinks(Zikula_Event $event)
    {
        $module = ModUtil::getName();
        if (HookUtil::isSubscriberCapable(ModUtil::getName()) && ($module <> 'PostCalendar')) {
            $event->data[] = array('url' => ModUtil::url($module, 'admin', 'postcalendarhookconfig'), 'text' => __('PostCalendar Hook Options'));
        }
    }
}
