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
 * This is the event handler api
 **/

/**
 * postcalendar_eventapi_queryEvents //new name
 * Returns an array containing the event's information (plural or singular?)
 * @param array $args arguments. Expected keys:
 *              eventstatus: -1 == hidden ; 0 == queued ; 1 == approved (default)
 *              start: Events start date (default today)
 *              end: Events end_date (default 000-00-00)
 *              s_keywords: search info
 *              s_category: search info
 *              s_topic: search info
 * @return array The events
 */
function postcalendar_eventapi_queryEvents($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $end = '0000-00-00';
    extract($args);

    //CAH we should not be getting form values in an API function
    if (pnModGetVar('PostCalendar', 'pcAllowUserCalendar')) { $filterdefault = _PC_FILTER_ALL; } else { $filterdefault = _PC_FILTER_GLOBAL; }
    $pc_username = FormUtil::getPassedValue('pc_username', $filterdefault); // poorly named var now because actually an int userid/constant
    if (!pnUserLoggedIn()) $pc_username = _PC_FILTER_GLOBAL;
    $topic       = FormUtil::getPassedValue('pc_topic');
    $category    = FormUtil::getPassedValue('pc_category');

    $userid = pnUserGetVar('uid');
    unset($ruserid);

    // convert $pc_username to useable information
    if ($pc_username > 0) {
        // possible values: a user id - only an admin can use this
        $ruserid = $pc_username; // keep the id
        $pc_username = _PC_FILTER_PRIVATE;
    } else {
        /* possible values:
            _PC_FILTER_GLOBAL (0)   = all public events
            _PC_FILTER_ALL (-1)     = all public events + my events
            _PC_FILTER_PRIVATE (-2) = just my private events
        */
        $ruserid = $userid; // use current user's ID
    }

    if (!isset($eventstatus) || ((int) $eventstatus < -1 || (int) $eventstatus > 1)) $eventstatus = 1;

    if (!isset($start)) $start = Date_Calc::dateNow('%Y-%m-%d');
    list($sy, $sm, $sd) = explode('-', $start);

    $where = "WHERE pc_eventstatus=$eventstatus
              AND (pc_endDate>='$start' 
              OR (pc_endDate='0000-00-00' 
              AND pc_recurrtype<>'0') 
              OR pc_eventDate>='$start')
              AND pc_eventDate<='$end' ";

    // filter event display based on selection
    /* possible event sharing values @v5.8
    define('SHARING_PRIVATE',       0);
    define('SHARING_PUBLIC',        1); //remove in v6.0 - convert to SHARING_GLOBAL
    define('SHARING_BUSY',          2); //remove in v6.0 - convert to SHARING_PRIVATE
    define('SHARING_GLOBAL',        3);
    define('SHARING_HIDEDESC',      4); //remove in v6.0 - convert to SHARING_GLOBAL, delete description
    */
    switch ($pc_username) {
        case _PC_FILTER_PRIVATE:
            $where .= "AND pc_aid = $ruserid ";
            $where .= "AND pc_sharing = '" . SHARING_PRIVATE . "' ";
            break;
        case _PC_FILTER_ALL:
            $where .= "AND (pc_aid = $ruserid ";
            $where .= "OR pc_sharing = '" . SHARING_GLOBAL . "' ";
            $where .= "OR pc_sharing = '" . SHARING_HIDEDESC . "') ";
            break;
        case _PC_FILTER_GLOBAL:
        default:
            $where .= "AND (pc_sharing = '" . SHARING_GLOBAL . "' ";
            $where .= "OR pc_sharing = '" . SHARING_PUBLIC . "' ";
            $where .= "OR pc_sharing = '" . SHARING_HIDEDESC . "') ";
    }

    // Start Search functionality
    if (!empty($s_keywords)) $where .= "AND ($s_keywords) ";
    if (!empty($s_category)) $where .= "AND ($s_category) ";
    if (!empty($s_topic))    $where .= "AND ($s_topic) ";
    if (!empty($category))   $where .= "AND (tbl.pc_catid = '" . DataUtil::formatForStore($category) . "') ";
    if (!empty($topic))      $where .= "AND (tbl.pc_topic = '" . DataUtil::formatForStore($topic) . "') ";
    // End Search functionality

    $sort = "ORDER BY pc_meeting_id";

    // FIXME !!! < pre v5.0
    $joinInfo = array();
    $joinInfo[] = array(
                    'join_table' => 'postcalendar_categories',
                    'join_field' => 'catname',
                    'object_field_name' => 'catname',
                    'compare_field_table' => 'catid',
                    'compare_field_join' => 'catid');
    $joinInfo[] = array(
                    'join_table' => 'postcalendar_categories',
                    'join_field' => 'catdesc',
                    'object_field_name' => 'catdesc',
                    'compare_field_table' => 'catid',
                    'compare_field_join' => 'catid');
    $joinInfo[] = array(
                    'join_table' => 'postcalendar_categories',
                    'join_field' => 'catcolor',
                    'object_field_name' => 'catcolor',
                    'compare_field_table' => 'catid',
                    'compare_field_join' => 'catid');

    $events = DBUtil::selectExpandedObjectArray('postcalendar_events', $joinInfo, $where, $sort);

    // this prevents duplicate display of same event for different participants
    // in PC v5.8, I think to leave this in, but should remove in v6
    // when removing, will have to remove duplicate events in table
    $old_m_id = "NULL";
    foreach ($events as $key => $evt) {
        $new_m_id = $evt['meeting_id'];
        if (($old_m_id) && ($old_m_id != "NULL") && ($new_m_id > 0) && ($old_m_id == $new_m_id)) {
            $old_m_id = $new_m_id;
            unset($events[$key]);
        }
        $events[$key] = pnModAPIFunc('PostCalendar', 'event', 'fixEventDetails', $events[$key]);
        $old_m_id = $evt['meeting_id'];
    }
    return $events;
}

