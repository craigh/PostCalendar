<?php

/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

namespace Zikula\PostCalendarModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Zikula\PostCalendarModule\Entity\CalendarEventEntity as CalendarEvent;
use Zikula\PostCalendarModule\Api\EventApi;
use Zikula\PostCalendarModule\Helper\PostCalendarUtil;
use DateTime;
use System;
use SecurityUtil;
use ModUtil;
use LogUtil;
use StringUtil;
use CategoryRegistryUtil;

class AdminController extends \Zikula_AbstractController
{

    const ACTION_APPROVE = 0;
    const ACTION_HIDE = 1;
    const ACTION_VIEW = 3;
    const ACTION_DELETE = 4;

    public function postInitialize()
    {
        $this->view->setCaching(false);
    }

    /**
     * This function is the default function, and is called whenever the
     * module is initiated without defining arguments.
     * 
     * @Route("/admin")
     */
    public function mainAction()
    {
        $this->redirect(ModUtil::url('PostCalendar', 'admin', 'listevents'));
    }
    /**
     * This function is the default function, and is called whenever the
     * module is initiated without defining arguments.
     * 
     * @Route("/admin/index")
     */
    public function indexAction()
    {
        $this->redirect(ModUtil::url('PostCalendar', 'admin', 'listevents'));
    }

