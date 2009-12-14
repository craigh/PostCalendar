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
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // simple replacement, no need to cache anything
    if (isset($args['nid']) && !empty($args['nid'])) {
        if (substr($args['nid'], 0, 1) != '-') {
            $args['nid'] =  '-' . $args['nid'];
        }
        list($dispose,$eid,$displaytype) = explode('-', $args['nid']);
        $link = pnModURL('PostCalendar', 'user', 'view', array('viewtype' => 'details', 'eid' => $eid));
        $displaytype = $displaytype ? strtoupper($displaytype) : 'NLI'; // in any order: N (name) D (date) T (time) I (icon) L (uselink) - default: NL
        if (!$event = postcalendar_needleapi_eventarray(compact('eid'))) return __f('No event with eid %s', $eid, $dom);
        if ($event == -1) return ''; // event not allowed for user

        $icon='';$uselink=false;
        $moddir = pnModGetBaseDir($modname = 'PostCalendar');
        if (strpos($displaytype, 'I') !== false) $icon = "<img src='$moddir/pnimages/smallcalicon.jpg' alt='".__('cal icon', $dom)."' title='".__('PostCalendar Event', $dom)."' /> ";
        $linkarray = array();
        if (strpos($displaytype, 'N') !== false) $linkarray['name'] = $event['title'];
        if (strpos($displaytype, 'D') !== false) $linkarray['date'] = $event['eventDate'];
        if (strpos($displaytype, 'T') !== false) $linkarray['time'] = '@'.$event['startTime'];
        if (strpos($displaytype, 'L') !== false) $uselink           = true;
        $linktext = implode(' ', $linkarray);

        $linktext = DataUtil::formatForDisplay($linktext);
        if ($uselink) {
            $link   = DataUtil::formatForDisplay($link);
            $result = "$icon<a href='$link'>$linktext</a>";
        } else {
            $result = $icon.$linktext;
        }
    } else {
        $result = __('No needle ID', $dom);
    }
    return $result;
}

function postcalendar_needleapi_eventarray($args)
{
    // get the event from the DB
    $event = DBUtil::selectObjectByID('postcalendar_events', $args['eid'], 'eid');
    if (!$event) return false;

    if (!$event = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event)) return false;
    $event['eventDate'] = strftime(pnModGetVar('PostCalendar', 'pcEventDateFormat'), strtotime($event['eventDate']));
    
    // is event allowed for this user?
    if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != pnUserGetVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
        return -1;
    }
    
    // compensate for recurring events
    if ($event['recurrtype']) {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $event['eventDate'] = __("recurring event beginning %s", $event['eventDate'], $dom);
    }
    return $event;
}