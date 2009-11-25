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
                filtercats: categories to query events from
 * @return array The events
 */
function postcalendar_eventapi_queryEvents($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $end = '0000-00-00';
    extract($args); //start, end, s_keywords, filtercats, pc_username, eventstatus

    if (_SETTING_ALLOW_USER_CAL) { 
        $filterdefault = _PC_FILTER_ALL; 
    } else { 
        $filterdefault = _PC_FILTER_GLOBAL;
    }
    if (empty($pc_username)) $pc_username = $filterdefault;
    if (!pnUserLoggedIn()) $pc_username = _PC_FILTER_GLOBAL;

    $userid = pnUserGetVar('uid');
    unset($ruserid);

    // convert $pc_username to useable information
    /* possible values:
        _PC_FILTER_GLOBAL (-1)  = all public events
        _PC_FILTER_ALL (-2)     = all public events + my events
        _PC_FILTER_PRIVATE (-3) = just my private events
    */
    if ($pc_username > 0) {
        // possible values: a user id - only an admin can use this
        $ruserid = $pc_username; // keep the id
        $pc_username = _PC_FILTER_PRIVATE;
    } else {
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
    define('SHARING_HIDEDESC',      4); //remove in v6.0 - convert to SHARING_PRIVATE
    */
    switch ($pc_username) {
        case _PC_FILTER_PRIVATE: // show just private events
            $where .= "AND pc_aid = $ruserid ";
            $where .= "AND (pc_sharing = '" . SHARING_PRIVATE . "' ";
            $where .= "OR pc_sharing = '" . SHARING_BUSY . "' "; //deprecated
            $where .= "OR pc_sharing = '" . SHARING_HIDEDESC . "') "; //deprecated
            break;
        case _PC_FILTER_ALL:  // show all public/global AND private events
            $where .= "AND (pc_aid = $ruserid ";
            $where .= "AND (pc_sharing = '" . SHARING_PRIVATE . "' ";
            $where .= "OR pc_sharing = '" . SHARING_BUSY . "' "; //deprecated
            $where .= "OR pc_sharing = '" . SHARING_HIDEDESC . "')) "; //deprecated
            $where .= "OR (pc_sharing = '" . SHARING_GLOBAL . "' ";
            $where .= "OR pc_sharing = '" . SHARING_PUBLIC . "') "; //deprecated
            break;
        case _PC_FILTER_GLOBAL: // show all public/global events
        default:
            $where .= "AND (pc_sharing = '" . SHARING_GLOBAL . "' ";
            $where .= "OR pc_sharing = '" . SHARING_PUBLIC . "') "; //deprecated
    }


    // convert categories array to proper filter info
    $catsarray = $filtercats['__CATEGORIES__'];
    foreach ($catsarray as $propname => $propid) {
        if ($propid <= 0) unset($catsarray[$propname]); // removes categories set to 'all' (0)
    }
    if (!empty($catsarray)) $catsarray['__META__']['module']="PostCalendar"; // required for search operation

    if (!empty($s_keywords)) $where .= "AND $s_keywords";

    $events = DBUtil::selectObjectArray('postcalendar_events', $where, null, null, null, null, null, $catsarray);

    foreach ($events as $key => $evt) {
        $events[$key] = pnModAPIFunc('PostCalendar', 'event', 'fixEventDetails', $events[$key]);
    }

    return $events;
}

/**
 * This function returns an array of events sorted by date
 * expected args (from postcalendar_userapi_buildView): start, end
 *    if either is present, both must be present. else uses today's/jumped date.
 * expected args (from search/postcalendar_search_options): s_keywords, filtercats
 **/
function postcalendar_eventapi_getEvents($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $s_keywords = ''; // search WHERE string
    extract($args); //start, end, filtercats, Date, s_keywords, pc_username

    $date  = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'])); //formats date

    if (!empty($s_keywords)) { unset($start); unset($end); } // clear start and end dates for search

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
        $events = pnModAPIFunc('PostCalendar', 'event', 'queryEvents', 
            array('start'=>$start_date, 'end'=>$end_date, 's_keywords'=>$s_keywords, 
                  'filtercats'=>$filtercats, 'pc_username'=>$pc_username));
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
        // get the user id of event's author
        $cuserid = pnUserGetIDFromName( strtolower($event['informant'])); // change this to aid? for v6.0?

        // check the current event's permissions
        // the user does not have permission to view this event
        // if any of the following evaluate as false
        if (!pnSecAuthAction(0, 'PostCalendar::Event', "{$event['title']}::{$event['eid']}", ACCESS_OVERVIEW)) {
            continue;
        /*} elseif (!pnSecAuthAction(0, 'PostCalendar::Category', "$event[catname]::$event[catid]", ACCESS_OVERVIEW)) {
            continue;*/
        } elseif (!pnSecAuthAction(0, 'PostCalendar::User', "$event[uname]::$cuserid", ACCESS_OVERVIEW)) {
            continue;
        }
        // parse the event start date
        list($esY, $esM, $esD) = explode('-', $event['eventDate']);
        // grab the recurring specs for the event
        $event_recurrspec = unserialize($event['recurrspec']); // <-- this is already done in fixEvent routine
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
                // any ideas on how to do that, exactly??? dateToDays probably. (RNG <5.0)
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
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    $eventdata = $args['eventdata'];
    $Date      = $args['Date'];

    define('PC_ACCESS_ADMIN', pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN));

    // determine if the event is to be published immediately or not
    if ((bool) _SETTING_DIRECT_SUBMIT || (bool) PC_ACCESS_ADMIN || ($event_sharing != SHARING_GLOBAL)) {
        $eventdata['eventstatus'] = _EVENT_APPROVED;
    } else {
        $eventdata['eventstatus'] = _EVENT_QUEUED;
    }

    // format some vars for the insert statement
    $eventdata['endDate'] = $eventdata['endtype'] == 1 ? $eventdata['endDate'] : '0000-00-00';
    unset($eventdata['endtype']);

    if (!isset($eventdata['alldayevent'])) $eventdata['alldayevent'] = 0;

    if (empty($eventdata['hometext'])) {
        $eventdata['hometext'] = __(/*!(abbr) not applicable or not available*/'n/a', $dom); // default description
    } else {
        $eventdata['hometext'] = ':'. $eventdata['html_or_text'] .':'. $eventdata['hometext']; // inserts :text:/:html: before actual content
    }

    $eventdata['location'] = serialize($eventdata['location']);
    if (!isset($eventdata['repeat']['repeatval'])) $eventdata['repeat']['repeatval'] = 0;
    $eventdata['recurrspec'] = serialize($eventdata['repeat']); unset($eventdata['repeat']);
    unset($eventdata['html_or_text']);
    unset($eventdata['data_loaded']);

    if (!isset($eventdata['is_update'])) $eventdata['is_update'] = false;

    if ($eventdata['is_update']) {
        unset($eventdata['is_update']);
        $result = pnModAPIFunc('postcalendar', 'event', 'update', array($eventdata[$eid] => $eventdata));
    } else { //new event
        unset($eventdata['eid']); //be sure that eid is not set on insert op to autoincrement value
        unset($eventdata['is_update']);
        $eventdata['time'] = date("Y-m-d H:i:s"); //current date
        $result = pnModAPIFunc('postcalendar', 'event', 'create', $eventdata);
    }
    if ($result === false) return false;

    return $result['eid'];
}

