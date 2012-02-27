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