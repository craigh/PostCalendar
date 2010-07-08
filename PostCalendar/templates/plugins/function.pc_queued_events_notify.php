<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/pntemplates/plugins/function.pc_queued_events_notify.php $
 * @version     $Id: function.pc_queued_events_notify.php 639 2010-06-30 22:16:08Z craigh $
 * @description determine if there are queued events and format a notice
 * @return      if (assign) return count, else return formatted alert notice
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_queued_events_notify($args, &$smarty)
{
    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        return;
    }

    $assign = array_key_exists('assign', $args) && !empty($args['assign']) ? $args['assign'] : null;
    unset($args);

    $prefix = System::getVar('prefix');

    $count = DBUtil::selectObjectCount('postcalendar_events', 'WHERE pc_eventstatus=0');

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
