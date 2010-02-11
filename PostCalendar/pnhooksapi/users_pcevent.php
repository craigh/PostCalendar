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

    $user        = pnUserGetVars($args['objectid'], true);

    // $user['activated'] ??
    $eventstatus = 1; // approved

    $event = array(
        'title'             => __('New user: ', $dom) . $user['uname'],
        'hometext'          => ":html:" . __('Profile link: ', $dom) . "<a href='" . pnModURL('Profile', 'user', 'view', array('uid' => $user['uid'])) . "'>" . $user['uname'] . "</a>",
        'aid'               => $user['uid'], // userid of creator
        'time'              => $user['user_regdate'], // mysql timestamp YYYY-MM-DD HH:MM:SS
        'informant'         => $user['uid'], // userid of creator
        'eventDate'         => substr($user['user_regdate'], 0, 10), // date of event: YYYY-MM-DD
        'duration'          => 3600, // default duration in seconds (not used)
        'recurrtype'        => 0, // norepeat
        'recurrspec'        => 'a:5:{s:17:"event_repeat_freq";s:0:"";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:0:"";}', // default recurrance info - serialized (not used)
        'startTime'         => substr($user['user_regdate'], -8), // time of event: HH:MM:SS
        'alldayevent'       => 1, // yes
        'location'          => 'a:6:{s:14:"event_location";s:0:"";s:13:"event_street1";s:0:"";s:13:"event_street2";s:0:"";s:10:"event_city";s:0:"";s:11:"event_state";s:0:"";s:12:"event_postal";s:0:"";}', // default location info - serialized (not used)
        'eventstatus'       => $eventstatus,
        'sharing'           => 3, // global
        'hooked_modulename' => 'users',
        'hooked_objectid'   => $user['uid'],
        '__CATEGORIES__'    => $args['hookinfo']['cats'],
        '__META__'          => array('module' => 'PostCalendar'),
    );
    return $event;
}