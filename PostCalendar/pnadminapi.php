<?php
/**
 * SVN: $Id$
 *
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Revision$
 *
 * PostCalendar::Zikula Events Calendar Module
 * Copyright (C) 2002  The PostCalendar Team
 * http://postcalendar.tv
 * Copyright (C) 2009  Sound Web Development
 * Craig Heydenburg
 * http://code.zikula.org/soundwebdevelopment/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * To read the license please read the docs/license.txt or visit
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

//=========================================================================
//  Require utility classes
//=========================================================================
require_once 'modules/PostCalendar/common.api.php';

/**
 * Get available admin panel links
 *
 * @return array array of admin links
 */
function postcalendar_adminapi_getlinks()
{
    // Define an empty array to hold the list of admin links
    $links = array();

    // Load the admin language file
    // This allows this API to be called outside of the module
    pnModLangLoad('PostCalendar', 'admin');

    /**********************************************************************************/
    @define('_AM_VAL', 1);
    @define('_PM_VAL', 2);

    @define('_EVENT_APPROVED', 1);
    @define('_EVENT_QUEUED', 0);
    @define('_EVENT_HIDDEN', -1);
    /**********************************************************************************/

    // Check the users permissions to each avaiable action within the admin panel
    // and populate the links array if the user has permission
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'modifyconfig'), 'text' => _EDIT_PC_CONFIG_GLOBAL);
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'categories'),
                        'text' => _EDIT_PC_CONFIG_CATEGORIES);
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'event', 'new'), 'text' => _PC_CREATE_EVENT);
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listapproved'), 'text' => _PC_VIEW_APPROVED);
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listhidden'), 'text' => _PC_VIEW_HIDDEN);
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listqueued'), 'text' => _PC_VIEW_QUEUED);
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'manualClearCache'), 'text' => _PC_CLEAR_CACHE);
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'testSystem'), 'text' => _PC_TEST_SYSTEM);
    }

    // Return the links array back to the calling function
    return $links;
}

function postcalendar_adminapi_getAdminListEvents($args)
{
    extract($args);

    $where = "WHERE pc_eventstatus=$type";
    if ($sort) {
        if ($sdir == 0) $sort .= ' DESC';
        elseif ($sdir == 1) $sort .= ' ASC';
    }

    return DBUtil::selectObjectArray('postcalendar_events', $where, $sort, $offset, $offset_increment, false);
}

function postcalendar_adminapi_clearCache()
{
    $pnRender = pnRender::getInstance('PostCalendar'); // PostCalendarSmartySetup not needed
    $res = $pnRender->clear_all_cache();

    return $res;
}

/**
 * Send email to participants of a meeting
 *
 * @param array $args array with arguments. Expected:
 *                    event_duration, event_desc, event_subject, $pc_description,
 *                    startDate, startTime, uname, pc_eid, pc_mail_users
 * @return bool True if successfull, False otherwise
 */
function postcalendar_adminapi_meeting_mailparticipants($args)
{
    //TODO: if ($is_update) send appropriate message...
    extract($args);
    /* expected: $event_subject,$event_duration,$event_desc,$startDate,$startTime,$uname,$eid,$pc_mail_users,$is_update */

    $pnRender = pnRender::getInstance('PostCalendar');
    $pnRender->assign('eid', $eid);
    $pnRender->assign('event_subject', $event_subject);

	@list($pc_dur_hours, $dmin) = @explode('.', ($event_duration / 60 / 60));
    $pnRender->assign('pc_dur_hours', $pc_dur_hours);

	$pc_dur_minutes = substr(sprintf('%.2f', '.' . 60 * ($dmin / 100)), 2, 2);
    $pnRender->assign('pc_dur_minutes', $pc_dur_minutes);

	$pc_description = substr($event_desc, 6);
    $pnRender->assign('pc_description', $event_desc);

	list($x, $y, $z) = explode('-', $startDate);
	list($a, $b, $c) = explode('-', $startTime);
	$pc_start_time = strftime('%H:%M', mktime($a, $b, $c, $y, $z, $x));
    $pnRender->assign('startDate', $startDate);
    $pnRender->assign('pc_start_time', $pc_start_time);

    $pc_author = $uname;
    $pnRender->assign('pc_author', $pc_author);

	$pc_URL = pnModURL('PostCalendar', 'user', 'view', array('viewtype' => 'details', 'eid' => $eid), null, null, true);
    $pnRender->assign('pc_URL', $pc_URL);

    $modinfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    $modversion = DataUtil::formatForOS($modinfo['version']);
    $pnRender->assign('modversion', $modversion);

	for ($i = 0; $i < count($pc_mail_users); $i++) {
		$toaddress[$i] = pnUserGetVar('email', $pc_mail_users[$i]); //create array of email addresses to mailto
	}

	$subject = _PC_MEETING_MAIL_TITLE . ": $event_subject";
    $message = $pnRender->fetch('email/postcalendar_email_meetingnotify.htm');

    $messagesent = pnModAPIFunc('Mailer', 'user', 'sendmessage', array('toaddress' => $toaddress, 'subject' => $subject, 'body' => $message, 'html' => true));

    if ($messagesent) {
        LogUtil::registerStatus('Meeting notify email sent');
        return true;
    } else {
        LogUtil::registerError('Meeting notify email not sent');
        return false;
    }
}

/**
 * Send an email to admin on new event submission
 *
 * @param array $args array with arguments. Expected keys: is_update, eid
 * @return bool True if successfull, False otherwise
 */
function postcalendar_adminapi_notify($args)
{
    //TODO: needd to put a test in here for if the admin submitted the event, if not, probably don't send email. (ticket 24)
    extract($args);

    if (!(bool) _SETTING_NOTIFY_ADMIN) return true;

    $modinfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    $modversion = DataUtil::formatForOS($modinfo['version']);

    $pnRender = pnRender::getInstance('PostCalendar');
    $pnRender->assign('is_update', $is_update);
    $pnRender->assign('modversion', $modversion);
    $pnRender->assign('eid', $eid);
    $pnRender->assign('link', pnModURL('PostCalendar', 'admin', 'adminevents', array('pc_event_id' => $eid, 'action' => _ADMIN_ACTION_VIEW), null, null, true));
    $message = $pnRender->fetch('email/postcalendar_email_adminnotify.htm');

    $messagesent = pnModAPIFunc('Mailer', 'user', 'sendmessage', array('toaddress' => _SETTING_NOTIFY_EMAIL, 'subject' => _PC_NOTIFY_SUBJECT, 'body' => $message, 'html' => true));

    if ($messagesent) {
        LogUtil::registerStatus('Admin notify email sent');
        return true;
    } else {
        LogUtil::registerError('Admin notify email not sent');
        return false;
    }
}
/****************************************************
 * The functions below are moved to eventapi
 ****************************************************/
function postcalendar_adminapi_submitEvent($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'writeEvent', $args);
}
function postcalendar_adminapi_buildSubmitForm($args)
{
    $args['admin'] = true;
    return pnModAPIFunc('PostCalendar', 'event', 'buildSubmitForm', $args);
}
function postcalendar_adminapi_eventDetail($args)
{
    $args['admin'] = true;
    return pnModAPIFunc('PostCalendar', 'event', 'eventDetail', $args);
}