<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: $
 * @version     $Id: $
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * event needle
 * @param $args['nid'] needle id
 * @return link
 */
function postcalendar_needleapi_event($args)
{
    // simple replacement, no need to cache anything
    if (isset($args['nid']) && !empty($args['nid'])) {
        if (substr($args['nid'], 0, 1) != '-') {
            $args['nid'] =  '-' . $args['nid'];
        }
        list($dispose,$eid,$displaytype) = explode('-', $args['nid']);
        $link = pnModURL('PostCalendar', 'user', 'view', array('viewtype' => 'details', 'eid' => $eid));
        $displaytype = $displaytype ? $displaytype : "T";
        $event = postcalendar_needleapi_eventarray(compact('eid'));
        switch ($displaytype) {
            case "TDt": // display title, date and time as link text
                $linktext = $event['title']." (".$event['eventDate']." @".$event['startTime'].")";
                break;
            case "TD": // display title and date as link text
                $linktext = $event['title']." (".$event['eventDate'].")";
                break;
            case "Dt": // display date and time as link text
                $linktext = $event['eventDate']." @".$event['startTime'];
                break;
            case "D": // display date as link text
                $linktext = $event['eventDate'];
                break;
            case "T": // display title as link text
                $linktext = $event['title'];
                break;
        }
        $linktext = DataUtil::formatForDisplay($linktext);
        $link     = DataUtil::formatForDisplay($link);
        $result   = "<a href='$link'>$linktext</a>";
    } else {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $result = __('No needle ID', $dom);
    }
    return $result;
}

function postcalendar_needleapi_eventarray($args)
{
    // get the event from the DB
    $event = DBUtil::selectObjectByID('postcalendar_events', $args['eid'], 'eid');
    if (!$event) return false;

    $event = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);
    $event['eventDate'] = strftime(pnModGetVar('PostCalendar', 'pcEventDateFormat'), strtotime($event['eventDate']));

    
    // is event allowed for this user?
    if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != pnUserGetVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
        return false;
    }
    
    // since recurrevents are dynamically calculcated, we need to change the date
    // to ensure that the correct/current date is being displayed (rather than the
    // date on which the recurring booking was executed).
    if ($event['recurrtype']) {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $event['eventDate'] = __("recurring event beginning %s", $event['eventDate'], $dom);
    }
    return $event;
}