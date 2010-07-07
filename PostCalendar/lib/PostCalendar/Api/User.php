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

class PostCalendar_Api_User extends Zikula_Api
{
    /**
     * buildView
     *
     * Builds the calendar display
     * @param string $Date mm/dd/yyyy format (we should use timestamps)
     * @return string generated html output
     * @access public
     */
    public function buildView($args)
    {
        $Date        = $args['Date'];
        $viewtype    = $args['viewtype'];
        $pc_username = $args['pc_username'];
        $filtercats  = $args['filtercats'];
        $func        = $args['func'];
    
        if (strlen($Date) == 8 && is_numeric($Date)) {
            $Date .= '000000'; // 20060101 + 000000
        }
    
        // finish setting things up
        $the_year  = substr($Date, 0, 4);
        $the_month = substr($Date, 4, 2);
        $the_day   = substr($Date, 6, 2);
        $last_day  = DateUtil::getDaysInMonth($the_month, $the_year);
    
        $pc_colclasses = array(
            0 => "pcWeekday", 
            1 => "pcWeekday", 
            2 => "pcWeekday", 
            3 => "pcWeekday", 
            4 => "pcWeekday", 
            5 => "pcWeekday", 
            6 => "pcWeekday");
    
        // set up some information for later variable creation.
        // This helps establish the correct date ranges for each view.
        // There may be a better way to handle all this.
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
                $pc_colclasses[5] = "pcWeekend";
                $pc_colclasses[6] = "pcWeekend";
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
                $pc_colclasses[0] = "pcWeekend";
                $pc_colclasses[1] = "pcWeekend";
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
                $pc_colclasses[0] = "pcWeekend";
                $pc_colclasses[6] = "pcWeekend";
                break;
        }
        // prepare Month Names, Long Day Names and Short Day Names
        $pc_short_day_names = explode(" ", $this->__(/*!First Letter of each Day of week*/'S M T W T F S'));
        $pc_long_day_names  = explode(" ", $this->__('Sunday Monday Tuesday Wednesday Thursday Friday Saturday'));
        // Create an array with the day names in the correct order
        $daynames = array();
        $sdaynames = array();
        for ($i = 0; $i < 7; $i++) {
            if ($pc_array_pos >= 7) {
                $pc_array_pos = 0;
            }
            $daynames[]  = $pc_long_day_names[$pc_array_pos];
            $sdaynames[] = $pc_short_day_names[$pc_array_pos];
            $pc_array_pos++;
        }
    
        $function_out = array();
    