/**
 * I believe this function returns an array of events sorted by date
 * expected args (from postcalendar_userapi_buildView): start, end
 *    if either is present, both must be present. else uses today's/jumped date.
 * expected args (from pnsearch/postcalendar.php/search_postcalendar): s_keywords, s_category, s_topic
 * same ^^^ in (postcalendar_user_search)
 **/
function postcalendar_eventapi_getEvents($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $s_keywords = $s_category = $s_topic = '';
    extract($args);
    //not sure these three lines are needed with call to getDate here
    // don't like getPassedValue in api function
    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');
    $Date  = FormUtil::getPassedValue('Date');
    $date  = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));

    $cy = substr($date, 0, 4);
    $cm = substr($date, 4, 2);
    $cd = substr($date, 6, 2);

    if (isset($start) && isset($end)) {
        // parse start date
        list($sm, $sd, $sy) = explode('/', $start);
        // parse end date
        list($em, $ed, $ey) = explode('/', $end);

        $s = (int) "$sy$sm$sd";
        if ($s > $date) {
            $cy = $sy;
            $cm = $sm;
            $cd = $sd;
        }
        $start_date = Date_Calc::dateFormat($sd, $sm, $sy, '%Y-%m-%d');
        $end_date = Date_Calc::dateFormat($ed, $em, $ey, '%Y-%m-%d');
    } else {
        $sm = $em = $cm;
        $sd = $ed = $cd;
        $sy = $cy;
        $ey = $cy + 2;
        $start_date = $sy . '-' . $sm . '-' . $sd;
        $end_date = $ey . '-' . $em . '-' . $ed;
    }
    if (!isset($events)) { // why would $events have a value?
        if (!isset($s_keywords)) $s_keywords = '';
        $a = array('start' => $start_date, 'end' => $end_date, 's_keywords' => $s_keywords, 's_category' => $s_category, 's_topic' => $s_topic);
        $events = pnModAPIFunc('PostCalendar', 'event', 'queryEvents', $a);
    }

    //==============================================================
    // Here an array is built consisting of the date ranges
    // specific to the current view.  This array is then
    // used to build the calendar display.
    //==============================================================
    $days = array();
    $sday = Date_Calc::dateToDays($sd, $sm, $sy);
    $eday = Date_Calc::dateToDays($ed, $em, $ey);
    for ($cday = $sday; $cday <= $eday; $cday++) {
        $d = Date_Calc::daysToDate($cday, '%d');
        $m = Date_Calc::daysToDate($cday, '%m');
        $y = Date_Calc::daysToDate($cday, '%Y');
        $store_date = Date_Calc::dateFormat($d, $m, $y, '%Y-%m-%d');
        $days[$store_date] = array();
    }

    foreach ($events as $event) {
        // get the name of the topic
        if (!empty($event['topic'])) $topicname = DBUtil::selectFieldByID('topics', 'topicname', $event['topic'], 'topicid');
        // get the user id of event's author
        $cuserid = pnUserGetIDFromName( strtolower($event['informant'])); // change this to aid? for v6.0?

        // check the current event's permissions
        // the user does not have permission to view this event
        // if any of the following evaluate as false
        if (!pnSecAuthAction(0, 'PostCalendar::Event', "{$event['title']}::{$event['eid']}", ACCESS_OVERVIEW)) {
            continue;
        } elseif (!pnSecAuthAction(0, 'PostCalendar::Category', "$event[catname]::$event[catid]", ACCESS_OVERVIEW)) {
            continue;
        } elseif (!pnSecAuthAction(0, 'PostCalendar::User', "$event[uname]::$cuserid", ACCESS_OVERVIEW)) {
            continue;
        } elseif (!pnSecAuthAction(0, 'PostCalendar::Topic', "$topicname::$event[topic]", ACCESS_OVERVIEW)) {
            continue;
        }
        // parse the event start date
        list($esY, $esM, $esD) = explode('-', $event['eventDate']);
        // grab the recurring specs for the event
        $event_recurrspec = unserialize($event['recurrspec']);
        // determine the stop date for this event
        if ($event['endDate'] == '0000-00-00') {
            $stop = $end_date;
        } else {
            $stop = $event['endDate'];
        }

        switch ($event['recurrtype']) {
            //==============================================================
            // Events that do not repeat only have a startday
            //==============================================================
            case NO_REPEAT:
                if (isset($days[$event['eventDate']])) {
                    array_push($days[$event['eventDate']], $event); //CAH this line has no meaning. it seems backward and pushes the same value
                }
                break;
            //==============================================================
            // Find events that repeat at a certain frequency
            // Every,Every Other,Every Third,Every Fourth
            // Day,Week,Month,Year,MWF,TR,M-F,SS
            //==============================================================
            case REPEAT:
                $rfreq = $event_recurrspec['event_repeat_freq'];
                $rtype = $event_recurrspec['event_repeat_freq_type'];
                // we should bring the event up to date to make this a tad bit faster
                // any ideas on how to do that, exactly??? dateToDays probably.
                $nm = $esM;
                $ny = $esY;
                $nd = $esD;
                $occurance = Date_Calc::dateFormat($nd, $nm, $ny, '%Y-%m-%d');
                while ($occurance < $start_date) {
                    $occurance = dateIncrement($nd, $nm, $ny, $rfreq, $rtype);
                    list($ny, $nm, $nd) = explode('-', $occurance);
                }
                while ($occurance <= $stop) {
                    if (isset($days[$occurance])) {
                        array_push($days[$occurance], $event);
                    }
                    $occurance = dateIncrement($nd, $nm, $ny, $rfreq, $rtype);
                    list($ny, $nm, $nd) = explode('-', $occurance);
                }
                break;
            //==============================================================
            // Find events that repeat on certain parameters
            // On 1st,2nd,3rd,4th,Last
            // Sun,Mon,Tue,Wed,Thu,Fri,Sat
            //    Every N Months
            //==============================================================
            case REPEAT_ON:
                $rfreq = $event_recurrspec['event_repeat_on_freq'];
                $rnum = $event_recurrspec['event_repeat_on_num'];
                $rday = $event_recurrspec['event_repeat_on_day'];
                //==============================================================
                // Populate - Enter data into the event array
                //==============================================================
                $nm = $esM;
                $ny = $esY;
                $nd = $esD;
                // make us current
                while ($ny < $cy) {
                    $occurance = date('Y-m-d', mktime(0, 0, 0, $nm + $rfreq, $nd, $ny));
                    list($ny, $nm, $nd) = explode('-', $occurance);
                }
                // populate the event array
                while ($ny <= $cy) {
                    $dnum = $rnum; // get day event repeats on
                    do {
                        $occurance = Date_Calc::NWeekdayOfMonth(
                            $dnum--,
                            $rday, $nm,
                            $ny,
                            "%Y-%m-%d");
                    } while ($occurance === -1);
                    if (isset($days[$occurance]) && $occurance <= $stop) {
                        array_push($days[$occurance], $event);
                    }
                    $occurance = date('Y-m-d',
                        mktime(0, 0, 0, $nm + $rfreq,
                            $nd, $ny));
                    list($ny, $nm, $nd) = explode('-', $occurance);
                }
                break;
        } // <- end of switch($event['recurrtype'])
    } // <- end of foreach($events as $event)
    return $days;
}
/**
 * postcalendar_eventapi_writeEvent()
 * write an event to the DB
 * @param $args array of event data
 * @return bool true on success : false on failure;
 */
