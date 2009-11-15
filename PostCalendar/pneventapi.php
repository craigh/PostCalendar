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


    // convert categories array to proper filter info
    $catsarray = $filtercats['__CATEGORIES__'];
    foreach ($catsarray as $propname => $propid) {
        if ($propid <= 0) unset($catsarray[$propname]); // removes categories set to 'all' (0)
    }
    if (!empty($catsarray)) $catsarray['__META__']['module']="PostCalendar";

    if (!empty($s_keywords)) $where .= "AND $s_keywords";

    $events = DBUtil::selectObjectArray('postcalendar_events', $where, null, null, null, null, null, $catsarray);

    foreach ($events as $key => $evt) {
        $events[$key] = pnModAPIFunc('PostCalendar', 'event', 'fixEventDetails', $events[$key]);
    }

    return $events;
}

/**
 * I believe this function returns an array of events sorted by date
 * expected args (from postcalendar_userapi_buildView): start, end
 *    if either is present, both must be present. else uses today's/jumped date.
 * expected args (from pnsearch/postcalendar.php/search_postcalendar): s_keywords, filtercats
 * same ^^^ in (postcalendar_user_search)
 **/
function postcalendar_eventapi_getEvents($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $s_keywords = ''; // search stuff
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
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    echo "<div style='text-align:left;'><b>_writeEvent:</b><br /><pre style='background-color:#ffffcc;'>"; print_r($args); echo "</pre></div>";
    //extract($args); //'eventdata','Date','event_for_userid'
    //unset($args);
    $eventdata = $args['eventdata'];
    $Date      = $args['Date'];

    define('PC_ACCESS_ADMIN', pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN));

    // determine if the event is to be published immediately or not
    if ((bool) _SETTING_DIRECT_SUBMIT || (bool) PC_ACCESS_ADMIN || ($event_sharing != SHARING_GLOBAL)) {
        $eventdata['eventstatus'] = _EVENT_APPROVED;
    } else {
        $eventdata['eventstatus'] = _EVENT_QUEUED;
    }

    // set up some vars for the insert statement
    //$startDate = $event_startyear . '-' . $event_startmonth . '-' . $event_startday;
    $eventdata['eventDate'] = $eventdata['eventDate']['full'];
    if ($eventdata['endtype'] == 1) {
        //$endDate = $event_endyear . '-' . $event_endmonth . '-' . $event_endday;
        $eventdata['endDate'] = $eventdata['endDate']['full'];
    } else {
        $eventdata['endDate'] = '0000-00-00';
    }
    unset($eventdata['endtype']);

    if (!isset($eventdata['alldayevent'])) $eventdata['alldayevent'] = 0;

    if ((bool) _SETTING_TIME_24HOUR) {
        $eventdata['startTime'] = $eventdata['startTime']['Hour'] .':'. $eventdata['startTime']['Minute'] .':00';
    } else {
        if ($eventdata['startTime']['Meridian'] == _AM_VAL) {
            $eventdata['startTime']['Hour'] = $eventdata['startTime']['Hour'] == 12 ? '00' : $eventdata['startTime']['Hour'];
        } else {
            $eventdata['startTime']['Hour'] = $$eventdata['startTime']['Hour'] != 12 ? $eventdata['startTime']['Hour'] += 12 : $eventdata['startTime']['Hour'];
        }
    }

    $eventdata['startTime'] = sprintf('%02d', $eventdata['startTime']['Hour']) .':'. sprintf('%02d', $eventdata['startTime']['Minute']) .':00';

    if (empty($eventdata['hometext'])) {
        $eventdata['hometext'] = __(/*!(abbr) not applicable or not available*/'n/a', $dom); // default description
    } else {
        $eventdata['hometext'] = ':'. $eventdata['html_or_text'] .':'. $eventdata['hometext']; // inserts :text:/:html: before actual content
    }

    $eventdata['duration'] = $eventdata['duration']['full'];
    $eventdata['location'] = serialize($eventdata['location']);
    if (!isset($eventdata['repeat']['repeatval'])) $eventdata['repeat']['repeatval'] = 0;
    $eventdata['recurrspec'] = serialize($eventdata['repeat']); unset($eventdata['repeat']);
    unset($eventdata['html_or_text']);
    unset($eventdata['data_loaded']);
    //unset($eventdata['event_for_userid']);

    if (!isset($eventdata['is_update'])) $eventdata['is_update'] = false;

    echo "<div style='text-align:left;'><b>_writeEvent (eventdata before create):</b><br /><pre style='background-color:#ff9911;'>"; print_r($eventdata); echo "</pre></div>"; 

    if ($eventdata['is_update']) {
        //$eventdata['eid'] = $eid;
        unset($eventdata['is_update']);
        $result = pnModAPIFunc('postcalendar', 'event', 'update', array($eventdata[$eid] => $eventdata));
    } else { //new event
        unset($eventdata['eid']); //be sure that eid is not set on insert op to autoincrement value
        unset($eventdata['is_update']);
        $eventdata['time'] = date("Y-m-d H:i:s"); //current date
        //$eventdata['informant'] = $uname; // @v6.0 change this to uid
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
    $uid = pnUserGetVar('uid');
    //$uname = pnUserGetVar('uname');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }
    //echo "<div style='text-align:left;'><b>_buildSubmitForm:</b><br /><pre style='background-color:#ffffcc;'>"; print_r($args); echo "</pre></div>";

    /***************** SET UP EXISTING VALUES FOR EDITING **********************/
    $eventdata = $args['eventdata']; // contains data for editing if loaded
    $eventdata['endvalue'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>str_replace('-', '', $eventdata['endDate']), 'format'=>'%d-%m-%Y'));
    $eventdata['eventDatevalue'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>str_replace('-', '', $eventdata['eventDate']), 'format'=>'%d-%m-%Y'));


    /***************** SET UP DEFAULT VALUES **********************/
    // format date information 
    if (($eventdata['endDate'] == '') || ($eventdata['endDate'] == '00000000') || ($eventdata['endDate'] == '0000-00-00')) {
        $eventdata['endvalue'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'], 'format'=>'%d-%m-%Y'));
        $eventdata['endDate'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'], 'format'=>'%Y-%m-%d')); // format for JS cal
    } 
    if ($eventdata['eventDate'] == '') {
        $eventdata['eventDatevalue'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'], 'format'=>'%d-%m-%Y'));
        $eventdata['eventDate'] = pnModAPIFunc('PostCalendar','user','getDate',array('Date'=>$args['Date'], 'format'=>'%Y-%m-%d')); // format for JS cal
    }
    $eventdata['aid'] = $eventdata['aid'] ? $eventdata['aid'] : $uid; // set value of user-select box

    if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        @define('_PC_FORM_USERNAME', true); // this is used in pc_form_nav_close plugin, but don't know why
        $users = DBUtil::selectFieldArray('users', 'uname', null, null, null, 'uid');
        $tpl->assign('users', $users);
    }
    $tpl->assign('username_selected', pnUsergetVar('uname', $eventdata['aid'])); // for display of username

    // load the category registry util
    if (!Loader::loadClass('CategoryRegistryUtil')) {
        pn_exit(__f('Error! Unable to load class [%s%]', 'CategoryRegistryUtil'));
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
        $eventdata['hometext'] = substr($event_desc, 6);
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
    if (empty($eventdata['recurrspec']['event_repeat_freq_type']) || $eventdata['recurrspec']['event_repeat_freq_type'] < 1) $eventdata['recurrspec']['event_repeat_freq_type'] = REPEAT_EVERY_DAY;
    if (empty($eventdata['recurrspec']['event_repeat_on_num']) || $eventdata['recurrspec']['event_repeat_on_num'] < 1) $eventdata['recurrspec']['event_repeat_on_num'] = REPEAT_ON_1ST;
    if (empty($eventdata['recurrspec']['event_repeat_on_day']) || $eventdata['recurrspec']['event_repeat_on_day'] < 1) $eventdata['recurrspec']['event_repeat_on_day'] = REPEAT_ON_SUN;

    // endType
    $tpl->assign('SelectedEndOn', (int) $eventdata['endtype'] == 1 ? ' checked' : '');
    $tpl->assign('SelectedNoEnd', (((int) $eventdata['endtype'] == 0) OR (empty($eventdata['endtype']))) ? ' checked' : ''); //default

    $tpl->assign('is_update', $is_update);
    if (isset($data_loaded)) $tpl->assign('data_loaded', $data_loaded);

    // Assign the content format
    $formattedcontent = pnModAPIFunc('PostCalendar', 'event', 'isformatted', array('func' => 'new'));
    $tpl->assign('formattedcontent', $formattedcontent);

    // assign loaded data or default values
    $tpl->assign('loaded_event', DataUtil::formatForDisplay($eventdata));

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
    // this may no longer be needed...
    list($event['duration_hours'], $event['duration_minutes']) = explode(":", gmdate("H:i", $event['duration']));

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
        // FIXME: this entire thing should be a sub-array RNG < v5.0.0
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

    // compensate for changeover to new categories system
    $lang = ZLanguage::getLanguageCode();
    $event['catname']  = $event['__CATEGORIES__']['Main']['display_name'][$lang];
    $event['catcolor'] = $event['__CATEGORIES__']['Main']['__ATTRIBUTES__']['color'];

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

    $event = DBUtil::selectExpandedObjectByID('postcalendar_events', null, $eid, 'eid');
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

    extract($args); //eid, Date, func
    unset($args);

    if (!isset($eid)) return false;
    $uid = pnUserGetVar('uid');

    $function_out['FUNCTION'] = $func;

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

    $function_out['EVENT_CAN_EDIT'] = false;
    if ((pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD) && $logged_in_uid == $event['aid'])
        || pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        $function_out['EVENT_CAN_EDIT'] = true;
    }

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