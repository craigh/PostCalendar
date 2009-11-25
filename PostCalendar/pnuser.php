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
require_once dirname(__FILE__) . '/global.php';

/**
 * postcalendar_user_main
 *
 * main view functino for end user
 * @access public
 */
function postcalendar_user_main()
{
    // check the authorization
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }
    return postcalendar_user_view();
}

/**
 * view items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 */
function postcalendar_user_view()
{
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }

    // get the vars that were passed in
    $popup       = FormUtil::getPassedValue('popup');
    $pc_username = FormUtil::getPassedValue('pc_username');
    $eid         = FormUtil::getPassedValue('eid');
    $viewtype    = FormUtil::getPassedValue('viewtype', _SETTING_DEFAULT_VIEW);
    $jumpday     = FormUtil::getPassedValue('jumpday');
    $jumpmonth   = FormUtil::getPassedValue('jumpmonth');
    $jumpyear    = FormUtil::getPassedValue('jumpyear');
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
    extract($args); // 'viewtype','Date','filtercats','pc_username','popup','eid','func'
    if (empty($Date) && empty($viewtype)) {
        return LogUtil::registerError(__('Error! Required arguments not present.', $dom));
    }

    $dom     = ZLanguage::getModuleDomain('PostCalendar');
    $uid     = pnUserGetVar('uid');
    $theme   = pnUserGetTheme();
    $cacheid = md5($Date . $viewtype . $eid . $uid . 'u' . $pc_username . $theme);
    $tpl     = pnRender::getInstance('PostCalendar');
    
    switch ($viewtype) {
        case 'details':
            if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_READ)) {
                return LogUtil::registerPermissionError();
            }

            // build template and fetch:
            if ($tpl->is_cached('user/postcalendar_user_view_event_details.html', $cacheid)) {
                // use cached version
                return $tpl->fetch('user/postcalendar_user_view_event_details.html', $cacheid);
            } else {
                // get the event from the DB
                $event = DBUtil::selectObjectByID('postcalendar_events', $args['eid'], 'eid');
                $event = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);

                // is event allowed for this user?
                if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != pnUserGetVar('uid') && !pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
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
                $tpl->assign('24HOUR_TIME', _SETTING_TIME_24HOUR);
         
                if ((pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD) && (pnUserGetVar('uid') == $event['aid']))
                    || pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
                    $tpl->assign('EVENT_CAN_EDIT', true);
                } else {
                    $tpl->assign('EVENT_CAN_EDIT', false);
                }

                if ($popup == true) {
                    $tpl->display('user/postcalendar_user_view_popup.html', $cacheid);
                    return true; // displays template without theme wrap
                } else {
                    return $tpl->fetch('user/postcalendar_user_view_event_details.html', $cacheid);
                }
            }
            break;

        default:
            if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) {
                return LogUtil::registerPermissionError();
            }
            //now function just returns an array of information to pass to template 5/9/09 CAH
            $out = pnModAPIFunc('PostCalendar', 'user', 'buildView', 
                compact('Date','viewtype','pc_username','filtercats','func'));
            // build template and fetch:
            if ($tpl->is_cached($out['template'], $cacheid)) {
                // use cached version
                return $tpl->fetch($out['template'], $cacheid);
            } else {
                foreach ($out as $var => $val) {
                    $tpl->assign($var, $val);
                }
                $tpl->assign('24HOUR_TIME', _SETTING_TIME_24HOUR);
                return $tpl->fetch($out['template'], $cacheid);
            } // end if/else
            break;
    } // end switch
}
