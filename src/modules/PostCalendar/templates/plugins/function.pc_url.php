<?php

/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_url($args, Zikula_View $view)
{
    $request = $view->getRequest();
    
    $action = array_key_exists('action', $args) && isset($args['action']) ? $args['action'] : _SETTING_DEFAULT_VIEW;
    $print = array_key_exists('print', $args) && !empty($args['print']) ? true : false;
    $date = array_key_exists('date', $args) && !empty($args['date']) ? $args['date'] : null;
    $full = array_key_exists('full', $args) && !empty($args['full']) ? true : false;
    $class = array_key_exists('class', $args) && !empty($args['class']) ? $args['class'] : null;
    $display = array_key_exists('display', $args) && !empty($args['display']) ? $args['display'] : null;
    $eid = array_key_exists('eid', $args) && !empty($args['eid']) ? $args['eid'] : null;
    $javascript = array_key_exists('javascript', $args) && !empty($args['javascript']) ? $args['javascript'] : null;
    $assign = array_key_exists('assign', $args) && !empty($args['assign']) ? $args['assign'] : null;
    $navlink = array_key_exists('navlink', $args) && !empty($args['navlink']) ? true : false;
    $func = array_key_exists('func', $args) && !empty($args['func']) ? $args['func'] : 'create';
    $title = array_key_exists('title', $args) && !empty($args['title']) ? $args['title'] : '';
    $viewtype = $request->getPost()->get('viewtype', $request->getGet()->get('viewtype', _SETTING_DEFAULT_VIEW));
    $viewtype = array_key_exists('viewtype', $args) && !empty($args['viewtype']) ? $args['viewtype'] : strtolower($viewtype);
    unset($args['action']);
    unset($args['print']);
    unset($args['date']);
    unset($args['full']);
    unset($args['class']);
    unset($args['display']);
    unset($args['eid']);
    unset($args['javascript']);
    unset($args['assign']);
    unset($args['navlink']);
    unset($args['func']);
    unset($args['title']);
    unset($args['viewtype']);

    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if ($request->getPost()->get('func', $request->getGet()->get('func', null)) == 'create') {
        $viewtype = 'create';
    }
    $pc_username = $request->getPost()->get('pc_username', $request->getGet()->get('pc_username', null));

    if (is_null($date)) {
        //not sure these three lines are needed with call to getDate here
        $jumpday = $request->getPost()->get('jumpDay', $request->getGet()->get('jumpDay', null));
        $jumpmonth = $request->getPost()->get('jumpMonth', $request->getGet()->get('jumpMonth', null));
        $jumpyear = $request->getPost()->get('jumpYear', $request->getGet()->get('jumpYear', null));
        $Date = $request->getPost()->get('Date', $request->getGet()->get('Date', null));
        $jumpargs = array(
            'Date' => $Date,
            'jumpday' => $jumpday,
            'jumpmonth' => $jumpmonth,
            'jumpyear' => $jumpyear);
        $date = PostCalendar_Util::getDate($jumpargs);
    }
    // some extra cleanup if necessary
    $date = str_replace('-', '', $date);

    switch ($action) {
        case 'add':
        case 'submit':
        case 'submit-admin':
            $link = ModUtil::url('PostCalendar', 'event', $func, array(
                        'Date' => $date));
            break;
        case 'today':
            $link = ModUtil::url('PostCalendar', 'user', 'display', array(
                        'viewtype' => $viewtype,
                        'Date' => date('Ymd'), // . '000000',
                        'pc_username' => $pc_username));
            break;
        case 'day':
        case 'week':
        case 'month':
        case 'year':
        case 'list':
            $link = ModUtil::url('PostCalendar', 'user', 'display', array(
                        'viewtype' => $action,
                        'Date' => $date,
                        'pc_username' => $pc_username));
            break;
        case 'search':
            $link = ModUtil::url('Search', 'user', 'form');
            break;
        case 'print':
            $link = System::getCurrentUrl() . "&theme=Printer";
            break;
        case 'rss':
            $link = ModUtil::url('PostCalendar', 'user', 'display', array(
                        'viewtype' => 'xml',
                        'theme' => 'rss'));
            break;
        case 'detail':
            if (isset($eid)) {
                $linkparams = array(
                    'Date' => $date,
                    'viewtype' => 'details',
                    'eid' => $eid);
                if (_SETTING_OPEN_NEW_WINDOW) {
                    $linkparams['popup'] = true;
                }
                $link = ModUtil::url('PostCalendar', 'user', 'display', $linkparams);
            } else {
                $link = '';
            }
            break;
    }

    $link = DataUtil::formatForDisplay($link);
    $labeltexts = array(
        'today' => __('Jump to Today', $dom),
        'day' => __('Day View', $dom),
        'week' => __('Week View', $dom),
        'month' => __('Month View', $dom),
        'year' => __('Year View', $dom),
        'list' => __('List View', $dom),
        'add' => __('Submit New Event', $dom),
        'search' => __('Search', $dom),
        'print' => __('Print View', $dom),
        'rss' => __('RSS Feed', $dom));
    if ($full) {
        if ($navlink) {
            if (_SETTING_USENAVIMAGES) {
                $image_text = $labeltexts[$action];
                $image_src = ($viewtype == $action) ? $action . '_on.gif' : $action . '.gif';
                include_once $view->_get_plugin_filepath('function', 'img');
                $img_params = array(
                    'modname' => 'PostCalendar',
                    'src' => $image_src,
                    'alt' => $image_text,
                    'title' => $image_text);
                if ($action == 'print') {
                    $img_params['modname'] = 'core';
                    $img_params['set'] = 'icons/small';
                    $img_params['src'] = 'printer.png';
                }
                if ($action == 'rss') {
                    $img_params['modname'] = 'PostCalendar';
                    $img_params['src'] = 'feed.gif';
                }
                $display = smarty_function_img($img_params, $view);
                $class = 'postcalendar_nav_img';
                $title = $image_text;
            } else {
                $linkmap = array(
                    'today' => __('Today', $dom),
                    'day' => __('Day', $dom),
                    'week' => __('Week', $dom),
                    'month' => __('Month', $dom),
                    'year' => __('Year', $dom),
                    'list' => __('List', $dom),
                    'add' => __('Add', $dom),
                    'search' => __('Search', $dom),
                    'print' => __('Print', $dom),
                    'rss' => __('RSS', $dom));
                $display = $linkmap[$action];
                $class = ($viewtype == $action) ? 'postcalendar_nav_text_selected' : 'postcalendar_nav_text';
                $title = $labeltexts[$action];
            }
        } else {
            $classes = array($class);
            if (_SETTING_USE_POPUPS) {
                $classes[] = 'tooltips';
            }
            if ((_SETTING_OPEN_NEW_WINDOW) && ($action == "detail")) {
                $classes[] = 'event_details';
            }
            $class = implode(' ', $classes);
        }
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
