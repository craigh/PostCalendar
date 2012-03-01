<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_date_select($args, &$smarty)
{
    $dom       = ZLanguage::getModuleDomain('PostCalendar');
    $jumpday   = FormUtil::getPassedValue('jumpDay');
    $jumpmonth = FormUtil::getPassedValue('jumpMonth');
    $jumpyear  = FormUtil::getPassedValue('jumpYear');
    $Date      = FormUtil::getPassedValue('Date');
    $jumpargs  = array(
        'Date' => $Date,
        'jumpday' => $jumpday,
        'jumpmonth' => $jumpmonth,
        'jumpyear' => $jumpyear);
    $Date      = PostCalendar_Util::getDate($jumpargs);

    $y = substr($Date, 0, 4);
    $m = substr($Date, 4, 2);
    $d = substr($Date, 6, 2);

    $sel_data = array(
        'day'   => __('Day', $dom),
        'week'  => __('Week', $dom),
        'month' => __('Month', $dom),
        'year'  => __('Year', $dom),
        'list'  => __('List View', $dom));

    $smarty->assign('dateorderinfo', ModUtil::getVar('PostCalendar', 'pcNavDateOrder'));
    $smarty->assign('currentjumpdate', $y . '-' . $m . '-' . $d);
    $smarty->assign('viewtypeselector', $sel_data);

    return;
}