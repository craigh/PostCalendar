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
    $action = array_key_exists('action', $args) && isset($args['action']) ? $args['action'] : _SETTING_DEFAULT_VIEW;
    $print  = array_key_exists('print',  $args) && !empty($args['print']) ? true            : false;
    $date   = array_key_exists('date',   $args) && !empty($args['date'])  ? $args['date']   : null;
    $full   = array_key_exists('full',   $args) && !empty($args['full'])  ? true            : false;

    $viewtype    = strtolower(FormUtil::getPassedValue('viewtype', _SETTING_DEFAULT_VIEW));
    if (FormUtil::getPassedValue('func') == 'new') $viewtype='new';
    $pc_username = FormUtil::getPassedValue('pc_username');
    $popup       = FormUtil::getPassedValue('popup');
    $today       = DateUtil::getDatetime('', '%Y%m%d000000');

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
            $link = pnModURL('PostCalendar', 'event', 'new', array('Date' => $date));
            break;
        case 'today':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('viewtype' => $viewtype, 'Date' => $today, 'pc_username' => $pc_username));
            break;
        case 'day':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('viewtype' => 'day', 'Date' => $date, 'pc_username' => $pc_username));
            break;
        case 'week':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('viewtype' => 'week', 'Date' => $date, 'pc_username' => $pc_username));
            break;
        case 'month':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('viewtype' => 'month', 'Date' => $date, 'pc_username' => $pc_username));
            break;
        case 'year':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('viewtype' => 'year', 'Date' => $date, 'pc_username' => $pc_username));
            break;
        case 'list':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('viewtype' => 'list', 'Date' => $date, 'pc_username' => $pc_username));
            break;
        case 'search':
            $link = pnModURL('Search');
            break;
        case 'print':
            $link = pnGetCurrentURL() ."&theme=Printer";
            break;
        case 'detail':
            if (isset($args['eid'])) {
                if (_SETTING_OPEN_NEW_WINDOW && !$popup) {
                    $link = "javascript:opencal('{$args['eid']}','$date');";
                } else {
                    $link = pnModURL('PostCalendar', 'user', 'view',
                        array('Date' => $date, 'viewtype' => 'details', 'eid' => $args['eid']));
                }
            } else {
                $link = '';
            }
            break;
    }

    if (_SETTING_OPEN_NEW_WINDOW && $viewtype == 'details') $link .= '" target="csCalendar"';

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
        if (_SETTING_USENAVIMAGES) {
            $image_text = $labeltexts[$action];
            $image_src = ($viewtype==$action) ? $action.'_on.gif' : $action.'.gif';
            require_once $smarty->_get_plugin_filepath('function', 'pnimg');
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

        $ret_val = "<a class='$class' href='$link' title='$title'>$display</a>";
    } else {
        $ret_val = $link;
    }

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}
