<?php
/**
 * @package     PostCalendar
 * @description determine if there are queued events and format a notice
 * @return      if (assign) return count, else return formatted alert notice
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_queued_events_notify($args, &$smarty)
{
    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
        return;
    }
    if (!ModUtil::getVar('PostCalendar', 'pcNotifyPending')) {
        return;
    }

    $assign = array_key_exists('assign', $args) && !empty($args['assign']) ? $args['assign'] : null;
    unset($args);
    
    $em = ServiceUtil::getService('doctrine.entitymanager');
    $count = $em->getRepository('PostCalendar_Entity_CalendarEvent')->getEventCount(PostCalendar_Entity_CalendarEvent::QUEUED);

    if (empty($count) || ($count < 1)) {
        return;
    }

    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $url = ModUtil::url('PostCalendar', 'admin', 'listqueued');

    $text     = _fn('There is %s queued calendar event awaiting your review.', 'There are %s queued calendar events awaiting your review.', $count, $count, $dom);
    $linktext = __(/*!This is link text*/'Review queued events', $dom);

    $alert = "<div class='z-informationmsg'>$text [<a href='$url'>$linktext</a>]</div>";

    if (isset($assign)) {
        $smarty->assign($assign, $count);
    } else {
        return $alert;
    }
}
