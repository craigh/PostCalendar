<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/pntemplates/plugins/modifier.pc_date_format.php $
 * @version     $Id: modifier.pc_date_format.php 639 2010-06-30 22:16:08Z craigh $
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

function smarty_modifier_pc_date_format($string, $format = null, $default_date = null)
{
    $format = isset($format) && !empty($format) ? $format : _SETTING_DATE_FORMAT;
    //setlocale(LC_TIME, ZLanguage::getLocale()); //setlocale(LC_TIME, _PC_LOCALE);

    $ret_val = "";

    $smarty = Zikula_View::getInstance();
    require_once $smarty->_get_plugin_filepath('shared', 'make_timestamp');

    if ($string != '') {
        $ret_val = DateUtil::strftime($format, smarty_make_timestamp($string));
    } elseif (isset($default_date) && $default_date != '') {
        $ret_val = DateUtil::strftime($format, smarty_make_timestamp($default_date));
    } else {
        // when having empty var, just return the current date/time
        $ret_val = DateUtil::strftime($format, time());
    }

    return $ret_val;
}