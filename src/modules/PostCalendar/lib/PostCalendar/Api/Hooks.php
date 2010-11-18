<?php
/**
 * @package     PostCalendar
 * @author      Craig Heydenburg
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
     * @return  boolean    true/false
     */
    public function create($args)
    {
        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }
        $module = isset($args['extrainfo']['module']) ? $args['extrainfo']['module'] : ModUtil::getName(); // default to active module

        $hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST'); // array of data from 'new' hook
        $hookinfo = DataUtil::cleanVar($hookinfo);
        if (DataUtil::is_serialized($hookinfo['cats'], false)) {
            $hookinfo['cats'] = unserialize($hookinfo['cats']);
        }

        if ((!isset($hookinfo['optin'])) || (!$hookinfo['optin'])) {
            LogUtil::registerStatus($this->__("PostCalendar: Event not created (opt out)."));
            return;
        }

        if (!$eventObj = $this->_getClassObject($module)) {
            LogUtil::registerError($this->__("PostCalendar: Could not create Object."));
        }
        if (is_callable(array($eventObj, 'makeEvent'))) {
            $args = array(
                'objectid' => $args['objectid']);
            if($eventObj->makeEvent($args)) {
                $eventObj->setHooked_objectid($args['objectid']);
                $eventObj->set__CATEGORIES__($hookinfo['cats']);
                $event = $eventObj->toArray();
                // write event to postcal table
                if (DBUtil::insertObject($event, 'postcalendar_events', 'eid')) {
                    LogUtil::registerStatus($this->__("PostCalendar: Event created."));
                    return true;
                }
            } else {
                LogUtil::registerError($this->__("PostCalendar: Could not create event (method failed)."));
            }
        } else {
            LogUtil::registerError($this->__f("PostCalendar: Extended class for %s not found.", $module));
        }

        return LogUtil::registerError($this->__('Error! PostCalender: Could not create an event.'));
    }
    /**
     * delete action on hook
     *
     * @return  boolean    true/false
     */
    public function delete($args)
    {
        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return LogUtil::registerArgsError();
        }
        $module = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module
    
        // Get table info
        $table = DBUtil::getTables();
        $cols = $table['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'
                  AND "   . $cols['hooked_objectid']   . " = '" . DataUtil::formatForStore($args['objectid']) . "'";
    
        //return (bool)DBUtil::deleteWhere('postcalendar_events', $where);
        // TODO THIS IS NOT DELETING THE ROW IN categories_mapobj table!!!! (it should!)
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
     * @return  boolean    true/false
     */
    public function deletemodule($args)
    {
        if (isset($args['extrainfo']['module'])) {
            $module = strtolower($args['extrainfo']['module']);
        } else {
            return LogUtil::registerArgsError();
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
     * @return  boolean    true/false
     */
    public function update($args)
    {
        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }
        $module = isset($args['extrainfo']['module']) ? $args['extrainfo']['module'] : ModUtil::getName(); // default to active module
    
        $hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST'); // array of data from 'new' hook
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

        if (!$eventObj = $this->_getClassObject($module)) {
            LogUtil::registerError($this->__("PostCalendar: Could not create Object."));
        }
        if (is_callable(array($eventObj, 'makeEvent'))) {
            $args = array(
                'objectid' => $args['objectid']);
            if ($eventObj->makeEvent($args)) {
                $eventObj->setHooked_objectid($args['objectid']);
                $eventObj->set__CATEGORIES__($hookinfo['cats']);
                if (!empty($hookinfo['eid'])) {
                    // event already exists - just update
                    $eventObj->setEid($hookinfo['eid']);
                    $event = $eventObj->toArray();
                    if (DBUtil::updateObject($event, 'postcalendar_events', NULL, 'eid')) {
                        LogUtil::registerStatus($this->__("PostCalendar: Associated Calendar event updated."));
                        return true;
                    }
                } else {
                    // create a new event
                    $event = $eventObj->toArray();
                    if (DBUtil::insertObject($event, 'postcalendar_events', 'eid')) {
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

        return LogUtil::registerError($this->__('Error! Could not update the associated Calendar event.'));
    }
    /**
     * updateconfig action on hook
     *
     * @return  boolean    true/false
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

    /**
     * Find Class and instantiate
     *
     * @param string $module Module name
     * @return instantiated object of found class
     */
    private function _getClassObject($module) {
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
        return false;
    }
} // end class def