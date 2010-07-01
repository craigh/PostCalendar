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

class PostCalendar_Block_Calendar extends Zikula_Block
{
    /**
     * initialise block
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema('PostCalendar:calendarblock:', 'Block title::');
    }
    
    /**
     * get information on block
     */
    public function info()
    {
        return array(
            'text_type'      => 'PostCalendar',
            'module'         => 'PostCalendar',
            'text_type_long' => $this-.__('Calendar Block'),
            'allow_multiple' => true,
            'form_content'   => false,
            'form_refresh'   => false,
            'show_preview'   => true);
    }
    
    /**
     * display block
     */
    public function display($blockinfo)
    {
        if (!SecurityUtil::checkPermission('PostCalendar:calendarblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
            return;
        }
        if (!ModUtil::available('PostCalendar')) {
            return;
        }
    
        // today's date
        $Date = DateUtil::getDatetime('', '%Y%m%d%H%M%S');
    
        // Get variables from content block
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        $showcalendar   = $vars['pcbshowcalendar'];
        $showevents     = $vars['pcbeventoverview'];
        $hideevents     = $vars['pcbhideeventoverview'];
        $eventslimit    = $vars['pcbeventslimit'];
        $nextevents     = $vars['pcbnextevents'];
        $pcbshowsslinks = $vars['pcbshowsslinks'];
        $pcbeventsrange = $vars['pcbeventsrange'];
        $pcbfiltercats  = $vars['pcbfiltercats'];
    
        // setup the info to build this
        $the_year  = substr($Date, 0, 4);
        $the_month = substr($Date, 4, 2);
        $the_day   = substr($Date, 6, 2);
    
        $output = '';
    
        // If block is cached, return cached version
        $this->renderer->cache_id = $blockinfo['bid'] . ':' . UserUtil::getVar('uid');
        $templates_cached = true;
        if ($showcalendar) {
            if (!$this->renderer->is_cached('blocks/view_month.tpl')) {
                $templates_cached = false;
            }
        }
        if ($showevents) {
            if (!$this->renderer->is_cached('blocks/view_day.tpl')) {
                $templates_cached = false;
            }
        }
        if ($nextevents) {
            if (!$this->renderer->is_cached('blocks/view_upcoming.tpl')) {
                $templates_cached = false;
            }
        }
        if ($pcbshowsslinks) {
            if (!$this->renderer->is_cached('blocks/calendarlinks.tpl')) {
                $templates_cached = false;
            }
        }
    
        if ($templates_cached) {
            $blockinfo['content'] = $this->renderer->fetch('blocks/view_month.tpl');
            $blockinfo['content'] .= $this->renderer->fetch('blocks/view_day.tpl');
            $blockinfo['content'] .= $this->renderer->fetch('blocks/view_upcoming.tpl');
            $blockinfo['content'] .= $this->renderer->fetch('blocks/calendarlinks.tpl');
    
            return BlockUtil::themeBlock($blockinfo);
        }
        // end cache return
    
        // set up the next and previous months to move to
        $prev_month = DateUtil::getDatetime_NextMonth(-1, '%Y%m%d', $the_year, $the_month, 1);
        $next_month = DateUtil::getDatetime_NextMonth(1, '%Y%m%d', $the_year, $the_month, 1);
        $pc_prev = ModUtil::url('PostCalendar', 'user', 'view', array(
            'viewtype' => 'month',
            'Date'     => $prev_month));
        $pc_next = ModUtil::url('PostCalendar', 'user', 'view', array(
            'viewtype' => 'month',
            'Date'     => $next_month));
        $pc_month_name = DateUtil::strftime("%B", strtotime($Date));
        $month_link_url = ModUtil::url('PostCalendar', 'user', 'view', array(
            'viewtype' => 'month',
            'Date'     => date('Ymd', mktime(0, 0, 0, $the_month, 1, $the_year))));
        $month_link_text = $pc_month_name . ' ' . $the_year;
    
        $pc_colclasses      = array(
            0 => "pcWeekday", 
            1 => "pcWeekday", 
            2 => "pcWeekday", 
            3 => "pcWeekday", 
            4 => "pcWeekday", 
            5 => "pcWeekday", 
            6 => "pcWeekday");
        switch (_SETTING_FIRST_DAY_WEEK) {
            case _IS_MONDAY:
                $pc_array_pos = 1;
                $first_day = date('w', mktime(0, 0, 0, $the_month, 0, $the_year));
                $pc_colclasses[5] = "pcWeekend";
                $pc_colclasses[6] = "pcWeekend";
                break;
            case _IS_SATURDAY:
                $pc_array_pos = 6;
                $first_day = date('w', mktime(0, 0, 0, $the_month, 2, $the_year));
                $pc_colclasses[0] = "pcWeekend";
                $pc_colclasses[1] = "pcWeekend";
                break;
            case _IS_SUNDAY:
            default:
                $pc_array_pos = 0;
                $first_day = date('w', mktime(0, 0, 0, $the_month, 1, $the_year));
                $pc_colclasses[0] = "pcWeekend";
                $pc_colclasses[6] = "pcWeekend";
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
        $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
            'start'      => $starting_date,
            'end'        => $ending_date,
            'filtercats' => $filtercats));
        $calendarView = Date_Calc::getCalendarMonth($the_month, $the_year, '%Y-%m-%d');
    
        $pc_short_day_names = explode (" ", $this->__(/*!First Letter of each Day of week*/'S M T W T F S'));
        $sdaynames = array();
        for ($i = 0; $i < 7; $i++) {
            if ($pc_array_pos >= 7) {
                $pc_array_pos = 0;
            }
            $sdaynames[] = $pc_short_day_names[$pc_array_pos];
            $pc_array_pos++;
        }
    
        if (isset($calendarView)) {
            $this->renderer->assign('CAL_FORMAT', $calendarView);
        }
    
        $countTodaysEvents = count($eventsByDate[$today_date]);
        $hideTodaysEvents  = ($hideevents && ($countTodaysEvents == 0)) ? true : false;
    
        $this->renderer->assign('S_SHORT_DAY_NAMES', $sdaynames);
        $this->renderer->assign('A_EVENTS',          $eventsByDate);
        $this->renderer->assign('todaysEvents',      $eventsByDate[$today_date]);
        $this->renderer->assign('hideTodaysEvents',  $hideTodaysEvents);
        $this->renderer->assign('PREV_MONTH_URL',    $pc_prev);
        $this->renderer->assign('NEXT_MONTH_URL',    $pc_next);
        $this->renderer->assign('MONTH_START_DATE',  $month_view_start);
        $this->renderer->assign('MONTH_END_DATE',    $month_view_end);
        $this->renderer->assign('TODAY_DATE',        $today_date);
        $this->renderer->assign('DATE',              $Date);
        $this->renderer->assign('DISPLAY_LIMIT',     $eventslimit);
        $this->renderer->assign('pc_colclasses',     $pc_colclasses);
    
        if ($showcalendar) {
            $output .= $this->renderer->fetch('blocks/view_month.tpl');
        }
    
        if ($showevents) {
            if ($showcalendar) {
                $this->renderer->assign('SHOW_TITLE', 1);
            } else {
                $this->renderer->assign('SHOW_TITLE', 0);
            }
            $output .= $this->renderer->fetch('blocks/view_day.tpl');
        }
    
        if ($nextevents) {
            if ($showcalendar || $showevents) {
                $this->renderer->assign('SHOW_TITLE', 1);
            } else {
                $this->renderer->assign('SHOW_TITLE', 0);
            }
            $output .= $this->renderer->fetch('blocks/view_upcoming.tpl');
        }
    
        if ($pcbshowsslinks) {
            $output .= $this->renderer->fetch('blocks/calendarlinks.tpl');
        }
    
        $blockinfo['content'] = $output;
        return BlockUtil::themeBlock($blockinfo);
    }
    
