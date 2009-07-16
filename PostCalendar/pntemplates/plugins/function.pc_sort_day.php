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
