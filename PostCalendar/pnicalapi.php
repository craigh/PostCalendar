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

/**
 * This api utilizes iCalcreator class to manipulate uploaded files
 * and create downloadable event files
 */
Loader::requireOnce(dirname(__FILE__) . '/pnincludes/iCalcreator.class.php');
Loader::requireOnce(dirname(__FILE__) . '/global.php');

/**
 * process uploaded .ics file
 * store to PC database
 * args expected:
 *    Fileparts
 *      array([0]        =>pathname
 *            [1]        =>filename
 *            [delimiter]=>delimiter)
 */
function postcalendar_icalapi_processupload($data)
{
    /* echo "<pre>";print_r($data);echo"</pre>";die; */
    $delimiter = "/"; // assume filesystem delimiter is '/' - could change this based on server info?
    $fileparts = parsefilename($delimiter, $data['icsupload']['tmp_name'], -2);
    $fileparts['delimiter'] = $delimiter;

    $vcalendar = new vcalendar();
    $vcalendar->setConfig("unique_id", getenv('SERVER_NAME'));
    $vcalendar->setConfig("directory", $fileparts[0]);
    $vcalendar->setConfig("delimiter", $fileparts['delimiter']);
    $vcalendar->setConfig("filename", $fileparts[1]);
    $vcalendar->parse();
    while ($vevent = $vcalendar->getComponent("vevent")) {
        $ve = array(); // init array for event info
        $uid = $vevent->getProperty("uid");
        $ve['title'] = $vevent->getProperty("summary");
        $ve['description'] = $vevent->getProperty("description");
        $ve['url'] = $vevent->getProperty("url");
        $ve['location'] = $vevent->getProperty("location");
        $ve['dtstamp'] = $vevent->getProperty("dtstamp");
        $ve['dtstart'] = $vevent->getProperty("dtstart");
        $ve['dtend'] = $vevent->getProperty("dtend");
        $ve['duration'] = $vevent->getProperty("duration");
        $ve['recur'] = $vevent->getProperty("rrule");
        //while ($category = $vevent->getProperty("categories")) {
        //    $ve['categories'][] = $category;
        //}
        $ve['event_sharing'] = $data['event_sharing'];
        $ve['catid'] = $data['event_category'];
      echo "<pre>"; print_r($ve); echo "</pre>"; die();
        $eventwritten[$uid] = postcalendar_icalapi_writeicalevent($ve);
    }
    //if (in_array(false,$eventwritten) return false;    //is this the better way than below?
    foreach ($eventwritten as $valid) {
        if (!$valid) return false; // if any event not written return false
    }
    return true; //return true if all events written
}

/**
 * write event to DB
 * argument expect: $ve array of ONE event info
 */
