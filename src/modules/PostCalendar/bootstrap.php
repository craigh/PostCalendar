<?php
/**
 * @package     PostCalendar
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
define('_EVENT_APPROVED',       1);
define('_EVENT_QUEUED',         0);
define('_EVENT_HIDDEN',        -1);
// $event_repeat
define('NO_REPEAT',             0);
define('REPEAT',                1);
define('REPEAT_ON',             2);
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
// event sharing values
define('SHARING_PRIVATE',       0);
define('SHARING_PUBLIC',        1); //remove in v6.0
define('SHARING_BUSY',          2); //remove in v6.0
define('SHARING_GLOBAL',        3);
define('SHARING_HIDEDESC',      4); //remove in v6.0
// filter display values
define('_PC_FILTER_GLOBAL',     -1);
define('_PC_FILTER_ALL',        -2);
define('_PC_FILTER_PRIVATE',    -3);
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
$settings = ModUtil::getVar('PostCalendar');
define('_SETTING_USE_POPUPS',       $settings['pcUsePopups']);
define('_SETTING_OPEN_NEW_WINDOW',  $settings['pcEventsOpenInNewWindow']);
define('_SETTING_FIRST_DAY_WEEK',   $settings['pcFirstDayOfWeek']);
define('_SETTING_DATE_FORMAT',      $settings['pcEventDateFormat']);
define('_SETTING_TIME_24HOUR',      $settings['pcTime24Hours']);
define('_SETTING_ALLOW_USER_CAL',   $settings['pcAllowUserCalendar']);
define('_SETTING_HOW_MANY_EVENTS',  $settings['pcListHowManyEvents']);
define('_SETTING_DEFAULT_VIEW',     $settings['pcDefaultView']);
define('_SETTING_ALLOW_CAT_FILTER', $settings['pcAllowCatFilter']);
define('_SETTING_ENABLECATS',       $settings['enablecategorization']);
define('_SETTING_USENAVIMAGES',     $settings['enablenavimages']);
unset($settings);

// Setup utility classes and functions
define('DATE_CALC_BEGIN_WEEKDAY', _SETTING_FIRST_DAY_WEEK);

ZLoader::addAutoloader('Date', 'modules/PostCalendar/lib/vendor');