function postcalendar_eventapi_writeEvent($args)
{
    //TODO: remove use of $_POST vars
    // remove multiple event inserts based on participants (ticket filed)

    $event_for_userid = $_POST['event_for_userid']; // gets the value out of the event_for_userid dropdown :: becomes aid?

    extract($args);
    unset($args);

    define('PC_ACCESS_ADMIN', pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN));

    // determine if the event is to be published immediately or not
    if ((bool) _SETTING_DIRECT_SUBMIT || (bool) PC_ACCESS_ADMIN || ($event_sharing != SHARING_GLOBAL)) {
        $event_status = _EVENT_APPROVED;
    } else {
        $event_status = _EVENT_QUEUED;
    }

    // set up some vars for the insert statement
    $startDate = $event_startyear . '-' . $event_startmonth . '-' . $event_startday;
    if ($event_endtype == 1) {
        $endDate = $event_endyear . '-' . $event_endmonth . '-' . $event_endday;
    } else {
        $endDate = '0000-00-00';
    }

    if (!isset($event_allday)) $event_allday = 0;

    if ((bool) _SETTING_TIME_24HOUR) {
        $startTime = $event_starttimeh . ':' . $event_starttimem . ':00';
    } else {
        if ($event_startampm == _AM_VAL) {
            $event_starttimeh = $event_starttimeh == 12 ? '00' : $event_starttimeh;
        } else {
            $event_starttimeh = $event_starttimeh != 12 ? $event_starttimeh += 12 : $event_starttimeh;
        }
    }

    $startTime = sprintf('%02d', $event_starttimeh) . ':' . sprintf('%02d', $event_starttimem) . ':00';

    $event_userid = $event_for_userid;

    if (empty($event_desc)) {
        $event_desc .= 'n/a'; // default description
    } else {
        $event_desc = ':' . $pc_html_or_text . ':' . $event_desc; // inserts :text:/:html: before actual content
    }

    $pc_meeting_id = 0; // can pull this out if the column in the DB is removed.

    if (!isset($is_update)) $is_update = false;

    // build an array of users for mail notification
    $pc_mail_users = array();

    $eventarray = array(
        'title' => $event_subject,
        'hometext' => $event_desc,
        'topic' => (int) $event_topic,
        'eventDate' => $startDate,
        'endDate' => $endDate,
        'recurrtype' => (int) $event_repeat,
        'startTime' => $startTime,
        'alldayevent' => (int) $event_allday,
        'catid' => (int) $event_category,
        'location' => $event_location_info,
        'conttel' => $event_conttel,
        'contname' => $event_contname,
        'contemail' => $event_contemail,
        'website' => $event_website,
        'fee' => $event_fee,
        'eventstatus' => (int) $event_status,
        'recurrspec' => $event_recurrspec,
        'duration' => (int) $event_duration,
        'sharing' => (int) $event_sharing,
        'aid' => $event_for_userid,
    );
    if ($is_update) {
        $eventarray['eid'] = $eid;
        $result = pnModAPIFunc('postcalendar', 'event', 'update', array($eid => $eventarray));
    } else { //new event
        unset ($eventarray['eid']); //be sure that eid is not set on insert op to autoincrement value
        $eventarray['time'] = date("Y-m-d H:i:s"); //current date
        $eventarray['informant'] = $uname; // @v6.0 change this to uid
        $eventarray['meeting_id'] = $pc_meeting_id;
        $result = pnModAPIFunc('postcalendar', 'event', 'create', $eventarray);
        if (pnUserGetVar('uname', $event_for_userid) != $uname) {
            $pc_mail_users[] = $event_for_userid; // add intended user to notify list
            $pc_mail_events[] = $result['eid'];
        }
    }
    if ($result === false) {
        // post some kind of error message...
        return false;
    }

    $eid = $result['eid']; // set eid to last event submitted

    pnModAPIFunc('PostCalendar','admin','notify',compact('eid','is_update')); //notify admin and informant

    return true;
}

