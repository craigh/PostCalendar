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

class PostCalendar_Controller_Admin extends Zikula_Controller
{
    /**
     * the main administration function
     * This function is the default function, and is called whenever the
     * module is initiated without defining arguments.
     */
    public function main()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
        return $this->modifyconfig();
    }
    
    /**
     * @function    modifyconfig
     * @description present administrator options to change module configuration
     * @return      config template
     */
    public function modifyconfig()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('PostCalendar'));
        $this->view->assign('postcalendarversion', $modinfo['version']);
    
        $this->view->assign('pcFilterYearStart', ModUtil::getVar('PostCalendar', 'pcFilterYearStart', 1));
        $this->view->assign('pcFilterYearEnd', ModUtil::getVar('PostCalendar', 'pcFilterYearEnd', 2));
    
        return $this->view->fetch('admin/modifyconfig.tpl');
    }
    
    /**
     * @function    listapproved
     * @description list all events that have been previously approved
     * @return      list of events
     */
    public function listapproved()
    {
        $args = array();
        $args['type']     = _EVENT_APPROVED;
        $args['function'] = 'listapproved';
        $args['title']    = $this->__('Approved events administration');
        return $this->showlist($args);
    }
    
    /**
     * @function    listhidden
     * @description list all events that are currently hidden
     * @return      list of events
     */
    public function listhidden()
    {
        $args = array();
        $args['type']     = _EVENT_HIDDEN;
        $args['function'] = 'listhidden';
        $args['title']    = $this->__('Hidden events administration');
        return $this->showlist($args);
    }
    
    /**
     * @function    listqueued
     * @description list all events that are awaiting approval
     * @return      list of events
     */
    public function listqueued()
    {
        $args = array();
        $args['type']     = _EVENT_QUEUED;
        $args['function'] = 'listqueued';
        $args['title']    = $this->__('Queued events administration');
        return $this->showlist($args);
    }
    
    /**
     * @function    showlist
     * @description list events as requested/filtered
     *              send list to template
     * @return      showlist template
     */
    public function showlist($args)
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        // $args should be array with keys 'type', 'function', 'title'
        if (!isset($args['type']) or empty($args['function'])) {
            // $title not required, type can be 1, 0, -1
            return LogUtil::registerArgsError();
        }
    
    
        $offset_increment = _SETTING_HOW_MANY_EVENTS;
        if (empty($offset_increment)) {
            $offset_increment = 15;
        }
    
        $offset = FormUtil::getPassedValue('offset', 0);
        $sort   = FormUtil::getPassedValue('sort', 'time');
        $sdir   = FormUtil::getPassedValue('sdir', 1);
        $original_sdir = $sdir;
        $sdir = $sdir ? 0 : 1; //if true change to false, if false change to true
        if ($sdir == 0) {
            $sort .= ' DESC';
        }
        if ($sdir == 1) {
            $sort .= ' ASC';
        }
    
        $events = DBUtil::selectObjectArray('postcalendar_events', "WHERE pc_eventstatus=" . $args['type'], $sort, $offset, $offset_increment, false);
    
        $this->view->assign('title', $args['title']);
        $this->view->assign('function', $args['function']);
        $this->view->assign('functionname', substr($args['function'], 4));
        $this->view->assign('events', $events);
        $this->view->assign('title_sort_url', ModUtil::url('PostCalendar', 'admin', $args['function'], array(
            'sort' => 'title',
            'sdir' => $sdir)));
        $this->view->assign('time_sort_url', ModUtil::url('PostCalendar', 'admin', $args['function'], array(
            'sort' => 'time',
            'sdir' => $sdir)));
        $this->view->assign('formactions', array(
            _ADMIN_ACTION_VIEW    => $this->__('List'),
            _ADMIN_ACTION_APPROVE => $this->__('Approve'),
            _ADMIN_ACTION_HIDE    => $this->__('Hide'),
            _ADMIN_ACTION_DELETE  => $this->__('Delete')));
        $this->view->assign('actionselected', _ADMIN_ACTION_VIEW);
        if ($offset > 1) {
            $prevlink = ModUtil::url('PostCalendar', 'admin', $args['function'], array(
                'offset' => $offset - $offset_increment,
                'sort' => $sort,
                'sdir' => $original_sdir));
        } else {
            $prevlink = false;
        }
        $this->view->assign('prevlink', $prevlink);
        if (count($events) >= $offset_increment) {
            $nextlink = ModUtil::url('PostCalendar', 'admin', $args['function'], array(
                'offset' => $offset + $offset_increment,
                'sort' => $sort,
                'sdir' => $original_sdir));
        } else {
            $nextlink = false;
        }
        $this->view->assign('nextlink', $nextlink);
        $this->view->assign('offset_increment', $offset_increment);
    
        return $this->view->fetch('admin/showlist.tpl');
    }
    
    /**
     * @function    adminevents
     * @description allows admin to revue selected events then take action
     * @return      adminrevue template
     */
    public function adminevents()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $action  = FormUtil::getPassedValue('action');
        $events  = FormUtil::getPassedValue('events'); // could be an array or single val
        $thelist = FormUtil::getPassedValue('thelist');
    
        if (!isset($events)) {
            LogUtil::registerError($this->__('Please select an event.'));
    
            // return to where we came from
            switch ($thelist) {
                case 'listqueued':
                    return $this->showlist(array(
                        'type' => _EVENT_QUEUED,
                        'function' => 'showlist'));
                case 'listhidden':
                    return $this->showlist(array(
                        'type' => _EVENT_HIDDEN,
                        'function' => 'showlist'));
                case 'listapproved':
                    return $this->showlist(array(
                        'type' => _EVENT_APPROVED,
                        'function' => 'showlist'));
            }
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
        $function = '';
        switch ($action) {
            case _ADMIN_ACTION_APPROVE:
                $function = 'approveevents';
                $are_you_sure_text = $this->_n('Do you really want to approve this event?', 'Do you really want to approve these events?', $count);
                break;
            case _ADMIN_ACTION_HIDE:
                $function = 'hideevents';
                $are_you_sure_text = $this->_n('Do you really want to hide this event?', 'Do you really want to hide these events?', $count);
                break;
            case _ADMIN_ACTION_DELETE:
                $function = 'deleteevents';
                $are_you_sure_text = $this->_n('Do you really want to delete this event?', 'Do you really want to delete these events?', $count);
                break;
        }
    
        $this->view->assign('function', $function);
        $this->view->assign('areyousure', $are_you_sure_text);
        $this->view->assign('alleventinfo', $alleventinfo);
    
        return $this->view->fetch("admin/eventrevue.tpl");
    }
    
    /**
     * @function    resetDefaults
     * @description reset all module variables to default values as defined in pninit.php
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
     * @function    updateconfig
     * @description sets module variables as requested by admin
     * @return      status/error ->back to modify config page
     */
    public function updateconfig()
    {
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
        );
        $settings['pcNavDateOrder'] = ModUtil::apiFunc('PostCalendar', 'admin', 'getdateorder', $settings['pcEventDateFormat']);
        // save out event default settings so they are not cleared
        $settings['pcEventDefaults'] = ModUtil::getVar('PostCalendar', 'pcEventDefaults');
    
        // delete all the old vars
        ModUtil::delVar('PostCalendar');
    
        // set the new variables
        ModUtil::setVars('PostCalendar', $settings);
    
        // Let any other modules know that the modules configuration has been updated
        $this->callHooks('module', 'updateconfig', 'PostCalendar', array(
            'module' => 'PostCalendar'));
    
        // clear the cache
        $this->view->clear_cache();
    
        LogUtil::registerStatus($this->__('Done! Updated the PostCalendar configuration.'));
        return $this->modifyconfig();
    }
    
    /*
     * approveevents
     * update status of events so that they are viewable by users
     *
     */
    public function approveevents()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
    
        $pc_eid = FormUtil::getPassedValue('pc_eid');
        if (!is_array($pc_eid)) {
            return $this->__("Error! An 'unidentified error' occurred.");
        }
    
        // structure array for DB interaction
        $eventarray = array();
        foreach ($pc_eid as $eid) {
            $eventarray[$eid] = array(
                'eid' => $eid,
                'eventstatus' => _EVENT_APPROVED);
        }
        $count = count($pc_eid);
    
        // update the DB
        $res = DBUtil::updateObjectArray($eventarray, 'postcalendar_events', 'eid');
        if ($res) {
            LogUtil::registerStatus($this->_fn('Done! %s event approved.', 'Done! %s events approved.', $count, $count));
        } else {
            LogUtil::registerError($this->__("Error! An 'unidentified error' occurred."));
        }
    
        $this->view->clear_cache();
        return $this->showlist(array(
            'type'     => _EVENT_APPROVED,
            'function' => 'listapproved',
            'title'    => $this->__('Approved events administration')));
    }
    
    /*
     * hideevents
     * update status of events so that they are hidden from view
     *
     */
    public function hideevents()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
    
        $pc_eid = FormUtil::getPassedValue('pc_eid');
        if (!is_array($pc_eid)) {
            return $this->__("Error! An 'unidentified error' occurred.");
        }
    
        // structure array for DB interaction
        $eventarray = array();
        foreach ($pc_eid as $eid) {
            $eventarray[$eid] = array(
                'eid' => $eid,
                'eventstatus' => _EVENT_HIDDEN);
        }
        $count = count($pc_eid);
    
        // update the DB
        $res = DBUtil::updateObjectArray($eventarray, 'postcalendar_events', 'eid');
        if ($res) {
            LogUtil::registerStatus($this->_fn('Done! %s event was hidden.', 'Done! %s events were hidden.', $count, $count));
        } else {
            LogUtil::registerError($this->__("Error! An 'unidentified error' occurred."));
        }
    
        $this->view->clear_cache();
        return $this->showlist(array(
            'type'     => _EVENT_APPROVED,
            'function' => 'listapproved',
            'title'    => $this->__('Approved events administration')));
    }
    
    /*
     * deleteevents
     * delete array of events
     *
     */
    public function deleteevents()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            return LogUtil::registerPermissionError();
        }
    
        $pc_eid = FormUtil::getPassedValue('pc_eid');
        if (!is_array($pc_eid)) {
            return $this->__("Error! An 'unidentified error' occurred.");
        }
    
        // structure array for DB interaction
        $eventarray = array();
        foreach ($pc_eid as $eid) {
            $eventarray[$eid] = $eid;
        }
        $count = count($pc_eid);
    
        // update the DB
        $res = DBUtil::deleteObjectsFromKeyArray($eventarray, 'postcalendar_events', 'eid');
        if ($res) {
            LogUtil::registerStatus($this->_fn('Done! %s event deleted.', 'Done! %s events deleted.', $count, $count));
        } else {
            LogUtil::registerError($this->__("Error! An 'unidentified error' occurred."));
        }
    
        $this->view->clear_cache();
        return $this->showlist(array(
            'type'     => _EVENT_APPROVED,
            'function' => 'listapproved',
            'title'    => $this->__('Approved events administration')));
    }
    
    /**
     * @function    modifyeventdefaults
     * @description present administrator options to change event default values
     * @return      template
     */
    public function modifyeventdefaults()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        $eventDefaults = ModUtil::getVar('PostCalendar', 'pcEventDefaults');
    
        // load the category registry util
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $this->view->assign('catregistry', $catregistry);
    
        $props = array_keys($catregistry);
        $this->view->assign('firstprop', $props[0]);
        $selectedDefaultCategories = $eventDefaults['categories'];
        $this->view->assign('selectedDefaultCategories', $selectedDefaultCategories);
    
        // convert duration to HH:MM
        $eventDefaults['endTime']  = ModUtil::apiFunc('PostCalendar', 'event', 'computeendtime', $eventDefaults);
    
        // sharing selectbox
        $this->view->assign('sharingselect', ModUtil::apiFunc('PostCalendar', 'event', 'sharingselect'));
    
        $this->view->assign('Selected',  ModUtil::apiFunc('PostCalendar', 'event', 'alldayselect', $eventDefaults['alldayevent']));
    
        $this->view->assign('postcalendar_eventdefaults', $eventDefaults);
    
        return $this->view->fetch('admin/eventdefaults.tpl');
    }
    
    /**
     * @function    seteventdefaults
     * @description sets module variables as requested by admin
     * @return      status/error ->back to event defaults config page
     */
    public function seteventdefaults()
    {
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
        ModUtil::setVar('PostCalendar', 'pcEventDefaults', $eventDefaults);
    
        LogUtil::registerStatus($this->__('Done! Updated the PostCalendar event default values.'));
        return $this->modifyeventdefaults();
    }
} // end class def