/**
 * postcalendar_eventapi_buildSubmitForm()
 * this is also used on a preview of event function, so $eventdata is passed from that if 'loaded'
 * create event submit form
 */
function postcalendar_eventapi_buildSubmitForm($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $tpl = pnRender::getInstance('PostCalendar', false);    // Turn off template caching here
    pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);

    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }
    //echo "<div style='text-align:left;'><b>_buildSubmitForm:</b><br /><pre style='background-color:#ffffcc;'>"; print_r($args); echo "</pre></div>";

    $eventdata = $args['eventdata']; // contains data for editing if loaded


    /***************** SET UP DEFAULT VALUES **********************/
    // format date information 
    if (($eventdata['endDate'] == '') || ($eventdata['endDate'] == '00000000') || ($eventdata['endDate'] == '0000-00-00')) {
        $eventdata['endvalue'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'], 'format'=>_SETTING_DATE_FORMAT));
        $eventdata['endDate'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'], 'format'=>'%Y-%m-%d')); // format for JS cal & DB
    }  else {
        $eventdata['endvalue'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>str_replace('-', '', $eventdata['endDate']), 'format'=>_SETTING_DATE_FORMAT));
    }
    if ($eventdata['eventDate'] == '') {
        $eventdata['eventDatevalue'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'], 'format'=>_SETTING_DATE_FORMAT));
        $eventdata['eventDate'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'], 'format'=>'%Y-%m-%d')); // format for JS cal & DB
    } else {
        $eventdata['eventDatevalue'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>str_replace('-', '', $eventdata['eventDate']), 'format'=>_SETTING_DATE_FORMAT));
    }
    $eventdata['aid'] = $eventdata['aid'] ? $eventdata['aid'] : pnUserGetVar('uid'); // set value of user-select box

    if ((pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) && (_SETTING_ALLOW_USER_CAL)) {
        @define('_PC_FORM_USERNAME', true); // this is used in pc_form_nav_close plugin, but don't know why
        $users = DBUtil::selectFieldArray('users', 'uname', null, null, null, 'uid');
        $tpl->assign('users', $users);
    }
    $tpl->assign('username_selected', pnUsergetVar('uname', $eventdata['aid'])); // for display of username

    // load the category registry util
    if (!Loader::loadClass('CategoryRegistryUtil')) {
        pn_exit(__f('Error! Unable to load class [%s]', 'CategoryRegistryUtil'));
    }
    $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
    $tpl->assign('catregistry', $catregistry);

    // All-day event values for radio buttons
    $tpl->assign('SelectedAllday', $eventdata['alldayevent'] == 1 ? ' checked' : '');
    $tpl->assign('SelectedTimed', (($eventdata['alldayevent'] == 0) OR (!isset($eventdata['alldayevent']))) ? ' checked' : ''); //default

    // StartTime
    $tpl->assign('minute_interval', _SETTING_TIME_INCREMENT);
    if (empty($eventdata['startTime'])) $eventdata['startTime'] = "01:00:00"; // default to 1:00 AM

    // duration
    $eventdata['duration'] = (!empty($eventdata['duration'])) ? gmdate("H:i", $eventdata['duration']) : '1:00'; // default to 1:00 hours

    // hometext
    if ((empty($eventdata['html_or_text'])) && (!empty($eventdata['hometext']))) {
        $eventdata['html_or_text'] = substr($eventdata['hometext'], 1, 4);
        $eventdata['hometext'] = substr($eventdata['hometext'], 6);
    } else {
        $eventdata['html_or_text'] = 'text'; // default
    }

    // create html/text selectbox
    $eventHTMLorText = array('text' => __('Plain text', $dom), 'html' => __('HTML-formatted', $dom));
    $tpl->assign('EventHTMLorText', $eventHTMLorText);

    // create sharing selectbox
    $data = array();
    if (_SETTING_ALLOW_USER_CAL) $data[SHARING_PRIVATE]=__('Private', $dom);
    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN) || _SETTING_ALLOW_GLOBAL || !_SETTING_ALLOW_USER_CAL) {
        $data[SHARING_GLOBAL]=__('Global', $dom);
    }
    $tpl->assign('sharingselect', $data);

    if (!isset($eventdata['sharing'])) $eventdata['sharing'] = SHARING_GLOBAL; //default

    // recur type radio selects
    $tpl->assign('SelectedNoRepeat', (((int) $eventdata['recurrtype'] == 0) OR (empty($eventdata['recurrtype']))) ? ' checked' : ''); //default
    $tpl->assign('SelectedRepeat', (int) $eventdata['recurrtype'] == 1 ? ' checked' : '');
    $tpl->assign('SelectedRepeatOn', (int) $eventdata['recurrtype'] == 2 ? ' checked' : '');

    $in = explode ("/", __('Day(s)/Week(s)/Month(s)/Year(s)', $dom));
    $keys = array(REPEAT_EVERY_DAY, REPEAT_EVERY_WEEK, REPEAT_EVERY_MONTH, REPEAT_EVERY_YEAR);
    $selectarray = array_combine($keys, $in);
    $tpl->assign('repeat_freq_type', $selectarray);

    $in = explode ("/", __('First/Second/Third/Fourth/Last', $dom));
    $keys = array(REPEAT_ON_1ST, REPEAT_ON_2ND, REPEAT_ON_3RD, REPEAT_ON_4TH, REPEAT_ON_LAST);
    $selectarray = array_combine($keys, $in);
    $tpl->assign('repeat_on_num', $selectarray);

    $in = explode (" ", __('Sun Mon Tue Wed Thu Fri Sat', $dom));
    $keys = array(REPEAT_ON_SUN, REPEAT_ON_MON, REPEAT_ON_TUE, REPEAT_ON_WED, REPEAT_ON_THU, REPEAT_ON_FRI, REPEAT_ON_SAT);
    $selectarray = array_combine($keys, $in);
    $tpl->assign('repeat_on_day', $selectarray);

     // recur defaults
    if (empty($eventdata['repeat']['event_repeat_freq_type']) || $eventdata['repeat']['event_repeat_freq_type'] < 1) $eventdata['repeat']['event_repeat_freq_type'] = REPEAT_EVERY_DAY;
    if (empty($eventdata['repeat']['event_repeat_on_num']) || $eventdata['repeat']['event_repeat_on_num'] < 1) $eventdata['repeat']['event_repeat_on_num'] = REPEAT_ON_1ST;
    if (empty($eventdata['repeat']['event_repeat_on_day']) || $eventdata['repeat']['event_repeat_on_day'] < 1) $eventdata['repeat']['event_repeat_on_day'] = REPEAT_ON_SUN;

    // endType
    $tpl->assign('SelectedEndOn', (int) $eventdata['endtype'] == 1 ? ' checked' : '');
    $tpl->assign('SelectedNoEnd', (((int) $eventdata['endtype'] == 0) OR (empty($eventdata['endtype']))) ? ' checked' : ''); //default

    //$tpl->assign('is_update', $is_update);
    //if (isset($data_loaded)) $tpl->assign('data_loaded', $data_loaded);

    // Assign the content format
    $formattedcontent = pnModAPIFunc('PostCalendar', 'event', 'isformatted', array('func' => 'new'));
    $tpl->assign('formattedcontent', $formattedcontent);

    // assign loaded data or default values
    $tpl->assign('loaded_event', DataUtil::formatForDisplay($eventdata));

    // assign function in case we were editing
    $tpl->assign('func', $args['func']);

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
    $event['duration_formatted'] = gmdate("H:i", $event['duration']); // converts seconds to HH:MM

    //remap sharing values to global/private (this sharing map converts pre-6.0 values to 6.0+ values)
    $sharingmap = array(SHARING_PRIVATE=>SHARING_PRIVATE, SHARING_PUBLIC=>SHARING_GLOBAL, SHARING_BUSY=>SHARING_PRIVATE, SHARING_GLOBAL=>SHARING_GLOBAL, SHARING_HIDEDESC=>SHARING_PRIVATE);
    $event['sharing'] = $sharingmap[$event['sharing']]; //remap sharing values to global/private
    if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != pnUserGetVar('uid') && !pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
        return false;
    }
    // add unserialized info to array
    $event['location_info'] = unserialize($event['location']);
    $event['repeat']        = unserialize($event['recurrspec']);

    // compensate for changeover to new categories system
    $lang = ZLanguage::getLanguageCode();
    $event['catname']  = $event['__CATEGORIES__']['Main']['display_name'][$lang];
    $event['catcolor'] = $event['__CATEGORIES__']['Main']['__ATTRIBUTES__']['color'];
    $event['cattextcolor'] = postcalendar_eventapi_color_inverse($event['catcolor']);

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
    if (!isset($args['eid'])) return false;

    extract($args); //eid, Date, func
    //unset($args);

    // get the DB information
    $event = DBUtil::selectObjectByID('postcalendar_events', $args['eid'], 'eid');
    $event = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);
    // if the above is false, it's a private event for another user
    // we should not diplay this - so we just exit gracefully
    if ($event === false) return false;

    // since recurrevents are dynamically calculcated, we need to change the date
    // to ensure that the correct/current date is being displayed (rather than the
    // date on which the recurring booking was executed).
    if ($event['recurrtype']) {
        $y = substr($args['Date'], 0, 4);
        $m = substr($args['Date'], 4, 2);
        $d = substr($args['Date'], 6, 2);
        $event['eventDate'] = "$y-$m-$d";
    }

    $function_out['loaded_event'] = $event;

    if ((pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD) && (pnUserGetVar('uid') == $event['aid']))
        || pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $function_out['EVENT_CAN_EDIT'] = true;
    } else {
        $function_out['EVENT_CAN_EDIT'] = false;
    }

    return $function_out;
}

