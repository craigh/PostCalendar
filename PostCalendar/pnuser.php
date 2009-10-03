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
Loader::requireOnce('includes/pnForm.php');

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

    switch ($viewtype) {
        case 'details':
            if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_READ)) {
                return LogUtil::registerPermissionError();
            }

            // build template and fetch:
            $tpl = pnRender::getInstance('PostCalendar');
            pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);
            if ($tpl->is_cached($detailstemplate, $cacheid)) {
                // use cached version
                return $tpl->fetch($detailstemplate, $cacheid);
            } else {
                $out = pnModAPIFunc('PostCalendar', 'event', 'eventDetail',
                    array('eid' => $eid, 'Date' => $Date, 'cacheid' => $cacheid));
                if ($out === false) {
                    pnRedirect(pnModURL('PostCalendar', 'user'));
                }
                foreach ($out as $var => $val) {
                    $tpl->assign($var, $val);
                }
                if ($popup == true) {
                    $tpl->display('user/postcalendar_user_view_popup.html');
                    return true; // displays template without theme wrap
                } else {
                    return $tpl->fetch('user/postcalendar_user_view_event_details.html');
                }
            }
            break;

        default:
            if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_OVERVIEW)) {
                return LogUtil::registerPermissionError();
            }
            //now function just returns an array of information to pass to template 5/9/09 CAH
            $out = pnModAPIFunc('PostCalendar', 'user', 'buildView',
                array('Date' => $Date, 'viewtype' => $viewtype, 'cacheid' => $cacheid));
            // build template and fetch:
            $tpl = pnRender::getInstance('PostCalendar');
            pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);
            if ($tpl->is_cached($out['template'], $cacheid)) {
                // use cached version
                return $tpl->fetch($out['template'], $cacheid);
            } else {
                foreach ($out as $var => $val) {
                    $tpl->assign($var, $val);
                }
                return $tpl->fetch($out['template']);
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

/**
 * postcalendar_user_findContact
 * legacy function - possibly related to address book usage
 * not currently in use
 * @return outputs contact form
 * @access public
 */
function postcalendar_user_findContact()
{

    //$tpl_contact = new pnRender();
    $tpl_contact = pnRender::getInstance('PostCalendar');
    pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl_contact);
    /* Trim as needed */
    $func = FormUtil::getPassedValue('func');
    $template_view = FormUtil::getPassedValue('tplview');
    if (!$template_view) $template_view = _SETTING_DEFAULT_VIEW;
    $tpl_contact->assign('FUNCTION', $func);
    $tpl_contact->assign('TPL_VIEW', $template_view);
    /* end */

    $tpl_contact->caching = false;

    pnModDBInfoLoad('v4bAddressBook');
    $cid = FormUtil::getPassedValue('cid');
    $bid = FormUtil::getPassedValue('bid');
    $contact_id = FormUtil::getPassedValue('contact_id');

    // v4bAddressBook compatability layer
    if ($cid) $company = DBUtil::selectObjectByID('v4b_addressbook_company', $cid);

    if ($bid) $branch = DBUtil::selectObjectByID('v4b_addressbook_company_branch', $bid);

    if ($contact_id) $contact = DBUtil::selectObjectByID('v4b_addressbook_contact', $contact_id);
    // v4bAddressBook compatability layer

    $contact_phone = $contact['addr_phone1'];
    $contact_mail = $contact['addr_email1'];
    $contact_www = $contact['homepage'];

    $location = $company['name'];
    if ($branch['name']) $location .= " / " . $branch['name'];

    // assign the values
    $tpl_contact->assign('cid', $cid);
    $tpl_contact->assign('bid', $bid);
    $tpl_contact->assign('contact_id', $contact_id);
    $tpl_contact->assign('contact', $contact);
    $tpl_contact->assign('location', $location);
    $tpl_contact->assign('contact_phone', $contact_phone);
    $tpl_contact->assign('contact_mail', $contact_mail);
    $tpl_contact->assign('contact_www', $contact_www);

    $output = $tpl_contact->fetch("findContact.html");
    echo $output;

    return true;
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