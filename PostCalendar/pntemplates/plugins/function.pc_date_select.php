<?php
/**
 *	SVN: $Id$
 *
 *  @package     PostCalendar
 *  @author      $Author$
 *  @link	     $HeadURL$
 *  @version     $Revision$
 *
 *  PostCalendar::Zikula Events Calendar Module
 *  Copyright (C) 2002  The PostCalendar Team
 *  http://postcalendar.tv
 *  Copyright (C) 2009  Sound Web Development
 *  Craig Heydenburg
 *  http://code.zikula.org/soundwebdevelopment/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *  To read the license please read the docs/license.txt or visit
 *  http://www.gnu.org/copyleft/gpl.html
 *
 */
function smarty_function_pc_date_select($args)
{
    $print = pnVarCleanFromInput('print');
    $tplview = pnVarCleanFromInput('tplview');
    $viewtype = pnVarCleanFromInput('viewtype');
    $Date = postcalendar_getDate();

    if (!isset($viewtype)) $viewtype = _SETTING_DEFAULT_VIEW;

    if (!isset($y)) $y = substr($Date, 0, 4);
    if (!isset($m)) $m = substr($Date, 4, 2);
    if (!isset($d)) $d = substr($Date, 6, 2);

    if (!isset($args['day']) || strtolower($args['day']) == 'on') {
        $args['day'] = true;
        @define('_PC_FORM_DATE', true);
    } else {
        $args['day'] = false;
    }
    if (!isset($args['month']) || strtolower($args['month']) == 'on') {
        $args['month'] = true;
        @define('_PC_FORM_DATE', true);
    } else {
        $args['month'] = false;
    }
    if (!isset($args['year']) || strtolower($args['year']) == 'on') {
        $args['year'] = true;
        @define('_PC_FORM_DATE', true);
    } else {
        $args['year'] = false;
    }
    if (!isset($args['view']) || strtolower($args['view']) == 'on') {
        $args['view'] = true;
        @define('_PC_FORM_VIEW_TYPE', true);
    } else {
        $args['view'] = false;
    }

    $dayselect = $monthselect = $yearselect = $viewselect = '';
    // note: pcHtmlUtil is an extension of the core HtmlUtil class
    //       it was extended to provide month names (text)
    //       a patch for core was supplied to Zikula and may appear in v1.2
    //       at which time, this should be reverted to HtmlUtil
    Loader::loadClass('pcHtmlUtil',
        'modules/PostCalendar/pnincludes');
    if ($args['day'] === true) {
        $dayselect = pcHtmlUtil::getSelector_DatetimeDay($d, 'jumpday');
    }
    if ($args['month'] === true) {

        $monthselect = pcHtmlUtil::getSelector_DatetimeMonth($m, 'jumpmonth', false, false, 1, true);
    }
    if ($args['year'] === true) {
        $yearselect = pcHtmlUtil::getSelector_DatetimeYear($y, 'jumpyear', date('Y') - 10, date('Y') + 10);
    }

    if ($args['view'] === true) {
        $sel_data = array();
        $sel_data[0]['id'] = 'day';
        $sel_data[0]['selected'] = $viewtype == 'day';
        $sel_data[0]['name'] = _CAL_DAYVIEW;
        $sel_data[1]['id'] = 'week';
        $sel_data[1]['selected'] = $viewtype == 'week';
        $sel_data[1]['name'] = _CAL_WEEKVIEW;
        $sel_data[2]['id'] = 'month';
        $sel_data[2]['selected'] = $viewtype == 'month';
        $sel_data[2]['name'] = _CAL_MONTHVIEW;
        $sel_data[3]['id'] = 'year';
        $sel_data[3]['selected'] = $viewtype == 'year';
        $sel_data[3]['name'] = _CAL_YEARVIEW;
        $viewselect = pcHtmlUtil::FormSelectMultipleSubmit('viewtype', $sel_data);
    }

    if (!isset($args['label'])) $args['label'] = _PC_JUMP_MENU_SUBMIT;

    $jumpsubmit = '<input type="submit" name="submit" value="' . $args['label'] . '" />';

    $orderArray = array('day' => $dayselect, 'month' => $monthselect, 'year' => $yearselect, 'view' => $viewselect,
                    'jump' => $jumpsubmit);

    if (isset($args['order'])) {
        $newOrder = array();
        $order = explode(',', $args['order']);

        foreach ($order as $tmp_order)
            array_push($newOrder, $orderArray[$tmp_order]);

        foreach ($orderArray as $key => $old_order)
            if (!in_array($old_order, $newOrder)) array_push($newOrder, $orderArray[$key]);

        $order = $newOrder;
    } else
        $order = $orderArray;

    foreach ($order as $element)
        echo $element;
}
