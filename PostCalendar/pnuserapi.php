<?php
/**
 * SVN: $Id$
 *
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Revision$
 *
 * PostCalendar::Zikula Events Calendar Module
 * Copyright (C) 2002    The PostCalendar Team
 * http://postcalendar.tv
 * Copyright (C) 2009    Sound Web Development
 * Craig Heydenburg
 * http://code.zikula.org/soundwebdevelopment/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * To read the license please read the docs/license.txt or visit
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

//=========================================================================
// Require utility classes
//=========================================================================
require_once 'modules/PostCalendar/global.php';

function postcalendar_userapi_getLongDayName($args)
{
    if (!isset($args['Date'])) {
        return false;
    }
    $pc_long_day = array(
                    _CALLONGFIRSTDAY,
                    _CALLONGSECONDDAY,
                    _CALLONGTHIRDDAY,
                    _CALLONGFOURTHDAY,
                    _CALLONGFIFTHDAY,
                    _CALLONGSIXTHDAY,
                    _CALLONGSEVENTHDAY);
    return $pc_long_day[Date("w", $args['Date'])];
}

/**
 * postcalendar_userapi_buildView
 *
 * Builds the calendar display
 * @param string $Date mm/dd/yyyy format (we should use timestamps)
 * @return string generated html output
 * @access public
 */
