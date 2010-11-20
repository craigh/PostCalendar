<?php

class PostCalendar_Util
{
    /**
     * PostCalendar Default Module Settings
     * @author Arjen Tebbenhof
     * @author Craig Heydenburg
     * @return array An associated array with key=>value pairs of the default module settings
     */
    public static function getdefaults()
    {
        // figure out associated categories and assign default value of 0 (none)
        $defaultscats = array();
        $cats = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        foreach ($cats as $prop => $id) {
            $defaultcats[$prop] = 0;
        }
        $i18n = ZI18n::getInstance();
    
        // PostCalendar Default Settings
        $defaults = array(
            'pcTime24Hours'           => $i18n->locale->getTimeformat() == 24 ? '1' : '0',
            'pcEventsOpenInNewWindow' => '0',
            'pcFirstDayOfWeek'        => '0', // Sunday
            'pcUsePopups'             => '0',
            'pcAllowDirectSubmit'     => '0',
            'pcListHowManyEvents'     => '15',
            'pcEventDateFormat'       => '%B %e, %Y', // American: e.g. July 4, 2010
            'pcAllowUserCalendar'     => '0', // no group
            'pcTimeIncrement'         => '15',
            'pcDefaultView'           => 'month',
            'pcNotifyAdmin'           => '1',
            'pcNotifyEmail'           => System::getVar('adminmail'),
            'pcNotifyAdmin2Admin'     => '0',
            'pcNotifyPending'         => '1',
            'pcAllowCatFilter'        => '1',
            'enablecategorization'    => '1',
            'enablenavimages'         => '1',
            'enablelocations'         => '0',
            'pcFilterYearStart'       => 1,
            'pcFilterYearEnd'         => 2,
            'pcListMonths'            => 12,
            'pcNavDateOrder'          => array(
                'format'                  => 'MDY',
                'D'                       => '%e',
                'M'                       => '%B',
                'Y'                       => '%Y'),
            'pcEventDefaults'         => array(
                'sharing'                 => SHARING_GLOBAL,
                'categories'              => $defaultcats,
                'alldayevent'             => 0,
                'startTime'               => '01:00:00',
                'duration'                => '3600',
                'fee'                     => '',
                'contname'                => '',
                'conttel'                 => '',
                'contemail'               => '',
                'website'                 => '',
                'location'                => array(
                    'event_location'          => '',
                    'event_street1'           => '',
                    'event_street2'           => '',
                    'event_city'              => '',
                    'event_state'             => '',
                    'event_postal'            => '')));
    
        return $defaults;
    }
    /**
     * get the correct day, format it and return
     * @param string format
     * @param string Date
     * @param string jumpday
     * @param string jumpmonth
     * @param string jumpyear
     * @return string formatted date string
     */
    public static function getDate($args)
    {
        $format = (!empty($args['format'])) ? $args['format'] : '%Y%m%d%H%M%S';
    
        $time      = time();
        $jumpday   = isset($args['jumpday']) ? $args['jumpday'] : strftime('%d', $time);
        $jumpmonth = isset($args['jumpmonth']) ? $args['jumpmonth'] : strftime('%m', $time);
        $jumpyear  = isset($args['jumpyear']) ? $args['jumpyear'] : strftime('%Y', $time);
    
        if (UserUtil::isLoggedIn()) {
            $time += (UserUtil::getVar('timezone_offset') - System::getVar('timezone_offset')) * 3600;
        }
    
        $Date = isset($args['Date']) ? $args['Date'] : '';
        if (empty($Date)) {
            // if we still don't have a date then calculate it
            $Date = (int) "$jumpyear$jumpmonth$jumpday";
        }
    
        $y = substr($Date, 0, 4);
        $m = substr($Date, 4, 2);
        $d = substr($Date, 6, 2);
        return DateUtil::strftime($format, mktime(0, 0, 0, $m, $d, $y));
    }

} // end class def