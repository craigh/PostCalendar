<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

use PostCalendar_Entity_CalendarEvent as CalendarEvent;

class PostCalendar_Controller_Admin extends Zikula_AbstractController
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
     */
    public function main()
    {
		$this->redirect(ModUtil::url('PostCalendar', 'admin', 'listevents'));
    }
    
    /**
     * @desc present administrator options to change module configuration
     * @return string config template
     */
    public function modifyconfig()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());
    
        return $this->view->fetch('admin/modifyconfig.tpl');
    }
    
    /**
     * @desc list events as requested/filtered
     *              send list to template
     * @return string showlist template
     */
    public function listevents(array $args)
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
            'time'  => 'z-order-unsorted',
            'eventDate' => 'z-order-unsorted');
    
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

        $filtercats = $this->request->query->get('postcalendar_events', $this->request->request->get('postcalendar_events', null));
        $filtercats_serialized = $this->request->query->get('filtercats_serialized', false);
        $filtercats = $filtercats_serialized ? unserialize($filtercats_serialized) : $filtercats;
        $catsarray = PostCalendar_Api_Event::formatCategoryFilter($filtercats);

        $events = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')
                       ->getEventlist($listtype, $sort, $offset-1, _SETTING_HOW_MANY_EVENTS, $catsarray);
        $events = $this->_appendObjectActions($events, $listtype);

        $total_events = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')
                       ->getEventCount($listtype, $catsarray);
        $this->view->assign('total_events', $total_events);

        $this->view->assign('filter_active', (($listtype == CalendarEvent::ALLSTATUS) && empty($catsarray)) ? false : true);

        $this->view->assign('functionname', $functionname);
        $this->view->assign('events', $events);
        $sorturls = array('title', 'time', 'eventDate');
        foreach ($sorturls as $sorturl) {
            $this->view->assign($sorturl . '_sort_url', ModUtil::url('PostCalendar', 'admin', 'listevents', array(
                'listtype' => $listtype,
                'filtercats_serialized' => serialize($filtercats),
                'sort' => $sorturl,
                'sdir' => $sdir)));
        }
        $this->view->assign('formactions', array(
            '-1'                  => $this->__('With selected:'),
            self::ACTION_VIEW    => $this->__('View'),
            self::ACTION_APPROVE => $this->__('Approve'),
            self::ACTION_HIDE    => $this->__('Hide'),
            self::ACTION_DELETE  => $this->__('Delete')));
        $this->view->assign('actionselected', '-1');
        $this->view->assign('listtypes', array(
            CalendarEvent::ALLSTATUS => $this->__('All Events'),
            CalendarEvent::APPROVED  => $this->__('Approved Events'),
            CalendarEvent::HIDDEN    => $this->__('Hidden Events'),
            CalendarEvent::QUEUED    => $this->__('Queued Events')));
        $this->view->assign('listtypeselected', $listtype);

        $this->view->assign('catregistry', CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent'));
        // convert categories array to proper filter info
        $selectedcategories = array();
        if (is_array($filtercats)) {
            $catsarray = $filtercats['categories'];
            foreach ($catsarray as $propname => $propid) {
                if ($propid > 0) {
                    $selectedcategories[$propname] = $propid; // removes categories set to 'all'
                }
            }
        }
        $this->view->assign('selectedcategories', $selectedcategories);

        return $this->view->fetch('admin/showlist.tpl');
    }
    
    /**
     * @desc allows admin to revue selected events then take action
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

        $events = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->findBy(array('eid' => $events));
        foreach ($events as $event) {
            // get event info
            $eventitems = $event->getOldArray();
            $eventitems = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $eventitems);
            $alleventinfo[$event->getEid()] = $eventitems;
        }
    
        $count = count($events);
        $texts = array(
            self::ACTION_VIEW => "view",
            self::ACTION_APPROVE => "approve",
            self::ACTION_HIDE => "hide",
            self::ACTION_DELETE => "delete");

        $this->view->assign('actiontext', $texts[$action]);
        $this->view->assign('action', $action);
        $are_you_sure_text = $this->_fn('Do you really want to %s this event?', 'Do you really want to %s these events?', $count, $texts[$action]);
        $this->view->assign('areyousure', $are_you_sure_text);
        $this->view->assign('alleventinfo', $alleventinfo);
    
        return $this->view->fetch("admin/eventrevue.tpl");
    }
    
    /**
     * @desc reset all module variables to default values as defined in pninit.php
     * @return      status/error ->back to modify config page
     */
    public function resetDefaults()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());
    
        $defaults = PostCalendar_Util::getdefaults();
        if (!count($defaults)) {
            return LogUtil::registerError($this->__('Error! Could not load default values.'));
        }
    
        // delete all the old vars
        ModUtil::delVar('PostCalendar');
    
        // set the new variables
        ModUtil::setVars('PostCalendar', $defaults);
    
        // clear the cache
        $this->view->clear_cache();
    
        LogUtil::registerStatus($this->__('Done! PostCalendar configuration reset to use default values.'));
        return $this->modifyconfig();
    }
    
    /**
     * @desc sets module variables as requested by admin
     * @return      status/error ->back to modify config page
     */
    public function updateconfig()
    {
        $this->checkCsrfToken();

        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());
    
        $defaults = PostCalendar_Util::getdefaults();
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
            'pcAllowUserCalendar' => $this->request->request->get('pcAllowUserCalendar', 0),
            'pcTimeIncrement' => $this->request->request->get('pcTimeIncrement', $defaults['pcTimeIncrement']),
            'pcDefaultView' => $this->request->request->get('pcDefaultView', $defaults['pcDefaultView']),
            'pcNotifyAdmin' => $this->request->request->get('pcNotifyAdmin', 0),
            'pcNotifyEmail' => $this->request->request->get('pcNotifyEmail', $defaults['pcNotifyEmail']),
            'pcListMonths' => abs((int)$this->request->request->get('pcListMonths', $defaults['pcListMonths'])),
            'pcNotifyAdmin2Admin' => $this->request->request->get('pcNotifyAdmin2Admin', 0),
            'pcAllowCatFilter' => $this->request->request->get('pcAllowCatFilter', 0),
            'enablecategorization' => $this->request->request->get('enablecategorization', 0),
            'enablenavimages' => $this->request->request->get('enablenavimages', 0),
            'enablelocations' => $this->request->request->get('enablelocations', 0),
            'pcFilterYearStart' => abs((int)$this->request->request->get('pcFilterYearStart', $defaults['pcFilterYearStart'])), // ensures positive value
            'pcFilterYearEnd' => abs((int)$this->request->request->get('pcFilterYearEnd', $defaults['pcFilterYearEnd'])), // ensures positive value
            'pcNotifyPending' => $this->request->request->get('pcNotifyPending', 0),
            'pcAllowedViews' => $this->request->request->get('pcAllowedViews', $defaults['pcAllowedViews']),
            'pcNavDateOrder' => $this->request->request->get('pcNavDateOrder', $defaults['pcNavDateOrder']),
            'pcEventStrftimeFormat' => $this->request->request->get('pcEventStrftimeFormat', $defaults['pcEventStrftimeFormat']),
        );
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
                $res = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->updateEventStatus(CalendarEvent::APPROVED, $pc_eid);
                $words = array('approve', 'approved');
                break;
            case self::ACTION_HIDE:
                $res = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->updateEventStatus(CalendarEvent::HIDDEN, $pc_eid);
                $words = array('hide', 'hidden');
                break;
            case self::ACTION_DELETE:
                $res = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->deleteEvents($pc_eid);
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
     * @desc present administrator options to change event default values
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
        $this->view->assign('Selected',  ModUtil::apiFunc('PostCalendar', 'event', 'alldayselect', $eventDefaults['alldayevent']));
    
        return $this->view->fetch('admin/eventdefaults.tpl');
    }
    
    /**
     * @desc sets module variables as requested by admin
     * @return      status/error ->back to event defaults config page
     */
    public function seteventdefaults()
    {
        $this->checkCsrfToken();
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());
    
        $eventDefaults = $this->request->request->get('postcalendar_eventdefaults'); //array

        // filter through locations translator
        $eventDefaults = ModUtil::apiFunc('PostCalendar', 'event', 'correctlocationdata', $eventDefaults);
    
        //convert times to storable values
        $eventDefaults['duration'] = ModUtil::apiFunc('PostCalendar', 'event', 'computeduration', $eventDefaults);
        $eventDefaults['duration'] = ($eventDefaults['duration'] > 0) ? $eventDefaults['duration'] : 3600; //disallow duration < 0
    
        $startTime = $eventDefaults['startTime'];
        unset($eventDefaults['startTime']); // clears the whole array
        $eventDefaults['startTime'] = ModUtil::apiFunc('PostCalendar', 'event', 'convertstarttime', $startTime);

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
    private function _appendObjectActions($events, $listtype=CalendarEvent::APPROVED)
    {
        $statusmap = array(
            CalendarEvent::QUEUED => ' (Queued)',
            CalendarEvent::HIDDEN => ' (Hidden)',
            CalendarEvent::APPROVED => ''
        );
        $eventArray = array();
        foreach($events as $key => $event) {
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
     * Migrate existing tags in crpTag to Tag
     * Migrates both Tags and Objects with relation
     * Does not confirm existence of tagged object
     */
/*    public function migrateTimeIt()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());
        if ($this->getVar('pcTimeItMigrateComplete')) {
            LogUtil::registerError($this->__('TimeIt events have already been migrated. You can only run the migration once.'));
            $this->redirect(ModUtil::url('PostCalendar', 'admin', 'main'));
        }

        // get all available TimeIt events with direct SQL
        $prefix = System::getVar('prefix');
        if (!empty($prefix)) {
            $prefix = $prefix . '_';
        }
        $sql = "SELECT * FROM {$prefix}TimeIt_events";
        $res = DBUtil::executeSql($sql);
        $events = DBUtil::marshallObjects($res);

        // $timeit is an PHP array with the whole database table for TimeIt_events
        // $pc will hold the converted PostCalendar postcalendar_events table
        $pc = array();
        $pck = 0;
        foreach ($events as $k => $event) {
            $pc[$pck]['aid'] = $event['pn_cr_uid'];
            $pc[$pck]['title'] = $event['pn_title'];
            $pc[$pck]['ttime'] = $event['pn_cr_date'];
            if (!empty($event['pn_text'])) {
                $pc[$pck]['hometext'] = strpos('#plaintext#', $event['pn_text']) != false ? str_replace('#plaintext#', ':text:', $event['pn_text']) : ':html:'.$event['pn_text'];
            } else {
                $pc[$pck]['hometext'] = '';
            }
            $pc[$pck]['informant'] = $event['pn_cr_uid'];
            $pc[$pck]['eventDate'] = $event['pn_startDate'];
            $durtmp = explode(',', $event['pn_allDayDur']);
            switch (count($durtmp)) {
                case 1:
                    $pc[$pck]['duration'] = $durtmp[0]; // normally 0
                    break;
                case 2:
                    $pc[$pck]['duration'] = $durtmp[0] * 3600; // only hours
                    break;
                case 3:
                    $pc[$pck]['duration'] = $durtmp[0] * 3600 + $durtmp[2] * 60; // hours + minutes
                    break;
            }
            $pc[$pck]['endDate'] = $event['pn_endDate'];
            $pc[$pck]['recurrtype'] = $event['pn_repeatType'];
            $reptype = '';
            switch ($event['pn_repeatSpec']) {
                case 'day':
                    $reptype = '0';
                    break;
                case 'week':
                    $reptype = '1';
                    break;
                case 'month':
                    $reptype = '2';
                    break;
                case 'year':
                    $reptype = '3';
                    break;
            }
            $pc[$pck]['recurrspec'] = serialize(
                        array('event_repeat_freq' => $event['pn_repeatFrec'],
                            'event_repeat_freq_type' => $reptype,
                            'event_repeat_on_num' => '1',
                            'event_repeat_on_day' => '0',
                            'event_repeat_on_freq' => ''));
            $pc[$pck]['startTime'] = $event['pn_allDayStart'];
            $pc[$pck]['alldayevent'] = $event['pn_allDay'];
            $data = unserialize($timeit[$k]['pn_data']);
            $pc[$pck]['location'] = serialize(
                        array('locations_id' => -1,
                            'event_location' => $data['plugindata']['LocationTimeIt']['name'],
                            'event_street1' => $data['plugindata']['LocationTimeIt']['street'] . ' ' . $data['plugindata']['LocationTimeIt']['houseNumber'],
                            'event_street2' => '',
                            'event_city' => $data['plugindata']['LocationTimeIt']['city'],
                            'event_state' => '',
                            'event_postal' => $data['plugindata']['LocationTimeIt']['zip']));
            $pc[$pck]['conttel'] = $data['plugindata']['ContactTimeIt']['phoneNr'];
            $pc[$pck]['contname'] = $data['plugindata']['ContactTimeIt']['contactPerson'];
            $pc[$pck]['contemail'] = $data['plugindata']['ContactTimeIt']['email'];
            $pc[$pck]['website'] = $data['plugindata']['ContactTimeIt']['website'];
            $pc[$pck]['fee'] = '';
            $pc[$pck]['eventstatus'] = '1'; // Active ?
            $pc[$pck]['sharing'] = '2'; // Global
            $pc[$pck]['cr_date'] = $event['pn_cr_date'];
            $pc[$pck]['cr_uid'] = $event['pn_cr_uid'];
            $pc[$pck]['lu_date'] = $event['pn_lu_date'];
            $pc[$pck]['lu_uid'] = $event['pn_lu_uid'];
            
            // TODO CATEGORIES transfer
            // TimeIt uses the Core categories. So the category stuff should be inserted as well.
            // See News Installer
            // See TimeIt importapi
            
            $pck =+ 1;
        }
        
//        print_r($pc[0]);
        
        LogUtil::registerStatus($this->__f('TimeIt events have been migrated. In total %1$s events and %2$s categories completed.', array($eventCount, $catCount)));
        $this->setVar('pcTimeItMigrateComplete', true);
        $this->redirect(ModUtil::url('PostCalendar', 'admin', 'main'));

/*


        $objCount = 0;
        $tagCount = 0;

        // use 'brute force' sql to obtain all tags
        $conn = $this->entityManager->getConnection();
        $prefix = $this->serviceManager['prefix'];
        // get all available tags
        $sql = "SELECT DISTINCT name from {$prefix}_crptag";
        $tags = $conn->fetchAll($sql);
        foreach ($tags as $tag) {
            $word = $tag['name'];
            $tagObject = $this->entityManager->getRepository('Tag_Entity_Tag')->findOneBy(array('tag' => $word));
            if (!isset($tagObject)) {
                $tagObject = new Tag_Entity_Tag();
                $tagObject->setTag($word);
                $this->entityManager->persist($tagObject);
                $tagCount++;
            }
        }
        $this->entityManager->flush();

        // more 'brute force' sql to obtain object values
        $sql = "SELECT DISTINCT id_module, module from {$prefix}_crptag_archive";
        $objects = $conn->fetchAll($sql);
        foreach ($objects as $object) {
            // search for existing object - it SHOULDN'T exist!
            $hookObject = $this->entityManager
                    ->getRepository('Tag_Entity_Object')
                    ->findOneBy(array(
                        'module' => $object['module'],
                        'objectId' => $object['id_module']));
            if (isset($hookObject)) {
                $this->entityManager->remove($hookObject);
            }
            // get the most likely areaID
            // Doctrine 1.2 method because Hook Tables support only this
            $area = Doctrine_Core::getTable('Zikula_Doctrine_Model_HookArea')->createQuery()
                    ->where("owner = ?", $object['module'])
                    ->andWhere("areatype = ?", 's')
                    ->andWhere("category = ?", 'ui_hooks')
                    ->execute()
                    ->toArray();
            $areaId = $area[0]['id'];
            // no way to adequately determine URL, so insert generic module link
            $objUrl = ModUtil::url($object['module'], 'user', 'main');
            $hookObject = new Tag_Entity_Object($object['module'], $object['id_module'], $areaId, $objUrl);

            // even more 'brute force' sql to obtain related tag values
            $sql = "SELECT t.name FROM {$prefix}_crptag_archive a LEFT JOIN {$prefix}_crptag t" .
                    " ON a.id_tag = t.id WHERE a.id_module=$object[id_module]";
            $tags = $conn->fetchAll($sql);

            foreach ($tags as $tag) {
                $word = $tag['name'];
                $tagObject = $this->entityManager->getRepository('Tag_Entity_Tag')->findOneBy(array('tag' => $word));
                // all tags should exist - but just in case
                if (!isset($tagObject)) {
                    $tagObject = new Tag_Entity_Tag();
                    $tagObject->setTag($word);
                    $this->entityManager->persist($tagObject);
                }
                $hookObject->assignToTags($tagObject);
            }
            $this->entityManager->persist($hookObject);
            $objCount++;
        }
        $this->entityManager->flush();
        LogUtil::registerStatus($this->__f('CrpTag has been migrated. %1$s objects and %2$s tags completed.', array($objCount, $tagCount)));
        $this->setVar('crpTagMigrateComplete', true);
        $this->redirect(ModUtil::url('Tag', 'admin', 'view'));
        
    }
        */
   
} // end class def