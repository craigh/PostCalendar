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

include_once 'modules/PostCalendar/global.php';
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

    // Check the users permissions to each avaiable action within the admin panel
    // and populate the links array if the user has permission
    if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array(
            'url' => ModUtil::url('PostCalendar', 'admin', 'modifyconfig'),
            'text' => __('Settings', $dom));
    }
    if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        $links[] = array(
            'url' => ModUtil::url('PostCalendar', 'event', 'new'),
            'text' => __('Create new event', $dom));
    }
    if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array(
            'url' => ModUtil::url('PostCalendar', 'admin', 'listapproved'),
            'text' => __('Approved events', $dom));
    }
    if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array(
            'url' => ModUtil::url('PostCalendar', 'admin', 'listhidden'),
            'text' => __('Hidden events', $dom));
    }
    if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        $links[] = array(
            'url' => ModUtil::url('PostCalendar', 'admin', 'listqueued'),
            'text' => __('Queued events', $dom));
    }

    // Return the links array back to the calling function
    return $links;
}

/**
 * @function    postcalendar_adminapi_clearCache
 *
 * @return bool true if all cached templates successfully cleared, false otherwise.
 */
function postcalendar_adminapi_clearCache()
{
    $render = Renderer::getInstance('PostCalendar');
    // Do not call clear_all_cache, but only clear the cached templates of this module
    return $render->clear_cache();
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
    $dom       = ZLanguage::getModuleDomain('PostCalendar');

    $eid       = $args['eid'];
    $is_update = $args['is_update'];

    if (!isset($eid)) {
        return LogUtil::registerError(__f('Error! %1$s required in %2$s.', array('eid', 'postcalendar_adminapi_notify'), $dom));
    }

    if (!(bool) _SETTING_NOTIFY_ADMIN) {
        return true;
    }
    $isadmin = SecurityUtil::checkPermission('PostCalendar::', 'null::null', ACCESS_ADMIN);
    $notifyadmin2admin = ModUtil::getVar('PostCalendar', 'pcNotifyAdmin2Admin');
    if ($isadmin && !$notifyadmin2admin) {
        return true;
    }

    $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('PostCalendar'));
    $modversion = DataUtil::formatForOS($modinfo['version']);

    // Turn off template caching here
    $render = Renderer::getInstance('PostCalendar', false);
    $render->assign('is_update', $is_update);
    $render->assign('modversion', $modversion);
    $render->assign('eid', $eid);
    $render->assign('link', ModUtil::url('PostCalendar', 'admin', 'adminevents', array(
        'events' => $eid,
        'action' => _ADMIN_ACTION_VIEW), null, null, true));
    $message = $render->fetch('email/postcalendar_email_adminnotify.htm');

    $messagesent = ModUtil::apiFunc('Mailer', 'user', 'sendmessage', array(
        'toaddress' => _SETTING_NOTIFY_EMAIL,
        'subject' => __('Notice: PostCalendar submission/change', $dom),
        'body' => $message,
        'html' => true));

    if ($messagesent) {
        LogUtil::registerStatus(__('Done! Sent administrator notification e-mail message.', $dom));
        return true;
    } else {
        LogUtil::registerError(__('Error! Could not send administrator notification e-mail message.', $dom));
        return false;
    }
}

function postcalendar_adminapi_getdateorder($format)
{
    $possiblevals = array(
        'D' => array(
            "%e",
            "%d"),
        'M' => array(
            "%B",
            "%b",
            "%h",
            "%m"),
        'Y' => array(
            "%y",
            "%Y"));
    foreach ($possiblevals as $type => $vals) {
        foreach ($vals as $needle) {
            $tail = strstr($format, $needle);
            if ($tail !== false) {
                $$type = $needle;
                break;
            }
        }
        $format = str_replace($$type, $type, $format);
    }
    $format = str_replace(array(
        " ",
        ",",
        "."), '', $format); // remove extraneous punctuation
    if ($format == "%F") {
        $format = 'YMD';
        $D = '%d';
        $M = '%m';
        $Y = '%Y';
    }
    if (strlen($format) != 3) {
        $format = 'MDY';
        $D = '%e';
        $M = '%B';
        $Y = '%Y';
    } // default to American
    return array(
        'format' => $format,
        'D' => $D,
        'M' => $M,
        'Y' => $Y);
}