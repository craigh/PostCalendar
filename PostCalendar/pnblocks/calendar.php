<?php
/**
 *  SVN: $Id$
 *
 *  @package         PostCalendar 
 *  @lastmodified    $Date$ 
 *  @modifiedby      $Author$ 
 *  @HeadURL	       $HeadURL$ 
 *  @version         $Revision$ 
 *  
 *  PostCalendar::PostNuke Events Calendar Module
 *  Copyright (C) 2002  The PostCalendar Team
 *  http://postcalendar.tv
 *  Copyright (C) 2009  Sound Web Development
 *  Craig Heydenburg
 *  http://code.zikula.org/soundwebdevelopment/
 *  
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *  To read the license please read the docs/license.txt or visit
 *  http://www.gnu.org/copyleft/gpl.html
 *
 */

/**
 * initialise block
 */
function postcalendar_calendarblock_init()
{
    pnSecAddSchema('PostCalendar:calendarblock:', 'Block title::');
}

/**
 * get information on block
 */
function postcalendar_calendarblock_info()
{
    return array('text_type'      => 'PostCalendar',
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
    // You supposed to be here?
    if (!pnSecAuthAction(0,'PostCalendar:calendarblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
        return false;
    }

    if (!pnModLoad('PostCalendar'))
        return 'Unable to load module [PostCalendar]';
    
    // find out what view we're using
/*
    $template_view = FormUtil::getPassedValue ('tplview');
    if(!isset($template_view)) {
        $template_view ='default';
    }

    // find out what template we're using
    $template_name = _SETTING_TEMPLATE;
    if(!isset($template_name) || empty($template_name)) {
        $template_name ='default';
    }
*/    
    // What is today's correct date
    $Date = pnModFunc('postcalendar', 'user', 'getDate');
    
    // Get variables from content block
    $vars = unserialize($blockinfo['content']);
    $showcalendar   = $vars['pcbshowcalendar'];
    $showevents     = $vars['pcbeventoverview'];
    $eventslimit    = $vars['pcbeventslimit'];
    $nextevents     = $vars['pcbnextevents'];
    $pcbshowsslinks = $vars['pcbshowsslinks'];
    $pcbeventsrange = $vars['pcbeventsrange'];
    
    // Let's setup the info to build this sucka!
    $the_year   = substr($Date,0,4);
    $the_month  = substr($Date,4,2);
    $the_day    = substr($Date,6,2);
    $uid = pnUserGetVar('uid');
    
    $cacheid1 = $cacheid2 = $cacheid3 = '';
    $theme = pnUserGetTheme();
    
    //pnThemeLoad($theme);
    //global $bgcolor1, $bgcolor2, $bgcolor3, $bgcolor4, $bgcolor5;
    //global $textcolor1, $textcolor2;
    
    // 20021125 - rraymond :: we have to do this to make it work with envolution
    $pcModInfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    $pcDir = pnVarPrepForOS($pcModInfo['directory']);
    unset($pcModInfo);
    
    // set up pn/pcRender
    require_once 'includes/pnRender.class.php';
    //$tpl = new pcRender ();
    $tpl = pnRender::getInstance('PostCalendar');
		PostCalendarSmartySetup($tpl);   
 
    // setup the pnRender cache id
    $templates_cached = true;
    if($showcalendar) {
        $cacheid1 = md5($Date.'M'.$template_view.$template_name.$showcalendar.$showevents.$nextevents.$uid.$theme);
        if(!$tpl->is_cached('blocks/postcalendar_block_view_month.html',$cacheid1)) {
            $templates_cached = false;
        }
    }
    if($showevents) {
        $cacheid2 = md5($Date.'T'.$template_view.$template_name.$showcalendar.$showevents.$nextevents.$uid.$theme);
        if(!$tpl->is_cached('blocks/postcalendar_block_view_day.html',$cacheid2)) {
            $templates_cached = false;
        }
    }   
    if($nextevents) {
        $cacheid3 = md5($Date.'U'.$template_view.$template_name.$showcalendar.$showevents.$nextevents.$uid.$theme);
        if(!$tpl->is_cached('blocks/postcalendar_block_view_upcoming.html',$cacheid3)) {
            $templates_cached = false;
        }
    }
    
    // if one of the templates is not cached, we need to run the following
    if(!$templates_cached) {
        // set up the next and previous months to move to
        $prev_month      = Date_Calc::beginOfPrevMonth(1,$the_month,$the_year,'%Y%m%d');
        $next_month      = Date_Calc::beginOfNextMonth(1,$the_month,$the_year,'%Y%m%d');
        $last_day        = Date_Calc::daysInMonth($the_month,$the_year);
        $pc_prev         = pnModURL('PostCalendar','user','view',array('tplview'=>$template_view,'viewtype'=>'month','Date'=>$prev_month));
        $pc_next         = pnModURL('PostCalendar','user','view',array('tplview'=>$template_view,'viewtype'=>'month','Date'=>$next_month));
        $pc_month_name   = pnModAPIFunc('PostCalendar', 'user', 'getmonthname', array('Date'=>mktime(0,0,0,$the_month,$the_day,$the_year)));
        $month_link_url  = pnModURL('PostCalendar', 'user', 'view', array('tplview'=>$template_view,'viewtype'=>'month','Date'=>date('Ymd',mktime(0,0,0,$the_month,1,$the_year))));
        $month_link_text = $pc_month_name.' '.$the_year;
        //*******************************************************************
        //  Here we get the events for the current month view
        //*******************************************************************
        $day_of_week = 1;
        $pc_month_names = array(_CALJAN,_CALFEB,_CALMAR,_CALAPR,_CALMAY,_CALJUN,
                                _CALJUL,_CALAUG,_CALSEP,_CALOCT,_CALNOV,_CALDEC);

        $pc_short_day_names = array(_CALSUNDAYSHORT, _CALMONDAYSHORT, 
                                    _CALTUESDAYSHORT, _CALWEDNESDAYSHORT,
                                    _CALTHURSDAYSHORT, _CALFRIDAYSHORT, 
                                    _CALSATURDAYSHORT);

        $pc_long_day_names = array(_CALSUNDAY, _CALMONDAY, 
                                   _CALTUESDAY, _CALWEDNESDAY,
                                   _CALTHURSDAY, _CALFRIDAY, 
                                   _CALSATURDAY);
        switch (_SETTING_FIRST_DAY_WEEK) 
        {
            case _IS_MONDAY:
                $pc_array_pos = 1;
                $first_day  = date('w',mktime(0,0,0,$the_month,0,$the_year));
                $end_dow = date('w',mktime(0,0,0,$the_month,$last_day,$the_year));
                if($end_dow != 0) {
                    $the_last_day = $last_day+(7-$end_dow);
                } else {
                    $the_last_day = $last_day;
                }
                break;
            case _IS_SATURDAY:
                $pc_array_pos = 6;
                $first_day  = date('w',mktime(0,0,0,$the_month,2,$the_year));
                $end_dow = date('w',mktime(0,0,0,$the_month,$last_day,$the_year));
                if($end_dow == 6) {
                    $the_last_day = $last_day+6;
                } elseif($end_dow != 5) {
                    $the_last_day = $last_day+(5-$end_dow);
                } else {
                    $the_last_day = $last_day;
                }
                break;
            case _IS_SUNDAY:
            default:
                $pc_array_pos = 0;
                $first_day  = date('w',mktime(0,0,0,$the_month,1,$the_year));
                $end_dow = date('w',mktime(0,0,0,$the_month,$last_day,$the_year));
                if($end_dow != 6) {
                    $the_last_day = $last_day+(6-$end_dow);
                } else {
                    $the_last_day = $last_day;
                }
                break;
        }

        $month_view_start = date('Y-m-d',mktime(0,0,0,$the_month,1,$the_year));
        $month_view_end   = date('Y-m-t',mktime(0,0,0,$the_month,1,$the_year));
        $today_date       = postcalendar_today('%Y-%m-%d');
        $starting_date    = date('m/d/Y',mktime(0,0,0,$the_month,1-$first_day,$the_year));
        $ending_date      = date('m/t/Y',mktime(0,0,0,$the_month+$pcbeventsrange,1,$the_year));

        $eventsByDate = pnModAPIFunc('PostCalendar','user','pcGetEvents', array('start'=>$starting_date,'end'=>$ending_date));
        $calendarView = Date_Calc::getCalendarMonth($the_month, $the_year, '%Y-%m-%d');

        $sdaynames = array();
        $numDays = count($pc_short_day_names);
        for($i=0; $i < $numDays; $i++) {
            if($pc_array_pos >= $numDays) {
                $pc_array_pos = 0;
            }
            array_push($sdaynames,$pc_short_day_names[$pc_array_pos]);
            $pc_array_pos++;
        }

        $daynames = array();
        $numDays = count($pc_long_day_names);
        for($i=0; $i < $numDays; $i++) {
            if($pc_array_pos >= $numDays) {
                $pc_array_pos = 0;
            }
            array_push($daynames,$pc_long_day_names[$pc_array_pos]);
            $pc_array_pos++;
        }

        $dates = array();
        while($starting_date <= $ending_date) {
            array_push($dates,$starting_date);
            list($m,$d,$y) = explode('/',$starting_date);
            $starting_date = Date_Calc::nextDay($d,$m,$y,'%m/%d/%Y');
        }

        $categories = pnModAPIFunc('PostCalendar','user','getCategories');
        if(isset($calendarView)) {
            $tpl->assign_by_ref('CAL_FORMAT',$calendarView);
        }

	// V4B RNG Hack ... 
	foreach ($eventsByDate as $k=>$v)
	{
	    foreach ($v as $kk=>$vv)
	    {
                // there has to be a more intelligent way to do this
                @list($eventsByDate[$k][$kk]['duration_hours'],$dmin) = @explode('.',($eventsByDate[$k][$kk]['duration']/60/60));
                $eventsByDate[$k][$kk]['duration_minutes'] = substr(sprintf('%.2f','.' . 60*($dmin/100)),2,2);
                // ---
	    }
	}
	// V4B RNG Hack end ... 


        $tpl->assign_by_ref('A_MONTH_NAMES',$pc_month_names);
        $tpl->assign_by_ref('A_LONG_DAY_NAMES',$pc_long_day_names);
        $tpl->assign_by_ref('A_SHORT_DAY_NAMES',$pc_short_day_names);
        $tpl->assign_by_ref('S_LONG_DAY_NAMES',$daynames);
        $tpl->assign_by_ref('S_SHORT_DAY_NAMES',$sdaynames);
        $tpl->assign_by_ref('A_EVENTS',$eventsByDate);
        $tpl->assign_by_ref('A_CATEGORY',$categories);
        $tpl->assign_by_ref('PREV_MONTH_URL',$pc_prev);
        $tpl->assign_by_ref('NEXT_MONTH_URL',$pc_next);
        $tpl->assign_by_ref('MONTH_START_DATE',$month_view_start);
        $tpl->assign_by_ref('MONTH_END_DATE',$month_view_end);
        $tpl->assign_by_ref('TODAY_DATE',$today_date);
        $tpl->assign_by_ref('DATE',$Date);
        $tpl->assign_by_ref('DISPLAY_LIMIT',$eventslimit);
        $tpl->assign('TODAYS_EVENTS_TITLE',_PC_TODAYS_EVENTS);
        $tpl->assign('UPCOMING_EVENTS_TITLE',_PC_UPCOMING_EVENTS);
        $tpl->assign('NO_EVENTS',_PC_BLOCK_NO_EVENTS);   
    }
/*
    $pcTheme = pnModGetVar('PostCalendar','pcTemplate');
    if(!$pcTheme)
        $pcTheme='default';
 */   
    if($showcalendar) {
        // we need to create a unique ID for caching purposes
        $output .= $tpl->fetch("blocks/postcalendar_block_view_month.html",$cacheid1);
    }
    
    if($showevents) {
        if($showcalendar) {
            $tpl->assign('SHOW_TITLE',1);
        } else {
            $tpl->assign('SHOW_TITLE',0);
        }
        // we need to create a unique ID for caching purposes
        $output .= $tpl->fetch("blocks/postcalendar_block_view_day.html",$cacheid2);
    }   
    
    if($nextevents) {
        if($showcalendar || $showevents) {
            $tpl->assign('SHOW_TITLE',1);
        } else {
            $tpl->assign('SHOW_TITLE',0);
        }
        // we need to create a unique ID for caching purposes
        $output .= $tpl->fetch("blocks/postcalendar_block_view_upcoming.html",$cacheid3);
    }   

    if($pcbshowsslinks) {
        $submit_event_url = pnModURL('PostCalendar','user','submit');
        	$submit_event_url = DataUtil::formatForDisplay($submit_event_url);
        $search_event_url = pnModURL('PostCalendar','user','search'); 
        	$search_event_url = DataUtil::formatForDisplay($search_event_url);
        $output .= '<div class="pc_centerblocksubmitlinks">';
        if(PC_ACCESS_ADD) {
            $output .= '[<a href="'.$submit_event_url.'">'._PC_SUBMIT_EVENT.'</a>] '; 
        }
        $output .= '[<a href="'.$search_event_url.'">'._PC_SEARCH_EVENT.'</a>]';
        $output .= '</div>';  
    }
    
    $blockinfo['content'] = $output;
    return themesideblock($blockinfo);
}


/**
 * modify block settings ..
 */
function postcalendar_calendarblock_modify($blockinfo)
{
    if (!pnSecAuthAction(0,'PostCalendar:calendarblock:',"$blockinfo[title]::",ACCESS_ADMIN)) {
        return false;
    }

    if (!pnModLoad('PostCalendar'))
        return 'Unable to load module [PostCalendar]';
    
    $output = new pnHTML();
    $vars   = unserialize($blockinfo['content']);
    $i=0;    
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    
    $settings[$i][]     = $output->Text(_PC_BLOCK_SHOW_CALENDAR);
    $settings[$i++][]   = $output->FormCheckBox('pcbshowcalendar', @$vars['pcbshowcalendar']);
    
    $settings[$i][]     = $output->Text(_PC_BLOCK_EVENT_OVERVIEW);
    $settings[$i++][]   = $output->FormCheckBox('pcbeventoverview', @$vars['pcbeventoverview']);
    
    $settings[$i][]     = $output->Text(_PC_BLOCK_UPCOMING_EVENTS);
    $settings[$i++][]   = $output->FormCheckBox('pcbnextevents', @$vars['pcbnextevents']);
    
    $settings[$i][]     = $output->Text(_PC_SHOW_SS_LINKS);
    $settings[$i++][]   = $output->FormCheckBox('pcbshowsslinks', @$vars['pcbshowsslinks']);
    
    $settings[$i][]     = $output->Text(_PC_BLOCK_EVENTS_DISPLAY_LIMIT);
    $settings[$i++][]   = $output->FormText('pcbeventslimit', @$vars['pcbeventslimit'],5);
    
    $settings[$i][]     = $output->Text(_PC_BLOCK_EVENTS_DISPLAY_RANGE);
    $settings[$i++][]   = $output->FormText('pcbeventsrange', @$vars['pcbeventsrange'],5);
        
    $output->SetOutputMode(_PNH_KEEPOUTPUT);

    $output->SetInputMode(_PNH_VERBATIMINPUT);
    for($i=0; $i<count($settings); $i++) {
        $output->TableAddRow($settings[$i], 'left');
    }
    $output->SetInputMode(_PNH_PARSEINPUT);

    return $output->GetOutput();
}

/**
 * update block settings
 */
function postcalendar_calendarblock_update($blockinfo)
{
    // Security check
    if (!pnSecAuthAction(0,'PostCalendar:calendarblock:',"$blockinfo[title]::",ACCESS_ADMIN)) {
        return false;
    }
    
    $vars = array();
    $vars['pcbshowcalendar']  = FormUtil::getPassedValue ('pcbshowcalendar', 0);
    $vars['pcbeventslimit']   = FormUtil::getPassedValue ('pcbeventslimit', 5);
    $vars['pcbeventoverview'] = FormUtil::getPassedValue ('pcbeventoverview', 0);
    $vars['pcbnextevents']    = FormUtil::getPassedValue ('pcbnextevents', 0);
    $vars['pcbeventsrange']   = FormUtil::getPassedValue ('pcbeventsrange', 6);
    $vars['pcbshowsslinks']   = FormUtil::getPassedValue ('pcbshowsslinks', 0);

    require_once 'includes/pnRender.class.php';

    //$tpl = new pcRender ();
 	$tpl = pnRender::getInstance('PostCalendar'); // PostCalendarSmartySetup not needed
   $tpl->clear_all_cache();
    $blockinfo['content'] = serialize($vars);
    return $blockinfo;
}
?>