        // Setup the starting and ending date ranges for pcGetEvents()
        switch ($viewtype) {
            case 'day':
                $starting_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_day, $the_year));
                $ending_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_day, $the_year));
    
                $prev_day = DateUtil::getDatetime_NextDay(-1, '%Y%m%d', $the_year, $the_month, $the_day);
                $next_day = DateUtil::getDatetime_NextDay(1, '%Y%m%d', $the_year, $the_month, $the_day);
                $pc_prev_day = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => 'day',
                    'Date' => $prev_day,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $pc_next_day = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => 'day',
                    'Date' => $next_day,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $function_out['PREV_DAY_URL'] = DataUtil::formatForDisplay($pc_prev_day);
                $function_out['NEXT_DAY_URL'] = DataUtil::formatForDisplay($pc_next_day);
                break;
            case 'week':
                $first_day_of_week = sprintf('%02d', $the_day - $week_day);
                $week_first_day = date('m/d/Y', mktime(0, 0, 0, $the_month, $first_day_of_week, $the_year));
                list ($week_first_day_month, $week_first_day_date, $week_first_day_year) = explode('/', $week_first_day);
                $week_last_day = date('m/d/Y', mktime(0, 0, 0, $the_month, $first_day_of_week + 6, $the_year));
                list ($week_last_day_month, $week_last_day_date, $week_last_day_year) = explode('/', $week_last_day);
    
                $starting_date = "$week_first_day_month/$week_first_day_date/$week_first_day_year";
                $ending_date = "$week_last_day_month/$week_last_day_date/$week_last_day_year";
                $calendarView = Date_Calc::getCalendarWeek($week_first_day_date, $week_first_day_month, $week_first_day_year, '%Y-%m-%d');
    
                $prev_week = date('Ymd', mktime(0, 0, 0, $week_first_day_month, $week_first_day_date - 7, $week_first_day_year));
                $next_week = date('Ymd', mktime(0, 0, 0, $week_last_day_month, $week_last_day_date + 1, $week_last_day_year));
                $pc_prev_week = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => 'week',
                    'Date' => $prev_week,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $pc_next_week = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => 'week',
                    'Date' => $next_week,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $function_out['PREV_WEEK_URL'] = DataUtil::formatForDisplay($pc_prev_week);
                $function_out['NEXT_WEEK_URL'] = DataUtil::formatForDisplay($pc_next_week);
                break;
            case 'month':
                $starting_date = date('m/d/Y', mktime(0, 0, 0, $the_month, 1 - $first_day, $the_year));
                $ending_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_last_day, $the_year));
                $calendarView = Date_Calc::getCalendarMonth($the_month, $the_year, '%Y-%m-%d');
    
                $prev_month = DateUtil::getDatetime_NextMonth(-1, '%Y%m%d', $the_year, $the_month, 1);
                $next_month = DateUtil::getDatetime_NextMonth(1, '%Y%m%d', $the_year, $the_month, 1);
                $pc_prev_month = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => $viewtype,
                    'Date' => $prev_month,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $pc_next_month = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => $viewtype,
                    'Date' => $next_month,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $function_out['PREV_MONTH_URL']   = DataUtil::formatForDisplay($pc_prev_month);
                $function_out['NEXT_MONTH_URL']   = DataUtil::formatForDisplay($pc_next_month);
                $function_out['S_LONG_DAY_NAMES'] = $daynames;
                break;
            case 'year':
                $starting_date = date('m/d/Y', mktime(0, 0, 0, 1, 1, $the_year));
                $ending_date = date('m/d/Y', mktime(0, 0, 0, 1, 1, $the_year + 1));
                $calendarView = Date_Calc::getCalendarYear($the_year, '%Y-%m-%d');
    
                $prev_year = date('Ymd', mktime(0, 0, 0, 1, 1, $the_year - 1));
                $next_year = date('Ymd', mktime(0, 0, 0, 1, 1, $the_year + 1));
                $pc_prev_year = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => 'year',
                    'Date' => $prev_year,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $pc_next_year = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => 'year',
                    'Date' => $next_year,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $function_out['PREV_YEAR_URL']      = DataUtil::formatForDisplay($pc_prev_year);
                $function_out['NEXT_YEAR_URL']      = DataUtil::formatForDisplay($pc_next_year);
                $function_out['A_MONTH_NAMES']      = explode(" ", $this->__('January February March April May June July August September October November December'));
                $function_out['S_SHORT_DAY_NAMES']  = $sdaynames;
                break;
            case 'xml':
            case 'list':
                $listmonths    = ModUtil::getVar('PostCalendar', 'pcListMonths');
                $listyears     = floor($listmonths/12);
                $listendyears  = (int) $the_year + (int) $listyears;
                $listmonths    = $listmonths % 12;
                $listendmonths = (int) $the_month + (int) $listmonths;
                if ($listendmonths > 12) {
                    $listendyears++;
                    $listendmonths = $listendmonths - 12;
                }
                $starting_date = "$the_month/$the_day/$the_year";
                $ending_date   = "$listendmonths/$the_day/$listendyears";
    
                $prev_list = date('Ymd', mktime(0, 0, 0, $the_month - $listmonths, $the_day, $the_year));
                $next_list = date('Ymd', mktime(0, 0, 0, $listendmonths, $the_day, $listendyears));
                $pc_prev_list = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => 'list',
                    'Date' => $prev_list,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $pc_next_list = ModUtil::url('PostCalendar', 'user', 'view', array(
                    'viewtype' => 'list',
                    'Date' => $next_list,
                    'pc_username' => $pc_username,
                    'filtercats' => $filtercats));
                $function_out['PREV_LIST_URL'] = DataUtil::formatForDisplay($pc_prev_list);
                $function_out['NEXT_LIST_URL'] = DataUtil::formatForDisplay($pc_next_list);
                break;
        }
    
        // Load the events
        $eventsByDate = & ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
            'start' => $starting_date,
            'end' => $ending_date,
            'filtercats' => $filtercats,
            'Date' => $Date,
            'pc_username' => $pc_username));
    
        if (isset($calendarView)) {
            $function_out['CAL_FORMAT'] = $calendarView;
        }
        // convert categories array to proper filter info
        $selectedcategories = array();
        if (is_array($filtercats)) {
            $catsarray = $filtercats['__CATEGORIES__'];
            foreach ($catsarray as $propname => $propid) {
                if ($propid > 0) {
                    $selectedcategories[$propname] = $propid; // removes categories set to 'all'
                }
            }
        }
    
        $function_out['FUNCTION']           = $func;
        $function_out['VIEW_TYPE']          = $viewtype;
        $function_out['A_EVENTS']           = $eventsByDate;
        $function_out['selectedcategories'] = $selectedcategories;
        $function_out['MONTH_START_DATE']   = date('Y-m-d', mktime(0, 0, 0, $the_month, 1, $the_year));
        $function_out['MONTH_END_DATE']     = date('Y-m-t', mktime(0, 0, 0, $the_month, 1, $the_year));
        $function_out['TODAY_DATE']         = DateUtil::getDatetime('', '%Y-%m-%d');
        $function_out['DATE']               = $Date;
        $function_out['pc_colclasses']      = $pc_colclasses;
    
        return $function_out;
    }
} // end class def