function postcalendar_icalapi_writeicalevent($ve)
{
    // divide ical arrays into date/time arrays
    $ve['stdate'] = array_slice($ve['dtstamp'], 0, 3, true);
    $ve['sttime'] = array_slice($ve['dtstamp'], 3, 3, true);
    $ve['startdate'] = array_slice($ve['dtstart'], 0, 3, true);
    $ve['starttime'] = array_slice($ve['dtstart'], 3, 3, true);
    // set default time of midnight if not given
    if (empty($ve['starttime']['hour'])) $ve['starttime']['hour'] = "00";
    if (empty($ve['starttime']['min'])) $ve['starttime']['min'] = "00";
    if (empty($ve['starttime']['sec'])) $ve['starttime']['sec'] = "00";
    if ((!$ve['dtend']) and ($ve['duration'])) {
        $ve['dtend'] = convert_dtend($ve['dtend'], $ve['duration']); //should that be dtstart?
        // automatically adds duration to dtstart if
    }
    $ve['enddate'] = array_slice($ve['dtend'], 0, 3, true);
    $ve['endtime'] = array_slice($ve['dtend'], 3, 3, true);
    // set default time of midnight if not given
    if (empty($ve['endtime']['hour'])) $ve['endtime']['hour'] = "00";
    if (empty($ve['endtime']['min'])) $ve['endtime']['min'] = "00";
    if (empty($ve['endtime']['sec'])) $ve['endtime']['sec'] = "00";

    // what is this for? It looks like it is trying to validate the start/end times
    // but looks like it would mess stuff up...
    // comment this out for now. needs a condition that date and hour are equal to be useful
    //$ve['endtime']['sec'] < $ve['starttime']['sec'] ? $ve['endtime']['min']++ : '';
    //$ve['endtime']['min'] < $ve['starttime']['min'] ? $ve['endtime']['hour']++ : '';

    // determine if this is an all day event
    $endstamp = mktime($ve['endtime']['hour'], $ve['endtime']['min'], $ve['endtime']['sec'],
        $ve['enddate']['month'], $ve['enddate']['day'], $ve['enddate']['year']);
    $startstamp = mktime($ve['starttime']['hour'], $ve['starttime']['min'], $ve['starttime']['sec'], $ve['startdate']['month'],
        $ve['startdate']['day'], $ve['startdate']['year']);
    $stampdiff = $endstamp - $startstamp;
    if (($stampdiff == 86400) and ($ve['endtime']['hour'] == "00") and ($ve['endtime']['min'] == "00") and ($ve['endtime']['sec'] == "00")) {
        $ve['allday'] = 1; // allday event true
    }

    // set duration if it doesn't exist (in seconds)
    if (empty($ve['duration'])) {
        $duration = 3600 * ($ve['endtime']['hour'] - $ve['starttime']['hour']) + 60 * ($ve['endtime']['min'] - $ve['starttime']['min']) + ($ve['endtime']['sec'] - $ve['starttime']['sec']);
    } else {
        $duration = 3600 * $ve['duration']['hour'] + 60 * $ve['duration']['min'] + $ve['duration']['sec'];
    }
    //$pc_aid = pnUserGetVar('uid'); // seems like it should be this...
    $pc_aid = pnSessionGetVar('uid');
    $pc_informant = pnUserGetVar('uname', $pc_aid);

    //$emid = DBUtil::selectFieldMax('postcalendar_events', 'meeting_id'); //disabled June 27 2009 CAH
    //CAH seems like the emid should be incremented by 1 for a new event...
    //note: should only be adding meeting_id if the meeting has participants.
    //$emid++; // normal writeEvent function leaves at 0 if no participants

    $pc_endDate = $ve['enddate']['year'] . "-" . $ve['enddate']['month'] . "-" . $ve['enddate']['day'];
    $pc_endTime = $ve['endtime']['hour'] . ":" . $ve['endtime']['min'] . ":" . $ve['endtime']['sec'];
    $pc_eventDate = $ve['startdate']['year'] . "-" . $ve['startdate']['month'] . "-" . $ve['startdate']['day'];
    $pc_startTime = $ve['starttime']['hour'] . ":" . $ve['starttime']['min'] . ":" . $ve['starttime']['sec'];
    $pc_timestamp = $ve['stdate']['year'] . "-" . $ve['stdate']['month'] . "-" . $ve['stdate']['day'] . " " . $ve['sttime']['hour'] . ":" . $ve['sttime']['min'] . ":" . $ve['sttime']['sec'];

    $adddescitems = postcalendar_icalapi_parsedesc($ve['description']);
    foreach ($adddescitems as $key => $val) // should be using array_merge here?
    {
        if (empty($key)) $key = "description"; // overwrites old extended version
        $ve[$key] = $ve[$val];
    }

    //$event_location_data = postcalendar_icalapi_parseloc($ve['location']);
    $ve['pc_location'] = serialize(postcalendar_icalapi_parseloc($ve['location']));

    //will replace this with cat select on import - DONE - $ve[catid'] is now on import
    //foreach ($ve['categories'] as $category) {
        // PC only expects one category right now, so this should loop and then keep the last one
    //    $ve['catid'] = postcalendar_icalapi_parsecats($category);
    //}
    if (empty($ve['catid'])) $ve['catid'] = 1;

    //CAH I wonder why this appears all hardcoded... not accepting recurring events?
    //$event_repeat_data = array('event_reqeat_freq' => "1", 'event_reqeat_freq_type' => "0",
    //                'event_reqeat_on_num' => "1",
    //                'event_reqeat_on_day' => "0",
    //                'event_reqeat_on_freq' => "1");
    $event_repeat_data = postcalendar_icalapi_icaltopostcalendar($ve['recur']);
    $ve['pc_recurrtype'] = $event_repeat_data['event_repeat']; unset($event_repeat_data['event_repeat']);
    $ve['pc_recurrspec'] = serialize($event_repeat_data);

    $ve = DataUtil::formatForStore($ve);

    // check to see if an event already exists that is exactly the same...
    // seems like there should be an easier way to check...
    $where = array();
    if ($ve['catid'])              $where[] = "pc_catid     = {$ve['catid']}";
    if (is_numeric($pc_aid))       $where[] = "pc_aid       = '$pc_aid'";
    if (isset($ve['title']))       $where[] = "pc_title     = '{$ve['title']}'";
    if (isset($ve['description'])) $where[] = "pc_hometext  = ':text:{$ve['description']}'";
    if (strlen($pc_eventDate) > 2) $where[] = "pc_eventDate = '$pc_eventDate'";
    if (!is_null($duration))       $where[] = "pc_duration  = $duration";
    if (strlen($pc_startTime) > 2) $where[] = "pc_startTime = '$pc_startTime'";

    $where = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';
    $event = DBUtil::selectObject('postcalendar_events', $where);

    // if event doesn't exist, then create it.
    if (!$event) {
        $obj = array();
        $obj['catid'] = (int) $ve['catid'];
        $obj['aid'] = $pc_aid;
        $obj['title'] = $ve['title'];
        $obj['time'] = $pc_timestamp;
        $obj['hometext'] = ":text:$ve[description]";
        $obj['topic'] = $ve['topic'];
        $obj['informant'] = $pc_informant;
        $obj['eventDate'] = $pc_eventDate; //startdate
        $obj['endDate'] = $pc_endDate;
        $obj['duration'] = $duration;
        $obj['recurrtype'] = $ve['pc_recurrtype'];
        $obj['recurrspec'] = $ve['pc_recurrspec'];
        $obj['recurrfreq'] = 0; //unused information in DB - suspect superceded by recurrspec
        $obj['startTime'] = $pc_startTime;
        $obj['endTime'] = $pc_endTime;
        $obj['alldayevent'] = $ve['allday'];
        $obj['location'] = $ve['pc_location'];
        $obj['conttel'] = $ve['phone'];
        $obj['contname'] = $ve['contact'];
        $obj['contemail'] = $ve['email'];
        $obj['website'] = $ve['url'];
        $obj['fee'] = $ve['fee'];
        $obj['eventstatus'] = 1; // 0 would be pending
        $obj['sharing'] = $ve['event_sharing']; //1; // 3 would be global
        $obj['language'] = NULL;
        $obj['meeting_id'] = 0; // $emid;  //disabled June 27 2009 CAH

        $result = pnModAPIFunc('PostCalendar', 'event', 'create', $obj);
    }

    pnModAPIFunc('PostCalendar', 'admin', 'clearCache');
    if ($result == true) {
        return true;
    } else {
        return false;
    }
}

