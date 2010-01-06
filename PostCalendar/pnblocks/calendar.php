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

include_once 'modules/PostCalendar/pnincludes/DateCalc.class.php';

/**
 * initialise block
 */
function postcalendar_calendarblock_init()
{
    SecurityUtil::registerPermissionSchema('PostCalendar:calendarblock:', 'Block title::');
}

/**
 * get information on block
 */
function postcalendar_calendarblock_info()
{
    return array(
        'text_type'      => 'PostCalendar',
        'module'         => 'PostCalendar',
        'text_type_long' => 'Calendar Block',
        'allow_multiple' => true,
        'form_content'   => false,
        'form_refresh'   => false,
        'show_preview'   => true);
}

/**
 * display block
 */
function postcalendar_calendarblock_display($blockinfo)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!SecurityUtil::checkPermission('PostCalendar:calendarblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
        return;
    }
    if (!pnModAvailable('PostCalendar')) {
        return;
    }

    // today's date
    $Date = DateUtil::getDatetime('', '%Y%m%d%H%M%S');

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    $showcalendar   = $vars['pcbshowcalendar'];
    $showevents     = $vars['pcbeventoverview'];
    $eventslimit    = $vars['pcbeventslimit'];
    $nextevents     = $vars['pcbnextevents'];
    $pcbshowsslinks = $vars['pcbshowsslinks'];
    $pcbeventsrange = $vars['pcbeventsrange'];
    $pcbfiltercats  = $vars['pcbfiltercats'];


    // setup the info to build this
    $the_year  = substr($Date, 0, 4);
    $the_month = substr($Date, 4, 2);
    $the_day   = substr($Date, 6, 2);

    $tpl = pnRender::getInstance('PostCalendar');
    $output = '';

    // If block is cached, return cached version
    $tpl->cache_id = $blockinfo['bid'] . ':' . pnUserGetVar('uid');
    $templates_cached = true;
    if ($showcalendar) {
        if (!$tpl->is_cached('blocks/postcalendar_block_view_month.htm')) {
            $templates_cached = false;
        }
    }
    if ($showevents) {
        if (!$tpl->is_cached('blocks/postcalendar_block_view_day.htm')) {
            $templates_cached = false;
        }
    }
    if ($nextevents) {
        if (!$tpl->is_cached('blocks/postcalendar_block_view_upcoming.htm')) {
            $templates_cached = false;
        }
    }
    if ($pcbshowsslinks) {
        if (!$tpl->is_cached('blocks/postcalendar_block_calendarlinks.htm')) {
            $templates_cached = false;
        }
    }

    if ($templates_cached) {
        $blockinfo['content'] = $tpl->fetch('blocks/postcalendar_block_view_month.htm');
        $blockinfo['content'] .= $tpl->fetch('blocks/postcalendar_block_view_day.htm');
        $blockinfo['content'] .= $tpl->fetch('blocks/postcalendar_block_view_upcoming.htm');
        $blockinfo['content'] .= $tpl->fetch('blocks/postcalendar_block_calendarlinks.htm');

        return pnBlockThemeBlock($blockinfo);
    }
    // end cache return

    // set up the next and previous months to move to
    $prev_month = DateUtil::getDatetime_NextMonth(-1, '%Y%m%d', $the_year, $the_month, 1);
    $next_month = DateUtil::getDatetime_NextMonth(1, '%Y%m%d', $the_year, $the_month, 1);
    $last_day   = DateUtil::getDaysInMonth($the_month, $the_year);
    $pc_prev = pnModURL('PostCalendar', 'user', 'view', array(
        'viewtype' => 'month',
        'Date' => $prev_month));
    $pc_next = pnModURL('PostCalendar', 'user', 'view', array(
        'viewtype' => 'month',
        'Date' => $next_month));
    $pc_month_name = pnModAPIFunc('PostCalendar', 'user', 'getmonthname', array(
        'Date' => mktime(0, 0, 0, $the_month, $the_day, $the_year)));
    $month_link_url = pnModURL('PostCalendar', 'user', 'view', array(
        'viewtype' => 'month',
        'Date' => date('Ymd', mktime(0, 0, 0, $the_month, 1, $the_year))));
    $month_link_text = $pc_month_name . ' ' . $the_year;
    //*******************************************************************
    // get the events for the current month view
    //*******************************************************************
    $day_of_week = 1;
    $pc_month_names     = explode (" ", __('January February March April May June July August September October November December', $dom));
    $pc_short_day_names = explode (" ", __(/*!First Letter of each Day of week*/'S M T W T F S', $dom));
    $pc_long_day_names  = explode (" ", __('Sunday Monday Tuesday Wednesday Thursday Friday Saturday', $dom));
    switch (_SETTING_FIRST_DAY_WEEK) {
        case _IS_MONDAY:
            $pc_array_pos = 1;
            $first_day = date('w', mktime(0, 0, 0, $the_month, 0, $the_year));
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
            $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
            if ($end_dow != 6) {
                $the_last_day = $last_day + (6 - $end_dow);
            } else {
                $the_last_day = $last_day;
            }
            break;
    }

    $month_view_start = date('Y-m-d', mktime(0, 0, 0, $the_month, 1, $the_year));
    $month_view_end   = date('Y-m-t', mktime(0, 0, 0, $the_month, 1, $the_year));
    $today_date       = DateUtil::getDatetime('', '%Y-%m-%d');
    $starting_date    = date('m/d/Y', mktime(0, 0, 0, $the_month, 1 - $first_day, $the_year));
    $ending_date      = date('m/t/Y', mktime(0, 0, 0, $the_month + $pcbeventsrange, 1, $the_year));

    // this grabs more events that required and could slow down the process. RNG
    // suggest addming $limit paramter to getEvents() to reduce load CAH Sept 29, 2009
    $filtercats['__CATEGORIES__'] = $pcbfiltercats; //reformat array
    $eventsByDate = pnModAPIFunc('PostCalendar', 'event', 'getEvents', array(
        'start'      => $starting_date,
        'end'        => $ending_date,
        'filtercats' => $filtercats));
    $calendarView = Date_Calc::getCalendarMonth($the_month, $the_year, '%Y-%m-%d');

    $sdaynames = array();
    $numDays = count($pc_short_day_names);
    for ($i = 0; $i < $numDays; $i++) {
        if ($pc_array_pos >= $numDays) {
            $pc_array_pos = 0;
        }
        array_push($sdaynames, $pc_short_day_names[$pc_array_pos]);
        $pc_array_pos++;
    }

    $daynames = array();
    $numDays = count($pc_long_day_names);
    for ($i = 0; $i < $numDays; $i++) {
        if ($pc_array_pos >= $numDays) {
            $pc_array_pos = 0;
        }
        array_push($daynames, $pc_long_day_names[$pc_array_pos]);
        $pc_array_pos++;
    }

    $categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
    if (isset($calendarView)) {
        $tpl->assign_by_ref('CAL_FORMAT', $calendarView);
    }

    $tpl->assign_by_ref('A_MONTH_NAMES', $pc_month_names);
    $tpl->assign_by_ref('A_LONG_DAY_NAMES', $pc_long_day_names);
    $tpl->assign_by_ref('A_SHORT_DAY_NAMES', $pc_short_day_names);
    $tpl->assign_by_ref('S_LONG_DAY_NAMES', $daynames);
    $tpl->assign_by_ref('S_SHORT_DAY_NAMES', $sdaynames);
    $tpl->assign_by_ref('A_EVENTS', $eventsByDate);
    $tpl->assign_by_ref('A_CATEGORY', $categories);
    $tpl->assign_by_ref('PREV_MONTH_URL', $pc_prev);
    $tpl->assign_by_ref('NEXT_MONTH_URL', $pc_next);
    $tpl->assign_by_ref('MONTH_START_DATE', $month_view_start);
    $tpl->assign_by_ref('MONTH_END_DATE', $month_view_end);
    $tpl->assign_by_ref('TODAY_DATE', $today_date);
    $tpl->assign_by_ref('DATE', $Date);
    $tpl->assign_by_ref('DISPLAY_LIMIT', $eventslimit);

    if ($showcalendar) {
        $output .= $tpl->fetch('blocks/postcalendar_block_view_month.htm');
    }

    if ($showevents) {
        if ($showcalendar) {
            $tpl->assign('SHOW_TITLE', 1);
        } else {
            $tpl->assign('SHOW_TITLE', 0);
        }
        $output .= $tpl->fetch('blocks/postcalendar_block_view_day.htm');
    }

    if ($nextevents) {
        if ($showcalendar || $showevents) {
            $tpl->assign('SHOW_TITLE', 1);
        } else {
            $tpl->assign('SHOW_TITLE', 0);
        }
        $output .= $tpl->fetch('blocks/postcalendar_block_view_upcoming.htm');
    }

    if ($pcbshowsslinks) {
        $output .= $tpl->fetch('blocks/postcalendar_block_calendarlinks.htm');
    }

    $blockinfo['content'] = $output;
    return pnBlockThemeBlock($blockinfo);
}

