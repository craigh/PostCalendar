<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class PostCalendar_Controller_Admin extends Zikula_AbstractController
{
    /**
     * This function is the default function, and is called whenever the
     * module is initiated without defining arguments.
     */
    public function main()
    {
        return $this->listevents(array());
    }
    
    /**
     * @desc present administrator options to change module configuration
     * @return string config template
     */
    public function modifyconfig()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        return $this->view->fetch('admin/modifyconfig.tpl');
    }
    
    /**
     * @desc list events as requested/filtered
     *              send list to template
     * @return string showlist template
     */
    public function listevents(array $args)
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            return LogUtil::registerPermissionError();
        }
    
        $listtype = isset($args['listtype']) ? $args['listtype'] : FormUtil::getPassedValue('listtype', _EVENT_APPROVED);
        $where = "WHERE pc_eventstatus=" . $listtype;
        switch ($listtype) {
            case _EVENT_ALL:
                $functionname = "all";
                $where = '';
                break;
            case _EVENT_HIDDEN:
                $functionname = "hidden";
                break;
            case _EVENT_QUEUED:
                $functionname = "queued";
                break;
            case _EVENT_APPROVED:
            default:
                $functionname = "approved";
            }
    
        $sortcolclasses = array(
            'title' => 'z-order-unsorted',
            'time'  => 'z-order-unsorted',
            'eventDate' => 'z-order-unsorted');
    
        $offset = FormUtil::getPassedValue('offset', 0);
        $sort = FormUtil::getPassedValue('sort', 'time');
        $original_sdir = FormUtil::getPassedValue('sdir', 1);
        $this->view->assign('offset', $offset);
        $this->view->assign('sort', $sort);
        $this->view->assign('sdir', $original_sdir);
        $original_sort = $sort;
        $sdir = $original_sdir ? 0 : 1; //if true change to false, if false change to true
        // setup sort col name
        ModUtil::dbInfoLoad('PostCalendar');
        $dbtable = DBUtil::getTables();
        $cols = $dbtable['postcalendar_events_column'];
        $sort = $cols[$sort];
        if ($sdir == 0) {
            $sortcolclasses[$original_sort] = 'z-order-desc';
            $sort .= ' DESC';
        }
        if ($sdir == 1) {
            $sortcolclasses[$original_sort] = 'z-order-asc';
            $sort .= ' ASC';
        }
        $this->view->assign('sortcolclasses', $sortcolclasses);

        $filtercats = FormUtil::getPassedValue('postcalendar_events', null, 'GETPOST');
        $filtercats_serialized = FormUtil::getPassedValue('filtercats_serialized', false, 'GET');
        $filtercats = $filtercats_serialized ? unserialize($filtercats_serialized) : $filtercats;
        $catsarray = PostCalendar_Api_Event::formatCategoryFilter($filtercats);

        $events = DBUtil::selectObjectArray('postcalendar_events', $where, $sort, $offset-1, _SETTING_HOW_MANY_EVENTS, false, null, $catsarray);
        $events = $this->_appendObjectActions($events, $listtype);

        $total_events = DBUtil::selectObjectCount('postcalendar_events', $where, '1', false, $catsarray);
        $this->view->assign('total_events', $total_events);

        $this->view->assign('filter_active', (empty($where) && empty($catsarray)) ? false : true);

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
            _ADMIN_ACTION_VIEW    => $this->__('View'),
            _ADMIN_ACTION_APPROVE => $this->__('Approve'),
            _ADMIN_ACTION_HIDE    => $this->__('Hide'),
            _ADMIN_ACTION_DELETE  => $this->__('Delete')));
        $this->view->assign('actionselected', '-1');
        $this->view->assign('listtypes', array(
            _EVENT_ALL      => $this->__('All Events'),
            _EVENT_APPROVED => $this->__('Approved Events'),
            _EVENT_HIDDEN   => $this->__('Hidden Events'),
            _EVENT_QUEUED   => $this->__('Queued Events')));
        $this->view->assign('listtypeselected', $listtype);

        $this->view->assign('catregistry', CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events'));
        // convert categories array to proper filter info
        $selectedcategories = array();
        if (is_array($filtercats)) {
            $catsarray = $filtercats['__CATEGORIES__'];
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
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            return LogUtil::registerPermissionError();
        }
    
        $action = FormUtil::getPassedValue('action');
        $events = FormUtil::getPassedValue('events'); // could be an array or single val
    
        if (!isset($events)) {
            LogUtil::registerError($this->__('Please select an event.'));
            // return to where we came from
            $listtype = FormUtil::getPassedValue('listtype', _EVENT_APPROVED);
            return $this->listevents(array('listtype' => $listtype));
        }
    
        if (!is_array($events)) {
            $events = array(
                $events);
        } //create array if not already
    
        foreach ($events as $eid) {
            // get event info
            $eventitems = DBUtil::selectObjectByID('postcalendar_events', $eid, 'eid');
            $eventitems = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $eventitems);
            $alleventinfo[$eid] = $eventitems;
        }
    
        $count = count($events);
        $texts = array(
            _ADMIN_ACTION_VIEW => "view",
            _ADMIN_ACTION_APPROVE => "approve",
            _ADMIN_ACTION_HIDE => "hide",
            _ADMIN_ACTION_DELETE => "delete");

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
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
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

        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $defaults = PostCalendar_Util::getdefaults();
        if (!count($defaults)) {
            return LogUtil::registerError($this->__('Error! Could not load default values.'));
        }
    
        $settings = array(
            'pcTime24Hours'           => FormUtil::getPassedValue('pcTime24Hours',               0),
            'pcEventsOpenInNewWindow' => FormUtil::getPassedValue('pcEventsOpenInNewWindow',     0),
            'pcFirstDayOfWeek'        => FormUtil::getPassedValue('pcFirstDayOfWeek',            $defaults['pcFirstDayOfWeek']),
            'pcUsePopups'             => FormUtil::getPassedValue('pcUsePopups',                 0),
            'pcAllowDirectSubmit'     => FormUtil::getPassedValue('pcAllowDirectSubmit',         0),
            'pcListHowManyEvents'     => FormUtil::getPassedValue('pcListHowManyEvents',         $defaults['pcListHowManyEvents']),
            'pcEventDateFormat'       => FormUtil::getPassedValue('pcEventDateFormat',           $defaults['pcEventDateFormat']),
            'pcAllowUserCalendar'     => FormUtil::getPassedValue('pcAllowUserCalendar',         0),
            'pcTimeIncrement'         => FormUtil::getPassedValue('pcTimeIncrement',             $defaults['pcTimeIncrement']),
            'pcDefaultView'           => FormUtil::getPassedValue('pcDefaultView',               $defaults['pcDefaultView']),
            'pcNotifyAdmin'           => FormUtil::getPassedValue('pcNotifyAdmin',               0),
            'pcNotifyEmail'           => FormUtil::getPassedValue('pcNotifyEmail',               $defaults['pcNotifyEmail']),
            'pcListMonths'            => abs((int) FormUtil::getPassedValue('pcListMonths',      $defaults['pcListMonths'])),
            'pcNotifyAdmin2Admin'     => FormUtil::getPassedValue('pcNotifyAdmin2Admin',         0),
            'pcAllowCatFilter'        => FormUtil::getPassedValue('pcAllowCatFilter',            0),
            'enablecategorization'    => FormUtil::getPassedValue('enablecategorization',        0),
            'enablenavimages'         => FormUtil::getPassedValue('enablenavimages',             0),
            'enablelocations'         => FormUtil::getPassedValue('enablelocations',             0),
            'pcFilterYearStart'       => abs((int) FormUtil::getPassedValue('pcFilterYearStart', $defaults['pcFilterYearStart'])), // ensures positive value
            'pcFilterYearEnd'         => abs((int) FormUtil::getPassedValue('pcFilterYearEnd',   $defaults['pcFilterYearEnd'])), // ensures positive value
            'pcNotifyPending'         => FormUtil::getPassedValue('pcNotifyPending',             0),
        );
        $settings['pcNavDateOrder'] = ModUtil::apiFunc('PostCalendar', 'admin', 'getdateorder', $settings['pcEventDateFormat']);
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

        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }

        $pc_eid = FormUtil::getPassedValue('pc_eid');
        $action = FormUtil::getPassedValue('action');
        if (!is_array($pc_eid)) {
            return $this->__("Error! An the eid must be passed as an array.");
        }
        $state = array (
            _ADMIN_ACTION_APPROVE => _EVENT_APPROVED,
            _ADMIN_ACTION_HIDE => _EVENT_HIDDEN,
            _ADMIN_ACTION_DELETE => 5); // just a random value for deleted

        // structure array for DB interaction
        $eventarray = array();
        foreach ($pc_eid as $eid) {
            $eventarray[$eid] = array(
                'eid' => $eid,
                'eventstatus' => $state[$action]); // field not used in delete action
        }
        $count = count($pc_eid);

        // update the DB
        switch ($action) {
            case _ADMIN_ACTION_APPROVE:
                $res = DBUtil::updateObjectArray($eventarray, 'postcalendar_events', 'eid');
                $words = array('approve', 'approved');
                break;
            case _ADMIN_ACTION_HIDE:
                $res = DBUtil::updateObjectArray($eventarray, 'postcalendar_events', 'eid');
                $words = array('hide', 'hidden');
                break;
            case _ADMIN_ACTION_DELETE:
                $res = DBUtil::deleteObjectsFromKeyArray($eventarray, 'postcalendar_events', 'eid');
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
            'listtype' => _EVENT_APPROVED));
    }

    /**
     * @desc present administrator options to change event default values
     * @return string html template
     */
    public function modifyeventdefaults()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        // load the category registry util
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
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
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $eventDefaults = FormUtil::getPassedValue('postcalendar_eventdefaults'); //array

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
    private function _appendObjectActions($events, $listtype=_EVENT_APPROVED)
    {
        $statusmap = array(
            _EVENT_QUEUED => ' (Queued)',
            _EVENT_HIDDEN => ' (Hidden)',
            _EVENT_APPROVED => ''
        );
        foreach($events as $key => $event) {
            $options = array();
            $truncated_title = StringUtil::getTruncatedString($event['title'], 25);
            $options[] = array('url' => ModUtil::url('PostCalendar', 'user', 'display', array('viewtype' => 'details', 'eid' => $event['eid'])),
                    'image' => '14_layer_visible.png',
                    'title' => $this->__f("View '%s'", $truncated_title));

            if (SecurityUtil::checkPermission('PostCalendar::Event', "{$event['title']}::{$event['eid']}", ACCESS_EDIT)) {
                if ($event['eventstatus'] == _EVENT_APPROVED) {
                    $options[] = array('url' => ModUtil::url('PostCalendar', 'admin', 'adminevents', array('action' => _ADMIN_ACTION_HIDE, 'events' => $event['eid'])),
                            'image' => 'db_remove.png',
                            'title' => $this->__f("Hide '%s'", $truncated_title));
                } else {
                    $options[] = array('url' => ModUtil::url('PostCalendar', 'admin', 'adminevents', array('action' => _ADMIN_ACTION_APPROVE, 'events' => $event['eid'])),
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
            $events[$key]['options'] = $options;
            $events[$key]['title'] = ($listtype == _EVENT_ALL) ? $event['title'] . $statusmap[$event['eventstatus']] : $event['title'];
        }
        return $events;
    }

} // end class def