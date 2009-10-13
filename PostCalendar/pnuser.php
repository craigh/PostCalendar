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
    $Date = FormUtil::getPassedValue('Date');
    $viewtype = FormUtil::getPassedValue('viewtype');
    $jumpday = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear = FormUtil::getPassedValue('jumpyear');

    if (empty($Date)) $Date = pnModAPIFunc('PostCalendar','user','getDate',compact('jumpday','jumpmonth','jumpyear'));
    if (!isset($viewtype)) $viewtype = _SETTING_DEFAULT_VIEW;

    return postcalendar_user_display(array('viewtype' => $viewtype, 'Date' => $Date));
}

/**
 * display item
 * This is a standard function to provide detailed information on a single item
 * available from the module.
 */
function postcalendar_user_display($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    $eid = FormUtil::getPassedValue('eid');
    $Date = FormUtil::getPassedValue('Date');
    $pc_category = FormUtil::getPassedValue('pc_category');
    $pc_topic = FormUtil::getPassedValue('pc_topic');
    $pc_username = FormUtil::getPassedValue('pc_username');
    $popup = FormUtil::getPassedValue('popup');

    extract($args);
    if (empty($Date) && empty($viewtype)) {
        return LogUtil::registerError(__('Required arguments not present in '.__FUNCTION__, $dom));
        return false;
    }

    $uid = pnUserGetVar('uid');
    $theme = pnUserGetTheme();
    $cacheid = md5($Date . $viewtype . _SETTING_TEMPLATE . $eid . $uid . 'u' . $pc_username . $theme . 'c' . $category . 't' . $topic);
    $tpl = pnRender::getInstance('PostCalendar');
    
    switch ($viewtype) {
        case 'details':
            if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_READ)) {
                return LogUtil::registerPermissionError();
            }

            // build template and fetch:
            pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);
            if ($tpl->is_cached($detailstemplate, $cacheid)) {
                // use cached version
                return $tpl->fetch($detailstemplate, $cacheid);
            } else {
                $out = pnModAPIFunc('PostCalendar', 'event', 'eventDetail', array('eid' => $eid, 'Date' => $Date));
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
            $out = pnModAPIFunc('PostCalendar', 'user', 'buildView', array('Date' => $Date, 'viewtype' => $viewtype));
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

/**
 * postcalendar_user_splitdate
 *
 * @param $args string      expected to be a string of integers YYYYMMDD
 * @return array              date split with keys
 */
function postcalendar_user_splitdate($args)
{
    $splitdate = array();
    $splitdate['day'] = substr($args, 6, 2);
    $splitdate['month'] = substr($args, 4, 2);
    $splitdate['year'] = substr($args, 0, 4);
    return $splitdate;
}

/**
 * postcalendar_user_splittime
 * The function is made for GMT+1 with DaySaveTime Set to enabled
 *
 * @param $args string      expected to be a string of integers HHMMSS
 * @return array              time split with keys
 */
function postcalendar_user_splittime($args)
{
    $splittime = array();
    $splittime['hour'] = substr($args, 0, 2);
    $splittime['hour'] < 10 ? $splittime['hour'] = "0" . $splittime['hour'] : '';
    $splittime['minute'] = substr($args, 2, 2);
    $splittime['second'] = substr($args, 4, 2);
    return $splittime;
}

/**
 * eventdatecmp
 * compare dates/times ??
 *
 * @param a array
 * @param b array
 * @return 1/-1
 * @access private
 */
function eventdatecmp($a, $b)
{
    if ($a[startTime] < $b[startTime]) return -1;
    elseif ($a[startTime] > $b[startTime]) return 1;
}

// parsefilename returns an array
// ([0]=>pathname, [1]=>filename)
// could be used to parse many strings
// is an extension of the explode function
function parsefilename($delim, $str, $lim = 1)
{
    if ($lim > -2) return explode($delim, $str, abs($lim));

    $lim = -$lim;
    $out = explode($delim, $str);
    if ($lim >= count($out)) return $out;

    $out = array_chunk($out, count($out) - $lim + 1);

    return array_merge(array(implode($delim, $out[0])), $out[1]);
}