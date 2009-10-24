<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

//=========================================================================
//  Require utility classes
//=========================================================================
require_once dirname(__FILE__) . '/global.php';

/**
 * Get available admin panel links
 *
 * @return array array of admin links
 */
function postcalendar_adminapi_getlinks()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // Define an empty array to hold the list of admin links
    $links = array();

    // Load the admin language file
    // This allows this API to be called outside of the module
    //contains gettext-type code in the function, so maybe z1.2 appropriate...
    // but is it duplicate functionality to getModuleDomain() above?
    pnModLangLoad('PostCalendar', 'admin');


    // Check the users permissions to each avaiable action within the admin panel
    // and populate the links array if the user has permission
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'modifyconfig'), 'text' => __('Settings', $dom));
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'categories'),
                        'text' => __('Categories', $dom));
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'event', 'new'), 'text' => __('Create new event', $dom));
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listapproved'), 'text' => __('Approved', $dom));
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listhidden'), 'text' => __('Hidden', $dom));
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listqueued'), 'text' => __('Queued', $dom));
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'manualClearCache'), 'text' => __('Clear Smarty cache', $dom));
    }
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('PostCalendar', 'admin', 'testSystem'), 'text' => __('Test system', $dom));
    }

    // Return the links array back to the calling function
    return $links;
}

/**
 * @function    postcalendar_adminapi_getAdminListEvents
 * @param       int    type             event type
 * @param       string sort             field to sort by
 * @param       int    sdir             sort direction
 * @param       int    offset
 * @param       int    offset_increment
 *
 * @return array array of events sorted and incremented as requested
 */
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

/**
 * @function    postcalendar_adminapi_clearCache
 *
 * @return bool clear the pnRender cache
 */
function postcalendar_adminapi_clearCache()
{
    $pnRender = pnRender::getInstance('PostCalendar');
    // Do not call clear_all_cache, but only clear the cache of this module
    return $pnRender->clear_cache();
}

/**
 * @function postcalendar_adminapi_meeting_mailparticipants
 * @purpose Send email to participants of a meeting
 *
 * @param array $args array with arguments. Expected:
 *                    event_duration, event_desc, event_subject, $pc_description,
 *                    startDate, startTime, uname, pc_eid, pc_mail_users
 * @return bool True if successfull, False otherwise
 */
function postcalendar_adminapi_meeting_mailparticipants($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    //TODO: if ($is_update) send appropriate message...
    extract($args);

    // Turn off template caching here
    $pnRender = pnRender::getInstance('PostCalendar', false);
    $pnRender->assign('eid', $eid);
    $pnRender->assign('event_subject', DataUtil::formatForDisplay($event_subject));

	@list($pc_dur_hours, $dmin) = @explode('.', ($event_duration / 60 / 60));
    $pnRender->assign('pc_dur_hours', DataUtil::formatForDisplay($pc_dur_hours));

	$pc_dur_minutes = substr(sprintf('%.2f', '.' . 60 * ($dmin / 100)), 2, 2);
    $pnRender->assign('pc_dur_minutes', DataUtil::formatForDisplay($pc_dur_minutes));

	$pc_description = substr($event_desc, 6);
    if (substr($event_desc, 0, 6) == ":text:") $pc_description = nl2br(strip_tags($pc_description));
    $pnRender->assign('pc_description', DataUtil::formatForDisplayHTML(substr($event_desc, 6)));

    // need to investigate all day event?
	list($x, $y, $z) = explode('-', $startDate);
	list($a, $b, $c) = explode('-', $startTime);
	$pc_start_time = strftime('%H:%M', mktime($a, $b, $c, $y, $z, $x));
    $pnRender->assign('startDate', $startDate);
    $pnRender->assign('pc_start_time', DataUtil::formatForDisplay($pc_start_time));

    $pc_author = $uname;
    $pnRender->assign('pc_author', DataUtil::formatForDisplay($pc_author));

	$pc_URL = pnModURL('PostCalendar', 'user', 'view', array('viewtype' => 'details', 'eid' => $eid), null, null, true);
    $pnRender->assign('pc_URL', DataUtil::formatForOS($pc_URL));

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
        LogUtil::registerStatus(__('Done! Sent meeting-notification e-mail message.', $dom));
        return true;
    } else {
        LogUtil::registerError(__('Error! Could not send meeting-notification e-mail message.', $dom));
        return false;
    }
}

/**
 * @function postcalendar_adminapi_notify
 * @purpose Send an email to admin on new event submission
 *
 * @param array $args array with arguments. Expected keys: is_update, eid
 * @return bool True if successfull, False otherwise
 */
function postcalendar_adminapi_notify($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    extract($args);

    if (!(bool) _SETTING_NOTIFY_ADMIN) return true;
    $isadmin = SecurityUtil::checkPermission('PostCalendar::', 'null::null', ACCESS_ADMIN);
    $notifyadmin2admin = pnModGetVar('PostCalendar', 'pcNotifyAdmin2Admin');
    if ($isadmin && !$notifyadmin2admin) return true;

    $modinfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    $modversion = DataUtil::formatForOS($modinfo['version']);

    // Turn off template caching here
    $pnRender = pnRender::getInstance('PostCalendar', false);
    $pnRender->assign('is_update', $is_update);
    $pnRender->assign('modversion', $modversion);
    $pnRender->assign('eid', $eid);
    $pnRender->assign('link', pnModURL('PostCalendar', 'admin', 'adminevents', array('events' => $eid, 'action' => _ADMIN_ACTION_VIEW), null, null, true));
    $message = $pnRender->fetch('email/postcalendar_email_adminnotify.htm');

    $messagesent = pnModAPIFunc('Mailer', 'user', 'sendmessage', array('toaddress' => _SETTING_NOTIFY_EMAIL, 'subject' => __('Notice: PostCalendar submission/change', $dom), 'body' => $message, 'html' => true));

    if ($messagesent) {
        LogUtil::registerStatus(__('Done! Sent administrator notification e-mail message.', $dom));
        return true;
    } else {
        LogUtil::registerError(__('Error! Could not send administrator notification e-mail message.', $dom));
        return false;
    }
}