/**
 * postcalendar_eventapi_buildSubmitForm()
 * this is also used on a preview of event function, so $eventdata is passed from that if 'loaded'
 * create event submit form
 */
function postcalendar_eventapi_buildSubmitForm($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    extract($args);
    unset($args);

    if (!$admin) $admin = false; //reset default value

    // Turn off template caching here
    $tpl = pnRender::getInstance('PostCalendar', false);
    pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);

    // V4B RNG start

    $endDate = $event_endyear . $event_endmonth . $event_endday;
    //not sure these three lines are needed with call to getDate here
    // don't like getPassedValue in api function
    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');
    $Date  = FormUtil::getPassedValue('Date');
    $today  = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));
    if (($endDate == '') || ($endDate == '00000000')) {
        $endvalue = substr($today, 6, 2) . '-' . substr($today, 4, 2) . '-' . substr($today, 0, 4);
        // V4B RNG: build other date format for JS cal
        $endDate = substr($today, 0, 4) . '-' . substr($today, 4, 2) . '-' . substr($today, 6, 2);
    } else {
        $endvalue = substr($endDate, 6, 2) . '-' . substr($endDate, 4, 2) . '-' . substr($endDate, 0, 4);
        // V4B RNG: build other date format for JS cal
        $endDate = substr($endDate, 0, 4) . '-' . substr($endDate, 4, 2) . '-' . substr($endDate, 6, 2);
    }
    $tpl->assign('endvalue', $endvalue);
    $tpl->assign('endDate', $endDate);

    $startdate = $event_startyear . $event_startmonth . $event_startday;
    //$today = postcalendar_getDate(); //already set 14 lines above
    if ($startdate == '') {
        $startvalue = substr($today, 6, 2) . '-' . substr($today, 4, 2) . '-' . substr($today, 0, 4);
        // V4B RNG: build other date format for JS cal
        $startdate = substr($today, 0, 4) . '-' . substr($today, 4, 2) . '-' . substr($today, 6, 2);
    } else {
        $startvalue = substr($startdate, 6, 2) . '-' . substr($startdate, 4, 2) . '-' . substr($startdate, 0, 4);
        // V4B RNG: build other date format for JS cal
        $startdate = substr($startdate, 0, 4) . '-' . substr($startdate, 4, 2) . '-' . substr($startdate, 6, 2);
    }
    $tpl->assign('startvalue', $startvalue);
    $tpl->assign('startdate', $startdate);

    //================================================================
    // build the userlist select box
    // the purpose of this box is to allow user to create a private event for another user
    // this should be configurable by admin to allow/deny
    // if denied, selected user should default to submittor
    //================================================================

    $event_for_userid = (int) DBUtil::selectFieldByID('postcalendar_events', 'aid', $eid, 'eid');
    $uid = pnUserGetVar('uid');
    $uname = pnUserGetVar('uname');
    $idsel = ($event_for_userid ? $event_for_userid : $uid);

    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) 
    {
        @define('_PC_FORM_USERNAME', true); // this is used in pc_form_nav_close plugin, but don't know why
        $users = DBUtil::selectFieldArray('users', 'uname', null, null, null, 'uid');
        $tpl->assign('users', $users);
    }
    $tpl->assign('username_selected', pnUsergetVar('uname', $idsel));
    $tpl->assign('user_selected', $idsel);

    //=================================================================
    // PARSE MAIN
    //=================================================================
    $tpl->assign('VIEW_TYPE', ''); // E_ALL Fix
    $tpl->assign('FUNCTION', FormUtil::getPassedValue('func'));
    $tpl->assign('ModuleName', $modname);
    $tpl->assign('ModuleDirectory', $modir);

    $all_categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
    $tpl->assign('category', $all_categories);

    //=================================================================
    // PARSE INPUT_EVENT_TITLE
    //=================================================================
    $tpl->assign('ValueEventTitle', DataUtil::formatForDisplay($event_subject));

    //=================================================================
    // PARSE SELECT_DATE_TIME
    //=================================================================
    $tpl->assign('SelectedAllday', $event_allday == 1 ? 'checked' : '');
    $tpl->assign('SelectedTimed', $event_allday == 0 ? 'checked' : '');

    //=================================================================
    // PARSE SELECT_TIMED_EVENT
    //=================================================================
    $tpl->assign('minute_interval', _SETTING_TIME_INCREMENT);
    if (empty($event_starttimeh)) {
        $event_starttimeh = "01";
        $event_starttimem = "00";
        $event_startampm = _AM_VAL;
    }
    if ((!empty($event_startampm)) && ($event_startampm == _PM_VAL)) $event_starttimeh = $event_starttimeh + 12;
    $tpl->assign('SelectedTime', $event_starttimeh . ":" . $event_starttimem);

    //=================================================================
    // PARSE SELECT_DURATION
    //=================================================================

    if (!$event_dur_hours) $event_dur_hours = 1; // provide a reasonable default rather than 0 hours
    if (!$event_dur_minutes) $event_dur_minutes = 00; // provide a reasonable default rather than 0 hours
    $tpl->assign('event_duration', $event_dur_hours.":".$event_dur_minutes);

    //=================================================================
    // PARSE INPUT_EVENT_DESC
    //=================================================================
    if (empty($pc_html_or_text)) {
        $display_type = substr($event_desc, 0, 6);
        if ($display_type == ':text:') {
            $pc_html_or_text = 'text';
            $event_desc = substr($event_desc, 6);
        } elseif ($display_type == ':html:') {
            $pc_html_or_text = 'html';
            $event_desc = substr($event_desc, 6);
        } else
            $pc_html_or_text = 'text';

        unset($display_type);
    }

    $tpl->assign('ValueEventDesc', DataUtil::formatForDisplay($event_desc));

    $eventHTMLorText = array('text' => __('Plain Text', $dom), 'html' => __('HTML', $dom));
    $tpl->assign('EventHTMLorText', $eventHTMLorText);
    $tpl->assign('EventHTMLorTextVal', $pc_html_or_text);

    //=================================================================
    // PARSE select_event_topic_block
    //=================================================================
    $tpl->assign('displayTopics', _SETTING_DISPLAY_TOPICS);
    if ((bool) _SETTING_DISPLAY_TOPICS) {
        $a_topics = pnModAPIFunc('PostCalendar', 'user', 'getTopics');
        $topics = array();
        foreach ($a_topics as $topic) {
            array_push($topics,
                array('value' => $topic['topicid'],
                                'selected' => ($topic['topicid'] == $event_topic ? 'selected' : ''),
                                'name' => $topic['topictext']));
        }
        unset($a_topics);
        // only show this if we have topics to show
        if (count($topics) > 0) {
            $tpl->assign('topics', $topics);
        }
    }

    //=================================================================
    // PARSE select_event_type_block
    //=================================================================
    $categories = array();
    foreach ($all_categories as $category) {
        $categories[$category['catid']] = $category['catname'];
    }
    if (count($categories) > 0) {
        $tpl->assign('categories', $categories);
    }
    //$event_category is selected category id
    $tpl->assign('event_category', $event_category);

    //=================================================================
    // PARSE event_sharing_block
    //=================================================================
    $data = array();
    if (_SETTING_ALLOW_USER_CAL) {
        $data[SHARING_PRIVATE]=__('Private', $dom);
        //$data[SHARING_PUBLIC]=__('Public', $dom);
        //$data[SHARING_BUSY]=__('Show as Busy', $dom);
    }

    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN) || _SETTING_ALLOW_GLOBAL || !_SETTING_ALLOW_USER_CAL) {
        $data[SHARING_GLOBAL]=__('Global', $dom);
        //$data[SHARING_HIDEDESC]=__('Global, description private', $dom);
    }
    $tpl->assign('sharingselect', $data);

    if (!isset($event_sharing)) $event_sharing = SHARING_GLOBAL;
    $tpl->assign('event_sharing', $event_sharing);

    //=================================================================
    // location information
    //=================================================================
    $tpl->assign('ValueLocation', DataUtil::formatForDisplay($event_location));
    $tpl->assign('ValueStreet1', DataUtil::formatForDisplay($event_street1));
    $tpl->assign('ValueStreet2', DataUtil::formatForDisplay($event_street2));
    $tpl->assign('ValueCity', DataUtil::formatForDisplay($event_city));
    $tpl->assign('ValueState', DataUtil::formatForDisplay($event_state));
    $tpl->assign('ValuePostal', DataUtil::formatForDisplay($event_postal));
    //=================================================================
    // contact information
    //=================================================================
    $tpl->assign('ValueContact', DataUtil::formatForDisplay($event_contname));
    $tpl->assign('ValuePhone', DataUtil::formatForDisplay($event_conttel));
    $tpl->assign('ValueEmail', DataUtil::formatForDisplay($event_contemail));
    $tpl->assign('ValueWebsite', DataUtil::formatForDisplay($event_website));
    $tpl->assign('ValueFee', DataUtil::formatForDisplay($event_fee));
    //=================================================================
    // Repeating Information
    //=================================================================
    $tpl->assign('SelectedNoRepeat', (int) $event_repeat == 0 ? 'checked' : '');
    $tpl->assign('SelectedRepeat', (int) $event_repeat == 1 ? 'checked' : '');

    unset($in);
    $in = explode ("/", __('Every/Every Other/Every Third/Every Fourth', $dom));
    $keys = array(REPEAT_EVERY, REPEAT_EVERY_OTHER, REPEAT_EVERY_THIRD, REPEAT_EVERY_FOURTH);
    $repeat_freq = array();
    foreach ($in as $k => $v)
        array_push($repeat_freq, array('value' => $keys[$k], 'selected' => ($keys[$k] == $event_repeat_freq ? 'selected' : ''), 'name' => $v));

    if (empty($event_repeat_freq) || $event_repeat_freq < 1) $event_repeat_freq = 1;
    $tpl->assign('InputRepeatFreqVal', $event_repeat_freq);
    $tpl->assign('repeat_freq', $repeat_freq);

    unset($in);
    $in = explode ("/", __('Day(s)/Week(s)/Month(s)/Year(s)', $dom));
    $keys = array(REPEAT_EVERY_DAY, REPEAT_EVERY_WEEK, REPEAT_EVERY_MONTH, REPEAT_EVERY_YEAR);
    $repeat_freq_type = array();
    foreach ($in as $k => $v)
        array_push($repeat_freq_type, array('value' => $keys[$k], 'selected' => ($keys[$k] == $event_repeat_freq_type ? 'selected' : ''), 'name' => $v));

    $tpl->assign('repeat_freq_type', $repeat_freq_type);
    $tpl->assign('SelectedRepeatOn', (int) $event_repeat == 2 ? 'checked' : '');

    unset($in);
    $in = explode ("/", __('First/Second/Third/Fourth/Last', $dom));
    $keys = array(REPEAT_ON_1ST, REPEAT_ON_2ND, REPEAT_ON_3RD, REPEAT_ON_4TH, REPEAT_ON_LAST);
    $repeat_on_num = array();
    foreach ($in as $k => $v)
        array_push($repeat_on_num, array('value' => $keys[$k], 'selected' => ($keys[$k] == $event_repeat_on_num ? 'selected' : ''), 'name' => $v));

    $tpl->assign('repeat_on_num', $repeat_on_num);

    unset($in);
    $in = explode (" ", __('Sun Mon Tue Wed Thu Fri Sat', $dom));
    $keys = array(REPEAT_ON_SUN, REPEAT_ON_MON, REPEAT_ON_TUE, REPEAT_ON_WED, REPEAT_ON_THU, REPEAT_ON_FRI, REPEAT_ON_SAT);
    $repeat_on_day = array();
    foreach ($in as $k => $v)
        array_push($repeat_on_day, array('value' => $keys[$k], 'selected' => ($keys[$k] == $event_repeat_on_day ? 'selected' : ''), 'name' => $v));

    $tpl->assign('repeat_on_day', $repeat_on_day);

    unset($in);
    $in = explode ("/", __('month/other month/3 months/4 months/6 months/year', $dom));
    $keys = array(REPEAT_ON_MONTH, REPEAT_ON_2MONTH, REPEAT_ON_3MONTH, REPEAT_ON_4MONTH, REPEAT_ON_6MONTH, REPEAT_ON_YEAR);
    $repeat_on_freq = array();
    foreach ($in as $k => $v)
        array_push($repeat_on_freq, array('value' => $keys[$k], 'selected' => ($keys[$k] == $event_repeat_on_freq ? 'selected' : ''), 'name' => $v));

    if (empty($event_repeat_on_freq) || $event_repeat_on_freq < 1) $event_repeat_on_freq = 1;
    $tpl->assign('InputRepeatOnFreqVal', $event_repeat_on_freq);
    $tpl->assign('repeat_on_freq', $repeat_on_freq);

    //=================================================================
    // PARSE INPUT_END_DATE
    //=================================================================
    $tpl->assign('SelectedEndOn', (int) $event_endtype == 1 ? 'checked' : '');
    //=================================================================
    // PARSE INPUT_NO_END
    //=================================================================
    $tpl->assign('SelectedNoEnd', (int) $event_endtype == 0 ? 'checked' : '');

    $tpl->assign('is_update', $is_update);
    $tpl->assign('eid', $eid);
    if (isset($data_loaded)) {
        $tpl->assign('data_loaded', $data_loaded);
    }

    $tpl->assign('preview', $preview);

    // Assign the content format
    $formattedcontent = pnModAPIFunc('PostCalendar', 'event', 'isformatted', array('func' => 'new'));
    $tpl->assign('formattedcontent', $formattedcontent);

    return $tpl->fetch("event/postcalendar_event_submit.html");
}
/**
 * @function postcalendar_eventapi_fixEventDetails
 * @description modify the way some of the event details are displayed based on other variables
 * @param array event   array of event data
 * @return array event  modified array of event data
 */
