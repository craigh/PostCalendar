<?php
@define('__POSTCALENDAR__','PostCalendar');
/**
 *  SVN: $Id$
 *
 *  @package         PostCalendar 
 *  @lastmodified    $Date$ 
 *  @modifiedby      $Author$ 
 *  @HeadURL	       $HeadURL$ 
 *  @version         $Revision$ 
 *  
 *  PostCalendar::Zikula Events Calendar Module
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

//=========================================================================
//  Require utility classes
//=========================================================================
$pcModInfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
$pcDir = pnVarPrepForOS($pcModInfo['directory']);
require_once("modules/$pcDir/common.api.php");
unset($pcModInfo,$pcDir);

function postcalendar_userapi_getLongDayName($args) 
{
    extract($args); unset($args);
    if(!isset($Date)) { return false; }
    $pc_long_day = array(_CALLONGFIRSTDAY,
                         _CALLONGSECONDDAY,
                         _CALLONGTHIRDDAY,
                         _CALLONGFOURTHDAY,
                         _CALLONGFIFTHDAY,
                         _CALLONGSIXTHDAY,
                         _CALLONGSEVENTHDAY);
    return $pc_long_day[Date("w",$Date)];
}

/**
 *  postcalendar_userapi_buildView
 *
 *  Builds the month display
 *  @param string $Date mm/dd/yyyy format (we should use timestamps)
 *  @return string generated html output 
 *  @access public
 */
