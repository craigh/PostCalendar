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
    $module = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module

    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

	$hookinfo = FormUtil::getPassedValue('postcalendar', array(), 'POST'); // array of data from 'new' hook
    if (DataUtil::is_serialized($hookinfo['cats'])) {
        $hookinfo['cats'] = unserialize($hookinfo['cats']);
    }

    if ((!isset($hookinfo['optin'])) || (!$hookinfo['optin'])) {
        LogUtil::registerStatus(__("PostCalendar: Event not created (opt out).", $dom));
        return;
    }

    if (!$home = ModUtil::apiFunc('PostCalendar', 'hooks', 'funcisavail', array(
        'module' => $module))) {
        return LogUtil::registerError(__('Hook function not available', $dom));;
    }
    $event = ModUtil::apiFunc($home, 'hooks', $module . '_pcevent', array(
        'objectid' => $args['objectid']));

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
            LogUtil::registerStatus(__("PostCalendar: Event created.", $dom));
            return true;
        }
    } else {
        // if the _pcevent function returns false, it means that an event is not desired, so quietly exit
        return;
    }

    return LogUtil::registerError(__('Error! PostCalender: Could not create an event.', $dom));
}