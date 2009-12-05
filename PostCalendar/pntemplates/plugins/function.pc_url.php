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
function smarty_function_pc_url($args, &$smarty)
{
    $action     = array_key_exists('action',     $args) && isset($args['action'])      ? $args['action']     : _SETTING_DEFAULT_VIEW; unset($args['action']);
    $print      = array_key_exists('print',      $args) && !empty($args['print'])      ? true                : false; unset($args['print']);
    $date       = array_key_exists('date',       $args) && !empty($args['date'])       ? $args['date']       : null; unset($args['date']);
    $full       = array_key_exists('full',       $args) && !empty($args['full'])       ? true                : false; unset($args['full']);
    $class      = array_key_exists('class',      $args) && !empty($args['class'])      ? $args['class']      : null; unset($args['class']);
    $display    = array_key_exists('display',    $args) && !empty($args['display'])    ? $args['display']    : null; unset($args['display']);
    $eid        = array_key_exists('eid',        $args) && !empty($args['eid'])        ? $args['eid']        : null; unset($args['eid']);
    $javascript = array_key_exists('javascript', $args) && !empty($args['javascript']) ? $args['javascript'] : null; unset($args['javascript']);
    $assign     = array_key_exists('assign',     $args) && !empty($args['assign'])     ? $args['assign']     : null; unset($args['assign']);
    $navlink    = array_key_exists('navlink',    $args) && !empty($args['navlink'])    ? true                : false; unset($args['navlink']);
    $func       = array_key_exists('func',       $args) && !empty($args['func'])       ? $args['func']       : 'new'; unset($args['func']);

    $viewtype    = strtolower(FormUtil::getPassedValue('viewtype', _SETTING_DEFAULT_VIEW));
    if (FormUtil::getPassedValue('func') == 'new') $viewtype='new';
    $pc_username = FormUtil::getPassedValue('pc_username');

    if (is_null($date)) {
        //not sure these three lines are needed with call to getDate here
        $jumpday   = FormUtil::getPassedValue('jumpday');
        $jumpmonth = FormUtil::getPassedValue('jumpmonth');
        $jumpyear  = FormUtil::getPassedValue('jumpyear');
        $Date      = FormUtil::getPassedValue('Date');
        $date      = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));
    }
    // some extra cleanup if necessary
    $date = str_replace('-', '', $date);

    switch ($action) {
        case 'add':
        case 'submit':
        case 'submit-admin':
            $link = pnModURL('PostCalendar', 'event', $func, array('Date' => $date));
            break;
        case 'today':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('viewtype' => $viewtype, 'Date' => DateUtil::getDatetime('', '%Y%m%d000000'), 'pc_username' => $pc_username));
            break;
        case 'day':
        case 'week':
        case 'month':
        case 'year':
        case 'list':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('viewtype' => $action, 'Date' => $date, 'pc_username' => $pc_username));
            break;
        case 'search':
            $link = pnModURL('Search');
            break;
        case 'print':
            $link = pnGetCurrentURL() ."&theme=Printer";
            break;
        case 'detail':
            if (isset($eid)) {
                if (_SETTING_OPEN_NEW_WINDOW && !_SETTING_USE_POPUPS) {
                    $javascript = " onClick=\"opencal('$eid','$date'); return false;\"";
                    $link = "#";
                } else {
                    $link = pnModURL('PostCalendar', 'user', 'view',
                        array('Date' => $date, 'viewtype' => 'details', 'eid' => $eid));
                }
            } else {
                $link = '';
            }
            break;
    }

    $link = DataUtil::formatForDisplay($link);

    $labeltexts = array('today'  => __('Jump to Today', $dom), 
                        'day'    => __('Day View', $dom), 
                        'week'   => __('Week View', $dom),
                        'month'  => __('Month View', $dom),
                        'year'   => __('Year View', $dom),
                        'list'   => __('List View', $dom),
                        'add'    => __('Submit New Event', $dom),
                        'search' => __('Search', $dom),
                        'print'  => __('Print View', $dom),
                        );
    if ($full) {
        if ($navlink) {
            if (_SETTING_USENAVIMAGES) {
                $image_text = $labeltexts[$action];
                $image_src = ($viewtype==$action) ? $action.'_on.gif' : $action.'.gif';
                include $smarty->_get_plugin_filepath('function', 'pnimg');
                $pnimg_params = array('src'=>$image_src, 'alt'=>$image_text, 'title'=>$image_text);
                if ($action == 'print') { $pnimg_params['modname']='core';$pnimg_params['set']='icons/small';$pnimg_params['src']='printer1.gif'; }
                $display = smarty_function_pnimg($pnimg_params, $smarty);
                $class = 'postcalendar_nav_img';
                $title = $image_text;
            } else {
                $linkmap = array('today'  => __('Today', $dom), 
                                 'day'    => __('Day', $dom), 
                                 'week'   => __('Week', $dom),
                                 'month'  => __('Month', $dom),
                                 'year'   => __('Year', $dom),
                                 'list'   => __('List', $dom),
                                 'add'    => __('Add', $dom),
                                 'search' => __('Search', $dom),
                                 'print'  => __('Print', $dom),
                                 );
                $display = $linkmap[$action];
                $class = ($viewtype==$action) ? 'postcalendar_nav_text_selected' : 'postcalendar_nav_text';
                $title = $labeltexts[$action];
            }
        }
        // create string of remaining properties and values
        if (!empty($args)) {
            $props = "";
            foreach ($args as $prop=>$val) {
                $props .= " $prop='$val'";
            }
        }
        if ($class) $class=" class='$class'";
        if ($title) $title=" title='$title'";
        $ret_val = "<a href='$link'".$class.$title.$props.$javascript.">$display</a>";
    } else {
        $ret_val = $link;
    }

    if (isset($assign)) {
        $smarty->assign($assign, $ret_val);
    } else {
        return $ret_val;
    }
}
