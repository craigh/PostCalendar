<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_date_select($args, Zikula_View $view)
{
    $dom       = ZLanguage::getModuleDomain('PostCalendar');
    $request   = $view->getRequest();
    $jumpday   = $request->getPost()->get('jumpDay');
    $jumpmonth = $request->getPost()->get('jumpMonth');
    $jumpyear  = $request->getPost()->get('jumpYear');
    $Date      = $request->getPost()->get('Date');
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

    $view->assign('dateorderinfo', ModUtil::getVar('PostCalendar', 'pcNavDateOrder'));
    $view->assign('currentjumpdate', $y . '-' . $m . '-' . $d);
    $view->assign('viewtypeselector', $sel_data);

    return;
}