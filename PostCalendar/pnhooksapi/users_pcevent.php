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
 * get users info for Postcalendar event creation
 *
 * @author  Craig Heydenburg
 * @access  public
 * @args    array(objectid) news id
 * @return  array() event info or false if no desire to publish event
 */
function postcalendar_hooksapi_users_pcevent($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
        return false;
    }

    $user = UserUtil::getVars($args['objectid'], true);

    // $user['activated'] ??
    // cannot rely on 'activated' attribute as update hook is not called on user update/activation (http://code.zikula.org/core/ticket/1804)
    $eventstatus = 1; // approved

    $event = array(
        'title'             => __('New user: ', $dom) . $user['uname'],
        'hometext'          => ":html:" . __('Profile link: ', $dom) . "<a href='" . ModUtil::url('Profile', 'user', 'view', array('uid' => $user['uid'])) . "'>" . $user['uname'] . "</a>",
        'aid'               => $user['uid'], // userid of creator
        'time'              => $user['user_regdate'], // mysql timestamp YYYY-MM-DD HH:MM:SS
        'informant'         => $user['uid'], // userid of creator
        'eventDate'         => substr($user['user_regdate'], 0, 10), // date of event: YYYY-MM-DD
        'duration'          => 3600, // default duration in seconds (not used)
        'startTime'         => substr($user['user_regdate'], -8), // time of event: HH:MM:SS
        'alldayevent'       => 1, // yes
        'eventstatus'       => $eventstatus,
        'sharing'           => 3, // global
    );
    return $event;
}