/**
 * @function    postcalendar_eventapi_formateventarrayfordisplay()
 * @description This function reformats the information in an event array for proper display in detail
 * @args        $event (array) event array as pulled from the DB
 * @author      Craig Heydenburg
 *
 * @return      $event (array) modified array for display
 */
function postcalendar_eventapi_formateventarrayfordisplay($event)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if ((empty($event)) or (!is_array($event))) return LogUtil::registerError(__('Required argument not present.', $dom));

    //remap sharing values to global/private (this sharing map converts pre-6.0 values to 6.0+ values)
    $sharingmap = array(SHARING_PRIVATE=>SHARING_PRIVATE, SHARING_PUBLIC=>SHARING_GLOBAL, SHARING_BUSY=>SHARING_PRIVATE, SHARING_GLOBAL=>SHARING_GLOBAL, SHARING_HIDEDESC=>SHARING_PRIVATE);
    $event['sharing'] = $sharingmap[$event['sharing']];

    // prep hometext for display
    $display_type = substr($event['hometext'], 0, 6);
    $event['hometext'] = substr($event['hometext'], 6);
    if ($display_type==":text:") $event['hometext']  = nl2br(strip_tags($event['hometext']));

    // add unserialized info to event array
    $event['location_info'] = unserialize($event['location']);
    $event['repeat']        = unserialize($event['recurrspec']);

    // build recurrance sentence for display
    $repeat_freq_type = explode ("/", __('Day(s)/Week(s)/Month(s)/Year(s)', $dom));
    $repeat_on_num    = explode ("/", __('First/Second/Third/Fourth/Last', $dom));
    $repeat_on_day    = explode (" ", __('Sun Mon Tue Wed Thu Fri Sat', $dom));
    if ($event['recurrtype'] == REPEAT) {
        $event['recurr_sentence']  = __f("Event recurs every %s", $event['repeat']['event_repeat_freq'], $dom);
        $event['recurr_sentence'] .= " ".$repeat_freq_type[$event['repeat']['event_repeat_freq_type']];
        $event['recurr_sentence'] .= " ".__("until", $dom)." ".$event['endDate'];
    } elseif ($event['recurrtype'] == REPEAT_ON) {
        $event['recurr_sentence']  = __("Event recurs on", $dom)." ".$repeat_on_num[$event['repeat']['event_repeat_on_num']];
        $event['recurr_sentence'] .= " ".$repeat_on_day[$event['repeat']['event_repeat_on_day']];
        $event['recurr_sentence'] .= " ".__f("of the month, every %s months", $event['repeat']['event_repeat_on_freq'], $dom);
        $event['recurr_sentence'] .= " ".__("until", $dom)." ".$event['endDate'];
    } else {
        $event['recurr_sentence']  = __("This event does not recur.", $dom);
    }

    // build sharing sentence for display
    $event['sharing_sentence'] = ($event['sharing'] == SHARING_PRIVATE) ? __('This is a private event.', $dom) : __('This is a public event. ', $dom);

    // converts seconds to HH:MM for display
    $event['duration_formatted'] = gmdate("H:i", $event['duration']);

    // format endtype for form
    $event['endtype'] = $event['endDate'] == '0000-00-00' ? '0' : '1';

    // compensate for changeover to new categories system
    $lang = ZLanguage::getLanguageCode();
    $event['catname']  = $event['__CATEGORIES__']['Main']['display_name'][$lang];
    $event['catcolor'] = $event['__CATEGORIES__']['Main']['__ATTRIBUTES__']['color'];
    $event['cattextcolor'] = postcalendar_eventapi_color_inverse($event['catcolor']);

    // format all the values for display
    return DataUtil::formatForDisplay($event);
}