function postcalendar_eventapi_fixEventDetails($event)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // there has to be a more intelligent way to do this
    @list($event['duration_hours'], $dmin) = @explode('.', ($event['duration'] / 60 / 60));
    $event['duration_minutes'] = substr(sprintf('%.2f', '.' . 60 * ($dmin / 100)), 2, 2);
    // ---

    $suid = pnUserGetVar('uid');
    // $euid = DBUtil::selectFieldByID ('users', 'uid', $event['uname'], 'uname');
    $euid = $event['aid'];
    $IS_ADMIN = pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN);

    // with v6.0 this whole section below should be reworked - private events are handled differently
    // is this a public event to be shown as busy?
    if ($event['sharing'] == SHARING_PRIVATE && $euid != $suid && !$IS_ADMIN) {
        // they are not supposed to see this
        return false;
    } elseif ($event['sharing'] == SHARING_BUSY && $euid != $suid && !$IS_ADMIN) {
        // make it not display any information
        $event['title'] = __('Busy', $dom);
        $event['hometext'] = __('I am busy during this time.', $dom);
        $event['desc'] = __('I am busy during this time.', $dom);

        $fields = array('event_location', 'conttel', 'contname', 'contemail', 'website', 'fee', 'event_street1',
                        'event_street2', 'event_city',
                        'event_state', 'event_postal');
        foreach ($fields as $field)
            $event[$field] = '';
    } else {
        // FIXME: this entire thing should be a sub-array
        if (!empty($location)) {
           $location = unserialize($event['location']);
           $event['event_location'] = $location['event_location'];
           $event['event_street1'] = $location['event_street1'];
           $event['event_street2'] = $location['event_street2'];
           $event['event_city'] = $location['event_city'];
           $event['event_state'] = $location['event_state'];
           $event['event_postal'] = $location['event_postal'];
           //$event['date'] = str_replace('-','',$Date);
        }
    }

    return $event;
}

