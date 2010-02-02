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
 * create action on hook
 *
 * @author  Craig Heydenburg
 * @return  boolean    true/false
 * @access  public
 */
function postcalendar_hooksapi_create($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
        return false;
    }
    $module = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(pnModGetName()); // default to active module

    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

	$hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST'); // array of data from 'new' hook


    if (!$home = pnModAPIFunc('PostCalendar', 'hooks', 'funcisavail', array(
        'module' => $module))) {
        return LogUtil::registerError(__('Hook function not available', $dom));;
    }
    $event = pnModAPIFunc($home, 'hooks', 'create_' . $module, array(
        'objectid' => $args['objectid'],
        'hookinfo' => $hookinfo));

    // add correct category information to new event

    // write event to postcal table
    if (DBUtil::insertObject($event, 'postcalendar_events', 'eid')) {
        LogUtil::registerStatus(__("PostCalendar: News event created.", $dom));
        return true;
    }

    return LogUtil::registerError(__('Error! PostCalender: Could not create an News event.', $dom));
}