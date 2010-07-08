<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/lib/PostCalendar/Api/User.php $
 * @version     $Id: User.php 641 2010-07-01 21:22:06Z craigh $
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class PostCalendar_Api_Hooks extends Zikula_Api
{
    /**
     * create action on hook
     *
     * @author  Craig Heydenburg
     * @return  boolean    true/false
     * @access  public
     */
    public function create($args)
    {
        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }
        $module = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module
    
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
    
        $hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST'); // array of data from 'new' hook
        if (DataUtil::is_serialized($hookinfo['cats'])) {
            $hookinfo['cats'] = unserialize($hookinfo['cats']);
        }
    
        if ((!isset($hookinfo['optin'])) || (!$hookinfo['optin'])) {
            LogUtil::registerStatus($this->__("PostCalendar: Event not created (opt out)."));
            return;
        }

        $eventObj   = new PostCalendar_Hookutil;
        $methodName = $module . '_pcevent';
        $args       = array(
            'objectid' => $args['objectid']);
        if (is_callable(array($eventObj, $methodName))) {
            if (!$event = $eventObj->$methodName($args)) {
                LogUtil::registerError($this->__("PostCalendar: Could not create event (method failed)."));
            }
        } else {
            LogUtil::registerError($this->__f("PostCalendar: Method %s not callable.", $eventObj . $methodName));
        }

        if ($event) {
            // add hook specific and non-changing values
            $event['hooked_modulename'] = $module;
            $event['hooked_objectid']   = $args['objectid'];
            $event['__CATEGORIES__']    = $hookinfo['cats'];
            $event['__META__']          = array('module' => 'PostCalendar');
            $event['recurrtype']        = 0; // norepeat
            $event['recurrspec']        = 'a:5:{s:17:"event_repeat_freq";s:0:"";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:0:"";}'; // default recurrance info - serialized (not used)
            $event['location']          = 'a:6:{s:14:"event_location";s:0:"";s:13:"event_street1";s:0:"";s:13:"event_street2";s:0:"";s:10:"event_city";s:0:"";s:11:"event_state";s:0:"";s:12:"event_postal";s:0:"";}'; // default location info - serialized (not used)
    
            // write event to postcal table
            if (DBUtil::insertObject($event, 'postcalendar_events', 'eid')) {
                LogUtil::registerStatus($this->__("PostCalendar: Event created."));
                return true;
            }
        } else {
            // if the _pcevent function returns false, it means that an event is not desired, so quietly exit
            return;
        }
    
        return LogUtil::registerError($this->__('Error! PostCalender: Could not create an event.'));
    }
    /**
     * delete action on hook
     *
     * @author  Craig Heydenburg
     * @return  boolean    true/false
     * @access  public
     */
    public function delete($args)
    {
        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return LogUtil::registerError($this->__f("PostCalendar: %s not provided in delete hook", 'objectid'));
        }
        $module = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module
    
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
    
        // Get table info
        $pntable = DBUtil::getTables();
        $cols = $pntable['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'
                  AND "   . $cols['hooked_objectid']   . " = '" . DataUtil::formatForStore($args['objectid']) . "'";
    
        //return (bool)DBUtil::deleteWhere('postcalendar_events', $where);
        if (!DBUtil::deleteObject(array(), 'postcalendar_events', $where, 'eid')) {
            return LogUtil::registerError($this->__('Error! Could not delete associated PostCalendar event.'));
        }
    
        LogUtil::registerStatus($this->__('Associated PostCalendar event also deleted.'));
        return true;
    }
    /**
     * deletemodule action on hook
     * this function is called when a hooked module is uninstalled
     *
     * @author  Craig Heydenburg
     * @return  boolean    true/false
     * @access  public
     */
    public function deletemodule($args)
    {
        if (isset($args['extrainfo']['module'])) {
            $module = strtolower($args['extrainfo']['module']);
        } else {
            return LogUtil::registerError($this->__f('Error! Module name not present in %s hook.', 'deletemodule'));
        }
    
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        // Get table info
        $pntable = DBUtil::getTables();
        $cols = $pntable['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'";
    
        //return (bool)DBUtil::deleteWhere('postcalendar_events', $where);
        if (!DBUtil::deleteObject(array(), 'postcalendar_events', $where, 'eid')) {
            return LogUtil::registerError($this->__('Error! Could not delete associated PostCalendar events.'));
        }
    
        LogUtil::registerStatus($this->__('ALL associated PostCalendar events also deleted.'));
        return true;
    }
    /**
     * update action on hook
     *
     * @author  Craig Heydenburg
     * @return  boolean    true/false
     * @access  public
     */
    public function update($args)
    {
        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }
        $module = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module
    
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }
    
        $hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST'); // array of data from 'new' hook
        if (DataUtil::is_serialized($hookinfo['cats'])) {
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

        $eventObj   = new PostCalendar_Hookutil;
        $methodName = $module . '_pcevent';
        $args       = array(
            'objectid' => $args['objectid'],
            'hookinfo' => $hookinfo);
        if (is_callable(array($eventObj, $methodName))) {
            if (!$event = $eventObj->$methodName($args)) {
                LogUtil::registerError($this->__("PostCalendar: Could not create event (method failed)."));
            }
        } else {
            LogUtil::registerError($this->__f("PostCalendar: Method %s not callable.", $eventObj . $methodName));
        }

        if ($event) {
            if (!empty($hookinfo['eid'])) {
                // event already exists - just update
                $event['eid'] = $hookinfo['eid'];
                if (DBUtil::updateObject($event, 'postcalendar_events', NULL, 'eid')) {
                    LogUtil::registerStatus($this->__("PostCalendar: Associated Calendar event updated."));
                    return true;
                }
            } else {
                // create a new event
                if (DBUtil::insertObject($event, 'postcalendar_events', 'eid')) {
                    LogUtil::registerStatus($this->__("PostCalendar: Event created."));
                    return true;
                }
            }
        } else {
            // if the _pcevent function returns false, it means that an event is not desired, so quietly exit
            return;
        }
    
        return LogUtil::registerError($this->__('Error! Could not update the associated Calendar event.'));
    }
    /**
     * updateconfig action on hook
     *
     * @author  Craig Heydenburg
     * @return  boolean    true/false
     * @access  public
     */
    public function updateconfig($args)
    {
        $hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST'); // array of data from 'modifyconfig' hook
        if ((!isset($hookinfo['postcalendar_optoverride'])) || (empty($hookinfo['postcalendar_optoverride']))) {
            $hookinfo['postcalendar_optoverride'] = 0;
        }
        $thismodule = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module
        ModUtil::setVars($thismodule, $hookinfo);
        // ModVars: postcalendar_admincatselected, postcalendar_optoverride
    
        LogUtil::registerStatus($this->__("PostCalendar: module config updated."));
    
        return;
    }
} // end class def