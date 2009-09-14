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
require_once $smarty->_get_plugin_filepath('shared', 'make_timestamp');
function smarty_modifier_pc_date_format($string, $format = null, $default_date = null)
{
    setlocale(LC_TIME, _PC_LOCALE);

    if (empty($format)) $format = _SETTING_DATE_FORMAT;

    $ret_val = "";

    if ($string != '') {
        $ret_val =  strftime($format, smarty_make_timestamp($string));
    } elseif (isset($default_date) && $default_date != '') {
        $ret_val =  strftime($format, smarty_make_timestamp($default_date));
    }

    return $ret_val;
}