function postcalendar_userapi_buildView($args)
{   
	extract($args); unset($args);
	//=================================================================
	//  get the module's information
	$modinfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
	$pcDir = $modinfo['directory'];
	unset($modinfo);
	//=================================================================
	//  grab the for post variable
    $pc_username = FormUtil::getPassedValue('pc_username');
	$category = FormUtil::getPassedValue('pc_category');
	$topic    = FormUtil::getPassedValue('pc_topic');
	//=================================================================
	//  set the correct date
	if (!$Date) // if no explicit arg, get from input
	    $Date = postcalendar_getDate();
        else
	if (strlen($Date) == 8 && is_numeric($Date)) // 20060101
	    $Date .= '000000';

    //=================================================================
		//  get the current view
    if(!isset($viewtype)) { $viewtype = 'month'; }
		//=================================================================
    //  Find out what Template we're using

    $function_out['template'] = pnVarPrepForOS('view_' . $viewtype . '.html');

		//=================================================================
    //  Insert necessary JavaScript into the page
    //$function_out['output'] = pnModAPIFunc(__POSTCALENDAR__,'user','pageSetup');
		//=================================================================
    //  Let's just finish setting things up
    $the_year   = substr($Date,0,4);
   	$the_month  = substr($Date,4,2);
   	$the_day    = substr($Date,6,2);
		$last_day = Date_Calc::daysInMonth($the_month,$the_year);
		//=================================================================
    //  populate the template object with information for
    //  Month Names, Long Day Names and Short Day Names
    //  as translated in the language files
    //  (may be adding more here soon - based on need)
    //=================================================================
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
		//=================================================================
   	//  here we need to set up some information for later
   	//  variable creation.  This helps us establish the correct
   	//  date ranges for each view.  There may be a better way
   	//  to handle all this, but my brain hurts, so your comments
   	//  are very appreciated and welcomed.
   	//=================================================================
   	switch (_SETTING_FIRST_DAY_WEEK) 
   	{
     	case _IS_MONDAY:
	     	$pc_array_pos = 1;
       	$first_day  = date('w',mktime(0,0,0,$the_month,0,$the_year));
       	$week_day   = date('w',mktime(0,0,0,$the_month,$the_day-1,$the_year));
       	$end_dow    = date('w',mktime(0,0,0,$the_month,$last_day,$the_year));
       	if($end_dow != 0) {
         	$the_last_day = $last_day+(7-$end_dow);
       	} else {
         	$the_last_day = $last_day;
       	}
       	break;
     	case _IS_SATURDAY:
       	$pc_array_pos = 6;
       	$first_day  = date('w',mktime(0,0,0,$the_month,2,$the_year));
       	$week_day   = date('w',mktime(0,0,0,$the_month,$the_day+1,$the_year));
       	$end_dow    = date('w',mktime(0,0,0,$the_month,$last_day,$the_year));
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
       	$week_day   = date('w',mktime(0,0,0,$the_month,$the_day,$the_year));
       	$end_dow    = date('w',mktime(0,0,0,$the_month,$last_day,$the_year));
       	if($end_dow != 6) {
         	$the_last_day = $last_day+(6-$end_dow);
       	} else {
         	$the_last_day = $last_day;
       	}
       	break;
 			}
    	//=================================================================
    	//  Week View is a bit of a pain in the ass, so we need to
    	//  do some extra setup for that view.  This section will
    	//  find the correct starting and ending dates for a given
    	//  seven day period, based on the day of the week the
    	//  calendar is setup to run under (Sunday, Saturday, Monday)
    	//=================================================================
    	$first_day_of_week = sprintf('%02d',$the_day-$week_day);
    	$week_first_day = date('m/d/Y',mktime(0,0,0,$the_month,$first_day_of_week,$the_year));
    	list($week_first_day_month, $week_first_day_date, $week_first_day_year) = explode('/',$week_first_day);
    	$week_first_day_month_name  = pnModAPIFunc(__POSTCALENDAR__, 'user', 'getmonthname',
                                            	  array('Date'=>mktime(0,0,0,$week_first_day_month,$week_first_day_date,$week_first_day_year)));
    	$week_last_day = date('m/d/Y',mktime(0,0,0,$the_month,$first_day_of_week+6,$the_year));
    	list($week_last_day_month, $week_last_day_date, $week_last_day_year) = explode('/',$week_last_day);
    	$week_last_day_month_name = pnModAPIFunc(__POSTCALENDAR__, 'user', 'getmonthname',
                                            	 array('Date'=>mktime(0,0,0,$week_last_day_month,$week_last_day_date,$week_last_day_year)));

    	//=================================================================
    	//  Setup some information so we know the actual month's dates
    	//  also get today's date for later use and highlighting  
    	//=================================================================
    	$month_view_start = date('Y-m-d',mktime(0,0,0,$the_month,1,$the_year));
    	$month_view_end   = date('Y-m-t',mktime(0,0,0,$the_month,1,$the_year));
			$today_date = postcalendar_today('%Y-%m-%d');
			//=================================================================
    	//  Setup the starting and ending date ranges for pcGetEvents()
    	//=================================================================
    	switch($viewtype) {
        case 'day' :
         	$starting_date = date('m/d/Y',mktime(0,0,0,$the_month,$the_day,$the_year));
         	$ending_date   = date('m/d/Y',mktime(0,0,0,$the_month,$the_day,$the_year));
         	break;
       	case 'week' :
         	$starting_date = "$week_first_day_month/$week_first_day_date/$week_first_day_year";
         	$ending_date   = "$week_last_day_month/$week_last_day_date/$week_last_day_year";
					$calendarView  = Date_Calc::getCalendarWeek($week_first_day_date,
			                                            		$week_first_day_month,
																											$week_first_day_year,
																											'%Y-%m-%d');
           break;
        case 'month' :
         	$starting_date = date('m/d/Y',mktime(0,0,0,$the_month,1-$first_day,$the_year));
         	$ending_date   = date('m/d/Y',mktime(0,0,0,$the_month,$the_last_day,$the_year));
					$calendarView  = Date_Calc::getCalendarMonth($the_month, $the_year, '%Y-%m-%d');
          break;
        case 'year' :
         	$starting_date = date('m/d/Y',mktime(0,0,0,1,1,$the_year));
         	$ending_date   = date('m/d/Y',mktime(0,0,0,1,1,$the_year+1));
					$calendarView  = Date_Calc::getCalendarYear($the_year, '%Y-%m-%d');
         	break;
    	}
			//=================================================================
    	//  Load the events
    	//=================================================================
			$eventsByDate =& postcalendar_userapi_pcGetEvents(array('start'=>$starting_date,'end'=>$ending_date));

			//=================================================================
    	//  Create and array with the day names in the correct order
    	//=================================================================
    	$daynames = array();
    	$numDays = count($pc_long_day_names);
    	for($i=0; $i < $numDays; $i++)
    	{   
				if($pc_array_pos >= $numDays) {
         	$pc_array_pos = 0;
        }
        array_push($daynames,$pc_long_day_names[$pc_array_pos]);
        $pc_array_pos++;
    	}
			unset($numDays);
			$sdaynames = array();
    	$numDays = count($pc_short_day_names);
    	for($i=0; $i < $numDays; $i++)
    	{
				if($pc_array_pos >= $numDays) {
        	$pc_array_pos = 0;
        }
        array_push($sdaynames,$pc_short_day_names[$pc_array_pos]);
        $pc_array_pos++;
    	}
			unset($numDays);
    	//=================================================================
    	//  Prepare some values for the template
    	//=================================================================
    	$prev_month = Date_Calc::beginOfPrevMonth(1,$the_month,$the_year,'%Y%m%d');
    	$next_month = Date_Calc::beginOfNextMonth(1,$the_month,$the_year,'%Y%m%d');

    	$pc_prev = pnModURL(__POSTCALENDAR__,'user','view',
                        	array('viewtype'=>'month',
                            	  'Date'=>$prev_month,
                            	  'pc_username'=>$pc_username,
								  'pc_category'=>$category,
								  'pc_topic'=>$topic));

    	$pc_next = pnModURL(__POSTCALENDAR__,'user','view',
                        	array('viewtype'=>'month',
                            	  'Date'=>$next_month,
                            	  'pc_username'=>$pc_username,
								  'pc_category'=>$category,
								  'pc_topic'=>$topic));

    	$prev_day = Date_Calc::prevDay($the_day,$the_month,$the_year,'%Y%m%d');
    	$next_day = Date_Calc::nextDay($the_day,$the_month,$the_year,'%Y%m%d');
    	$pc_prev_day = pnModURL(__POSTCALENDAR__,'user','view',
                            	 array('viewtype'=>'day',
                                	   'Date'=>$prev_day,
                                	   'pc_username'=>$pc_username,
								  	   'pc_category'=>$category,
								  	   'pc_topic'=>$topic));

    	$pc_next_day = pnModURL(__POSTCALENDAR__,'user','view',
                            	array('viewtype'=>'day',
                                	  'Date'=>$next_day,
                                	  'pc_username'=>$pc_username,
								  	  'pc_category'=>$category,
								  	  'pc_topic'=>$topic));

    	$prev_week = date('Ymd',mktime(0,0,0,$week_first_day_month,$week_first_day_date-7,$week_first_day_year));
    	$next_week = date('Ymd',mktime(0,0,0,$week_last_day_month,$week_last_day_date+1,$week_last_day_year));
    	$pc_prev_week = pnModURL(__POSTCALENDAR__,'user','view',
                            	 array('viewtype'=>'week',
                                	   'Date'=>$prev_week,
                                	   'pc_username'=>$pc_username,
								  	   'pc_category'=>$category,
								  	   'pc_topic'=>$topic));
    	$pc_next_week = pnModURL(__POSTCALENDAR__,'user','view', 
                            	 array('viewtype'=>'week',
                                	   'Date'=>$next_week,
                                	   'pc_username'=>$pc_username,
								  	   'pc_category'=>$category,
								  	   'pc_topic'=>$topic));
    	
			$prev_year = date('Ymd',mktime(0,0,0,1,1,$the_year-1));
    	$next_year = date('Ymd',mktime(0,0,0,1,1,$the_year+1));
    	$pc_prev_year = pnModURL(__POSTCALENDAR__,'user','view',
                            	 array('viewtype'=>'year',
                                	   'Date'=>$prev_year,
                                	   'pc_username'=>$pc_username,
								  	   'pc_category'=>$category,
								  	   'pc_topic'=>$topic));
    	$pc_next_year = pnModURL(__POSTCALENDAR__,'user','view',
                            	 array('viewtype'=>'year',
                                	   'Date'=>$next_year,
                                	   'pc_username'=>$pc_username,
								  	   'pc_category'=>$category,
								  	   'pc_topic'=>$topic));
    	
			//=================================================================
    	//  Populate the template
    	//=================================================================
			$all_categories = pnModAPIFunc(__POSTCALENDAR__,'user','getCategories');
			$categories = array();
			foreach($all_categories as $category)
			{
				// FIXME !!!!!
				$categories[] = array('value'    => $category['catid'],
								  'selected' => ($category['catid']==$event_category ? 'selected' : ''),
								  'name'     => $category['catname'],
								  'color'    => $category['catcolor'],
								  'desc'     => $category['catdesc']);
			}

			if(isset($calendarView)) {
	    		$function_out['CAL_FORMAT'] = $calendarView;
			}
			$func  = FormUtil::getPassedValue('func');
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

			if ($popup)
			{
				// this concept needs to be changed to simply use a different template if using a popup. CAH 5/9/09
				$theme = pnUserGetTheme();
				$function_out['raw1'] = "<html><head></head><body>\n";
        //$tpl->display("$template");
				$function_out['raw2'] .= postcalendar_footer();
				// V4B TS start ***  Hook code for displaying stuff for events in popup
				if ($_GET["type"] != "admin") {
					$hooks = pnModCallHooks('item', 'display', $eid, "index.php?module=PostCalendar&amp;type=user&amp;func=view&amp;viewtype=details&amp;eid=$eid&amp;popup=1");
					$function_out['raw2'] .=  $hooks;
				}
				$function_out['raw2'] .=  "\n</body></html>";
				//session_write_close();
				//exit;
				$function_out['displayaspopup'] = true;
				return $function_out;
			}	else {
				return $function_out;
			}
}