/**
 * export ical event from provided event info
 */
function postcalendar_icalapi_export_ical($sevents)
{
    /* echo "<pre>"; print_r($sevents); echo "</pre>"; die; */

    $eid = FormUtil::getPassedValue('eid');
    $category = FormUtil::getPassedValue('category');
    $sitename = getenv('SERVER_NAME');

    $v = new vcalendar();
    $v->setConfig('unique_id', $sitename);

    $v->setProperty('method', 'PUBLISH');
    $v->setProperty("x-wr-calname", "Calendar from " . $sitename);
    $v->setProperty("X-WR-CALDESC", "Calendar from " . $sitename);
    list($tzoffset, $tztext) = postcalendar_icalapi_getTZ();
    $v->setProperty("X-WR-TIMEZONE", $tztext);

    foreach ($sevents as $cdate => $event) {
        # $cdate has the events actual date
        # $event has the event array for $cdate day
        foreach ($event as $item) {
            # Allow a selection by unique eventid and/or category
            if (($item['eid'] == $eid || $eid == "") && ($item['catname'] == $category || $category == "")) {
                # slurp out the fields to make it more convenient
                $starttime = $item['startTime'];
                $duration = $item['duration'];
                $title = $item['title'];
                $summary = $item['title'];
                $description = html_entity_decode(strip_tags(substr($item['hometext'], 6)));
                $evcategory = $item['catname'];
                $location = $item['event_location'];
                $uid = $item['eid'] . "--" . strtotime($item['time']) . "@$sitename";
                $url = $item['website'];
                $peid = $item['eid'];
                $allday = $item['alldayevent'];
                $fee = $item['fee'];
                $topic = $item['topic'];

                # this block of code cleans up encodings such as &#113; in the
                # email addresses.    These were escaped on store by postcalendar
                # and I'm too lazy to figure out a regexp to fix it.
                # it builds two arrays with search and replace and then calls
                # str_replace once to translate everything over.
                $email = $item['contemail'];
                for ($i = 1; $i <= 127; $i++) {
                    $srch[$i] = sprintf("&#%03.3d;", $i);
                    $repl[$i] = chr($i);
                }
                $item['contemail'] = str_replace($srch, $repl, $item['contemail']);
                $email = str_replace($srch, $repl, $email);
                $organizer = $email;

                # indent the original description so VEVENT doesn't blow up on DESCRIPTION
                $description = preg_replace('!^!m', str_repeat(' ', 2), $description);

                # Build the event description text.
                $descadd = "";
                if (!empty($item['contname'])) $descadd .= "     Contact: " . $item['contname'] . "\n";
                if (!empty($item['conttel']))  $descadd .= "     Phone: " . $item['conttel'] . "\n";
                if (!empty($email))            $descadd .= "     Email: " . $email . "\n";
                if (!empty($item['website']))  $descadd .= "     URL: " . $item['website'] . "\n";
                if (!empty($item['fee']))      $descadd .= "     Fee: " . $item['fee'] . "\n";
                if (!empty($item['topic']))    $descadd .= "     Topic: " . $item['topic'] . "\n";
                if (!empty($descadd))          $descadd  = "     For more information:\n" . $descadd;
                $evtdesc = $description . "\n" . $descadd;

                if ($item['event_location']) $eventdesc .= "     Location: " . $item['event_location'] . "\n";
                if ($item['event_street1'])  $eventdesc .= "     Street Addr 1: " . $item['event_street1'] . "\n";
                if ($item['event_street2'])  $eventdesc .= "     Street Addr 2: " . $item['event_street2'] . "\n";
                if ($item['event_city'])     $eventdesc .= "     City, ST ZIP: " . $item['event_city'] . "," . $item['event_state'] . " " . $item['event_postal'] . "\n";

                # Build the ALTREP line as a link to the actual calendar
                $args = array();
                $args['Date'] = date("Ymd", strtotime($cdate));
                $args['viewtype'] = 'details';
                $args['eid'] = $peid;
                $url = pnModURL('PostCalendar', 'user', 'view', $args, null, null, true);

                # output the vCard/iCal VEVENT object
                $vevent = new vevent();
                if ($organizer != "") {
                    $vevent->setProperty('ORGANIZER:MAILTO', $organizer);
                    $vevent->setProperty('CONTACT:MAILTO', $organizer);
                }
                if ($url != "") {
                    $vevent->setProperty('URL', $url);
                }
                $vevent->setProperty('SUMMARY', $summary);
                $vevent->setProperty('DESCRIPTION', $evtdesc);
                $vevent->setProperty('TZ', $tzoffset);
                $vevent->setProperty('CATEGORIES', $evcategory);
                $vevent->setProperty('LOCATION', $location);
                $vevent->setProperty('TRANSP', 'OPAQUE');
                $vevent->setProperty('CLASS', 'CONFIDENTIAL');
                $vevent->setProperty('DTSTAMP', date("Ymd") . "T" . date("His") . "Z");
                if ($allday) {
                    list($year, $month, $day) = explode("-", $item['eventDate']);
                    $vevent->setProperty("dtstart", array( 'year'=>$year, 'month'=>$month, 'day'=>$day), array('VALUE'=>'DATE'));
                    // add one day
                    list($year, $month, $day)=explode ("^", date("Y^m^d", (strtotime($item['eventDate']) + 86400)));
                    $vevent->setProperty("dtend", array('year'=>$year, 'month'=>$month, 'day'=>$day), array('VALUE'=>'DATE'));
                } else {
                   # format up the date/time into ical format for output
                   # build the normal date/time string ...
                   $evtstr = $item['eventDate'] . " " . $item['startTime'];
                   # convert it to unix time ...
                   $evttime = strtotime($evtstr);
                   # add duration to get the end time ...
                   $evtend = $evttime + $duration; //duration is already expressed in seconds (e.g. 3600 = one hour)
   
                   # format it for output
                   $startdate = date("Y^m^d^H^i^s", $evttime); //should we be using date or gmdate?
                   list($year, $month, $day, $hour, $min, $sec) = explode("^", $startdate);
                   $vevent->setProperty('dtstart',
                       array('year' => $year, 'month' => $month,
                                       'day' => $day,
                                       'hour' => $hour,
                                       'min' => $min,
                                       'sec' => $sec));
   
                   $enddate = date("Y^m^d^H^i^s", $evtend); //should we be using date or gmdate?
                   list($year, $month, $day, $hour, $min, $sec) = explode("^", $enddate);
                   $vevent->setProperty('dtend',
                       array('year' => $year, 'month' => $month,
                                       'day' => $day,
                                       'hour' => $hour,
                                       'min' => $min,
                                       'sec' => $sec));
                }

                # bury a serialized php structure in the COMMENT field.
                if (($extendedinfo == 1) && ($extendedinfoallowed == 1)) {
                    $extinfo['url'] = $url;
                    $extinfo['date'] = date("Ymd", $evttime);
                    $extinfo['eid'] = $peid;
                    $extinfo['eventtime'] = $evttime;
                    $extinfo['icallink'] = "http://$sitename/modules/PostCalendar/ical.php?eid=$peid&date=" . date("Ymd", strtotime($item['eventDate']));
                    $extinfo['evtstartunixtime'] = $evttime;
                    $extinfo['evtendunixtime'] = $evtend;

                    foreach ($item as $key => $data) {
                        $extinfo[$key] = $item[$key];
                    }

                    $vevent->setProperty('COMMENT', serialize($extinfo));
                }
                $v->setComponent($vevent);
            }
        }
    }
    $v->returnCalendar();
    return true;
}
/**
 * @function    postcalendar_icalapi_getTZ
 * @description get timezone
 * @return      array
 */
