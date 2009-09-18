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
function smarty_function_pc_date_format($args, &$smarty)
{
    $format = isset($args['format']) ? $args['format'] : _SETTING_DATE_FORMAT;
    setlocale(LC_TIME, _PC_LOCALE);
    if (isset($args['date'])) {
        $ret_val = strftime($format, smarty_make_timestamp($args['date']));
    } else {
        $ret_val = strftime($format, time());
    }

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}