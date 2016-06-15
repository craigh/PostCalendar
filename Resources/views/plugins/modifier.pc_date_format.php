<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

use Zikula\PostCalendarModule\Helper\PostCalendarUtil;

function smarty_modifier_pc_date_format($string, $format = null, $default_date = null)
{
    $defaultFormat = ModUtil::getVar('ZikulaPostCalendarModule', 'pcDateFormats');
    $format = (isset($format) && !empty($format)) ? $format : $defaultFormat['date'];
        
    if ($string != '') {
        if ($string instanceof DateTime) {
            $date = $string;
        } else {
            $date = DateTime::createFromFormat('Ymd', str_replace('-', '', $string));
        }
    } elseif (isset($default_date) && $default_date != '') {
        $date = DateTime::createFromFormat('Ymd', str_replace('-', '', $default_date));
    } else {
        // when having empty var, just use the current date/time
        $date = new DateTime();
    }

    return PostCalendarUtil::translate($date->format($format));
}