    /**
     * modify block settings ..
     */
    public function modify($blockinfo)
    {
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        // Defaults
        if (empty($vars['pcbshowcalendar']))      $vars['pcbshowcalendar']      = 0;
        if (empty($vars['pcbeventslimit']))       $vars['pcbeventslimit']       = 5;
        if (empty($vars['pcbeventoverview']))     $vars['pcbeventoverview']     = 0;
        if (empty($vars['pcbhideeventoverview'])) $vars['pcbhideeventoverview'] = 0;
        if (empty($vars['pcbnextevents']))        $vars['pcbnextevents']        = 0;
        if (empty($vars['pcbeventsrange']))       $vars['pcbeventsrange']       = 6;
        if (empty($vars['pcbshowsslinks']))       $vars['pcbshowsslinks']       = 0;
        if (empty($vars['pcbfiltercats']))        $vars['pcbfiltercats']        = array();
    
        // load the category registry util
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $this->renderer->assign('catregistry', $catregistry);
    
        $props = array_keys($catregistry);
        $this->renderer->assign('firstprop', $props[0]);
    
        $this->renderer->assign('vars', $vars);
    
        return $this->renderer->fetch('blocks/calendar_modify.tpl');
    }
    
    /**
     * update block settings
     */
    public function update($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        // overwrite with new values
        $vars['pcbshowcalendar']      = FormUtil::getPassedValue('pcbshowcalendar',      0);
        $vars['pcbeventslimit']       = FormUtil::getPassedValue('pcbeventslimit',       5);
        $vars['pcbeventoverview']     = FormUtil::getPassedValue('pcbeventoverview',     0);
        $vars['pcbhideeventoverview'] = FormUtil::getPassedValue('pcbhideeventoverview', 0);
        $vars['pcbnextevents']        = FormUtil::getPassedValue('pcbnextevents',        0);
        $vars['pcbeventsrange']       = FormUtil::getPassedValue('pcbeventsrange',       6);
        $vars['pcbshowsslinks']       = FormUtil::getPassedValue('pcbshowsslinks',       0);
        $vars['pcbfiltercats']        = FormUtil::getPassedValue('pcbfiltercats'); //array
    
        $this->renderer->clear_cache('blocks/view_day.tpl');
        $this->renderer->clear_cache('blocks/view_month.tpl');
        $this->renderer->clear_cache('blocks/view_upcoming.tpl');
        $this->renderer->clear_cache('blocks/calendarlinks.tpl');
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
    
        return $blockinfo;
    }
} // end class def