function postcalendar_userapi_buildView($args)
{
    $Date = $args['Date'];
    $viewtype = $args['viewtype'];

    //=================================================================
    // grab the post variables
    $pc_username = FormUtil::getPassedValue('pc_username');
    $category = FormUtil::getPassedValue('pc_category');
    $topic = FormUtil::getPassedValue('pc_topic');
    //=================================================================
    // set the correct date
    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');
    if (!$Date) $Date = pnModAPIFunc('PostCalendar','user','getDate',compact('jumpday','jumpmonth','jumpyear')); // if not explicit arg, get from input

    if (strlen($Date) == 8 && is_numeric($Date)) $Date .= '000000'; // 20060101

    //=================================================================
    // get the current view
    if (!isset($viewtype)) $viewtype = 'month'; // default view
    //=================================================================
    // Find out what Template we're using

    $function_out['template'] = DataUtil::formatForOS('user/postcalendar_user_view_' . $viewtype . '.html');

    //=================================================================
    // finish setting things up
    $the_year = substr($Date, 0, 4);
    $the_month = substr($Date, 4, 2);
    $the_day = substr($Date, 6, 2);
    $last_day = Date_Calc::daysInMonth($the_month, $the_year);
    //=================================================================
    // populate the template object with information for
    // Month Names, Long Day Names and Short Day Names
    // as translated in the language files
    // (may be adding more here soon - based on need)
    //=================================================================
    $pc_month_names = array(
                    _CALJAN, _CALFEB, _CALMAR, _CALAPR, _CALMAY,
                    _CALJUN, _CALJUL, _CALAUG, _CALSEP, _CALOCT,
                    _CALNOV, _CALDEC);
    $pc_short_day_names = array(_CALSUNDAYSHORT, _CALMONDAYSHORT, _CALTUESDAYSHORT, _CALWEDNESDAYSHORT, _CALTHURSDAYSHORT,
                    _CALFRIDAYSHORT, _CALSATURDAYSHORT);
    $pc_long_day_names = array(_CALSUNDAY, _CALMONDAY, _CALTUESDAY, _CALWEDNESDAY, _CALTHURSDAY, _CALFRIDAY, _CALSATURDAY);
    //=================================================================
    // here we need to set up some information for later
    // variable creation.    This helps us establish the correct
    // date ranges for each view.    There may be a better way
    // to handle all this, but my brain hurts, so your comments
    // are very appreciated and welcomed.
    //=================================================================
    switch (_SETTING_FIRST_DAY_WEEK) {
        case _IS_MONDAY:
            $pc_array_pos = 1;
            $first_day = date('w', mktime(0, 0, 0, $the_month, 0, $the_year));
            $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day - 1, $the_year));
            $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
            if ($end_dow != 0) {
                $the_last_day = $last_day + (7 - $end_dow);
            } else {
                $the_last_day = $last_day;
            }
            break;
        case _IS_SATURDAY:
            $pc_array_pos = 6;
            $first_day = date('w', mktime(0, 0, 0, $the_month, 2, $the_year));
            $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day + 1, $the_year));
            $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
            if ($end_dow == 6) {
                $the_last_day = $last_day + 6;
            } elseif ($end_dow != 5) {
                $the_last_day = $last_day + (5 - $end_dow);
            } else {
                $the_last_day = $last_day;
            }
            break;
        case _IS_SUNDAY:
        default:
            $pc_array_pos = 0;
            $first_day = date('w', mktime(0, 0, 0, $the_month, 1, $the_year));
            $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day, $the_year));
            $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
            if ($end_dow != 6) {
                $the_last_day = $last_day + (6 - $end_dow);
            } else {
                $the_last_day = $last_day;
            }
            break;
    }
    //=================================================================
    // Week View is a bit of a pain, so we need to
    // do some extra setup for that view.    This section will
    // find the correct starting and ending dates for a given
    // seven day period, based on the day of the week the
    // calendar is setup to run under (Sunday, Saturday, Monday)
    //=================================================================
    $first_day_of_week = sprintf('%02d', $the_day - $week_day);
    $week_first_day = date('m/d/Y', mktime(0, 0, 0, $the_month, $first_day_of_week, $the_year));
    list($week_first_day_month, $week_first_day_date, $week_first_day_year) = explode('/', $week_first_day);
    $week_first_day_month_name = pnModAPIFunc('PostCalendar', 'user', 'getmonthname',
        array('Date' => mktime(0, 0, 0, $week_first_day_month, $week_first_day_date, $week_first_day_year)));
    $week_last_day = date('m/d/Y', mktime(0, 0, 0, $the_month, $first_day_of_week + 6, $the_year));
    list($week_last_day_month, $week_last_day_date, $week_last_day_year) = explode('/', $week_last_day);
    $week_last_day_month_name = pnModAPIFunc('PostCalendar', 'user', 'getmonthname',
        array('Date' => mktime(0, 0, 0, $week_last_day_month, $week_last_day_date, $week_last_day_year)));

    //=================================================================
    // Setup some information so we know the actual month's dates
    // also get today's date for later use and highlighting
    //=================================================================
    $month_view_start = date('Y-m-d', mktime(0, 0, 0, $the_month, 1, $the_year));
    $month_view_end = date('Y-m-t', mktime(0, 0, 0, $the_month, 1, $the_year));
    $today_date = DateUtil::getDatetime('', '%Y-%m-%d');
    //=================================================================
    // Setup the starting and ending date ranges for pcGetEvents()
    //=================================================================
    switch ($viewtype) {
        case 'day':
            $starting_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_day, $the_year));
            $ending_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_day, $the_year));
            break;
        case 'week':
            $starting_date = "$week_first_day_month/$week_first_day_date/$week_first_day_year";
            $ending_date = "$week_last_day_month/$week_last_day_date/$week_last_day_year";
            $calendarView = Date_Calc::getCalendarWeek($week_first_day_date, $week_first_day_month,
            $week_first_day_year, '%Y-%m-%d');
            break;
        case 'month':
            $starting_date = date('m/d/Y', mktime(0, 0, 0, $the_month, 1 - $first_day, $the_year));
            $ending_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_last_day, $the_year));
            $calendarView = Date_Calc::getCalendarMonth($the_month, $the_year, '%Y-%m-%d');
            break;
        case 'year':
            $starting_date = date('m/d/Y', mktime(0, 0, 0, 1, 1, $the_year));
            $ending_date = date('m/d/Y', mktime(0, 0, 0, 1, 1, $the_year + 1));
            $calendarView = Date_Calc::getCalendarYear($the_year, '%Y-%m-%d');
            break;
    }
    //=================================================================
    // Load the events
    //=================================================================
    $eventsByDate = & pnModAPIFunc('PostCalendar', 'event', 'getEvents',
        array('start' => $starting_date, 'end' => $ending_date));

    //=================================================================
    // Create an array with the day names in the correct order
    //=================================================================
    $daynames = array();
    $numDays = count($pc_long_day_names);
    for ($i = 0; $i < $numDays; $i++) {
        if ($pc_array_pos >= $numDays) {
            $pc_array_pos = 0;
        }
        array_push($daynames, $pc_long_day_names[$pc_array_pos]);
        $pc_array_pos++;
    }
    unset($numDays);
    $sdaynames = array();
    $numDays = count($pc_short_day_names);
    for ($i = 0; $i < $numDays; $i++) {
        if ($pc_array_pos >= $numDays) {
            $pc_array_pos = 0;
        }
        array_push($sdaynames, $pc_short_day_names[$pc_array_pos]);
        $pc_array_pos++;
    }
    unset($numDays);
    //=================================================================
    // Prepare some values for the template
    //=================================================================
    $prev_month = Date_Calc::beginOfPrevMonth(1, $the_month, $the_year, '%Y%m%d');
    $next_month = Date_Calc::beginOfNextMonth(1, $the_month, $the_year, '%Y%m%d');

    //=================================================================
    // Prepare links for template
    //=================================================================
    $pc_prev = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'month', 'Date' => $prev_month, 'pc_username' => $pc_username, 'pc_category' => $category, 'pc_topic' => $topic));
    $pc_next = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'month', 'Date' => $next_month, 'pc_username' => $pc_username, 'pc_category' => $category, 'pc_topic' => $topic));
    $prev_day = Date_Calc::prevDay($the_day, $the_month, $the_year, '%Y%m%d');
    $next_day = Date_Calc::nextDay($the_day, $the_month, $the_year, '%Y%m%d');
    $pc_prev_day = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'day', 'Date' => $prev_day, 'pc_username' => $pc_username, 'pc_category' => $category, 'pc_topic' => $topic));
    $pc_next_day = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'day', 'Date' => $next_day, 'pc_username' => $pc_username, 'pc_category' => $category, 'pc_topic' => $topic));
    $prev_week = date('Ymd', mktime(0, 0, 0, $week_first_day_month, $week_first_day_date - 7, $week_first_day_year));
    $next_week = date('Ymd', mktime(0, 0, 0, $week_last_day_month, $week_last_day_date + 1, $week_last_day_year));
    $pc_prev_week = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'week', 'Date' => $prev_week, 'pc_username' => $pc_username, 'pc_category' => $category, 'pc_topic' => $topic));
    $pc_next_week = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'week', 'Date' => $next_week, 'pc_username' => $pc_username, 'pc_category' => $category, 'pc_topic' => $topic));
    $prev_year = date('Ymd', mktime(0, 0, 0, 1, 1, $the_year - 1));
    $next_year = date('Ymd', mktime(0, 0, 0, 1, 1, $the_year + 1));
    $pc_prev_year = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'year', 'Date' => $prev_year, 'pc_username' => $pc_username, 'pc_category' => $category, 'pc_topic' => $topic));
    $pc_next_year = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'year', 'Date' => $next_year, 'pc_username' => $pc_username, 'pc_category' => $category, 'pc_topic' => $topic));

    //=================================================================
    // Populate the template
    //=================================================================
    $all_categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
    $categories = array();
    foreach ($all_categories as $category) {
        // compensate for empty category - set to first avail cat
        // this doesn't actually correct the problem in the DB
        // if (!array_key_exists($event_category, $all_categories)) $event_category = $category['catid'];
        // FIXME !!!!!
        $categories[] = array(
                        'value' => $category['catid'],
                        'selected' => ($category['catid'] == $event_category ? 'selected' : ''),
                        'name' => $category['catname'],
                        'color' => $category['catcolor'],
                        'desc' => $category['catdesc']);
    }

    if (isset($calendarView)) $function_out['CAL_FORMAT'] = $calendarView;

    $func = FormUtil::getPassedValue('func');
    $template_view = FormUtil::getPassedValue('tplview');
    if (!$template_view) $template_view = 'month';
    $function_out['FUNCTION'] = $func;
    $function_out['TPL_VIEW'] = $template_view;
    $function_out['VIEW_TYPE'] = $viewtype;
    $function_out['A_MONTH_NAMES'] = $pc_month_names;
    $function_out['A_LONG_DAY_NAMES'] = $pc_long_day_names;
    $function_out['A_SHORT_DAY_NAMES'] = $pc_short_day_names;
    $function_out['S_LONG_DAY_NAMES'] = $daynames;
    $function_out['S_SHORT_DAY_NAMES'] = $sdaynames;
    $function_out['A_EVENTS'] = $eventsByDate;
    $function_out['A_CATEGORY'] = $categories;
    $function_out['PREV_MONTH_URL'] = DataUtil::formatForDisplay($pc_prev);
    $function_out['NEXT_MONTH_URL'] = DataUtil::formatForDisplay($pc_next);
    $function_out['PREV_DAY_URL'] = DataUtil::formatForDisplay($pc_prev_day);
    $function_out['NEXT_DAY_URL'] = DataUtil::formatForDisplay($pc_next_day);
    $function_out['PREV_WEEK_URL'] = DataUtil::formatForDisplay($pc_prev_week);
    $function_out['NEXT_WEEK_URL'] = DataUtil::formatForDisplay($pc_next_week);
    $function_out['PREV_YEAR_URL'] = DataUtil::formatForDisplay($pc_prev_year);
    $function_out['NEXT_YEAR_URL'] = DataUtil::formatForDisplay($pc_next_year);
    $function_out['MONTH_START_DATE'] = $month_view_start;
    $function_out['MONTH_END_DATE'] = $month_view_end;
    $function_out['TODAY_DATE'] = $today_date;
    $function_out['DATE'] = $Date;

    if ($popup) {
        // this concept needs to be changed to simply use a different template if using a popup. CAH 5/9/09
        $theme = pnUserGetTheme();
        $function_out['raw1'] = "<html><head></head><body>\n";
        //$tpl->display("$template");
        // V4B TS start ***     Hook code for displaying stuff for events in popup
        if ($_GET["type"] != "admin") {
            $hooks = pnModCallHooks('item', 'display', $eid, "index.php?module=PostCalendar&amp;type=user&amp;func=view&amp;viewtype=details&amp;eid=$eid&amp;popup=1");
            $function_out['raw2'] .= $hooks;
        }
        $function_out['raw2'] .= "\n</body></html>";
        //session_write_close();
        //exit;
        $function_out['displayaspopup'] = true;
        return $function_out;
    } else {
        return $function_out;
    }
}

