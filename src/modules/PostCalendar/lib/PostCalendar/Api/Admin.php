<?php
/**
 * @package     PostCalendar
 * @author      Craig Heydenburg
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
use PostCalendar_Entity_CalendarEvent as CalendarEvent;

class PostCalendar_Api_Admin extends Zikula_AbstractApi
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
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'admin', 'listevents'),
                'text' => $this->__('Event List'),
                'class' => 'z-icon-es-view',
                'links' => array(
                    array('url' => ModUtil::url('PostCalendar', 'admin', 'listevents', array('listtype' => CalendarEvent::APPROVED)),
                        'text' => $this->__('Approved Events')),
                    array('url' => ModUtil::url('PostCalendar', 'admin', 'listevents', array('listtype' => CalendarEvent::HIDDEN)),
                        'text' => $this->__('Hidden Events')),
                    array('url' => ModUtil::url('PostCalendar', 'admin', 'listevents', array('listtype' => CalendarEvent::QUEUED)),
                        'text' => $this->__('Queued Events'))
                ));
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'event', 'create'),
                'text' => $this->__('Create new event'),
                'class' => 'z-icon-es-new');
        }
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
/*        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            $links[] = array(
                'url' => ModUtil::url('PostCalendar', 'admin', 'migrateTimeIt'),
                'text' => $this->__('Migrate TimeIt'),
                'class' => 'z-icon-es-regenerate');
        }
*/

        // Return the links array back to the calling function
        return $links;
    }
    
    /**
     * Send an email to admin on new event submission
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
    
        if (!(bool) $this->getVar('pcNotifyAdmin')) {
            return true;
        }
        $isadmin = SecurityUtil::checkPermission('PostCalendar::', 'null::null', ACCESS_ADMIN);
        $notifyadmin2admin = $this->getVar('pcNotifyAdmin2Admin');
        if ($isadmin && !$notifyadmin2admin) {
            return true;
        }
    
        $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('PostCalendar'));
        $modversion = DataUtil::formatForOS($modinfo['version']);

        $renderer = Zikula_View::getInstance('PostCalendar');
    
        $renderer->assign('is_update', $is_update);
        $renderer->assign('modversion', $modversion);
        $renderer->assign('eid', $eid);
        $renderer->assign('link', ModUtil::url('PostCalendar', 'admin', 'adminevents', array(
            'events' => $eid,
            'action' => _ADMIN_ACTION_VIEW), null, null, true));
        $message = $renderer->fetch('email/adminnotify.tpl');
    
        $messagesent = ModUtil::apiFunc('Mailer', 'user', 'sendmessage', array(
            'toaddress' => $this->getVar('pcNotifyEmail'),
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