/**
 * @function postcalendar_eventapi_getEventDetails
 * @description 
 * @param int eid   event id
 * @return array event  array of event data
 */
function postcalendar_eventapi_getEventDetails($eid)
{
    if (!isset($eid)) return false;

    // FIXME !!!
    $joinInfo = array();
    $joinInfo[] = array(
                    'join_table' => 'postcalendar_categories',
                    'join_field' => 'catname',
                    'object_field_name' => 'catname',
                    'compare_field_table' => 'catid',
                    'compare_field_join' => 'catid');
    $joinInfo[] = array(
                    'join_table' => 'postcalendar_categories',
                    'join_field' => 'catdesc', '
                    object_field_name' => 'catdesc',
                    'compare_field_table' => 'catid',
                    'compare_field_join' => 'catid');
    $joinInfo[] = array(
                    'join_table' => 'postcalendar_categories',
                    'join_field' => 'catcolor',
                    'object_field_name' => 'catcolor',
                    'compare_field_table' => 'catid',
                    'compare_field_join' => 'catid');
    // FIXME!!!!!!
    //$joinInfo[] = array (      'join_table'            =>    'topics',
    //'join_field'            =>    'topictext',
    //'object_field_name'    =>    'topictext',
    //'compare_field_table' =>    'topicid',
    //'compare_field_join'    =>    'topic');
    $event = DBUtil::selectExpandedObjectByID('postcalendar_events', $joinInfo, $eid, 'eid');
    $event = pnModAPIFunc('PostCalendar', 'event', 'fixEventDetails', $event);
    return $event;
}