/**
 * @function    postcalendar_eventapi_formateventarrayforDB()
 * @description This function reformats the information in an event array for insert/update in DB
 * @args        $event (array) event array as pulled from the new/edit event form
 * @author      Craig Heydenburg
 *
 * @return      $event (array) modified array for DB insert/update
 */
function postcalendar_eventapi_formateventarrayforDB($event)
{
    return $event;
}

/**
 * @function    postcalendar_eventapi_formateventarrayforedit()
 * @description This function reformats the information in an event array for proper display in edit/copy event form
 * @args        $event (array) event array as pulled from the DB
 * @author      Craig Heydenburg
 *
 * @return      $event (array) modified array for edit/copy event form
 */
function postcalendar_eventapi_formateventarrayforedit($event)
{
    return $event;
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


/**
 * @description generate the inverse color
 * @author      Jonas John
 * @link        http://www.jonasjohn.de/snippets/php/color-inverse.htm
 * @license     public domain
 * @created     06/13/2006
 * @params      (string) hex color (e.g. #ffffff)
 * @return      (string) hex color (e.g. #000000)
 **/
function postcalendar_eventapi_color_inverse($color)
{
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6){ return '000000'; }
    $rgb = '';
    for ($x=0;$x<3;$x++){
        $c = 255 - hexdec(substr($color,(2*$x),2));
        $c = ($c < 0) ? 0 : dechex($c);
        $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
    }
    return '#'.$rgb;
}