function postcalendar_icalapi_getTZ()
{
    $tzinfo = pnConfigGetVar('timezone_info');
    $tzid = pnConfigGetVar('timezone_offset');
    $timezones = array();
    foreach ($tzinfo as $tzindex => $tzdata) {
        $timezones[$tzindex] = $tzdata;
    }
    return array($tzid, $timezones[$tzid]);
}
/**
 * @function    parseicalfield
 * @description parse an ical field into associative array
 * @params      text    field   
 * @return      array
 */
function parseicalfield($field)
{
    $items = array(); // array to hold parsed items
    // $field should be a long str
    // explode based on newline char
    $lines = explode("\N", $desc);
    foreach ($lines as $line) {
        // explode each line based on : char
        list($key, $val) = explode(":", $line);
        $items[] = array(trim($key) => trim($val));
    }
    return $items;
}
/**
 * @function    postcalendar_icalapi_parsedesc
 * @description parse an ical description into associative array
 * @params      text    desc   
 * @return      array
 */
function postcalendar_icalapi_parsedesc($desc)
{
    return parseicalfield($desc);
}
/**
 * @function    postcalendar_icalapi_parseloc
 * @description parse an ical location into associative array
 * @params      text    loc   
 * @return      array
 */
function postcalendar_icalapi_parseloc($loc)
{
    $locitems = parseicalfield($loc);
    if (!empty($locitems['City, ST ZIP'])) {
        $line = $locitems['City, ST ZIP'];
        list($locitems['city'], $str) = explode(", ", $line);
        list($locitems['state'], $locitems['zip']) = explode(" ", $str);
    }
    $event_location_data = array('event_location' => $locitems['location'], 'event_street1' => $locitems['street1'],
                    'event_street2' => $locitems['street2'],
                    'event_city' => $locitems['city'],
                    'event_state' => $locitems['state'],
                    'event_postal' => $locitems['zip']);
    return $event_location_data;
}
/**
 * @function    postcalendar_icalapi_parsecats
 * @description determine a category ID if available from name
 * @params      text    category   
 * @return      array
 */
