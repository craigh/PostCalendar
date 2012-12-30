<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * Multihook needle to display PostCalendar event as link
 * @param $args['nid'] needle id
 * @return link
 */
class PostCalendar_Needles_PostCalEvent extends Zikula_AbstractHelper
{
    public function info()
    {
        $info = array(
            'module'        => 'PostCalendar', // module name
            'info'          => 'POSTCALEVENT-{eventid-displaytype}', // possible needles
            'inspect'       => true,
            //'needle'        => array('http://', 'https://', 'ftp://', 'mailto://'),
            //'function'      => 'http',
            //'casesensitive' => false,
        );
        return $info;
    }
    
    public static function needle($args)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        // simple replacement, no need to cache anything
        if (isset($args['nid']) && !empty($args['nid'])) {
            if (substr($args['nid'], 0, 1) != '-') {
                $args['nid'] = '-' . $args['nid'];
            }
            list ($dispose, $eid, $displaytype) = explode('-', $args['nid']);
            $link = ModUtil::url('PostCalendar', 'user', 'display', array(
                'viewtype' => 'event',
                'eid' => $eid));
            $displaytype = $displaytype ? strtoupper($displaytype) : 'NLI'; // in any order: N (name) D (date) T (time) I (icon) L (uselink) - default: NL
            $e_array = array('eid' => $eid);
            if (!$event = self::getEventArray($e_array)) {
                return "(" . __f('No event with eid %s', $eid, $dom) . ")";
            }
            if ($event == -1) {
                return ''; // event not allowed for user
            }

            $icon = '';
            $uselink = false;
            $moddir = ModUtil::getBaseDir($modname = 'PostCalendar');
            if (strpos($displaytype, 'I') !== false) {
                $icon = "<img src='$moddir/images/smallcalicon.jpg' alt='" . __('cal icon', $dom) . "' title='" . __('PostCalendar Event', $dom) . "' /> ";
            }
            $linkarray = array();
            if (strpos($displaytype, 'N') !== false) {
                $linkarray['name'] = $event['title'];
            }
            if (strpos($displaytype, 'D') !== false) {
                $linkarray['date'] = $event['eventStart']->format('Y-m-d');
            }
            if (strpos($displaytype, 'T') !== false) {
                $linkarray['time'] = '@' . $event['startTime'];
            }
            if (strpos($displaytype, 'L') !== false) {
                $uselink = true;
            }
            $linktext = implode(' ', $linkarray);

            $linktext = DataUtil::formatForDisplay($linktext);
            if ($uselink) {
                $link = DataUtil::formatForDisplay($link);
                $result = "$icon<a href='$link'>$linktext</a>";
            } else {
                $result = $icon . $linktext;
            }
        } else {
            $result = __('No needle ID', $dom);
        }
        return $result;
    }

    public static function getEventArray($args)
    {
        // get the event from the DB
        $entityManager = ServiceUtil::getService('doctrine.entitymanager');
        $event = $entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->find($args['eid'])->getOldArray();
        if (!$event) {
            return false;
        }

        $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', array('event' => $event));

        // is event allowed for this user?
        if ($event['sharing'] == PostCalendar_Entity_CalendarEvent::SHARING_PRIVATE && $event['aid'] != UserUtil::getVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
            return -1;
        }

        // compensate for recurring events
//        if ($event['recurrtype']) {
//            $dom = ZLanguage::getModuleDomain('PostCalendar');
//            $event['eventDate'] = __("recurring event beginning %s", $event['eventDate'], $dom);
//        }
        return $event;
    }
}