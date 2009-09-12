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
function smarty_function_pc_url($args)
{
    $action = array_key_exists('action', $args) && isset($args['action']) ? $args['action'] : _SETTING_DEFAULT_VIEW;
    $print  = array_key_exists('print',  $args) && !empty($args['print']) ? true            : false;
    $date   = array_key_exists('date',   $args) && !empty($args['date'])  ? $args['date']   : null;

    $template_view = FormUtil::getPassedValue('tplview');
    $viewtype = strtolower(FormUtil::getPassedValue('viewtype'));
    $pc_username = FormUtil::getPassedValue('pc_username');
    $category = FormUtil::getPassedValue('pc_category');
    $topic = FormUtil::getPassedValue('pc_topic');
    $popup = FormUtil::getPassedValue('popup');
    $today = DateUtil::getDatetime('', '%Y%m%d000000');

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
        case 'submit':
        case 'submit-admin':
            $link = pnModURL('PostCalendar', 'event', 'new', array('tplview' => $template_view, 'Date' => $date));
            break;
        case 'today':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('tplview' => $template_view, 'viewtype' => $viewtype,
                                'Date' => $today,
                                'pc_username' => $pc_username,
                                'pc_category' => $category,
                                'pc_topic' => $topic));
            break;
        case 'day':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('tplview' => $template_view, 'viewtype' => 'day',
                                'Date' => $date,
                                'pc_username' => $pc_username,
                                'pc_category' => $category,
                                'pc_topic' => $topic));
            break;
        case 'week':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('tplview' => $template_view, 'viewtype' => 'week',
                                'Date' => $date,
                                'pc_username' => $pc_username,
                                'pc_category' => $category,
                                'pc_topic' => $topic));
            break;
        case 'month':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('tplview' => $template_view, 'viewtype' => 'month',
                                'Date' => $date,
                                'pc_username' => $pc_username,
                                'pc_category' => $category,
                                'pc_topic' => $topic));
            break;
        case 'year':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('tplview' => $template_view, 'viewtype' => 'year',
                                'Date' => $date,
                                'pc_username' => $pc_username,
                                'pc_category' => $category,
                                'pc_topic' => $topic));
            break;
        case 'list':
            $link = pnModURL('PostCalendar', 'user', 'view',
                array('tplview' => $template_view, 'viewtype' => 'list',
                                'Date' => $date,
                                'pc_username' => $pc_username,
                                'pc_category' => $category,
                                'pc_topic' => $topic));
            break;
        case 'detail':
            if (isset($args['eid'])) {
                if (_SETTING_OPEN_NEW_WINDOW && !$popup) {
                    $link = "javascript:opencal({$args['eid']},'$date');";
                } else {
                    $link = pnModURL('PostCalendar', 'user', 'view',
                        array('Date' => $date,
                                        'tplview' => $template_view,
                                        'viewtype' => 'details',
                                        'eid' => $args['eid']));
                }
            } else
                $link = '';

            break;
    }

    if ($print) {
        $link .= '" target="_blank"';
    } elseif (_SETTING_OPEN_NEW_WINDOW && $viewtype == 'details') {
        $link .= '" target="csCalendar"';
    }

    echo DataUtil::formatForDisplay($link);
}