function postcalendar_icalapi_parsecats($category)
{
    $cat_id = DBUtil::selectFieldByID('postcalendar_categories', 'catid', $category, 'catname');
    // actually using that a little backwards, but should select ID based on name of cat
    if (!$cat_id) $cat_id = 1;
    return $cat_id;
}
/**
 * @function    convert_dtend
 * @description automatically adds duration to dtstart
 *                  WONDERING IF THIS IS WORKING AS EXPECTED?
 * @params      array    end    end datetime
 * @params      array    dur    duration
 * @return      array   new endtime
 * @access      private
 */
function convert_dtend($end, $dur)
{
    extract($end);
    $endsecs = mktime($hour, $min, $sec, $month, $day, $year);
    extract($dur);
    $dursecs = 3600 * $hour + 60 * $min + $sec;
    $newendsecs = $endsecs + $dursecs;
    $datetime = date("Y^m^d^H^i^s", $newendsecs);
    list($year, $month, $day, $hour, $min, $sec) = explode("^", $datetime);
    return compact("year", "month", "day", "hour", "min", "sec");
}
/**
 * @function        postcalendar_icalapi_postcalendartoical
 * @description     convert postcalendar repeated events to iCal format
 *
 * @params
 * @return
 * @access          private
 */
function postcalendar_icalapi_postcalendartoical($params)
{
}
/**
 * @function        postcalendar_icalapi_icaltopostcalendar
 * @description     convert iCal repeated events to postcalendar format
 *
 * @params          EMPTY or Array ( [FREQ] => YEARLY [INTERVAL] => 1 [BYMONTH] => 9 )
 * @return          Array
 * @access          private
 */