/**
 * postcalendar_userapi_eventDetail
 * Creates the detailed event display and outputs html.
 * Accepts an array of key/value pairs
 * @param int $eid the id of the event to display
 * @return string html output
 * @access public
 */
function postcalendar_eventapi_eventDetail($args)
{
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    extract($args);
    unset($args);

    if (!isset($eid)) return false;
    if (!isset($nopop)) $nopop = false;
    $uid = pnUserGetVar('uid');

    /* Trim as needed */
    $func = FormUtil::getPassedValue('func');
    $template_view = FormUtil::getPassedValue('tplview');
    if (!$template_view) $template_view = 'month';
    $function_out['FUNCTION'] = $func;
    $function_out['TPL_VIEW'] = $template_view;
    /* end */

    // get the DB information
    $event = pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $eid);
    // if the above is false, it's a private event for another user
    // we should not diplay this - so we just exit gracefully
    if ($event === false) return false;

    // since recurrevents are dynamically calculcated, we need to change the date
    // to ensure that the correct/current date is being displayed (rather than the
    // date on which the recurring booking was executed).
    if ($event['recurrtype']) {
        $y = substr($Date, 0, 4);
        $m = substr($Date, 4, 2);
        $d = substr($Date, 6, 2);
        $event['eventDate'] = "$y-$m-$d";
    }

    // populate the template
    // prep the vars for output
    $display_type = substr($event['hometext'], 0, 6);
    $event['hometext'] = substr($event['hometext'], 6);
    if ($display_type==":text:") {
        $event['hometext']  = nl2br(strip_tags($event['hometext']));
    }
    $event['desc']      = $event['hometext'];
    $event['title']     = DataUtil::formatForDisplay($event['title']);
    $event['conttel']   = DataUtil::formatForDisplay($event['conttel']);
    $event['contname']  = DataUtil::formatForDisplay($event['contname']);
    $event['contemail'] = DataUtil::formatForDisplay($event['contemail']);
    $event['website']   = DataUtil::formatForDisplay(makeValidURL($event['website']));
    $event['fee']       = DataUtil::formatForDisplay($event['fee']);
    $event['location']  = DataUtil::formatForDisplay($event['event_location']);
    $event['street1']   = DataUtil::formatForDisplay($event['event_street1']);
    $event['street2']   = DataUtil::formatForDisplay($event['event_street2']);
    $event['city']      = DataUtil::formatForDisplay($event['event_city']);
    $event['state']     = DataUtil::formatForDisplay($event['event_state']);
    $event['postal']    = DataUtil::formatForDisplay($event['event_postal']);
    $function_out['A_EVENT'] = $event;

    if (!empty($event['location']) || !empty($event['street1']) || !empty($event['street2']) || !empty($event['city']) || !empty(
        $event['state']) || !empty($event['postal'])) {
        $function_out['LOCATION_INFO'] = true;
    } else {
        $function_out['LOCATION_INFO'] = false;
    }
    if (!empty($event['contname']) || !empty($event['contemail']) || !empty($event['conttel']) || !empty($event['website'])) {
        $function_out['CONTACT_INFO'] = true;
    } else {
        $function_out['CONTACT_INFO'] = false;
    }

    if (pnUserLoggedIn()) {
        $logged_in_uid = pnUserGetVar('uid');
    } else {
        $logged_in_uid = 1;
    }

    $user_edit_url = $user_delete_url = $user_copy_url = '';
    $can_edit = false;
    if ((pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD) && $logged_in_uid == $event['aid'])
        || pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $user_edit_url = pnModURL('PostCalendar', 'event', 'edit', array('eid' => $eid));
        $user_delete_url = pnModURL('PostCalendar', 'event', 'delete', array('eid' => $eid));
        $user_copy_url = pnModURL('PostCalendar', 'event', 'new', array('eid' => $eid, 'form_action' => 'copy'));
        $can_edit = true;
    }
    $function_out['EVENT_COPY'] = $user_copy_url;
    $function_out['EVENT_EDIT'] = $user_edit_url;
    $function_out['EVENT_DELETE'] = $user_delete_url;
    $function_out['EVENT_CAN_EDIT'] = $can_edit;

    return $function_out;
}