/**
 * postcalendar_userapi_eventPreview
 * Creates the detailed event display and outputs html.
 * Accepts an array of key/value pairs
 * @param array $event array of event details from the form
 * @return string html output
 * @access public
 */
function postcalendar_userapi_eventPreview($args)
{
    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($args['event_starttimeh']) ||
        !isset($args['event_starttimem']) ||
        !isset($args['event_startday']) ||
        !isset($args['event_startmonth']) ||
        !isset($args['event_endday']) ||
        !isset($args['event_endmonth']) ||
        !isset($args['event_startampm']) ||
        !isset($args['event_endmonth'])) {
        return LogUtil::registerError(_MODARGSERROR . ' in postcalendar_userapi_eventPreview');
    }

    //=================================================================
    // Setup Render Template Engine
    //=================================================================
    $tpl = pnRender::getInstance('PostCalendar');
    pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);
    $tpl->caching = false;
    /* Trim as needed */
    $func = FormUtil::getPassedValue('func');
    $template_view = FormUtil::getPassedValue('tplview');
    if (!$template_view) $template_view = 'month';
    $tpl->assign('FUNCTION', $func);
    $tpl->assign('TPL_VIEW', $template_view);
    /* end */

    // add preceding zeros
    $event_starttimeh = sprintf('%02d', $args['event_starttimeh']);
    $event_starttimem = sprintf('%02d', $args['event_starttimem']);
    $event_startday = sprintf('%02d', $args['event_startday']);
    $event_startmonth = sprintf('%02d', $args['event_startmonth']);
    $event_endday = sprintf('%02d', $args['event_endday']);
    $event_endmonth = sprintf('%02d', $args['event_endmonth']);

    if (!(bool) _SETTING_TIME_24HOUR) {
        if ($args['event_startampm'] == _PM_VAL) {
            if ($event_starttimeh != 12) {
                $event_starttimeh += 12;
            }
        } elseif ($args['event_startampm'] == _AM_VAL) {
            if ($event_starttimeh == 12) {
                $event_starttimeh = 00;
            }
        }
    }

    $startTime = $event_starttimeh . ':' . $event_starttimem . ' ';

    $event = array();
    $event['eid'] = '';
    $event['uname'] = $uname;
    $event['catid'] = $event_category;
    if ($pc_html_or_text == 'html') {
        $prepFunction = 'DataUtil::formatForDisplayHTML';
    } else {
        $prepFunction = 'DataUtil::formatForDisplay';
    }
    $event['title'] = $prepFunction($event_subject);
    $event['hometext'] = $prepFunction($event_desc);
    $event['desc'] = $event['hometext'];
    $event['date'] = str_pad(str_replace('-', '', $event_startyear . $event_startmonth . $event_startday), 14, '0');
    $event['duration'] = $event_duration;
    $event['duration_hours'] = $event_dur_hours;
    $event['duration_minutes'] = $event_dur_minutes;
    $event['endDate'] = $event_endyear . '-' . $event_endmonth . '-' . $event_endday;
    $event['startTime'] = $startTime;
    $event['recurrtype'] = '';
    $event['recurrfreq'] = '';
    $event['recurrspec'] = $event_recurrspec;
    $event['topic'] = $event_topic;
    $event['alldayevent'] = $event_allday;
    $event['conttel'] = $prepFunction($event_conttel);
    $event['contname'] = $prepFunction($event_contname);
    $event['contemail'] = $prepFunction($event_contemail);
    $event['website'] = $prepFunction(makeValidURL($event_website));
    $event['fee'] = $prepFunction($event_fee);
    $event['location'] = $prepFunction($event_location);
    $event['street1'] = $prepFunction($event_street1);
    $event['street2'] = $prepFunction($event_street2);
    $event['city'] = $prepFunction($event_city);
    $event['state'] = $prepFunction($event_state);
    $event['postal'] = $prepFunction($event_postal);

    $event['meetingdate_start'] = $meetingdate_start;
    //=================================================================
    // get event's topic information
    //=================================================================
    if (_SETTING_DISPLAY_TOPICS) {
        $topic = DBUtil::selectObjectByID('topics', $event['topic'], 'topicid');
        $event['topictext'] = $topic['topictext'];
        $event['topicimage'] = $topic['topicimage'];
    }

    //=================================================================
    // populate the template
    //=================================================================
    if (!empty($event['location']) || !empty($event['street1']) || !empty(
        $event['street2']) || !empty($event['city']) || !empty($event['state']) || !empty($event['postal'])) {
        $tpl->assign('LOCATION_INFO', true);
    } else {
        $tpl->assign('LOCATION_INFO', false);
    }
    if (!empty($event['contname']) || !empty($event['contemail']) || !empty($event['conttel']) || !empty($event['website'])) {
        $tpl->assign('CONTACT_INFO', true);
    } else {
        $tpl->assign('CONTACT_INFO', false);
    }
    $tpl->assign_by_ref('A_EVENT', $event);

    return $tpl->fetch("user/postcalendar_user_view_event_preview.html");
}