function postcalendar_icalapi_icaltopostcalendar($params)
{
    $defaults = array(
            'event_repeat'           => NO_REPEAT,
            'event_reqeat_freq'      => 1,
            'event_reqeat_freq_type' => REPEAT_EVERY_DAY,
            'event_reqeat_on_num'    => REPEAT_ON_1ST,
            'event_reqeat_on_day'    => REPEAT_ON_SUN,
            'event_reqeat_on_freq'   => 1);
    if (!$params) return $defaults;

    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if (!is_array($params)) return LogUtil::registerError(__('Incorrect parameter passed to '. __FUNCTION__, $dom));

    $rrule = postcalendar_icalapi_parserrule($params);
    $spec = $defaults; //resultant array
    $spec['event_repeat'] = REPEAT;

    // recur type
    $freq_type = array("YEARLY"=>REPEAT_EVERY_YEAR, "MONTHLY"=>REPEAT_EVERY_MONTH, "WEEKLY"=>REPEAT_EVERY_WEEK, "DAILY"=>REPEAT_EVERY_DAY);
        /* we will not consider HOURLY, MINUTELY, SECONDLY cases */
    if (array_key_exists($rrule['freq'],$freq_type)  { $spec['event_repeat_freq_type'] = $freq_type($rrule['freq']);
    } else { $spec['event_repeat_freq_type'] = REPEAT_EVERY_DAY; } // default to daily
    unset($rrule['freq']);

    // recur interval (how often)
    if (!empty($rrule['interval'])) $spec['event_repeat_freq'] = $rrule['interval'];
    unset($rrule['interval']);

    if (empty($rrule)) return $spec; // checking for remaining array values

    if (array_key_exists('byday',$rrule)) {
        //do something
        unset($rrule['byday']);
    }
    if (empty($rrule)) return $spec; // checking for remaining array values

    if (array_key_exists('bymonth',$rrule)) {
        //do something
        unset($rrule['bymonth']);
    }
    if (empty($rrule)) return $spec; // checking for remaining array values

}
// $event_repeat
//define('NO_REPEAT',             0);
//define('REPEAT',                1);
//define('REPEAT_ON',             2);
// $event_repeat_freq ***************** NOT USED - use (int)
//define('REPEAT_EVERY',          1);
//define('REPEAT_EVERY_OTHER',    2);
//define('REPEAT_EVERY_THIRD',    3);
//define('REPEAT_EVERY_FOURTH',   4);
// $event_repeat_freq_type
//define('REPEAT_EVERY_DAY',      0);
//define('REPEAT_EVERY_WEEK',     1);
//define('REPEAT_EVERY_MONTH',    2);
//define('REPEAT_EVERY_YEAR',     3);
// $event_repeat_on_num
//define('REPEAT_ON_1ST',         1);
//define('REPEAT_ON_2ND',         2);
//define('REPEAT_ON_3RD',         3);
//define('REPEAT_ON_4TH',         4);
//define('REPEAT_ON_LAST',        5);
// $event_repeat_on_day
//define('REPEAT_ON_SUN',         0);
//define('REPEAT_ON_MON',         1);
//define('REPEAT_ON_TUE',         2);
//define('REPEAT_ON_WED',         3);
//define('REPEAT_ON_THU',         4);
//define('REPEAT_ON_FRI',         5);
//define('REPEAT_ON_SAT',         6);
// $event_repeat_on_freq ***************** NOT USED - use (int)
//define('REPEAT_ON_MONTH',       1);
//define('REPEAT_ON_2MONTH',      2);
//define('REPEAT_ON_3MONTH',      3);
//define('REPEAT_ON_4MONTH',      4);
//define('REPEAT_ON_6MONTH',      6);
//define('REPEAT_ON_YEAR',        12);
/**
 * @function        postcalendar_icalapi_parserrule
 * @description     convert rrule array to assoc array
 *
 * @params          Array e.g. ( [FREQ] => YEARLY [INTERVAL] => 1 [BYMONTH] => 9 )
 * @return          Array e.g. ( freq => YEARLY interval => 1 bymonth => 9 )
 * @access          private
 */
function postcalendar_icalapi_parserrule($array)
{
    if ((!is_array($array)) or (empty($array))) return false;
    $newarray=array();
    foreach ($array as $k=>$v) {
        $newarray[strtolower(substr($k, 1, -1))]=$v;
    }
    return $newarray;
}