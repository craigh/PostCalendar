<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_sort_events($params, Zikula_View $view)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!array_key_exists('var', $params) || empty($params['var'])) {
        $view->trigger_error(__f('%1$s: missing or empty \'%2$s\' parameter', array(
            'Plugin:pc_sort_events',
            'var'), $dom));
        return;
    }

    if (!array_key_exists('value', $params) || !is_array($params['value'])) {
        $view->trigger_error(__f('%1$s: missing or empty \'%2$s\' parameter', array(
            'Plugin:pc_sort_events',
            'value'), $dom));
        return;
    }

    if (!array_key_exists('sort', $params)) {
        $view->trigger_error(__f('%1$s: missing or empty \'%2$s\' parameter', array(
            'Plugin:pc_sort_events',
            'sort'), $dom));
        return;
    }

    $order = array_key_exists('order', $params) ? $params['order'] : 'asc';

    switch ($params['sort']) {
        case 'category':
            if (strtolower($order) == 'asc') {
                $function = 'sort_byCategoryA';
            }
            if (strtolower($order) == 'desc') {
                $function = 'sort_byCategoryD';
            }
            break;

        case 'title':
            if (strtolower($order) == 'asc') {
                $function = 'sort_byTitleA';
            }
            if (strtolower($order) == 'desc') {
                $function = 'sort_byTitleD';
            }
            break;

        case 'time':
            if (strtolower($order) == 'asc') {
                $function = 'sort_byTimeA';
            }
            if (strtolower($order) == 'desc') {
                $function = 'sort_byTimeD';
            }
            break;
    }

    $newArray = array();
    foreach ($params['value'] as $date => $events) {
        usort($events, $function);
        $newArray[$date] = $events;
    }

    $view->assign_by_ref($params['var'], $newArray);
}
/**
 * Sorting Functions
 **/

function sort_byCategoryA($a, $b)
{
    if ($a['catname'] < $b['catname']) {
        return -1;
    } elseif ($a['catname'] > $b['catname']) {
        return 1;
    } else {
        return 0;
    }
}
function sort_byCategoryD($a, $b)
{
    if ($a['catname'] < $b['catname']) {
        return 1;
    } elseif ($a['catname'] > $b['catname']) {
        return -1;
    } else {
        return 0;
    }
}
function sort_byTitleA($a, $b)
{
    if ($a['title'] < $b['title']) {
        return -1;
    } elseif ($a['title'] > $b['title']) {
        return 1;
    } else {
        return 0;
    }
}
function sort_byTitleD($a, $b)
{
    if ($a['title'] < $b['title']) {
        return 1;
    } elseif ($a['title'] > $b['title']) {
        return -1;
    } else {
        return 0;
    }
}
function sort_byTimeA($a, $b)
{
    if ($a['eventStart'] < $b['eventStart']) {
        return -1;
    } elseif ($a['eventStart'] > $b['eventStart']) {
        return 1;
    } else {
        return 0;
    }
}
function sort_byTimeD($a, $b)
{
    if ($a['eventStart'] < $b['eventStart']) {
        return 1;
    } elseif ($a['eventStart'] > $b['eventStart']) {
        return -1;
    } else {
        return 0;
    }
}