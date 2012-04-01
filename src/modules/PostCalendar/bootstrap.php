<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

if (ModUtil::available('PostCalendar')) {
    //=================================================================
    // Get the global PostCalendar config settings
    // This will save us a lot of time and DB queries later
    //=================================================================
    $settings = ModUtil::getVar('PostCalendar');
    define('_SETTING_DATE_FORMAT',      $settings['pcEventDateFormat']);
    define('_SETTING_TIME_24HOUR',      $settings['pcTime24Hours']);
    define('_SETTING_ALLOW_USER_CAL',   $settings['pcAllowUserCalendar']);
    define('_SETTING_DEFAULT_VIEW',     $settings['pcDefaultView']);
    unset($settings);
}