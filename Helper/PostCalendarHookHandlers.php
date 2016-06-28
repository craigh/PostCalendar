<?php

/**
 * Copyright 2010-2012 PostCalendar Team.
 *
 * @license LPGL v2+
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
namespace Zikula\PostCalendarModule\Helper;

use Zikula_DisplayHook;
use Zikula_ValidationHook;
use Zikula_ProcessHook;
use Zikula_Event;
use CategoryRegistryUtil;
use DataUtil;
use HookUtil;
use ServiceUtil;
use System;
use ZLanguage;

class PostCalendarHookHandlers extends \Zikula_Hook_AbstractHandler
{

    const PROVIDER_AREANAME = 'provider.postcalendar.ui_hooks.event';

    /**
     * Zikula_View instance
     * @var object
     */
    private $view;
    /**
     * Zikula entity manager instance
     * @var object
     */
    private $_em;

    /**
     * Post constructor hook.
     *
     * @return void
     */
    public function setup()
    {
        $this->view = Zikula_View::getInstance("PostCalendar");
        $this->_em = ServiceUtil::getService('doctrine.entitymanager');
        $this->domain = ZLanguage::getModuleDomain('PostCalendar');
    }

    /**
     * Display hook for view.
     *
     * @param Zikula_DisplayHook $hook
     *
     * @return void
     */
    public function uiView(Zikula_DisplayHook $hook)
    {
        // Security check
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_READ)) {
            return;
        }
        // get data from $event
        $objectid = $hook->getId(); // id of hooked item

        if (!$objectid) {
            return;
        }

        $pc_event = $this->_em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->getHookedEvent($hook);

        if (!$pc_event) {
            return;
        }

        $this->view->assign('eid', $pc_event->getEid());

        // add this response to the event stack
        $hook->setResponse(new Zikula_Response_DisplayHook(self::PROVIDER_AREANAME, $this->view, 'hooks/view.tpl'));
    }

    /**
     * Display hook for edit views.
     *
     * @param Zikula_DisplayHook $hook
     *
     * @return void
     */
    public function uiEdit(Zikula_DisplayHook $hook)
    {
        // get data from $event
        $module = $hook->getCaller(); // default to active module
        $objectid = $hook->getId(); // id of hooked item

        if (!$objectid) {
            $access_type = ACCESS_ADD;
        } else {
            $access_type = ACCESS_EDIT;
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
                $pc_event = $this->_em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->getHookedEvent($hook);

                if ($pc_event) {
                    $pc_event = $pc_event->getOldArray();
                    $selectedcategories = array();
                    foreach ($pc_event['categories'] as $prop => $cats) {
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
        $postcalendarhookconfig = ModUtil::getVar($module, 'postcalendarhookconfig');
        $postcalendar_admincatselected = isset($postcalendarhookconfig[$hook->getAreaId()]['admincatselected']) ? $postcalendarhookconfig[$hook->getAreaId()]['admincatselected'] : 0;
        $postcalendar_optoverride = isset($postcalendarhookconfig[$hook->getAreaId()]['optoverride']) ? $postcalendarhookconfig[$hook->getAreaId()]['optoverride'] : false;

        if (($postcalendar_admincatselected['Main'] > 0) && (!$postcalendar_optoverride)) {
            $postcalendar_hide = true;
        } else {
            $postcalendar_hide = false;
        }
        $this->view->assign('postcalendar_hide', $postcalendar_hide);

        if ($postcalendar_admincatselected['Main'] == 0) {
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
            $this->view->assign('postcalendar_catregistry', $catregistry);
            $this->view->assign('postcalendar_selectedcategories', $selectedcategories);
        } else {
            $this->view->assign('postcalendar_admincatselected', serialize($postcalendar_admincatselected)); // value assigned by admin
        }
        $this->view->assign('postcalendar_optoverride', $postcalendar_optoverride);

        $this->view->assign('postcalendar_eid', $pceventid);

        // add this response to the event stack
        $hook->setResponse(new Zikula_Response_DisplayHook(self::PROVIDER_AREANAME, $this->view, 'hooks/edit.tpl'));
    }

    /**
     * Display hook for delete views.
     *
     * @param Zikula_DisplayHook $hook
     *
     * @return void
     */
    public function uiDelete(Zikula_DisplayHook $hook)
    {
        // Security check
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            return;
        }

        $pc_event = $this->_em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->getHookedEvent($hook);

        if (!empty($pc_event)) {
            $this->view->assign('eid', $pc_event->getEid());

            // add this response to the event stack
            $hook->setResponse(new Zikula_Response_DisplayHook(self::PROVIDER_AREANAME, $this->view, 'hooks/delete.tpl'));
        }
    }

    /**
     * validation handler for validate_edit hook type.
     *
     * @param Zikula_ValidationHook $hook
     *
     * @return void
     */
    public function validateEdit(Zikula_ValidationHook $hook)
    {
        // get data from post
        $data = $this->view->getRequest()->request->get('postcalendar', null);

        // create a new hook validation object and assign it to $this->validation
        $this->validation = new Zikula_Hook_ValidationResponse('data', $data);

        $hook->setValidator(self::PROVIDER_AREANAME, $this->validation);
    }

    /**
     * validation handler for validate_delete hook type.
     *
     * @param Zikula_ValidationHook $hook
     *
     * @return void
     */
    public function validateDelete(Zikula_ValidationHook $hook)
    {
        // nothing to do here really, just return
        // if however i wanted to check for something, i would do it like the
        // validate_edit function!!! [make sure you check ui_edit and process_edit also]

        return;
    }

    /**
     * process edit hook handler.
     *
     * @param Zikula_ProcessHook $hook
     *
     * @return void
     */
    public function processEdit(Zikula_ProcessHook $hook)
    {
        // check for validation here
        if (!$this->validation) {
            return;
        }

        $hookdata = $this->validation->getObject();
        $hookdata = DataUtil::cleanVar($hookdata);
        if (DataUtil::is_serialized($hookdata['cats'], false)) {
            $hookdata['cats'] = unserialize($hookdata['cats']);
        }

        if ((!isset($hookdata['optin'])) || (!$hookdata['optin'])) {
            // check to see if event currently exists - delete if so
            if (!empty($hookdata['eid'])) {
                $this->_em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->deleteEvents(array($hookdata['eid']));
                LogUtil::registerStatus($this->__("PostCalendar: Existing event deleted (opt out)."));
            } else {
                LogUtil::registerStatus($this->__("PostCalendar: Event not created (opt out)."));
            }
            return;
        }

        if (!empty($hookdata['eid'])) {
            // event already exists - just update
            $event = $this->_em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->find($hookdata['eid']);
            $word = $this->__("update");
        } else {
            // create a new event
            $event = new CalendarEventEntity();
            $word = $this->__("create");
        }
        $postCalendarEventInstance = $this->getClassInstance($hook);
        $postCalendarEventInstance->setEvent($event);
        $postCalendarEventInstance->setCategories($hookdata['cats']);
        if (!$postCalendarEventInstance->makeEvent()) {
            return false;
        }
        try {
            $this->_em->persist($postCalendarEventInstance->getEvent());
            $this->_em->flush();
            LogUtil::registerStatus($this->__f("PostCalendar: Associated Calendar event %sd.", $word));
        } catch (Exception $e) {
            LogUtil::registerError($this->__f('Error! Could not %s the associated Calendar event.', $word));
            return false;
        }
        return true;
    }

    /**
     * delete process hook handler.
     *
     * @param Zikula_ProcessHook $hook
     *
     * @return void
     */
    public function processDelete(Zikula_ProcessHook $hook)
    {
        $pc_event = $this->_em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->getHookedEvent($hook);
        $result = $this->_em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->deleteEvents(array($pc_event->getEid()));

        if (!$result) {
            return LogUtil::registerError($this->__('Error! Could not delete associated PostCalendar event.'));
        }

        LogUtil::registerStatus($this->__('Associated PostCalendar event also deleted.'));
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
        if (!($z_event['method'] == 'postcalendarhookconfig' && (strrpos(get_class($subject), '_Controller_Admin') || strrpos(get_class($subject), '\\AdminController')))) {
            return;
        }
        $moduleName = $subject->getName();
        if (!SecurityUtil::checkPermission($moduleName . '::', '::', ACCESS_ADMIN)) {
            throw new Zikula_Exception_Forbidden(LogUtil::getErrorMsgPermission());
        }
        $view = Zikula_View::getInstance('PostCalendar', false);
        $postcalendarhookconfig = ModUtil::getVar($moduleName, 'postcalendarhookconfig');

        $module = ModUtil::getModule($moduleName);
        if (null !== $module) {
            $versionClassName = $module->getVersionClass();
        } else {
            $versionClassName = $moduleName . '_Version';
        }
        $moduleVersionObj = new $versionClassName;
        $_em = ServiceUtil::getService('doctrine.entitymanager');
        $bindingsBetweenOwners = HookUtil::getBindingsBetweenOwners($moduleName, 'PostCalendar');
        foreach ($bindingsBetweenOwners as $k => $binding) {
            $areaname = $_em->getRepository('Zikula_Doctrine2_Entity_HookArea')->find($binding['sareaid'])->getAreaName();
            $bindingsBetweenOwners[$k]['areaname'] = $areaname;
            $bindingsBetweenOwners[$k]['areatitle'] = $view->__($moduleVersionObj->getHookSubscriberBundle($areaname)->getTitle());
            $postcalendarhookconfig[$binding['sareaid']]['admincatselected'] = isset($postcalendarhookconfig[$binding['sareaid']]['admincatselected']) ? $postcalendarhookconfig[$binding['sareaid']]['admincatselected'] : 0;
            $postcalendarhookconfig[$binding['sareaid']]['optoverride'] = isset($postcalendarhookconfig[$binding['sareaid']]['optoverride']) ? $postcalendarhookconfig[$binding['sareaid']]['optoverride'] : false;
        }
        $view->assign('areas', $bindingsBetweenOwners);
        $view->assign('postcalendarhookconfig', $postcalendarhookconfig);

        $view->assign('ActiveModule', $moduleName);

        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
        $view->assign('postcalendar_catregistry', $catregistry);

        $z_event->setData($view->fetch('hooks/modifyconfig.tpl'));
        $z_event->stop();
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
        if (!($z_event['method'] == 'postcalendarhookconfigprocess' && (strrpos(get_class($subject), '_Controller_Admin') || strrpos(get_class($subject), '\\AdminController')))) {
            return;
        }

        $dom = ZLanguage::getModuleDomain('PostCalendar');

        $request = ServiceUtil::getService('request');
        $hookdata = $request->request->get('postcalendar', array());
        $token = isset($hookdata['postcalendar_csrftoken']) ? $hookdata['postcalendar_csrftoken'] : null;
        if (!SecurityUtil::validateCsrfToken($token)) {
            throw new Zikula_Exception_Forbidden(__('Security token validation failed', $dom));
        }
        unset($hookdata['postcalendar_csrftoken']);

        $moduleName = $subject->getName();
        if (!SecurityUtil::checkPermission($moduleName . '::', '::', ACCESS_ADMIN)) {
            throw new Zikula_Exception_Forbidden(LogUtil::getErrorMsgPermission());
        }

        foreach ($hookdata as $area => $data) {
            if ((!isset($data['optoverride'])) || (empty($data['optoverride']))) {
                $hookdata[$area]['optoverride'] = "0";
            }
        }

        ModUtil::setVar($moduleName, 'postcalendarhookconfig', $hookdata);
        // ModVar: postcalendarhookconfig => array('areaid' => array(admincatselected, optoverride))

        LogUtil::registerStatus(__("PostCalendar: Hook option settings updated.", $dom));

        $z_event->setData(true);
        $z_event->stop();
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
        $_em = ServiceUtil::getService('doctrine.entitymanager');

        $events = $_em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->findBy(array('hooked_modulename' => DataUtil::formatForStore($module)));
        $i = 0;
        $affected = 0;
        foreach ($events as $event) {
            $_em->remove($event);
            $i++;
            $affected++;
            if ($i == 20) {
                $_em->flush();
                $i = 0;
            }
        }
        // flush remaining items
        $_em->flush();

        if ($affected > 0) {
            LogUtil::registerStatus(__f('ALL associated PostCalendar events also deleted. (%s)', $affected, $dom));
        }
    }

    /**
     * Find Class and instantiate
     *
     * @param Zikula_ProcessHook $hook
     * @return instantiated object of found class
     */
    private function getClassInstance(Zikula_ProcessHook $hook)
    {
        if (empty($hook)) {
            return false;
        }
        $module = $hook->getCaller();

        $locations = array($module, 'PostCalendar'); // locations to search for the class
        foreach ($locations as $location) {
            $classname = $location . '_PostCalendarEvent_' . $module;
            if (class_exists($classname)) {
                $instance = new $classname($hook);
                if ($instance instanceof PostCalendar_PostCalendarEvent_AbstractBase) {
                    return $instance;
                }
            }
        }
        return new PostCalendar_PostCalendarEvent_Generic($hook);
    }

    /**
     * populate Services menu with hook option link
     *
     * @param Zikula_Event $event
     */
    public static function servicelinks(Zikula_Event $event)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $module = $event['modname'];
        $bindingCount = count(HookUtil::getBindingsBetweenOwners($module, 'PostCalendar'));
        if (($bindingCount > 0) && ($module <> 'PostCalendar') && (empty($event->data) || (is_array($event->data)
                && !in_array(array('url' => ModUtil::url($module, 'admin', 'postcalendarhookconfig'), 'text' => __('PostCalendar Hook Options', $dom)), $event->data)))) {
            $event->data[] = array('url' => ModUtil::url($module, 'admin', 'postcalendarhookconfig'), 'text' => __('PostCalendar Hook Options', $dom));
        }
    }

}
