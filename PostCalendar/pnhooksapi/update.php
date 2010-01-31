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
 * update action on hook
 *
 * @author  Craig Heydenburg
 * @return  boolean    true/false
 * @access  public
 */
function postcalendar_hooksapi_update($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
        return false;
    }
    $module = isset($args['module']) ? strtolower($args['module']) : strtolower(pnModGetName()); // default to active module

    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    if (!$home = pnModAPIFunc('PostCalendar', 'hooks', 'funcisavail', array(
        'module' => $module))) {
        return LogUtil::registerError(__('Hook function not available', $dom));;
    }
    $event = pnModAPIFunc($home, 'hooks', 'create_' . $module, array(
        'objectid' => $args['objectid']));

    // add correct category information to new event

    // Get table info
    $pntable = pnDBGetTables();
    $cols = $pntable['postcalendar_events_column'];
    // build where statement
    $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'
              AND "   . $cols['hooked_objectid']   . " = '" . DataUtil::formatForStore($args['objectid']) . "'";

    // write event to postcal table
    if (DBUtil::updateObject($event, 'postcalendar_events', $where, 'eid')) {
        LogUtil::registerStatus(__("PostCalendar: Associated Calendar event updated.", $dom));
        return true;
    }

    return LogUtil::registerError(__('Error! Could not update the associated Calendar event.', $dom));
}