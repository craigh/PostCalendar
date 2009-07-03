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

function smarty_function_pc_sort_day($params, &$smarty)
{
    if (!array_key_exists('var', $params) || empty($params['var'])) {
        $smarty->trigger_error("sort_array: missing or empty 'var' parameter");
        return;
    }

    if (!array_key_exists('value', $params) || !is_array($params['value'])) {
        $smarty->trigger_error("sort_array: missing or invalid 'value' parameter");
        return;
    }

    $order = array_key_exists('order', $params) ? $params['order'] : 'asc';
    $inc = array_key_exists('inc', $params) ? $params['inc'] : 15;

    $sh = '08'; $sm = '00';
    if (array_key_exists('start', $params)) {
        list($sh, $sm) = explode(':', $params['start']);
    }
    $eh = '21'; $em = '00';
    if (array_key_exists('end', $params)) {
        list($eh, $em) = explode(':', $params['end']);
    }

    if (strtolower($order) == 'asc') $function = 'sort_byTimeA';
    if (strtolower($order) == 'desc') $function = 'sort_byTimeD';

    foreach ($params['value'] as $events) {
        usort($events, $function);
        $newArray[] = $events;
    }

    // here we want to create an intelligent array of
    // columns and rows to build a nice day view
    $ch = $sh;
    $cm = $sm;
    while ("$ch:$cm" <= "$eh:$em") {
        $hours["$ch:$cm"] = array();
        $cm += $inc;
        if ($cm >= 60) {
            $cm = '00';
            $ch = sprintf('%02d', $ch + 1);
        }
    }

    $alldayevents = array();
    foreach ($newArray as $event) {
        list($sh, $sm, ) = explode(':', $event['startTime']);
        $eh = sprintf('%02d', $sh + $event['duration_hours']);
        $em = sprintf('%02d', $sm + $event['duration_minutes']);

        if ($event['alldayevent']) {
            // we need an entire column . save till later
            $alldayevents[] = $event;
        } else {
            //find open time slots - avoid overlapping
            $needed = array();
            $ch = $sh;
            $cm = $sm;
            //what times do we need?
            while ("$ch:$cm" < "$eh:$em") {
                $needed[] = "$ch:$cm";
                $cm += $inc;
                if ($cm >= 60) {
                    $cm = '00';
                    $ch = sprintf('%02d', $ch + 1);
                }
            }
            $i = 0;
            foreach ($needed as $time) {
                if ($i == 0) {
                    $hours[$time][] = $event;
                    $key = count($hours[$time]) - 1;
                } else {
                    $hours[$time][$key] = 'continued';
                }
                $i++;
            }
        }
    }

    $smarty->assign_by_ref($params['var'], $hours);
}