/**
 *  postcalendar_userapi_eventPreview
 *  Creates the detailed event display and outputs html.  
 *  Accepts an array of key/value pairs
 *  @param array $event array of event details from the form
 *  @return string html output 
 *  @access public               
 */
function postcalendar_userapi_eventPreview($args)
{

	extract($args); unset($args);
	//echo "eventpreview::pnusergetvar";
    $uid = pnUserGetVar('uid');
    //=================================================================
    //  Setup Render Template Engine
    //=================================================================
//    $tpl = new pnRender();
	$tpl = pnRender::getInstance('PostCalendar');
	PostCalendarSmartySetup($tpl);
	$tpl->caching = false;
		/* Trim as needed */
			$func  = FormUtil::getPassedValue('func');
			$template_view = FormUtil::getPassedValue('tplview');
			if (!$template_view) $template_view = 'month'; 
			$tpl->assign('FUNCTION', $func);
			$tpl->assign('TPL_VIEW', $template_view);
		/* end */

	// add preceding zeros
    $event_starttimeh   = sprintf('%02d',$event_starttimeh);    
    $event_starttimem   = sprintf('%02d',$event_starttimem);    
    $event_startday     = sprintf('%02d',$event_startday);      
    $event_startmonth   = sprintf('%02d',$event_startmonth);    
    $event_endday       = sprintf('%02d',$event_endday);        
    $event_endmonth     = sprintf('%02d',$event_endmonth);      
    
    if(!(bool)_SETTING_TIME_24HOUR) {
        if($event_startampm == _PM_VAL) {
            if($event_starttimeh != 12) {
				$event_starttimeh+=12;
			}
        } elseif($event_startampm == _AM_VAL) {
            if($event_starttimeh == 12) {
				$event_starttimeh = 00;
			}
        }
    }
    
	$event_startampm." - ";
	$startTime = $event_starttimeh.':'.$event_starttimem.' ';
    
	$event = array();
	$event['eid'] = '';    
	$event['uname'] = $uname;
	$event['catid'] = $event_category;
	if($pc_html_or_text == 'html') {
		$prepFunction = 'pcVarPrepHTMLDisplay';
	} else {
		$prepFunction = 'pcVarPrepForDisplay';
	}
    $event['title'] = $prepFunction($event_subject); 
	$event['hometext'] = $prepFunction($event_desc);
	$event['desc'] = $event['hometext'];
    $event['date'] = str_pad(str_replace('-','',$event_startyear.$event_startmonth.$event_startday),14,'0');
	$event['duration'] = $event_duration;
	$event['duration_hours'] = $event_dur_hours;
	$event['duration_minutes'] = $event_dur_minutes;
	$event['endDate'] = $event_endyear.'-'.$event_endmonth.'-'.$event_endday;
    $event['startTime'] = $startTime;
	$event['recurrtype'] = '';
	$event['recurrfreq'] = '';
    $event['recurrspec'] = $event_recurrspec;
	$event['topic'] = $event_topic;
	$event['alldayevent'] = $event_allday;
    $event['conttel'] = $prepFunction($event_conttel);
	$event['contname'] = $prepFunction($event_contname);
    $event['contemail'] = $prepFunction($event_contemail);
	$event['website'] = $prepFunction(postcalendar_makeValidURL($event_website));
	$event['fee'] = $prepFunction($event_fee);
    $event['location'] = $prepFunction($event_location);
	$event['street1'] = $prepFunction($event_street1);
	$event['street2'] = $prepFunction($event_street2);
	$event['city'] = $prepFunction($event_city);
	$event['state'] = $prepFunction($event_state);
	$event['postal'] = $prepFunction($event_postal);
	
	$event['meetingdate_start'] = $meetingdate_start;
    //=================================================================
    //  get event's topic information
	//=================================================================
    if(_SETTING_DISPLAY_TOPICS) {
	$topic = DBUtil::selectObjectByID ('topics', $event['topic'], 'topicid');
    	$event['topictext']  = $topic['topictext'];
    	$event['topicimage'] = $topic['topicimage'];
	}
	//=================================================================
    //  Find out what Template we're using    
	//=================================================================
/*
    $template_name = _SETTING_TEMPLATE;
    if(!isset($template_name)) {
    	$template_name = 'default';
    }
*/
  	//=================================================================
    //  populate the template
    //=================================================================
	if(!empty($event['location']) || !empty($event['street1']) ||
	   !empty($event['street2']) || !empty($event['city']) ||
	   !empty($event['state']) || !empty($event['postal'])) {
	   $tpl->assign('LOCATION_INFO',true);
	} else {
		$tpl->assign('LOCATION_INFO',false);
	}
	if(!empty($event['contname']) || !empty($event['contemail']) ||
	   !empty($event['conttel']) || !empty($event['website'])) {
	   $tpl->assign('CONTACT_INFO',true);
	} else {
		$tpl->assign('CONTACT_INFO',false);
	}
	$tpl->assign_by_ref('A_EVENT',$event);
	
	//=================================================================
    //  Parse the template
    //=================================================================
    $output = "\n\n<!-- POSTCALENDAR TEMPLATE START -->\n\n";
/*
	$pcTheme = pnModGetVar(__POSTCALENDAR__,'pcTemplate');
	if(!$pcTheme)
	    $pcTheme='default';
    $output .= $tpl->fetch("$pcTheme/view_event_preview.html");
*/
    $output .= $tpl->fetch("view_event_preview.html");
    $output .= "\n\n<!-- POSTCALENDAR TEMPLATE END -->\n\n";
	
	return $output;
}

