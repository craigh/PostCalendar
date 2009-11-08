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

//=================================================================
//  define constants used to make the code more readable
//=================================================================
define('_IS_SUNDAY',            0);
define('_IS_MONDAY',            1);
define('_IS_SATURDAY',          6);
define('_AM_VAL',               1);
define('_PM_VAL',               2);
define('_ACTION_DELETE',        4);
define('_ACTION_EDIT',          2);
define('_EVENT_APPROVED',       1);
define('_EVENT_QUEUED',         0);
define('_EVENT_HIDDEN',        -1);
// $event_repeat
define('NO_REPEAT',             0);
define('REPEAT',                1);
define('REPEAT_ON',             2);
// $event_repeat_freq
define('REPEAT_EVERY',          1);
define('REPEAT_EVERY_OTHER',    2);
define('REPEAT_EVERY_THIRD',    3);
define('REPEAT_EVERY_FOURTH',   4);
// $event_repeat_freq_type
define('REPEAT_EVERY_DAY',      0);
define('REPEAT_EVERY_WEEK',     1);
define('REPEAT_EVERY_MONTH',    2);
define('REPEAT_EVERY_YEAR',     3);
// $event_repeat_on_num
define('REPEAT_ON_1ST',         1);
define('REPEAT_ON_2ND',         2);
define('REPEAT_ON_3RD',         3);
define('REPEAT_ON_4TH',         4);
define('REPEAT_ON_LAST',        5);
// $event_repeat_on_day
define('REPEAT_ON_SUN',         0);
define('REPEAT_ON_MON',         1);
define('REPEAT_ON_TUE',         2);
define('REPEAT_ON_WED',         3);
define('REPEAT_ON_THU',         4);
define('REPEAT_ON_FRI',         5);
define('REPEAT_ON_SAT',         6);
// $event_repeat_on_freq
define('REPEAT_ON_MONTH',       1);
define('REPEAT_ON_2MONTH',      2);
define('REPEAT_ON_3MONTH',      3);
define('REPEAT_ON_4MONTH',      4);
define('REPEAT_ON_6MONTH',      6);
define('REPEAT_ON_YEAR',        12);
// event sharing values
define('SHARING_PRIVATE',       0);
define('SHARING_PUBLIC',        1); //remove in v6.0
define('SHARING_BUSY',          2); //remove in v6.0
define('SHARING_GLOBAL',        3);
define('SHARING_HIDEDESC',      4); //remove in v6.0
// filter display values
define('_PC_FILTER_GLOBAL',     0);
define('_PC_FILTER_ALL',        -1);
define('_PC_FILTER_PRIVATE',    -2);
// admin defines
define('_ADMIN_ACTION_APPROVE', 0);
define('_ADMIN_ACTION_HIDE',    1);
define('_ADMIN_ACTION_EDIT',    2);
define('_ADMIN_ACTION_VIEW',    3);
define('_ADMIN_ACTION_DELETE',  4);

//=================================================================
// Get the global PostCalendar config settings
// This will save us a lot of time and DB queries later
//=================================================================
define('_SETTING_USE_POPUPS',       pnModGetVar('PostCalendar', 'pcUsePopups'));
define('_SETTING_OPEN_NEW_WINDOW',  pnModGetVar('PostCalendar', 'pcEventsOpenInNewWindow'));
define('_SETTING_FIRST_DAY_WEEK',   pnModGetVar('PostCalendar', 'pcFirstDayOfWeek'));
define('_SETTING_DATE_FORMAT',      pnModGetVar('PostCalendar', 'pcEventDateFormat'));
define('_SETTING_TIME_24HOUR',      pnModGetVar('PostCalendar', 'pcTime24Hours'));
define('_SETTING_DIRECT_SUBMIT',    pnModGetVar('PostCalendar', 'pcAllowDirectSubmit'));
define('_SETTING_ALLOW_USER_CAL',   pnModGetVar('PostCalendar', 'pcAllowUserCalendar'));
define('_SETTING_TIME_INCREMENT',   pnModGetVar('PostCalendar', 'pcTimeIncrement'));
define('_SETTING_HOW_MANY_EVENTS',  pnModGetVar('PostCalendar', 'pcListHowManyEvents'));
define('_SETTING_EVENTS_IN_YEAR',   pnModGetVar('PostCalendar', 'pcShowEventsInYear'));
define('_SETTING_DEFAULT_VIEW',     pnModGetVar('PostCalendar', 'pcDefaultView'));
define('_SETTING_SAFE_MODE',        pnModGetVar('PostCalendar', 'pcSafeMode'));
define('_SETTING_NOTIFY_ADMIN',     pnModGetVar('PostCalendar', 'pcNotifyAdmin'));
define('_SETTING_NOTIFY_EMAIL',     pnModGetVar('PostCalendar', 'pcNotifyEmail'));
define('_SETTING_ALLOW_CAT_FILTER', pnModGetVar('PostCalendar', 'pcAllowCatFilter'));
define('_SETTING_ENABLECATS',       pnModGetVar('PostCalendar', 'enablecategorization'));
define('_SETTING_USENAVIMAGES',     pnModGetVar('PostCalendar', 'enablenavimages'));

//  Require and Setup utility classes and functions
define('DATE_CALC_BEGIN_WEEKDAY', _SETTING_FIRST_DAY_WEEK);
require_once dirname(__FILE__) . '/pnincludes/DateCalc.class.php';
