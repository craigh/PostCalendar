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
    $eid = FormUtil::getPassedValue('eid');
    $Date = FormUtil::getPassedValue('Date');
    $pc_category = FormUtil::getPassedValue('pc_category');
    $pc_topic = FormUtil::getPassedValue('pc_topic');
    $pc_username = FormUtil::getPassedValue('pc_username');
    $popup = FormUtil::getPassedValue('popup');

    extract($args);
    if (empty($Date) && empty($viewtype)) {
        return LogUtil::registerError(_MODARGSERROR . ' in postcalendar_user_display');
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
 * Extension of the pnFormHandler class to handle a file upload
 */
class postcalendar_user_fileuploadHandler extends pnFormHandler
{
    function initialize(&$render)
    {
        if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) return $render->pnFormSetErrorMsg(_NOTAUTHORIZED);

        //=================================================================
        // select_event_type_block
        $all_categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
        $categories = array();
        foreach ($all_categories as $category) {
            $categories[] = array('text' => $category['catname'], 'value' => $category['catid']);
        }
        if (count($categories) > 0) {
            $render->assign('categories', $categories);
        }
        $render->assign('event_category', $event_category);
    
        //=================================================================
        // event_sharing_block
        $data = array();
        if (_SETTING_ALLOW_USER_CAL) {
            $data[]=array('text'=>__('Private', $dom), 'value'=>SHARING_PRIVATE);
            $data[]=array('text'=>__('Public', $dom), 'value'=>SHARING_PUBLIC);
            $data[]=array('text'=>__('Show as Busy', $dom), 'value'=>SHARING_BUSY);
        }
        if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN) || _SETTING_ALLOW_GLOBAL || !_SETTING_ALLOW_USER_CAL) {
            $data[]=array('text'=>__('Global', $dom), 'value'=>SHARING_GLOBAL);
            $data[]=array('text'=>__('Global, description private', $dom), 'value'=>SHARING_HIDEDESC);
        }
        $render->assign('sharingselect', $data);
        if (!isset($event_sharing)) $event_sharing = SHARING_PUBLIC;
        $render->assign('event_sharing', $event_sharing);

        return true;
    }

    /**
     * The handler for command 'submit' in the file upload handler
     *
     * @param  pnFormRender $render The pnFormRender instance
     * @param  array        $args   A reference to the arguments array
     * @return boolean              True if successfull, false otherwise
     */
    function handleCommand(&$render, $args)
    {
        if ($args['commandName'] == 'submit') {
            // Do forms validation. This call forces the framework to check all validators on the page
            // to validate their input. If anyone fails then pnFormIsValid() returns false, and so
            // should your command event also do.
            //   if (!$render->pnFormIsValid())
            //       return false;

            $data = $render->pnFormGetValues();

            $result = pnModAPIFunc('PostCalendar', 'ical', 'processupload', $data);

            if ($result != true) return $render->pnFormSetErrorMsg(_PC_COULDNOTPROCESSFILEUPLOAD);

            $url = pnModUrl('PostCalendar', 'user', 'view',
                array('viewtype' => pnModGetVar('PostCalendar', 'pcDefaultView')));

            return $render->pnFormRedirect($url);
        } else if ($args['commandName'] == 'cancel') {
            $redir = pnModUrl('PostCalendar', 'user', 'view',
                array('viewtype' => pnModGetVar('PostCalendar', 'pcDefaultView')));
            return $render->pnFormRedirect($redir);
        }
        echo "no command found";
        $data = $render->pnFormGetValues();
        return true;
    }
}

/**
 * postcalendar_user_upload
 *
 * displays form to upload iCal (.ics) files
 * 
 * @return
 */
