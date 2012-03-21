<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

function smarty_modifier_pc_date_format($string, $format = null, $default_date = null)
{
    $format = isset($format) && !empty($format) ? $format : _SETTING_DATE_FORMAT;
        
    if ($string != '') {
        $date = DateTime::createFromFormat('Ymd', str_replace('-', '', $string));
    } elseif (isset($default_date) && $default_date != '') {
        $date = DateTime::createFromFormat('Ymd', str_replace('-', '', $default_date));
    } else {
        // when having empty var, just return the current date/time
        $date = new DateTime();
    }

    return $date->format($format);
}