    /**
     * #Desc: present administrator options to change module configuration
     * @return string config template
     * 
     * @Route("/admin/modifyconfig")
     */
    public function modifyconfigAction()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());

        $prefix = System::getVar('prefix', '');
        if (!empty($prefix)) {
            $prefix = $prefix . '_';
        }
        $this->view->assign('timeit_table', "{$prefix}TimeIt_events");

        return $this->view->fetch('admin/modifyconfig.tpl');
    }

    /**
     * #Desc: list events as requested/filtered
     *              send list to template
     * @return string showlist template
     * 
     * @Route("/admin/listevents")
     */
    public function listeventsAction(array $args)
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE), LogUtil::getErrorMsgPermission());

        $listtype = isset($args['listtype']) ? $args['listtype'] : $this->request->query->get('listtype', $this->request->request->get('listtype', CalendarEvent::APPROVED));

        switch ($listtype) {
            case CalendarEvent::ALLSTATUS:
                $functionname = "all";
                break;
            case CalendarEvent::HIDDEN:
                $functionname = "hidden";
                break;
            case CalendarEvent::QUEUED:
                $functionname = "queued";
                break;
            case CalendarEvent::APPROVED:
            default:
                $functionname = "approved";
        }

        $sortcolclasses = array(
            'title' => 'z-order-unsorted',
            'time' => 'z-order-unsorted',
            'eventStart' => 'z-order-unsorted');

        $offset = $this->request->query->get('offset', $this->request->request->get('offset', 0));
        $sort = $this->request->query->get('sort', $this->request->request->get('sort', 'time'));
        $original_sdir = $this->request->query->get('sdir', $this->request->request->get('sdir', 1));
        $this->view->assign('offset', $offset);
        $this->view->assign('sort', $sort);
        $this->view->assign('sdir', $original_sdir);
        $original_sort = $sort;
        $sdir = $original_sdir ? 0 : 1; //if true change to false, if false change to true

        if ($sdir == 0) {
            $sortcolclasses[$original_sort] = 'z-order-desc';
            $sort = "a.$sort DESC";
        }
        if ($sdir == 1) {
            $sortcolclasses[$original_sort] = 'z-order-asc';
            $sort = "a.$sort ASC";
        }
        $this->view->assign('sortcolclasses', $sortcolclasses);

        $filtercats = isset($args['filtercats']) ? $args['filtercats'] : $this->request->query->get('filtercats', $this->request->request->get('filtercats', null));
        $filtercats_serialized = $this->request->query->get('filtercats_serialized', false);
        $filtercats = $filtercats_serialized ? unserialize($filtercats_serialized) : $filtercats;
        $selectedCategories = EventApi::formatCategoryFilter($filtercats);

        $events = $this->entityManager->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')
                ->getEventlist($listtype, $sort, $offset - 1, $this->getVar('pcListHowManyEvents'), $selectedCategories);
        $events = $this->_appendObjectActions($events, $listtype);

        $total_events = $this->entityManager->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')
                ->getEventCount($listtype, $selectedCategories);
        $this->view->assign('total_events', $total_events);

        $this->view->assign('filter_active', (($listtype == CalendarEvent::ALLSTATUS) && empty($selectedCategories)) ? false : true);

        $this->view->assign('functionname', $functionname);
        $this->view->assign('events', $events);
        $sorturls = array('title', 'time', 'eventStart');
        foreach ($sorturls as $sorturl) {
            $this->view->assign($sorturl . '_sort_url', ModUtil::url('PostCalendar', 'admin', 'listevents', array(
                        'listtype' => $listtype,
                        'filtercats_serialized' => serialize($selectedCategories),
                        'sort' => $sorturl,
                        'sdir' => $sdir)));
        }
        $this->view->assign('formactions', array(
            '-1' => $this->__('With selected:'),
            self::ACTION_VIEW => $this->__('View'),
            self::ACTION_APPROVE => $this->__('Approve'),
            self::ACTION_HIDE => $this->__('Hide'),
            self::ACTION_DELETE => $this->__('Delete')));
        $this->view->assign('actionselected', '-1');
        $this->view->assign('listtypes', array(
            CalendarEvent::ALLSTATUS => $this->__('All Events'),
            CalendarEvent::APPROVED => $this->__('Approved Events'),
            CalendarEvent::HIDDEN => $this->__('Hidden Events'),
            CalendarEvent::QUEUED => $this->__('Queued Events')));
        $this->view->assign('listtypeselected', $listtype);

        $this->view->assign('catregistry', CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent'));
        $this->view->assign('selectedcategories', $selectedCategories);

        return $this->view->fetch('admin/showlist.tpl');
    }

    /**
     * #Desc: allows admin to revue selected events then take action
     * @return string html template adminrevue template
     */
    public function adminevents()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE), LogUtil::getErrorMsgPermission());

        $action = $this->request->request->get('action', $this->request->query->get('action', self::ACTION_VIEW));
        $events = $this->request->request->get('events', $this->request->query->get('events', null)); // could be an array or single val

        if (!isset($events)) {
            LogUtil::registerError($this->__('Please select an event.'));
            // return to where we came from
            $listtype = $this->request->request->get('listtype', $this->request->query->get('listtype', CalendarEvent::APPROVED));
            return $this->listevents(array('listtype' => $listtype));
        }

        if (!is_array($events)) {
            $events = array(
                $events);
        } //create array if not already
        $alleventinfo = array();

        $events = $this->entityManager->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->findBy(array('eid' => $events));
        foreach ($events as $event) {
            // get event info
            $eventitems = $event->getOldArray();
            $eventitems = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', array('event' => $eventitems));
            $alleventinfo[$event->getEid()] = $eventitems;
        }

        $count = count($events);
        $texts = array(
            self::ACTION_VIEW => $this->__("view"),
            self::ACTION_APPROVE => $this->__("approve"),
            self::ACTION_HIDE => $this->__("hide"),
            self::ACTION_DELETE => $this->__("delete"));

        $this->view->assign('actiontext', $texts[$action]);
        $this->view->assign('action', $action);
        $are_you_sure_text = $this->_fn('Do you really want to %s this event?', 'Do you really want to %s these events?', $count, $texts[$action]);
        $this->view->assign('areyousure', $are_you_sure_text);
        $this->view->assign('alleventinfo', $alleventinfo);

        return $this->view->fetch("admin/eventrevue.tpl");
    }

    /**
     * #Desc: reset all module variables to default values as defined in pninit.php
     * @return      status/error ->back to modify config page
     */
    public function resetDefaults()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());

        $defaults = PostCalendarUtil::getdefaults();
        if (!count($defaults)) {
            return LogUtil::registerError($this->__('Error! Could not load default values.'));
        }

        // delete all the old vars
        $this->delVars();

        // set the new variables
        $this->setVars($defaults);

        // clear the cache
        $this->view->clear_cache();

        LogUtil::registerStatus($this->__('Done! PostCalendar configuration reset to use default values.'));
        return $this->modifyconfig();
    }

    /**
     * #Desc: sets module variables as requested by admin
     * @return      status/error ->back to modify config page
     */
    public function updateconfig()
    {
        $this->checkCsrfToken();

        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());

        $defaults = PostCalendarUtil::getdefaults();
        if (!count($defaults)) {
            return LogUtil::registerError($this->__('Error! Could not load default values.'));
        }

        $settings = array(
            'pcTime24Hours' => $this->request->request->get('pcTime24Hours', 0),
            'pcEventsOpenInNewWindow' => $this->request->request->get('pcEventsOpenInNewWindow', 0),
            'pcFirstDayOfWeek' => $this->request->request->get('pcFirstDayOfWeek', $defaults['pcFirstDayOfWeek']),
            'pcUsePopups' => $this->request->request->get('pcUsePopups', 0),
            'pcAllowDirectSubmit' => $this->request->request->get('pcAllowDirectSubmit', 0),
            'pcListHowManyEvents' => $this->request->request->get('pcListHowManyEvents', $defaults['pcListHowManyEvents']),
            'pcEventDateFormat' => $this->request->request->get('pcEventDateFormat', $defaults['pcEventDateFormat']),
            'pcDateFormats' => $this->request->request->get('pcDateFormats', $defaults['pcDateFormats']),
            'pcAllowUserCalendar' => $this->request->request->get('pcAllowUserCalendar', 0),
            'pcTimeIncrement' => $this->request->request->get('pcTimeIncrement', $defaults['pcTimeIncrement']),
            'pcDefaultView' => $this->request->request->get('pcDefaultView', $defaults['pcDefaultView']),
            'pcNotifyAdmin' => $this->request->request->get('pcNotifyAdmin', 0),
            'pcPendingContent' => $this->request->request->get('pcPendingContent', 0),
            'pcNotifyEmail' => $this->request->request->get('pcNotifyEmail', $defaults['pcNotifyEmail']),
            'pcListMonths' => abs((int)$this->request->request->get('pcListMonths', $defaults['pcListMonths'])),
            'pcNotifyAdmin2Admin' => $this->request->request->get('pcNotifyAdmin2Admin', 0),
            'pcAllowCatFilter' => $this->request->request->get('pcAllowCatFilter', 0),
            'enablenavimages' => $this->request->request->get('enablenavimages', 0),
            'pcFilterYearStart' => abs((int)$this->request->request->get('pcFilterYearStart', $defaults['pcFilterYearStart'])), // ensures positive value
            'pcFilterYearEnd' => abs((int)$this->request->request->get('pcFilterYearEnd', $defaults['pcFilterYearEnd'])), // ensures positive value
            'pcNotifyPending' => $this->request->request->get('pcNotifyPending', 0),
            'pcAllowedViews' => $this->request->request->get('pcAllowedViews', $defaults['pcAllowedViews']),
            'pcNavBarType' => $this->request->request->get('pcNavBarType', $defaults['pcNavBarType']),
        );
        // set pcDateFormats
        if ($settings['pcEventDateFormat'] <> "-1") {
            $settings['pcDateFormats'] = PostCalendarUtil::getDateFormats($settings['pcEventDateFormat']);
        }
        // save out event default settings so they are not cleared
        $settings['pcEventDefaults'] = $this->getVar('pcEventDefaults');

        // delete all the old vars
        $this->delVars();

        // set the new variables
        $this->setVars($settings);

        // clear the cache
        $this->view->clear_cache();

        LogUtil::registerStatus($this->__('Done! Updated the PostCalendar configuration.'));
        return $this->modifyconfig();
    }

    /**
     * update status of events to approve, hide or delete
     * @return string html template
     */
    public function updateevents()
    {
        $this->checkCsrfToken();

        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD), LogUtil::getErrorMsgPermission());

        $pc_eid = $this->request->request->get('pc_eid', $this->request->query->get('pc_eid', null));
        $action = $this->request->request->get('action', $this->request->query->get('action', self::ACTION_APPROVE));
        if (!is_array($pc_eid)) {
            return $this->__("Error! An the eid must be passed as an array.");
        }

        $count = count($pc_eid);

        // update the DB
        switch ($action) {
            case self::ACTION_APPROVE:
                $res = $this->entityManager->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->updateEventStatus(CalendarEvent::APPROVED, $pc_eid);
                $words = array('approve', 'approved');
                break;
            case self::ACTION_HIDE:
                $res = $this->entityManager->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->updateEventStatus(CalendarEvent::HIDDEN, $pc_eid);
                $words = array('hide', 'hidden');
                break;
            case self::ACTION_DELETE:
                $res = $this->entityManager->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->deleteEvents($pc_eid);
                $words = array('delete', 'deleted');
                break;
        }
        if ($res) {
            LogUtil::registerStatus($this->_fn('Done! %1$s event %2$s.', 'Done! %1$s events %2$s.', $count, array($count, $words[1])));
        } else {
            LogUtil::registerError($this->_fn("Error! Could not %s event.", "Error! Could not %s events.", $count, $words[0]));
        }

        $this->view->clear_cache();
        return $this->listevents(array(
                    'listtype' => CalendarEvent::APPROVED));
    }

    /**
     * #Desc: present administrator options to change event default values
     * @return string html template
     */
    public function modifyeventdefaults()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());

        // load the category registry util
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
        $this->view->assign('catregistry', $catregistry);

        $eventDefaults = $this->getVar('pcEventDefaults');
        // convert duration to HH:MM
        $this->view->assign('endTime', ModUtil::apiFunc('PostCalendar', 'event', 'computeendtime', $eventDefaults));

        $this->view->assign('sharingselect', ModUtil::apiFunc('PostCalendar', 'event', 'sharingselect'));
        $this->view->assign('Selected', ModUtil::apiFunc('PostCalendar', 'event', 'alldayselect', $eventDefaults['alldayevent']));

        return $this->view->fetch('admin/eventdefaults.tpl');
    }

    /**
     * #Desc: sets module variables as requested by admin
     * @return      status/error ->back to event defaults config page
     */
    public function seteventdefaults()
    {
        $this->checkCsrfToken();
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());

        $eventDefaults = $this->request->request->get('postcalendar_eventdefaults'); //array
        //convert times to storable values
        $eventDefaults['duration'] = ModUtil::apiFunc('PostCalendar', 'event', 'computeduration', $eventDefaults);
        $eventDefaults['duration'] = ($eventDefaults['duration'] > 0) ? $eventDefaults['duration'] : 3600; //disallow duration < 0
        unset($eventDefaults['endTime']); // do not store selected endTime

        $startTime = $eventDefaults['startTime'];
        unset($eventDefaults['startTime']); // clears the whole array
        $eventDefaults['startTime'] = ModUtil::apiFunc('PostCalendar', 'event', 'convertTimeArray', $startTime);

        // save the new values
        $this->setVar('pcEventDefaults', $eventDefaults);

        LogUtil::registerStatus($this->__('Done! Updated the PostCalendar event default values.'));
        return $this->modifyeventdefaults();
    }

    /**
     * Add object actions to each item
     * e.g. view, hide, approve, edit, delete
     * @param array $events
     * @return array
     */
    private function _appendObjectActions($events, $listtype = CalendarEvent::APPROVED)
    {
        $statusmap = array(
            CalendarEvent::QUEUED => ' (Queued)',
            CalendarEvent::HIDDEN => ' (Hidden)',
            CalendarEvent::APPROVED => ''
        );
        $eventArray = array();
        foreach ($events as $key => $event) {
            $eventArray[$key] = $event->getOldArray();
            // temp workaround for assignedcategorieslist plugin
            $eventArray[$key]['Categories'] = $eventArray[$key]['categories'];
            $options = array();
            $truncated_title = StringUtil::getTruncatedString($event['title'], 25);
            $options[] = array('url' => ModUtil::url('PostCalendar', 'user', 'display', array('viewtype' => 'event', 'eid' => $event['eid'])),
                'image' => '14_layer_visible.png',
                'title' => $this->__f("View '%s'", $truncated_title));

            if (SecurityUtil::checkPermission('PostCalendar::Event', "{$event['title']}::{$event['eid']}", ACCESS_EDIT)) {
                if ($event['eventstatus'] == CalendarEvent::APPROVED) {
                    $options[] = array('url' => ModUtil::url('PostCalendar', 'admin', 'adminevents', array('action' => self::ACTION_HIDE, 'events' => $event['eid'])),
                        'image' => 'db_remove.png',
                        'title' => $this->__f("Hide '%s'", $truncated_title));
                } else {
                    $options[] = array('url' => ModUtil::url('PostCalendar', 'admin', 'adminevents', array('action' => self::ACTION_APPROVE, 'events' => $event['eid'])),
                        'image' => 'button_ok.png',
                        'title' => $this->__f("Approve '%s'", $truncated_title));
                }
                $options[] = array('url' => ModUtil::url('PostCalendar', 'event', 'edit', array('eid' => $event['eid'])),
                    'image' => 'xedit.png',
                    'title' => $this->__f("Edit '%s'", $truncated_title));
            }

            if (SecurityUtil::checkPermission('PostCalendar::Event', "{$event['title']}::{$event['eid']}", ACCESS_DELETE)) {
                $options[] = array('url' => ModUtil::url('PostCalendar', 'event', 'delete', array('eid' => $event['eid'])),
                    'image' => '14_layer_deletelayer.png',
                    'title' => $this->__f("Delete '%s'", $truncated_title));
            }
            $eventArray[$key]['options'] = $options;
            $eventArray[$key]['title'] = ($listtype == CalendarEvent::ALLSTATUS) ? $event['title'] . $statusmap[$event['eventstatus']] : $event['title'];
        }
        return $eventArray;
    }

    /**
     * Migrate existing TimeIt Events into PC
     * Migrates both events and categories
     */
    public function migrateTimeIt()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());
        $pcTimeItMigrateComplete = $this->getVar('pcTimeItMigrateComplete');
        if (isset($pcTimeItMigrateComplete) && $pcTimeItMigrateComplete) {
            LogUtil::registerError($this->__('TimeIt events have already been migrated. You can only run the migration once.'));
            $this->redirect(ModUtil::url('PostCalendar', 'admin', 'main'));
        }

        // get all available TimeIt events with direct SQL
        $prefix = System::getVar('prefix');
        if (!empty($prefix)) {
            $prefix = $prefix . '_';
        }
        $connection = $this->entityManager->getConnection();
        $sql = "SELECT * FROM {$prefix}TimeIt_events";
        $events = $connection->fetchAll($sql);
        if (empty($events)) {
            LogUtil::registerError($this->__f('No TimeIt events found in the database. The TimeIt table should be called %s in the database. Mind the table prefix', $prefix . 'TimeIt_events'));
            $this->redirect(ModUtil::url('PostCalendar', 'admin', 'main'));
        }

        // get the category path to the TimeIt categories (default Global in TimeIt 2.1.1) and register that for PostCalendar
        $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/Global');
        if ($rootcat) {
            // create an entry in the categories registry to the TimeItImport property
            if (!CategoryRegistryUtil::insertEntry('PostCalendar', 'CalendarEvent', 'TimeItImport', $rootcat['id'])) {
                throw new Zikula_Exception("Cannot insert Category Registry entry.");
            }
        } else {
            $this->throwNotFound("Default TimeIt root category /__SYSTEM__/Modules/Global not found. This is needed for the import of the assigned categories into PostCalendar.");
        }

    	// the default PC category for events without category.
        //$defaultCat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar/Events');

        // create category for imported events
        if (!CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar/Imported')) {
            CategoryUtil::createCategory('/__SYSTEM__/Modules/PostCalendar', 'Imported', null, $this->__('Imported'), $this->__('TimeIt imported'));
        }
        $catImport = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar/Imported');

        $eventCount = 0;
        $flushCount = 0; // flush to Doctrine every 25 events
        // $events is an PHP array with the whole database table for TimeIt_events
        // loop through all events and create postcalendar events with doctrine methods
        foreach ($events as $event) {
            // determine recurrence type in TimeIt
            switch ($event['pn_repeatType']) {
                case 1: // repeat
                    $rTypes = array("day" => EventApi::REPEAT_EVERY_DAY,
                        "week" => EventApi::REPEAT_EVERY_WEEK,
                        "month" => EventApi::REPEAT_EVERY_MONTH,
                        "year" => EventApi::REPEAT_EVERY_YEAR,
                    );
                    $repeat_freq_type = $rTypes[$event['pn_repeatSpec']];
                    $endDate = DateTime::createFromFormat('Y-m-d', $event['pn_endDate']);
                    break;
                case 2: // repeat on
                    list($repeat_on_num, $repeat_on_day) = explode(' ', $event['pn_repeatSpec']);
                    $repeat_on_freq = $event['pn_repeatFrec'];
                    $endDate = DateTime::createFromFormat('Y-m-d', $event['pn_endDate']);
                    break;
                case 0: // standard event
                case 3: // repeat random (cannot duplicate in PC) so reset to standard
                default:
                    $repeat_freq_type = '0';
                    $repeat_on_num = '1';
                    $repeat_on_day = '0';
                    $repeat_on_freq = '1';
                    $event['pn_repeatType'] = 0; // resets case 3
                    $endDate = null;
                    break;
            }
            
            // determine duration in TimeIt and construct start and end datetime objects for PC
            $durtmp = explode(',', $event['pn_allDayDur']);
            switch (count($durtmp)) {
                case 1:
                    $duration = $durtmp[0]; // should be 0 for all day event
                    break;
                case 2:
                    $duration = $durtmp[0] * 3600; // time specified with hours
                    break;
                case 3:
                    $duration = $durtmp[0] * 3600 + $durtmp[2] * 60; // time specified with hours and minutes
                    break;
            }
            $start = DateTime::createFromFormat('Y-m-d H:i', $event['pn_startDate'] . " " . $event['pn_allDayStart']);
            if ($event['pn_allDay']) {
                // duration is zero, so use startdate time
                $end = DateTime::createFromFormat('Y-m-d H:i', $event['pn_endDate'] . " " . $event['pn_allDayStart']);
            } else {
                // timed event, in this case enddate = startdate afais + duration
                $end = DateTime::createFromFormat('Y-m-d H:i', $event['pn_startDate'] . " " . $event['pn_allDayStart']);
                $end->add(new DateInterval('PT' . $duration . 'S'));
            }

            // extract location and contact data
            $data = unserialize($event['pn_data']);

            // extract the hometext and distinguish between plain and html
            $hometext = '';
            if (!empty($event['pn_text'])) {
                if (strpos($event['pn_text'], '#plaintext#') !== false) {
                    $hometext = str_replace('#plaintext#', ':text:', $event['pn_text']);
                } else {
                    $hometext = ':html:' . $event['pn_text'];
                }
            }

            // obtain relevant categories from the category registry
            $catRegIds = CategoryRegistryUtil::getRegisteredModuleCategoriesIds('TimeIt', 'TimeIt_events');
            $sql = "SELECT category_id FROM `categories_mapobj` WHERE obj_id=" . $event['pn_id'] . " AND reg_id=" . $catRegIds['Main'];
            $cats = $connection->fetchAll($sql);
			// check if a category was set in TimeIt
			if (isset($cats[0]) && isset($cats[0]['category_id'])) {
				$eventCat = array('Main' => $catImport['id'], 'TimeItImport' => $cats[0]['category_id']);
			} else {
                $eventCat = array('Main' => $catImport['id']);
			}

            // obtain current sharing in TimeIt
            switch ($event['pn_sharing']) {
                case 1:
                    $sharing = CalendarEvent::SHARING_PRIVATE;
                    break;
                case 2:
                    $sharing = CalendarEvent::SHARING_PUBLIC;
                    break;
                case 3:
                default:
                    $sharing = CalendarEvent::SHARING_GLOBAL;
            }

            // construct the postcalendar eventarray
            $eventArray = array(
                'aid' => $event['pn_cr_uid'],
                'title' => $event['pn_title'],
                'time' => $event['pn_cr_date'],
                'hometext' => $hometext,
                'informant' => $event['pn_cr_uid'],
                'eventStart' => $start,
                'eventEnd' => $end,
				'endDate' => $endDate,
                'alldayevent' => $event['pn_allDay'],
                'recurrtype' => $event['pn_repeatType'],
                'recurrspec' => array('event_repeat_freq' => $event['pn_repeatFrec'],
                    'event_repeat_freq_type' => $repeat_freq_type,
                    'event_repeat_on_num' => $repeat_on_num,
                    'event_repeat_on_day' => $repeat_on_day,
                    'event_repeat_on_freq' => $repeat_on_freq),
                'location' => array('event_location' => $data['plugindata']['LocationTimeIt']['name'],
                    'event_street1' => $data['plugindata']['LocationTimeIt']['street'] . ' ' . $data['plugindata']['LocationTimeIt']['houseNumber'],
                    'event_street2' => '',
                    'event_city' => $data['plugindata']['LocationTimeIt']['city'],
                    'event_state' => '',
                    'event_postal' => $data['plugindata']['LocationTimeIt']['zip']),
                'conttel' => $data['plugindata']['ContactTimeIt']['phoneNr'],
                'contname' => $data['plugindata']['ContactTimeIt']['contactPerson'],
                'contemail' => $data['plugindata']['ContactTimeIt']['email'],
                'website' => $data['plugindata']['ContactTimeIt']['website'],
                'fee' => $data['fee'],
                'eventstatus' => ($event['pn_status'] != 1 ? CalendarEvent::QUEUED : CalendarEvent::APPROVED),
                'sharing' => $sharing,
                'categories' => $eventCat
            );
            
            // configure exceptions
            if (isset($event['pn_repeatIrg'])) {
                $eventArray['hasexceptions'] = true;
                $eventArray['recurexceptionstorage'] = explode(',', $event['pn_repeatIrg']);
            } else {
                $eventArray['hasexceptions'] = false;
                $eventArray['recurexceptionstorage'] = null;
            }

            // Now insert the created array into PostCalendar via Doctrine
            try {
                $event = new CalendarEventEntity();
                $event->setFromArray($eventArray);
                $this->entityManager->persist($event);
                $eventCount++;
                if ($flushCount > 25) {
                    $this->entityManager->flush();
                    $flushCount = 0;
                } else {
                    $flushCount++;
                }
                //$newEventId = $event->getEid();
            } catch (Exception $e) {
                return LogUtil::registerError($e->getMessage());
            }
        }
        // always flush after last event import
        if ($eventCount > 0) {
            $this->entityManager->flush();
        }

        // delete old TimeIt entries from categories tables
        $sqls = array();
        $sqls[] = "DELETE FROM categories_mapobj WHERE modname = 'TimeIt' AND tablename = 'TimeIt_events'";
        $sqls[] = "DELETE FROM categories_registry WHERE modname = 'TimeIt' AND tablename = 'TimeIt_events'";
        foreach ($sqls as $sql) {
            $stmt = $connection->prepare($sql);
            try {
                $stmt->execute();
            } catch (Exception $e) {
                LogUtil::registerError($e->getMessage());
            }
        }

        LogUtil::registerStatus($this->__f('TimeIt events have been migrated. In total %s events completed.', $eventCount));
        $this->setVar('pcTimeItMigrateComplete', true);
        $this->redirect(ModUtil::url('PostCalendar', 'admin', 'main'));
    }

    /**
     * Check to see if TimeIt table exists
     * @return boolean 
     */
    public function checkTimeIt()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());

        $timeItExists = PostCalendarUtil::timeItExists();
        $this->setVar('pcTimeItExists', $timeItExists);

        $this->redirect(ModUtil::url('PostCalendar', 'admin', 'modifyconfig#timeit'));
        return true;
    }
}
