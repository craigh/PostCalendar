<?php

/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * PostCalendar filter
 *
 * @param array  $args   array with arguments. Used values:
 *                       'type'  comma separated list of filter types;
 *                               can be one or both of 'user' or 'category' (optional, default 'user,category')
 *                       'class' the classname(s) (optional, default no class)
 *                       'label' the label on the submit button (optional, default _PC_TPL_VIEW_SUBMIT)
 *                       'order' comma separated list of arguments to sort on (optional, default user,category,jump)
 *                       'userfilter' userid to filter resultset. default configured in plugin
 *                       'selectedCategories' the categories that were slected to filter by
 * @param Smarty $smarty
 */

use Zikula\PostCalendarModule\Entity\Repository\CalendarEventRepository;

function smarty_function_pc_filter($args, Zikula_View $view)
{
    $dom = ZLanguage::getModuleDomain('ZikulaPostCalendarModule');
    $modVars = $view->get_template_vars('modvars');

    $type = isset($args['type']) ? $args['type'] : "user,category";
    $types = explode(',', $type);
    $class = (isset($args['class']) && !empty($args['class'])) ? ' class="' . $args['class'] . '"' : '';
    $label = isset($args['label']) ? $args['label'] : __('change', $dom);
    $order = isset($args['order']) ? $args['order'] : "user,category,jump";

    //================================================================
    // build the username filter pulldown
    //================================================================
    if ($modVars['ZikulaPostCalendarModule']['pcAllowUserCalendar']) {
        $filterdefault = CalendarEventRepository::FILTER_ALL;
    } else {
        $filterdefault = CalendarEventRepository::FILTER_GLOBAL;
    }
    $userFilter = isset($args['userfilter']) && !empty($args['userFilter']) ? $args['userfilter'] : $filterdefault;
    if (!UserUtil::isLoggedIn()) {
        $userFilter = PostCalendar_Entity_Repository_CalendarEventRepository::FILTER_GLOBAL;
    }
    define('IS_ADMIN', SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN));
    $allowedgroup = $modVars['ZikulaPostCalendarModule']['pcAllowUserCalendar'];
    $uid = UserUtil::getVar('uid');
    $uid = empty($uid) ? 1 : $uid;
    $ingroup = $allowedgroup > 0 ? ModUtil::apiFunc('Groups', 'user', 'isgroupmember', array(
                'uid' => $uid,
                'gid' => $allowedgroup)) : false;
    $useroptions = "";

    if ($ingroup || ($allowedgroup && IS_ADMIN)) {
        if (in_array('user', $types)) {
            //define array of filter options
            $filteroptions = array(
                PostCalendar_Entity_Repository_CalendarEventRepository::FILTER_GLOBAL => __('Global Events', $dom) . " " . __('Only', $dom),
                PostCalendar_Entity_Repository_CalendarEventRepository::FILTER_PRIVATE => __('My Events', $dom) . " " . __('Only', $dom),
                PostCalendar_Entity_Repository_CalendarEventRepository::FILTER_ALL => __('Global Events', $dom) . " + " . __('My Events', $dom));
            // if user is admin, add list of users in allowed group
            if (IS_ADMIN) {
                $group = ModUtil::apiFunc('Groups', 'user', 'get', array(
                            'gid' => $allowedgroup));
                $users = array();
                foreach ($group['members'] as $uid => $uarray) {
                    $users[$uid] = UserUtil::getVar('uname', $uid);
                }
                $filteroptions = $filteroptions + $users;
            }
            // generate html for selectbox - should move this to the template...
            $useroptions = "<select name='userfilter' $class>";
            foreach ($filteroptions as $k => $v) {
                $sel = ($userFilter == $k) ? ' selected="selected"' : '';
                $useroptions .= "<option value='$k'$sel$class>$v</option>";
            }
            $useroptions .= '</select>';
        }
    } else {
        // remove user from types array to force hidden input display below
        $key = array_search('user', $types);
        unset($types[$key]);
    }
    //================================================================
    // build the category filter pulldown
    //================================================================
    if (in_array('category', $types) && $modVars['ZikulaPostCalendarModule']['pcAllowCatFilter']) {
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('ZikulaPostCalendarModule', 'CalendarEvent');
        $view->assign('selectedcategories', $args['selectedCategories']);
        $view->assign('catregistry', $catregistry);
        $catoptions = $view->fetch('event/filtercats.tpl', 1); // force one cachefile
    } else {
        $catoptions = '';
        $key = array_search('category', $types);
        unset($types[$key]);
    }

    $ret_val = "";
    if (!empty($types)) {
        //================================================================
        // build it in the correct order
        //================================================================
        $submit = "<input type='submit' name='pc_submit' value='$label' $class />";
        $orderArray = array(
            'user' => $useroptions,
            'category' => $catoptions,
            'jump' => $submit);

        if (!is_null($order)) {
            $newOrder = array();
            $order = explode(',', $order);
            foreach ($order as $tmp_order) {
                array_push($newOrder, $orderArray[$tmp_order]);
            }
            foreach ($orderArray as $key => $old_order) {
                if (!in_array($old_order, $newOrder)) {
                    array_push($newOrder, $orderArray[$key]);
                }
            }

            $order = $newOrder;
        } else {
            $order = $orderArray;
        }

        foreach ($order as $element) {
            $ret_val .= $element;
        }
    }

    if (!in_array('user', $types)) {
        $ret_val .= "<input type='hidden' name='userfilter' value='$userFilter' />";
    }

    if (isset($args['assign'])) {
        $view->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}
