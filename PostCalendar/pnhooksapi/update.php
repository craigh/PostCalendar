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
            LogUtil::registerStatus(__("PostCalendar: Existing event deleted (opt out).", $dom));
        } else {
            LogUtil::registerStatus(__("PostCalendar: News event not created (opt out).", $dom));
        }
        return;
    }

    if (!$home = ModUtil::apiFunc('PostCalendar', 'hooks', 'funcisavail', array(
        'module' => $module))) {
        return LogUtil::registerError(__('Hook function not available', $dom));;
    }
    $event = ModUtil::apiFunc($home, 'hooks', $module . '_pcevent', array(
        'objectid' => $args['objectid'],
        'hookinfo' => $hookinfo));

    if ($event) {
        if (!empty($hookinfo['eid'])) {
            // event already exists - just update
            $event['eid'] = $hookinfo['eid'];
            if (DBUtil::updateObject($event, 'postcalendar_events', NULL, 'eid')) {
                LogUtil::registerStatus(__("PostCalendar: Associated Calendar event updated.", $dom));
                return true;
            }
        } else {
            // create a new event
            if (DBUtil::insertObject($event, 'postcalendar_events', 'eid')) {
                LogUtil::registerStatus(__("PostCalendar: Event created.", $dom));
                return true;
            }
        }
    } else {
        // if the _pcevent function returns false, it means that an event is not desired, so quietly exit
        return;
    }

    return LogUtil::registerError(__('Error! Could not update the associated Calendar event.', $dom));
}