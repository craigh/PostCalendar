<?php

/**
 * PostCalendar
 * 
 * @license MIT
 * @copyright   Copyright (c) 2012, Craig Heydenburg, Sound Web Development
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
class PostCalendar_Util
{

    /**
     * PostCalendar Default Module Settings
     * @return array An associated array with key=>value pairs of the default module settings
     */
    public static function getdefaults()
    {
        // figure out associated categories and assign default value of 0 (none)
        $defaultcats = array();
        $cats = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
        foreach ($cats as $prop => $id) {
            $defaultcats[$prop] = 0;
        }
        $i18n = ZI18n::getInstance();

        // PostCalendar Default Settings
        $defaults = array(
            'pcTime24Hours' => $i18n->locale->getTimeformat() == 24 ? '1' : '0',
            'pcEventsOpenInNewWindow' => '0',
            'pcFirstDayOfWeek' => '0', // Sunday
            'pcUsePopups' => '1',
            'pcAllowDirectSubmit' => '0',
            'pcListHowManyEvents' => '15',
            'pcEventDateFormat' => 'DMY', // European: e.g. 4 July 2010
            'pcDateFormats' => self::getDateFormats('DMY'),
            'pcAllowUserCalendar' => '0', // no group
            'pcTimeIncrement' => '15',
            'pcDefaultView' => 'month',
            'pcNotifyAdmin' => '1',
            'pcNotifyEmail' => System::getVar('adminmail'),
            'pcNotifyAdmin2Admin' => '0',
            'pcNotifyPending' => '1',
            'pcNavBarType' => 'buttonbar',
            'pcAllowCatFilter' => '1',
            'enablenavimages' => '1',
            'pcFilterYearStart' => 1,
            'pcFilterYearEnd' => 2,
            'pcListMonths' => 12,
            'pcEventDefaults' => array(
                'sharing' => PostCalendar_Entity_CalendarEvent::SHARING_GLOBAL,
                'categories' => $defaultcats,
                'alldayevent' => 0,
                'startTime' => '01:00',
                'duration' => 3600,
                'fee' => '',
                'contname' => '',
                'conttel' => '',
                'contemail' => '',
                'website' => '',
                'location' => array(
                    'event_location' => '',
                    'event_street1' => '',
                    'event_street2' => '',
                    'event_city' => '',
                    'event_state' => '',
                    'event_postal' => '')),
            'pcTimeItMigrateComplete' => false,
            'pcAllowedViews' => array(
                'today',
                'day',
                'week',
                'month',
                'year',
                'list',
                'create',
                'search',
                'print',
                'xml',
                'event'),
        );

        return $defaults;
    }

    /**
     * get the correct day, format it and return
     * @param string Date
     * @param string jumpday
     * @param string jumpmonth
     * @param string jumpyear
     * @return DateTime instance
     */
    public static function getDate($args)
    {
        if (isset($args['date'])) {
            if (is_object($args['date'])) {
                return $args['date'];
            }
            $args['date'] = str_replace('-', '', $args['date']);
            return DateTime::createFromFormat('Ymd', $args['date']);
        } elseif (isset($args['jumpday'], $args['jumpmonth'], $args['jumpyear'])) {
            return DateTime::createFromFormat('Ymd', $args['jumpyear'] . $args['jumpmonth'] . $args['jumpday']);
        } else {
            return new DateTime();
        }
    }

    /**
     * get appropriate date format settings for various code types
     * from a string setting
     * @param string $string
     * @return array|string 
     */
    public static function getDateFormats($string)
    {
        $formatsAvailable = array(
            'DMY' => array('date' => 'j F Y',
                'strftime' => '%e %B %Y',
                'javascript' => 'd MM yy'),
            'MDY' => array('date' => 'F j, Y',
                'strftime' => '%B %e, %Y',
                'javascript' => 'MM d, yy'),
            'YMD' => array('date' => 'Y-m-d',
                'strftime' => '%Y-%m-%d',
                'javascript' => 'yy-mm-dd'),
        );
        if (isset($formatsAvailable[$string])) {
            return $formatsAvailable[$string];
        } else {
            return "-1";
        }
    }

}