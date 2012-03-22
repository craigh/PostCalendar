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
    $jumpargs  = array(
        'date' => $request->request->get('date', $request->query->get('date')),
        'jumpday' => $request->request->get('jumpDay'),
        'jumpmonth' => $request->request->get('jumpMonth'),
        'jumpyear' => $request->request->get('jumpYear'));
    $date = PostCalendar_Util::getDate($jumpargs);

    $sel_data = array(
        'day'   => __('Day', $dom),
        'week'  => __('Week', $dom),
        'month' => __('Month', $dom),
        'year'  => __('Year', $dom),
        'list'  => __('List View', $dom));

    $view->assign('dateorderinfo', ModUtil::getVar('PostCalendar', 'pcNavDateOrder'));
    $view->assign('currentjumpdate', $date->format('Y-m-d'));
    $view->assign('viewtypeselector', $sel_data);

    return;
}