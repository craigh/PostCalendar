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
include 'modules/PostCalendar/global.php';

/**
 * postcalendar_user_main
 *
 * main view function for end user
 * @access public
 */
function postcalendar_user_main()
{
    return postcalendar_user_view();
}

/**
 * view items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 */
function postcalendar_user_view()
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
    $Date        = FormUtil::getPassedValue('Date', pnModAPIFunc('PostCalendar','user','getDate',compact('jumpday','jumpmonth','jumpyear')));
    $filtercats  = FormUtil::getPassedValue('postcalendar_events');
    $func        = FormUtil::getPassedValue('func');

    return postcalendar_user_display(compact('viewtype','Date','filtercats','pc_username','popup','eid','func'));
}

/**
 * display item available from the module.
 */
function postcalendar_user_display($args)
{
    $viewtype    = $args['viewtype'];
    $Date        = $args['Date'];
    $filtercats  = $args['filtercats'];
    $pc_username = $args['pc_username'];
    $popup       = $args['popup'];
    $eid         = $args['eid'];
    $func        = $args['func'];

    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (empty($Date) && empty($viewtype)) {
        return LogUtil::registerError(__('Error! Required arguments not present.', $dom));
    }

    $tpl = pnRender::getInstance('PostCalendar');
    $modinfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    $tpl->assign('postcalendarversion', $modinfo['version']);

    $tpl->cache_id = $Date . '|' . $viewtype . '|' . $eid . '|' . pnUserGetVar('uid');
    
    switch ($viewtype) {
        case 'details':
            if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_READ)) {
                return LogUtil::registerPermissionError();
            }

            // build template and fetch:
            if ($tpl->is_cached('user/postcalendar_user_view_event_details.htm')) {
                // use cached version
                return $tpl->fetch('user/postcalendar_user_view_event_details.htm');
            } else {
                // get the event from the DB
                $event = DBUtil::selectObjectByID('postcalendar_events', $args['eid'], 'eid');
                $event = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);

                // is event allowed for this user?
                if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != pnUserGetVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
                    // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
                    return LogUtil::registerError(__('You do not have permission to view this event.', $dom));
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
                $tpl->assign('loaded_event', $event);
         
                if ($popup == true) {
                    $tpl->display('user/postcalendar_user_view_popup.htm');
                    return true; // displays template without theme wrap
                } else {
                    if ((SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD) && (pnUserGetVar('uid') == $event['aid']))
                        || SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
                        $tpl->assign('EVENT_CAN_EDIT', true);
                    } else {
                        $tpl->assign('EVENT_CAN_EDIT', false);
                    }
                    return $tpl->fetch('user/postcalendar_user_view_event_details.htm');
                }
            }
            break;

        default:
            if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_OVERVIEW)) {
                return LogUtil::registerPermissionError();
            }
            $out = pnModAPIFunc('PostCalendar', 'user', 'buildView', 
                array('Date'=>$Date,'viewtype'=>$viewtype,'pc_username'=>$pc_username,'filtercats'=>$filtercats,'func'=>$func));
            // build template and fetch:
            if ($tpl->is_cached('user/postcalendar_user_view_'.$viewtype.'.htm')) {
                // use cached version
                return $tpl->fetch('user/postcalendar_user_view_'.$viewtype.'.htm');
            } else {
                foreach ($out as $var => $val) {
                    $tpl->assign($var, $val);
                }

                return $tpl->fetch('user/postcalendar_user_view_'.$viewtype.'.htm');
            } // end if/else
            break;
    } // end switch
}
