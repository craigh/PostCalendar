<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/pntemplates/plugins/function.pc_date_select.php $
 * @version     $Id: function.pc_date_select.php 639 2010-06-30 22:16:08Z craigh $
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_date_select($args, &$smarty)
{
    $dom       = ZLanguage::getModuleDomain('PostCalendar');
    $viewtype  = FormUtil::getPassedValue('viewtype');
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
    if (!isset($viewtype)) {
        $viewtype = _SETTING_DEFAULT_VIEW;
    }

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
    $smarty->assign('viewtypeselected', $viewtype);

    return;
}