function postcalendar_user_upload()
{
    $render = & FormUtil::newpnForm('PostCalendar');
    return $render->pnFormExecute('event/postcalendar_event_fileupload.htm', new postcalendar_user_fileuploadHandler());
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
 * postcalendar_user_export
 * export rss or ical file to user (rss may not be functional)
 * directs browser to correct function
 *
 * @return
 */
function postcalendar_user_export()
{
    # control whether debug and extendedinfo flags are allowed
    $debugallowed = 0;
    $extendedinfoallowed = 1;

    $date = FormUtil::getPassedValue('date');
    $start = FormUtil::getPassedValue('start');
    $end = FormUtil::getPassedValue('end');
    $eid = FormUtil::getPassedValue('eid');
    $format = FormUtil::getPassedValue('format');
    $debug = FormUtil::getPassedValue('debug');
    $category = FormUtil::getPassedValue('category');
    $etype = FormUtil::getPassedValue('etype', 'ical');

    # Clean up the dates and force the format to be correct
    if ($start == '') {
        $start = date("m/d/Y");
    } else {
        $start = date("m/d/Y", strtotime($start));
    }

    if ($end == '') {
        $end = date("m/d/Y", (time() + 30 * 60 * 60 * 24));
    } else {
        $end = date("m/d/Y", strtotime($end));
    }

    if ($date != "") {
        $start = date("m/d/Y", strtotime($date));
        $end = $start;
    }

    if (!$debug) {
        $filename = mktime() . ($etype == 'ical' ? '.ics' : '.xml');
        header("Content-Type: text/calendar");
        if (($format == "") || ($format == "inline")) {
            header("Content-Disposition: inline; filename=$filename");
        } else {
            header("Content-Disposition: attachment; filename=$filename");
        }
    }

    if ($debug) {
        echo ("<PRE>");
    }

    $events = pnModAPIFunc('PostCalendar', 'event', 'getEvents', array('start' => $start, 'end' => $end));

    # sort the events by start date and time, sevent has the sorted list
    $sevents = array();
    foreach ($events as $cdate => $event) {
        # $event has event array for $cdate day
        # sort the event array and store back in $sevent with $cdate as the index
        usort($event, "eventdatecmp");
        $sevents[$cdate] = array();
        $sevents[$cdate] = $event;
    }
    reset($sevents);

    if ($debug && $debugallowed) {
        echo "<P><HR WIDTH=100%><P>Original Events are <P>";
        prayer($events);
    }
    ;
    if ($debug && $debugallowed) {
        echo "<P><HR WIDTH=100%><P>Sorted Events are <P>\r\n";
        prayer($sevents);
    }
    ;

    if ($etype == 'ical') {
        return pnModAPIFunc('PostCalendar', 'ical', 'export_ical', ($sevents));
    } else {
        return pnModFunc('PostCalendar', 'user', 'export_rss', array($sevents, $start, $end));
    }
}

/**
 * postcalendar_user_export_rss
 * export rss (may not be functional)
 *
 * @return
 */
function postcalendar_user_export_rss($sevents, $start, $end)
{
    $eid = FormUtil::getPassedValue('eid');
    $category = FormUtil::getPassedValue('category');
    $sitename = getenv('SERVER_NAME');

    require_once dirname(__FILE__) . '/pnincludes/rssfeedcreator.php';
    $rss = new UniversalFeedCreator();
    $rss->useCached();
    $rss->title = "$sitename $start - $end Calendar";
    $rss->description = "$sitename $start - $end Calendar";
    $rss->descriptionTruncSize = 500;
    $rss->descriptionHtmlSyndicated = true;
    $rss->link = urlencode(pnModURL('PostCalendar', 'user', 'main'));

    foreach ($sevents as $cdate => $event) {
        # $cdate has the events actual date
        # $event has the event array for $cdate day
        foreach ($event as $item) {
            # Allow a selection by unique eventid and/or category
            if (($item['eid'] == $eid || $eid === '') && ($item['catid'] == $category || $category === '')) {
                # slurp out the fields to make it more convenient
                $starttime = $item['startTime'];
                $duration = $item['duration'];
                $title = $item['title'];
                $summary = htmlentities($item['title']);
                $description = htmlentities(
                    str_replace("<br />", "\n",
                        substr($item['hometext'], 6)));
                $evcategory = $item['catname'];
                $location = htmlentities($item['event_location']);
                $uid = $item['eid'] . "--" . strtotime($item['time']) . "@$sitename";
                $url = $item['website'];
                $peid = $item['eid'];

                # this block of code cleans up encodings such as &#113; in the
                # email addresses.  It builds two arrays with search and replace and then calls
                # str_replace once to translate everything over.
                $email = $item['contemail'];
                for ($i = 1; $i <= 127; $i++) {
                    $srch[$i] = sprintf("&#%03.3d;", $i);
                    $repl[$i] = chr($i);
                }

                $item['contemail'] = str_replace($srch, $repl, $item['contemail']);
                $email = str_replace($srch, $repl, $email);
                $organizer = $email;

                # indent the original description
                $description = preg_replace(
                    '!^!m', str_repeat(' ', 2), $description);

                # Build the item description text.
                $evtdesc = $description . "&lt;br /&gt; &lt;br /&gt;" . "  &lt;br /&gt;&lt;b&gt;";
                if ($item['contname']) $evtdesc .= "  Contact: " . htmlentities(
                    $item['contname']) . "&lt;br /&gt;";
                if ($item['conttel']) $evtdesc .= "  Phone: " . $item['conttel'] . "&lt;br /&gt;";
                if ($email) $evtdesc .= "  Email: " . $email . "&lt;br /&gt;";
                if ($item['website']) $evtdesc .= "  URL: " . $item['website'] . "&lt;br /&gt;";
                if ($item['event_location']) $evtdesc .= "  Location: " . htmlentities(
                    $item['event_location']) . "&lt;br /&gt;";
                if ($item['event_street1']) $evtdesc .= "  Location: " . htmlentities(
                    $item['event_street1']) . "&lt;br /&gt;";
                if ($item['event_street2']) $evtdesc .= "  Location: " . htmlentities(
                    $item['event_street2']) . "&lt;br /&gt;";
                if ($item['event_city']) $evtdesc .= "  City, ST ZIP: " . htmlentities(
                    $item['event_city']) . "," . $item['event_state'] . " " . $item['event_postal'] . "  &lt;br /&gt;";

                # Build the link to the actual calendar
                $args = array();
                $args['Date'] = date("Ymd", strtotime($cdate));
                $args['viewtype'] = 'details';
                $args['eid'] = $peid;
                $url = pnModURL('PostCalendar', 'user', 'view', $args);

                $item = new FeedItem();
                $item->title = "$summary - " . date("F jS", strtotime($cdate));
                $item->link = $url;
                $item->description = $evtdesc;
                $item->category = $evcategory;
                //$item->date                      = date ("l, F jS", strtotime ($cdate));
                $item->author = pnUserGetVar(
                    'uname', $peid);
                $item->descriptionTruncSize = 500;
                $item->descriptionHtmlSyndicated = true;
                $rss->addItem($item);
            }
        }
    }

    // TODO Actually save this in a logical place, and make sure the cached version is reachable and downloadable through
    // some backend process.
    $rss->saveFeed('RSS2.0', dirname(__FILE__) . '/rsstmp.xml', true);
    return true;
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
    if (!$template_view) $template_view = 'month';
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