/**
 *  postcalendar_userapi_pcQueryEvents
 *  Returns an array containing the event's information
 *  @params array(key=>value)
 *  @params string key eventstatus
 *  @params int value -1 == hidden ; 0 == queued ; 1 == approved
 *  @return array $events[][]
 */
function postcalendar_userapi_pcQueryEvents($args)
{   
	//echo "pcQuerydebug<br>";
	//pcDebugVar ($args);
	$end = '0000-00-00';
	extract($args);

	$pc_username = FormUtil::getPassedValue('pc_username');
	$topic       = FormUtil::getPassedValue('pc_topic');
	$category    = FormUtil::getPassedValue('pc_category');
	$userid      = pnUserGetVar('uid');

	if(!empty($pc_username) && (strtolower($pc_username) != 'anonymous')) {
		if($pc_username=='__PC_ALL__') {
			$ruserid = -1;
		} else {
			$ruserid = pnUserGetIDFromName(strtolower($pc_username));
    	}
    }

	if(!isset($eventstatus) || ((int)$eventstatus < -1 || (int)$eventstatus > 1)) $eventstatus = 1;

	if(!isset($start)) $start = Date_Calc::dateNow('%Y-%m-%d'); 
	list($sy,$sm,$sd) = explode('-',$start);

	$where = "WHERE pc_eventstatus=$eventstatus 
						AND (pc_endDate>='$start' OR (pc_endDate='0000-00-00' AND pc_recurrtype<>'0') OR pc_eventDate>='$start')
						AND pc_eventDate<='$end' ";

	if(isset($ruserid)) {
		// get all events for the specified username
		if($ruserid == -1) {
			$where .= "AND (pc_sharing = '" . SHARING_BUSY . "' ";
			$where .= "OR pc_sharing = '" . SHARING_PUBLIC . "') ";
		} else {
			// v4b TS start - always see the records of the logged in user too | disabled on 2004-10-18
			$where .= "AND pc_aid = $ruserid ";
			//$where .= "AND (pc_aid = $ruserid OR pc_aid = $userid) ";
		}
	} else if (!pnUserLoggedIn()) {
		// get all events for anonymous users
		$where .= "AND (pc_sharing = '" . SHARING_GLOBAL . "' ";
		$where .= "OR pc_sharing = '" . SHARING_HIDEDESC . "') ";
	} else {
		// get all events for logged in user plus global events
		$where .= "AND (pc_aid = $userid OR pc_sharing = '" . SHARING_GLOBAL . "' OR pc_sharing = '" . SHARING_HIDEDESC . "') ";
	}


	// Start Search functionality 
	if(!empty($s_keywords)) $where .= "AND ($s_keywords) ";
	if(!empty($s_category)) $where .= "AND ($s_category) ";
	if(!empty($s_topic))    $where .= "AND ($s_topic) ";
	if(!empty($category))   $where .= "AND (tbl.pc_catid = '".pnVarPrepForStore($category)."') ";
	if(!empty($topic))	    $where .= "AND (tbl.pc_topic = '".pnVarPrepForStore($topic)."') ";
	// End Search functionality 

	$sort .= "ORDER BY pc_meeting_id";

		// FIXME !!!
	$joinInfo = array ();
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catname',
				'object_field_name'   =>  'catname',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catdesc',
				'object_field_name'   =>  'catdesc',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');
	$joinInfo[] = array (   'join_table'          =>  'postcalendar_categories',
				'join_field'          =>  'catcolor',
				'object_field_name'   =>  'catcolor',
				'compare_field_table' =>  'catid',
				'compare_field_join'  =>  'catid');

	$events = DBUtil::selectExpandedObjectArray ('postcalendar_events', $joinInfo, $where, $sort);
	$topicNames = DBUtil::selectFieldArray ('topics', 'topicname', '', '', false, 'topicid');
    
	// added temp_meeting_id
	$old_m_id = "NULL";
	$ak = array_keys ($events);
	foreach ($ak as $key) {
		$new_m_id = $key['meeting_id'];
		if ( ($old_m_id) && ($old_m_id != "NULL") && ($new_m_id > 0) && ($old_m_id == $new_m_id) ) {
			$old_m_id = $new_m_id;
			unset ($events[$key]);
		}
		$events[$key] = postcalendar_userapi_pcFixEventDetails ($events[$key]);
	}
	return $events;
}

