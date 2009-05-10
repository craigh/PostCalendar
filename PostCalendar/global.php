<?php
/*
 *  SVN: $Id$
 *
 *  @package         PostCalendar 
 *  @lastmodified    $Date$ 
 *  @modifiedby      $Author$ 
 *  @HeadURL	       $HeadURL$ 
 *  @version         $Revision$ 
*/
//=================================================================
//  define constants used to make the code more readable
//=================================================================
define('_IS_SUNDAY',           0);
define('_IS_MONDAY',           1);
define('_IS_SATURDAY',         6);
define('_AM_VAL',              1);
define('_PM_VAL',              2);
define('_ACTION_DELETE',       4);
define('_ACTION_EDIT',         2);
define('_EVENT_APPROVED',      1);
define('_EVENT_QUEUED',        0);
define('_EVENT_HIDDEN',       -1);
// $event_repeat
define('NO_REPEAT',            0);
define('REPEAT',               1);
define('REPEAT_ON',            2);
// $event_repeat_freq
define('REPEAT_EVERY',         1);
define('REPEAT_EVERY_OTHER',   2);
define('REPEAT_EVERY_THIRD',   3);
define('REPEAT_EVERY_FOURTH',  4);
// $event_repeat_freq_type
define('REPEAT_EVERY_DAY',     0);
define('REPEAT_EVERY_WEEK',    1);
define('REPEAT_EVERY_MONTH',   2);
define('REPEAT_EVERY_YEAR',    3);
// $event_repeat_on_num
define('REPEAT_ON_1ST',        1);
define('REPEAT_ON_2ND',        2);
define('REPEAT_ON_3RD',        3);
define('REPEAT_ON_4TH',        4);
define('REPEAT_ON_LAST',       5);
// $event_repeat_on_day
define('REPEAT_ON_SUN',        0);
define('REPEAT_ON_MON',        1);
define('REPEAT_ON_TUE',        2);
define('REPEAT_ON_WED',        3);
define('REPEAT_ON_THU',        4);
define('REPEAT_ON_FRI',        5);
define('REPEAT_ON_SAT',        6);
// $event_repeat_on_freq
define('REPEAT_ON_MONTH',      1);
define('REPEAT_ON_2MONTH',     2);
define('REPEAT_ON_3MONTH',     3);
define('REPEAT_ON_4MONTH',     4);
define('REPEAT_ON_6MONTH',     6);
define('REPEAT_ON_YEAR',       12);
// event sharing values
define('SHARING_PRIVATE',      0);
define('SHARING_PUBLIC',       1);
define('SHARING_BUSY',         2);
define('SHARING_GLOBAL',       3);
define('SHARING_HIDEDESC',     4);
// admin defines
define('_ADMIN_ACTION_APPROVE',   0);
define('_ADMIN_ACTION_HIDE',      1);
define('_ADMIN_ACTION_EDIT',      2);
define('_ADMIN_ACTION_VIEW',      3);
define('_ADMIN_ACTION_DELETE',    4);


//=================================================================
//  Get the global PostCalendar config settings
//	This will save us a lot of time and DB queries later
//=================================================================
define('_SETTING_USE_POPUPS',	   pnModGetVar(__POSTCALENDAR__,'pcUsePopups'));
define('_SETTING_USE_INT_DATES',   pnModGetVar(__POSTCALENDAR__,'pcUseInternationalDates'));
define('_SETTING_OPEN_NEW_WINDOW', pnModGetVar(__POSTCALENDAR__,'pcEventsOpenInNewWindow'));
define('_SETTING_DAY_HICOLOR',	   pnModGetVar(__POSTCALENDAR__,'pcDayHighlightColor'));
define('_SETTING_FIRST_DAY_WEEK',  pnModGetVar(__POSTCALENDAR__,'pcFirstDayOfWeek'));		
define('_SETTING_DATE_FORMAT',	   pnModGetVar(__POSTCALENDAR__,'pcEventDateFormat'));
define('_SETTING_TIME_24HOUR',	   pnModGetVar(__POSTCALENDAR__,'pcTime24Hours')); 
define('_SETTING_DIRECT_SUBMIT',   pnModGetVar(__POSTCALENDAR__,'pcAllowDirectSubmit'));
define('_SETTING_DISPLAY_TOPICS',  pnModGetVar(__POSTCALENDAR__,'pcDisplayTopics'));
define('_SETTING_ALLOW_GLOBAL',	   pnModGetVar(__POSTCALENDAR__,'pcAllowSiteWide'));
define('_SETTING_ALLOW_USER_CAL',  pnModGetVar(__POSTCALENDAR__,'pcAllowUserCalendar'));
define('_SETTING_TIME_INCREMENT',  pnModGetVar(__POSTCALENDAR__,'pcTimeIncrement'));
define('_SETTING_HOW_MANY_EVENTS', pnModGetVar(__POSTCALENDAR__,'pcListHowManyEvents'));
define('_SETTING_TEMPLATE',        pnModGetVar(__POSTCALENDAR__,'pcTemplate'));
define('_SETTING_EVENTS_IN_YEAR',  pnModGetVar(__POSTCALENDAR__,'pcShowEventsInYear'));
define('_SETTING_USE_CACHE',	   pnModGetVar(__POSTCALENDAR__,'pcUseCache'));
define('_SETTING_CACHE_LIFETIME',  pnModGetVar(__POSTCALENDAR__,'pcCacheLifetime'));
define('_SETTING_DEFAULT_VIEW',	   pnModGetVar(__POSTCALENDAR__,'pcDefaultView'));
define('_SETTING_SAFE_MODE',	   pnModGetVar(__POSTCALENDAR__,'pcSafeMode'));
define('_SETTING_NOTIFY_ADMIN',	   pnModGetVar(__POSTCALENDAR__,'pcNotifyAdmin'));
define('_SETTING_NOTIFY_EMAIL',	   pnModGetVar(__POSTCALENDAR__,'pcNotifyEmail'));


//=================================================================
//  Make checking basic permissions easier
//=================================================================
define('PC_ACCESS_ADMIN', 	 pnSecAuthAction(0, 'PostCalendar::', 'null::null', ACCESS_ADMIN));
define('PC_ACCESS_DELETE', 	 pnSecAuthAction(0, 'PostCalendar::', 'null::null', ACCESS_DELETE));
define('PC_ACCESS_ADD', 	 pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD));
define('PC_ACCESS_EDIT', 	 pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_EDIT));
define('PC_ACCESS_MODERATE',     pnSecAuthAction(0, 'PostCalendar::', 'null::null', ACCESS_MODERATE));
define('PC_ACCESS_COMMENT',      pnSecAuthAction(0, 'PostCalendar::', 'null::null', ACCESS_COMMENT));
define('PC_ACCESS_READ', 	 pnSecAuthAction(0, 'PostCalendar::', 'null::null', ACCESS_READ));
define('PC_ACCESS_OVERVIEW',     pnSecAuthAction(0, 'PostCalendar::', 'null::null', ACCESS_OVERVIEW));
define('PC_ACCESS_NONE', 	 pnSecAuthAction(0, 'PostCalendar::', 'null::null', ACCESS_NONE));


//  Require and Setup utility classes and functions
define('DATE_CALC_BEGIN_WEEKDAY', _SETTING_FIRST_DAY_WEEK);
require_once("modules/$pcDir/pnincludes/DateCalc.class.php");

?>