/**
 * modify block settings ..
 */
function postcalendar_calendarblock_modify($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    // Defaults
    if (empty($vars['pcbshowcalendar']))  $vars['pcbshowcalendar']  = 0;
    if (empty($vars['pcbeventslimit']))   $vars['pcbeventslimit']   = 5;
    if (empty($vars['pcbeventoverview'])) $vars['pcbeventoverview'] = 0;
    if (empty($vars['pcbnextevents']))    $vars['pcbnextevents']    = 0;
    if (empty($vars['pcbeventsrange']))   $vars['pcbeventsrange']   = 6;
    if (empty($vars['pcbshowsslinks']))   $vars['pcbshowsslinks']   = 0;
    if (empty($vars['pcbfiltercats']))    $vars['pcbfiltercats']    = array();

    $pnRender = pnRender::getInstance('PostCalendar', false); // no caching

    // load the category registry util
    if (Loader::loadClass('CategoryRegistryUtil')) {
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $pnRender->assign('catregistry', $catregistry);
    }
    $props = array_keys($catregistry);
    $pnRender->assign('firstprop', $props[0]);

    $pnRender->assign('vars', $vars);

    return $pnRender->fetch('blocks/postcalendar_block_calendar_modify.htm');
}

/**
 * update block settings
 */
function postcalendar_calendarblock_update($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // overwrite with new values
    $vars['pcbshowcalendar']  = FormUtil::getPassedValue('pcbshowcalendar',  0);
    $vars['pcbeventslimit']   = FormUtil::getPassedValue('pcbeventslimit',   5);
    $vars['pcbeventoverview'] = FormUtil::getPassedValue('pcbeventoverview', 0);
    $vars['pcbnextevents']    = FormUtil::getPassedValue('pcbnextevents',    0);
    $vars['pcbeventsrange']   = FormUtil::getPassedValue('pcbeventsrange',   6);
    $vars['pcbshowsslinks']   = FormUtil::getPassedValue('pcbshowsslinks',   0);
    $vars['pcbfiltercats']    = FormUtil::getPassedValue('pcbfiltercats'); //array

    $pnRender = pnRender::getInstance('PostCalendar');
    $pnRender->clear_cache('blocks/postcalendar_block_view_day.htm');
    $pnRender->clear_cache('blocks/postcalendar_block_view_month.htm');
    $pnRender->clear_cache('blocks/postcalendar_block_view_upcoming.htm');
    $pnRender->clear_cache('blocks/postcalendar_block_calendarlinks.htm');
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    return $blockinfo;
}