function postcalendar_userapi_pcGetEvents($args)
{   
	//echo "pcGetdebug<br>";
	//pcDebugVar($args);
	
	$s_keywords = $s_category = $s_topic = '';
	extract($args);
    $date = postcalendar_getDate();
    $cy = substr($date,0,4);
    $cm = substr($date,4,2);
    $cd = substr($date,6,2);
    
    if(isset($start) && isset($end)) {
		// parse start date
    	list($sm,$sd,$sy) = explode('/',$start);
    	// parse end date
    	list($em,$ed,$ey) = explode('/',$end);
    
    	$s = (int) "$sy$sm$sd";
    	if($s > $date) {
        	$cy = $sy;
        	$cm = $sm;
        	$cd = $sd;
    	}
    	$start_date = Date_Calc::dateFormat($sd,$sm,$sy,'%Y-%m-%d');
    	$end_date = Date_Calc::dateFormat($ed,$em,$ey,'%Y-%m-%d');
	} else {
		$sm = $em = $cm;
		$sd = $ed = $cd;
		$sy = $cy;
		$ey = $cy+2;
		$start_date = $sy.'-'.$sm.'-'.$sd;
    	$end_date = $ey.'-'.$em.'-'.$ed;
	}
    if(!isset($events)) {
        if(!isset($s_keywords)) $s_keywords = '';
		$a = array('start'=>$start_date,'end'=>$end_date,'s_keywords'=>$s_keywords,'s_category'=>$s_category,'s_topic'=>$s_topic);
		$events = pnModAPIFunc(__POSTCALENDAR__,'user','pcQueryEvents',$a);
	}
	
    //==============================================================
    //  Here we build an array consisting of the date ranges
    //  specific to the current view.  This array is then
    //  used to build the calendar display.
    //==============================================================
    $days = array();
    $sday = Date_Calc::dateToDays($sd,$sm,$sy);
    $eday = Date_Calc::dateToDays($ed,$em,$ey);
    for($cday = $sday; $cday <= $eday; $cday++) {
        $d = Date_Calc::daysToDate($cday,'%d');
        $m = Date_Calc::daysToDate($cday,'%m');
        $y = Date_Calc::daysToDate($cday,'%Y');
        $store_date = Date_Calc::dateFormat($d,$m,$y,'%Y-%m-%d');
        $days[$store_date] = array();
    }
	
	//echo "GetEvents Line 729<br>";
    //$users = pnUserGetAll();
	//$nuke_users = array();
	
	//foreach($users as $user) {
    //    $nuke_users[strtolower($user['uname'])] = $user['uid'];
	//}
	//unset($users);
	
	foreach($events as $event) {
        // get the name of the topic
        $topicname = pcGetTopicName($event['topic']);
		// get the user id of event's author
        //$cuserid = @$nuke_users[strtolower($event['uname'])];
        
        // CAH mod 4/12/09
        $cuserid = pnUserGetIDFromName(strtolower($event['uname']));
                
        
		// check the current event's permissions
		// the user does not have permission to view this event
		// if any of the following evaluate as false
		if(!pnSecAuthAction(0, 'PostCalendar::Event', "$event[title]::$event[eid]", ACCESS_OVERVIEW)) {
            continue;
        } elseif(!pnSecAuthAction(0, 'PostCalendar::Category', "$event[catname]::$event[catid]", ACCESS_OVERVIEW)) {
            continue;
        } elseif(!pnSecAuthAction(0, 'PostCalendar::User', "$event[uname]::$cuserid", ACCESS_OVERVIEW)) {
            continue;
        } elseif(!pnSecAuthAction(0, 'PostCalendar::Topic', "$topicname::$event[topic]", ACCESS_OVERVIEW)) {
            continue;
        }
		// parse the event start date
        list($esY,$esM,$esD) = explode('-',$event['eventDate']);
        // grab the recurring specs for the event
        $event_recurrspec = @unserialize($event['recurrspec']);
        // determine the stop date for this event
        if($event['endDate'] == '0000-00-00') {
            $stop = $end_date;
        } else {
            $stop = $event['endDate'];
        }
        
        switch($event['recurrtype']) {
            //==============================================================
            //  Events that do not repeat only have a startday
            //==============================================================
            case NO_REPEAT :
                if(isset($days[$event['eventDate']])) {
                    array_push($days[$event['eventDate']],$event);
                }
                break;
            //==============================================================
            //  Find events that repeat at a certain frequency
            //  Every,Every Other,Every Third,Every Fourth
            //  Day,Week,Month,Year,MWF,TR,M-F,SS
            //==============================================================   
            case REPEAT :
                $rfreq = $event_recurrspec['event_repeat_freq'];
                $rtype = $event_recurrspec['event_repeat_freq_type'];
                // we should bring the event up to date to make this a tad bit faster
				// any ideas on how to do that, exactly??? dateToDays probably.
				$nm = $esM; $ny = $esY; $nd = $esD; 
                $occurance = Date_Calc::dateFormat($nd,$nm,$ny,'%Y-%m-%d');
				while($occurance < $start_date) {
					$occurance = __increment($nd,$nm,$ny,$rfreq,$rtype);
					list($ny,$nm,$nd) = explode('-',$occurance);
				}
				while($occurance <= $stop) {
                    if(isset($days[$occurance])) { array_push($days[$occurance],$event); }
                    $occurance = __increment($nd,$nm,$ny,$rfreq,$rtype);
					list($ny,$nm,$nd) = explode('-',$occurance);
                }
				break;
				
            //==============================================================
            //  Find events that repeat on certain parameters
            //  On 1st,2nd,3rd,4th,Last
            //  Sun,Mon,Tue,Wed,Thu,Fri,Sat
            //  Every N Months
            //==============================================================     
            case REPEAT_ON :
                $rfreq = $event_recurrspec['event_repeat_on_freq'];
                $rnum  = $event_recurrspec['event_repeat_on_num'];
                $rday  = $event_recurrspec['event_repeat_on_day'];
                //==============================================================
                //  Populate - Enter data into the event array
                //==============================================================
                $nm = $esM; $ny = $esY; $nd = $esD;
                // make us current
                while($ny < $cy) {
                    $occurance = date('Y-m-d',mktime(0,0,0,$nm+$rfreq,$nd,$ny));
					list($ny,$nm,$nd) = explode('-',$occurance);
                }
                // populate the event array
                while($ny <= $cy) {
                    $dnum = $rnum; // get day event repeats on
                    do {
                        $occurance = Date_Calc::NWeekdayOfMonth($dnum--,$rday,$nm,$ny,$format="%Y-%m-%d");
                    } while($occurance === -1);
                    if(isset($days[$occurance]) && $occurance <= $stop) { array_push($days[$occurance],$event); }
                    $occurance = date('Y-m-d',mktime(0,0,0,$nm+$rfreq,$nd,$ny));
					list($ny,$nm,$nd) = explode('-',$occurance);
                }
                break;
        } // <- end of switch($event['recurrtype'])
    } // <- end of foreach($events as $event)
	
	return $days;
}

/**
 *	__increment()
 *	returns the next valid date for an event based on the
 *	current day,month,year,freq and type
 *  @private
 *	@returns string YYYY-MM-DD
 */
function __increment($d,$m,$y,$f,$t)
{
	if($t == REPEAT_EVERY_DAY) {
		return date('Y-m-d',mktime(0,0,0,$m,($d+$f),$y));
	} elseif($t == REPEAT_EVERY_WEEK) {
		return date('Y-m-d',mktime(0,0,0,$m,($d+(7*$f)),$y));
	} elseif($t == REPEAT_EVERY_MONTH) {
		return date('Y-m-d',mktime(0,0,0,($m+$f),$d,$y));
	} elseif($t == REPEAT_EVERY_YEAR) {
		return date('Y-m-d',mktime(0,0,0,$m,$d,($y+$f)));
	}
}

?>