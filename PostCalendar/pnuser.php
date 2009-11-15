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
            pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);
            if ($tpl->is_cached('user/postcalendar_user_view_event_details.html', $cacheid)) {
                // use cached version
                return $tpl->fetch('user/postcalendar_user_view_event_details.html', $cacheid);
            } else {
                $out = pnModAPIFunc('PostCalendar', 'event', 'eventDetail', compact('eid','Date','func'));
                if ($out === false) {
                    pnRedirect(pnModURL('PostCalendar', 'user'));
                }
                foreach ($out as $var => $val) {
                    $tpl->assign($var, $val);
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
            pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);
            if ($tpl->is_cached($out['template'], $cacheid)) {
                // use cached version
                return $tpl->fetch($out['template'], $cacheid);
            } else {
                foreach ($out as $var => $val) {
                    $tpl->assign($var, $val);
                }
                return $tpl->fetch($out['template'], $cacheid);
            } // end if/else
            break;
    } // end switch
}