/**
 * makeValidURL()
 * returns 'improved' url based on input string
 * checks to make sure scheme is present
 * @private
 * @returns string
 */
function makeValidURL($s)
{
    if (empty($s)) return '';
    if (!preg_match('|^http[s]?:\/\/|i', $s)) $s = 'http://' . $s;
    return $s;
}

function postcalendar_userapi_getDate($args)
{
    if (!is_array($args)) {
        $format = $args; //backwards compatibility
    } else {
        $format    = $args['format'];
        $Date      = $args['Date'];
        $jumpday   = $args['jumpday'];
        $jumpmonth = $args['jumpmonth'];
        $jumpyear  = $args['jumpyear'];
    }
    if(empty($format)) $format = '%Y%m%d%H%M%S'; // default format

    if (empty($Date)) {
        // if we still don't have a date then calculate it
        $time = time();
        if (pnUserLoggedIn()) $time += (pnUserGetVar('timezone_offset') - pnConfigGetVar('timezone_offset')) * 3600;
        // check the jump menu
        if (!isset($jumpday))   $jumpday = strftime('%d', $time);
        if (!isset($jumpmonth)) $jumpmonth = strftime('%m', $time);
        if (!isset($jumpyear))  $jumpyear = strftime('%Y', $time);
        $Date = (int) "$jumpyear$jumpmonth$jumpday";
    }

    $y = substr($Date, 0, 4);
    $m = substr($Date, 4, 2);
    $d = substr($Date, 6, 2);
    return strftime($format, mktime(0, 0, 0, $m, $d, $y));
}

