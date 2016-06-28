<?php
/**
 * @package     PostCalendar
 * @description determine if there are queued events and format a notice
 * @return      if (assign) return count, else return formatted alert notice
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

use Zikula\PostCalendarModule\Entity\CalendarEventEntity;
use Zikula\PostCalendarModule\Helper\PostCalendarUtil;

function smarty_function_pc_queued_events_notify($args, Zikula_View $view)
{
    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
        return;
    }
    if (!ModUtil::getVar('ZikulaPostCalendarModule', 'pcNotifyPending')) {
        return;
    }

    $assign = array_key_exists('assign', $args) && !empty($args['assign']) ? $args['assign'] : null;
    unset($args);
    
    $em = ServiceUtil::getService('doctrine.entitymanager');
    $count = $em->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')
        ->getEventCount(CalendarEventEntity::QUEUED);

    if (empty($count) || ($count < 1)) {
        return;
    }

    $dom = ZLanguage::getModuleDomain('ZikulaPostCalendarModule');
    $url = ModUtil::url('ZikulaPostCalendarModule', 'admin', 'listqueued');

    $text     = _fn('There is %s queued calendar event awaiting your review.', 'There are %s queued calendar events awaiting your review.', $count, $count, $dom);
    $linktext = __(/*!This is link text*/'Review queued events', $dom);

    $alert = "<div class='z-informationmsg'>$text [<a href='$url'>$linktext</a>]</div>";

    if (isset($assign)) {
        $view->assign($assign, $count);
    } else {
        return $alert;
    }
}