/**
 * postcalendar_eventapi_create()
 * This function creates a new event row in the DB
 * expected args: obj=array([colname]=>[newval],[colname]=>[newval],[colname]=>[newval], etc...)
 *
 *  returns the created object with updated id field
 */
function postcalendar_eventapi_create($obj)
{
    if (!is_array($obj)) return false;
    $res = DBUtil::insertObject($obj, 'postcalendar_events', 'eid');
    if ($res) {
        return $res;
    } else {
        return false;
    }
}

/**
 * postcalendar_eventapi_update()
 * This function updates many events at once with any new values...
 * expected args: eventarray=array([id]=>array([id]=>[idval],[colname]=>[newval],
 *     [id2]=>array([id2]=>[idval],[colname]=>[newval])
 *
 *  returns the updated object(s)
 */
function postcalendar_eventapi_update($eventarray)
{
    if (!is_array($eventarray)) return false;
    $res = DBUtil::updateObjectArray($eventarray, 'postcalendar_events', 'eid');
    if ($res) {
        return $res;
    } else {
        return false;
    }
}

/**
 * postcalendar_eventapi_deleteevent
 * This function deletes one event provided the event ID (eid)
 * expected args: args=array(['eid']=>idval)
 *
 */
function postcalendar_eventapi_deleteevent($args)
{
    return DBUtil::deleteObjectByID('postcalendar_events', $args['eid'], 'eid');
}

/**
 * postcalendar_eventapi_deleteeventarray
 * This function deletes several events when provided an array of ids
 * expected args: args=array([idval]=>val,[idval2]=>val,[idval3]=>val...)
 * note the vals are not used. just the keys
 *
 */
function postcalendar_eventapi_deleteeventarray($args)
{
    if (!is_array($args)) return false;
    return DBUtil::deleteObjectsFromKeyArray($args, 'postcalendar_events', 'eid');
}
/**
 * makeValidURL()
 * returns 'improved' url based on input string
 * checks to make sure scheme is present
 * @private
 * @returns string
 */
if (!function_exists('makeValidURL')) { // also defined in pnadminapi.php
    function makeValidURL($s)
    {
        if (empty($s)) return '';
        if (!preg_match('|^http[s]?:\/\/|i', $s)) $s = 'http://' . $s;
        return $s;
    }
}
/**
 * dateIncrement()
 * returns the next valid date for an event based on the
 * current day,month,year,freq and type
 * @private
 * @returns string YYYY-MM-DD
 */
function dateIncrement($d, $m, $y, $f, $t)
{
    if ($t == REPEAT_EVERY_DAY) {
        return date('Y-m-d', mktime(0, 0, 0, $m, ($d + $f), $y));
    } elseif ($t == REPEAT_EVERY_WEEK) {
        return date('Y-m-d', mktime(0, 0, 0, $m, ($d + (7 * $f)), $y));
    } elseif ($t == REPEAT_EVERY_MONTH) {
        return date('Y-m-d', mktime(0, 0, 0, ($m + $f), $d, $y));
    } elseif ($t == REPEAT_EVERY_YEAR) {
        return date('Y-m-d', mktime(0, 0, 0, $m, $d, ($y + $f)));
    }
}

/**
 * postcalendar_eventapi_isformatted
 * This function is copied directly from the News module
 * credits to Jorn Wildt, Mark West, Philipp Niethammer or whoever wrote it
 *
 * @purpose analyze if the module has an Scribite! editor assigned
 * @param string func the function to check
 * @return bool
 * @access public
 */
function postcalendar_eventapi_isformatted($args)
{
    if (!isset($args['func'])) {
        $args['func'] = 'all';
    }

    if (pnModAvailable('scribite')) {
        $modinfo = pnModGetInfo(pnModGetIDFromName('scribite'));
        if (version_compare($modinfo['version'], '2.2', '>=')) {
            $apiargs = array('modulename' => 'PostCalendar'); // parameter handling corrected in 2.2
        } else {
            $apiargs = 'PostCalendar'; // old direct parameter
        }

        $modconfig = pnModAPIFunc('scribite', 'user', 'getModuleConfig', $apiargs);
        if (in_array($args['func'], (array)$modconfig['modfuncs']) && $modconfig['modeditor'] != '-') {
            return true;
        }
    }
    return false;
}