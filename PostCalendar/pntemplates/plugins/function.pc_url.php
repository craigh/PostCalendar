<?php
/**
 * SVN: $Id$
 *
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Revision$
 *
 * PostCalendar::Zikula Events Calendar Module
 * Copyright (C) 2002  The PostCalendar Team
 * http://postcalendar.tv
 * Copyright (C) 2009  Sound Web Development
 * Craig Heydenburg
 * http://code.zikula.org/soundwebdevelopment/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * To read the license please read the docs/license.txt or visit
 * http://www.gnu.org/copyleft/gpl.html
 *
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
        case 'search':
            $link = pnModURL('PostCalendar', 'user', 'search');
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
