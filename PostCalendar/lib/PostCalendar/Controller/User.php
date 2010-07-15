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

class PostCalendar_Controller_User extends Zikula_Controller
{
    /**
     * main
     *
     * main view function for end user
     * @access public
     */
    public function main()
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_OVERVIEW)) {
            return LogUtil::registerPermissionError();
        }
    
        // get the vars that were passed in
        $popup       = FormUtil::getPassedValue('popup');
        $pc_username = FormUtil::getPassedValue('pc_username');
        $eid         = FormUtil::getPassedValue('eid');
        $viewtype    = FormUtil::getPassedValue('viewtype', _SETTING_DEFAULT_VIEW);
        $jumpday     = FormUtil::getPassedValue('jumpDay');
        $jumpmonth   = FormUtil::getPassedValue('jumpMonth');
        $jumpyear    = FormUtil::getPassedValue('jumpYear');
        $jumpargs    = array(
            'jumpday' => $jumpday,
            'jumpmonth' => $jumpmonth,
            'jumpyear' => $jumpyear);
        $Date        = FormUtil::getPassedValue('Date', PostCalendar_Util::getDate($jumpargs));
        $filtercats  = FormUtil::getPassedValue('postcalendar_events');
        $func        = FormUtil::getPassedValue('func');
        $prop        = isset($args['prop']) ? $args['prop'] : (string)FormUtil::getPassedValue('prop', null, 'GET');
        $cat         = isset($args['cat']) ? $args['cat'] : (string)FormUtil::getPassedValue('cat', null, 'GET');
        
        if (empty($filtercats) && !empty($prop) && !empty($cat)) {
            $filtercats[__CATEGORIES__][$prop] = $cat;
        }
    
    
        return $this->display(array(
            'viewtype' => $viewtype,
            'Date' => $Date,
            'filtercats' => $filtercats,
            'pc_username' => $pc_username,
            'popup' => $popup,
            'eid' => $eid,
            'func' => $func));
    }
    
    /**
     * display item available from the module.
     */
    public function display($args)
    {
        $viewtype    = $args['viewtype'];
        $Date        = $args['Date'];
        $filtercats  = $args['filtercats'];
        $pc_username = $args['pc_username'];
        $popup       = $args['popup'];
        $eid         = $args['eid'];
        $func        = $args['func'];
    
        if (empty($Date) && empty($viewtype)) {
            return LogUtil::registerArgsError();
        }
    
        $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('PostCalendar'));
        $this->view->assign('postcalendarversion', $modinfo['version']);
    
        $this->view->cache_id = $Date . '|' . $viewtype . '|' . $eid . '|' . UserUtil::getVar('uid');
    
        switch ($viewtype) {
            case 'details':
                if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_READ)) {
                    return LogUtil::registerPermissionError();
                }
    
                // build template and fetch:
                if ($this->view->is_cached('user/view_event_details.tpl')) {
                    // use cached version
                    return $this->view->fetch('user/view_event_details.tpl');
                } else {
                    // get the event from the DB
                    $event = DBUtil::selectObjectByID('postcalendar_events', $args['eid'], 'eid');
                    $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);
    
                    // is event allowed for this user?
                    if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != UserUtil::getVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
                        // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
                        return LogUtil::registerError($this->__('You do not have permission to view this event.'));
                    }
    
                    // since recurrevents are dynamically calculcated, we need to change the date
                    // to ensure that the correct/current date is being displayed (rather than the
                    // date on which the recurring booking was executed).
                    if ($event['recurrtype']) {
                        $y = substr($args['Date'], 0, 4);
                        $m = substr($args['Date'], 4, 2);
                        $d = substr($args['Date'], 6, 2);
                        $event['eventDate'] = "$y-$m-$d";
                    }
                    $this->view->assign('loaded_event', $event);
    
                    if ($popup == true) {
                        $this->view->assign('popup', $popup);
                        $this->view->display('event/view.tpl');
                        return true; // displays template without theme wrap
                    } else {
                        if ((SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD) && (UserUtil::getVar('uid') == $event['aid'])) || SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
                            $this->view->assign('EVENT_CAN_EDIT', true);
                        } else {
                            $this->view->assign('EVENT_CAN_EDIT', false);
                        }
                        $this->view->assign('TODAY_DATE', DateUtil::getDatetime('', '%Y-%m-%d'));
                        $this->view->assign('DATE', $Date);
                        return $this->view->fetch('user/view_event_details.tpl');
                    }
                }
                break;
    
            default:
                if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_OVERVIEW)) {
                    return LogUtil::registerPermissionError();
                }
                $out = ModUtil::apiFunc('PostCalendar', 'user', 'buildView', array(
                    'Date' => $Date,
                    'viewtype' => $viewtype,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats,
                    'func' => $func));
                // build template and fetch:
                if ($this->view->is_cached('user/view_' . $viewtype . '.tpl')) {
                    // use cached version
                    return $this->view->fetch('user/view_' . $viewtype . '.tpl');
                } else {
                    foreach ($out as $var => $val) {
                        $this->view->assign($var, $val);
                    }
    
                    return $this->view->fetch('user/view_' . $viewtype . '.tpl');
                } // end if/else
                break;
        } // end switch
    }
} // end class def