/**
 * postcalendar_userapi_getmonthname()
 *
 * Returns the month name translated for the user's current language
 *
 * @param array $args['Date'] date to return month name of
 * @return string month name in user's language
 */
function postcalendar_userapi_getmonthname($args)
{
    if (!isset($args['Date'])) return LogUtil::registerError(_MODARGSERROR . ' in postcalendar_userapi_getmonthname');

    $month_name = array('01' => _CALJAN, '02' => _CALFEB, '03' => _CALMAR,
                    '04' => _CALAPR, '05' => _CALMAY, '06' => _CALJUN,
                    '07' => _CALJUL, '08' => _CALAUG, '09' => _CALSEP,
                    '10' => _CALOCT, '11' => _CALNOV, '12' => _CALDEC);
    return $month_name[date('m', $args['Date'])];
}

function postcalendar_userapi_getCategories()
{
    return DBUtil::selectObjectArray('postcalendar_categories', '', 'catname');
}

function postcalendar_userapi_getTopics()
{
    $permFilter = array();
    $permFilter[] = array(
                    'realm'            => 0,
                    'component_left'   => 'PostCalendar',
                    'component_middle' => '',
                    'component_right'  => 'Topic',
                    'instance_left'    => 'topicid',
                    'instance_middle'  => '',
                    'instance_right'   => 'topicname',
                    'level'        => ACCESS_OVERVIEW);

    return DBUtil::selectObjectArray('topics', '', 'topictext', -1, -1, '', $permFilter);
}
function postcalendar_userapi_SmartySetup(&$smarty)
{
    if (!is_object($smarty)) return LogUtil::registerError(_MODARGSERROR . ' in postcalendar_userapi_SmartySetup');

    $smarty->assign('USE_POPUPS', _SETTING_USE_POPUPS);
    $smarty->assign('USE_TOPICS', _SETTING_DISPLAY_TOPICS);
    $smarty->assign('USE_INT_DATES', _SETTING_USE_INT_DATES);
    $smarty->assign('OPEN_NEW_WINDOW', _SETTING_OPEN_NEW_WINDOW);
    $smarty->assign('EVENT_DATE_FORMAT', _SETTING_DATE_FORMAT);
    $smarty->assign('HIGHLIGHT_COLOR', _SETTING_DAY_HICOLOR);
    $smarty->assign('24HOUR_TIME', _SETTING_TIME_24HOUR);
    return true;
}

/****************************************************
 * The functions below are moved to eventapi
 ****************************************************/
function postcalendar_userapi_pcGetEvents($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'getEvents', $args);
}
function postcalendar_userapi_pcQueryEvents($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'queryEvents', $args);
}
function postcalendar_userapi_deleteevents($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'deleteevent', $args);
}
function postcalendar_userapi_submitEvent($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'writeEvent', $args);
}
function postcalendar_userapi_buildSubmitForm($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'buildSubmitForm', $args);
}
function postcalendar_userapi_pcFixEventDetails($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'fixEventDetails', $args);
}
function postcalendar_userapi_pcGetEventDetails($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'getEventDetails', $args);
}
function postcalendar_userapi_eventDetail($args)
{
    return pnModAPIFunc('PostCalendar', 'event', 'eventDetail', $args);
}