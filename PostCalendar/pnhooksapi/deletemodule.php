<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * deletemodule action on hook
 * this function is called when a hooked module is uninstalled
 *
 * @author  Craig Heydenburg
 * @return  boolean    true/false
 * @access  public
 */
function postcalendar_hooksapi_deletemodule($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if (isset($args['extrainfo']['module'])) {
        $module = strtolower($args['extrainfo']['module']);
    } else {
        return LogUtil::registerError(__f('Error! Module name not present in %s hook.', 'deletemodule', $dom));
    }

    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Get table info
    $pntable = System::dbGetTables();
    $cols = $pntable['postcalendar_events_column'];
    // build where statement
    $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'";

    //return (bool)DBUtil::deleteWhere('postcalendar_events', $where);
    if (!DBUtil::deleteObject(array(), 'postcalendar_events', $where, 'eid')) {
        return LogUtil::registerError(__('Error! Could not delete associated PostCalendar events.', $dom));
    }

    LogUtil::registerStatus(__('ALL associated PostCalendar events also deleted.', $dom));
    return true;
}