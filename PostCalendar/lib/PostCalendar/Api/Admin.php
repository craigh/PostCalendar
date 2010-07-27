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

class PostCalendar_Api_Admin extends Zikula_Api
{
    /**
     * Get available admin panel links
     *
     * @return array array of admin links
     */
    public function getlinks()
    {
        // Define an empty array to hold the list of admin links
        $links = array();
    
        // Check the users permissions to each avaiable action within the admin panel
        // and populate the links array if the user has permission
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'admin', 'modifyconfig'),
                'text' => $this->__('Settings'),
                'class' => 'z-icon-es-config');
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'admin', 'modifyeventdefaults'),
                'text' => $this->__('Event default values'),
                'class' => 'z-icon-es-config');
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'event', 'create'),
                'text' => $this->__('Create new event'),
                'class' => 'z-icon-es-new');
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'admin', 'listapproved'),
                'text' => $this->__('Approved events'),
                'class' => 'z-icon-es-list');
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'admin', 'listhidden'),
                'text' => $this->__('Hidden events'),
                'class' => 'z-icon-es-list');
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'admin', 'listqueued'),
                'text' => $this->__('Queued events'),
                'class' => 'z-icon-es-list');
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            $links[] = array(
                'url' => 'http://code.zikula.org/soundwebdevelopment/wiki/PostCalendarFeatureDocs70',
                'text' => $this->__('Documentation'),
                'class' => 'z-icon-es-info');
        }
        // Return the links array back to the calling function
        return $links;
    }
    
    /**
     * @function notify
     * @purpose Send an email to admin on new event submission
     *
     * @param array $args array with arguments. Expected keys: is_update, eid
     * @return bool True if successfull, False otherwise
     */
    public function notify($args)
    {
        $eid       = $args['eid'];
        $is_update = $args['is_update'];
    
        if (!isset($eid)) {
            return LogUtil::registerArgsError();
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
    
        $this->view->assign('is_update', $is_update);
        $this->view->assign('modversion', $modversion);
        $this->view->assign('eid', $eid);
        $this->view->assign('link', ModUtil::url('PostCalendar', 'admin', 'adminevents', array(
            'events' => $eid,
            'action' => _ADMIN_ACTION_VIEW), null, null, true));
        $message = $this->view->fetch('email/adminnotify.tpl');
    
        $messagesent = ModUtil::apiFunc('Mailer', 'user', 'sendmessage', array(
            'toaddress' => _SETTING_NOTIFY_EMAIL,
            'subject'   => $this->__('Notice: PostCalendar submission/change'),
            'body'      => $message,
            'html'      => true));
    
        if ($messagesent) {
            LogUtil::registerStatus($this->__('Done! Sent administrator notification e-mail message.'));
            return true;
        } else {
            LogUtil::registerError($this->__('Error! Could not send administrator notification e-mail message.'));
            return false;
        }
    }
    
    public function getdateorder($format)
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
} // end class def