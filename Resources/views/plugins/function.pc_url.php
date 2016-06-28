<?php

/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

use Zikula\PostCalendarModule\Helper\PostCalendarUtil;

function smarty_function_pc_url($args, Zikula_View $view)
{
    $request = $view->getRequest();
    $modVars = $view->get_template_vars('modvars');
    
    $action = array_key_exists('action', $args) && isset($args['action']) ? $args['action'] : $modVars['ZikulaPostCalendarModule']['pcDefaultView'];
    $date = array_key_exists('date', $args) && !empty($args['date']) ? $args['date'] : null;
    $full = array_key_exists('full', $args) && !empty($args['full']) ? true : false;
    $class = array_key_exists('class', $args) && !empty($args['class']) ? $args['class'] : null;
    $display = array_key_exists('display', $args) && !empty($args['display']) ? $args['display'] : null;
    $eid = array_key_exists('eid', $args) && !empty($args['eid']) ? $args['eid'] : null;
    $javascript = array_key_exists('javascript', $args) && !empty($args['javascript']) ? $args['javascript'] : null;
    $assign = array_key_exists('assign', $args) && !empty($args['assign']) ? $args['assign'] : null;
    $title = array_key_exists('title', $args) && !empty($args['title']) ? $args['title'] : '';
    $viewtype = $request->request->get('viewtype', $request->query->get('viewtype', $modVars['ZikulaPostCalendarModule']['pcDefaultView']));
    $viewtype = array_key_exists('viewtype', $args) && !empty($args['viewtype']) ? $args['viewtype'] : strtolower($viewtype);
    unset($args['action']);
    unset($args['date']);
    unset($args['full']);
    unset($args['class']);
    unset($args['display']);
    unset($args['eid']);
    unset($args['javascript']);
    unset($args['assign']);
    unset($args['title']);
    unset($args['viewtype']);
    
    $userFilter = $request->request->get('userfilter', $request->query->get('userfilter', null));

    if (is_null($date)) {
        $jumpargs = array(
            'date' => $request->request->get('date', $request->query->get('date', null)),
            'jumpday' => $request->request->get('jumpDay', $request->query->get('jumpDay', null)),
            'jumpmonth' => $request->request->get('jumpMonth', $request->query->get('jumpMonth', null)),
            'jumpyear' => $request->request->get('jumpYear', $request->query->get('jumpYear', null)));
        $date = PostCalendarUtil::getDate($jumpargs);
    } elseif (!is_object($date)) {
        $date = DateTime::createFromFormat('Y-m-d', $date);
    }

    switch ($action) {
        case 'submit':
            $link = ModUtil::url('ZikulaPostCalendarModule', 'event', 'create', array(
                        'date' => $date->format('Ymd')));
            break;
        case 'day':
        case 'week':
        case 'month':
            $link = ModUtil::url('ZikulaPostCalendarModule', 'user', 'display', array(
                        'viewtype' => $action,
                        'date' => $date->format('Ymd'),
                        'userfilter' => $userFilter));
            break;
        case 'event':
            if (isset($eid)) {
                $linkparams = array(
                    'date' => $date->format('Ymd'),
                    'viewtype' => 'event',
                    'eid' => $eid);
                if ($modVars['ZikulaPostCalendarModule']['pcEventsOpenInNewWindow']) {
                    $linkparams['popup'] = true;
                }
                $link = ModUtil::url('ZikulaPostCalendarModule', 'user', 'display', $linkparams);
            } else {
                $link = '';
            }
            break;
    }

    $link = DataUtil::formatForDisplay($link);

    if ($full) {
        $classes = array($class);
        if ($modVars['ZikulaPostCalendarModule']['pcUsePopups']) {
            $classes[] = 'tooltips';
        }
        if (($modVars['ZikulaPostCalendarModule']['pcEventsOpenInNewWindow']) && ($action == "event")) {
            $classes[] = 'event_details';
        }
        $class = implode(' ', $classes);

        // create string of remaining properties and values
        $props = "";
        if (!empty($args)) {
            foreach ($args as $prop => $val) {
                $props .= " $prop='$val'";
            }
        }
        if ($class) {
            $class = " class='$class'";
        }
        if ($title) {
            $title = " title='$title'";
        }
        $ret_val = "<a href='$link'" . $class . $title . $props . $javascript . ">$display</a>";
    } else {
        $ret_val = $link;
    }

    if (isset($assign)) {
        $view->assign($assign, $ret_val);
    } else {
        return $ret_val;
    }
}
