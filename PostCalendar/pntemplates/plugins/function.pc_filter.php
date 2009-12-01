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

/**
 * PostCalendar filter
 *
 * @param array  $args   array with arguments. Used values:
 *                       'type'  comma separated list of filter types;
 *                               can be one or both of 'user' or 'category' (required)
 *                       'class' the classname(s) (optional, default no class)
 *                       'label' the label on the submit button (optional, default _PC_TPL_VIEW_SUBMIT)
 *                       'order' comma separated list of arguments to sort on (optional)
 * @param Smarty $smarty
 */
function smarty_function_pc_filter($args, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (empty($args['type'])) {
        $smarty->trigger_error(__("%s: missing '%s' parameter", array('Plugin:pc_filter', 'type'), $dom));
        return;
    }
    $class = isset($args['class']) ? ' class="'.$args['class'].'"' : '';
    $label = isset($args['label']) ? $args['label'] : __('change', $dom);
    $order = isset($args['order']) ? $args['order'] : null;

    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');
    $Date      = FormUtil::getPassedValue('Date');
    $Date      = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));    

    if (!isset($y)) $y = substr($Date, 0, 4);
    if (!isset($m)) $m = substr($Date, 4, 2);
    if (!isset($d)) $d = substr($Date, 6, 2);

    $viewtype = FormUtil::getPassedValue('viewtype', _SETTING_DEFAULT_VIEW);
    if (pnModGetVar('PostCalendar', 'pcAllowUserCalendar')) { $filterdefault = _PC_FILTER_ALL; } else { $filterdefault = _PC_FILTER_GLOBAL; }
    $pc_username = FormUtil::getPassedValue('pc_username', $filterdefault);
    if (!pnUserLoggedIn()) $pc_username = _PC_FILTER_GLOBAL;
    $types = explode(',', $args['type']);

    //================================================================
    // build the username filter pulldown
    //================================================================
    if ((pnModGetVar('PostCalendar', 'pcAllowUserCalendar')) AND (pnUserLoggedIn())) { // do not show if users not allowed personal calendar or not logged in
        if (in_array('user', $types)) {
            //define array of filter options
            $filteroptions = array(
                _PC_FILTER_GLOBAL  => __('Global Events', $dom) ." ". __('Only', $dom),
                _PC_FILTER_PRIVATE => __('My Events', $dom) ." ". __('Only', $dom),
                _PC_FILTER_ALL     => __('Global Events', $dom) ." + ". __('My Events', $dom),
            );
            // if user is admin, add list of users with private events
            if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
                //compile users that have submitted calendar events based on informant or aid... this is a hack.
                // in the future will use one or the other...
                //$users_by_informant = DBUtil::selectFieldArray('postcalendar_events', 'informant', null, null, true);
                //foreach ($users_by_informant as $k=>$v) {
                //    if (pnUserGetIDFromName($v)) $users[pnUserGetIDFromName($v)] = $v;
                //}
                $users_by_aid = DBUtil::selectFieldArray('postcalendar_events', 'aid', null, null, true);
                foreach ($users_by_aid as $k=>$v) {
                    if (pnUserGetVar('uname', $v)) $users[$v] = pnUserGetVar('uname', $v);
                }
                    // if informant is converted to userid, then this area should be checked.
                $filteroptions = $filteroptions + $users;
            }
            // generate html for selectbox - should move this to the template...
            $useroptions = "<select name='pc_username' $class>";
            foreach ($filteroptions as $k => $v) {
                $sel = ($pc_username == $k ? ' selected="selected"' : '');
                $useroptions .= "<option value='$k'$sel$class>$v</option>";
            }
            $useroptions .= '</select>';
        }
    } else {
        // remove user from types array to force hidden input display below
        $key = array_search('user',$types);
        unset($types[$key]);
    }
    //================================================================
    // build the category filter pulldown
    //================================================================
    if (in_array('category', $types) && _SETTING_ALLOW_CAT_FILTER && _SETTING_ENABLECATS) {
        // load the category registry util
        if (!Loader::loadClass('CategoryRegistryUtil')) {
            pn_exit (__f('Error! Unable to load class [%s]', 'CategoryRegistryUtil'));
        }
        $catregistry  = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');

        $smarty->assign('enablecategorization', $modvars['enablecategorization']);
        $smarty->assign('catregistry', $catregistry);

        $catoptions = $smarty->fetch('event/postcalendar_event_filtercats.htm');

    } else {
        $catoptions = '';
        $key = array_search('category',$types);
        unset($types[$key]);
    }

    if (!empty($types)) {
        //================================================================
        // build it in the correct order
        //================================================================
        $submit = "<input type=\"submit\" name=\"submit\" value=\"$label\" $class />";
        $orderArray = array('user' => $useroptions, 'category' => $catoptions, 'jump' => $submit);
    
        if (!is_null($order)) {
            $newOrder = array();
            $order = explode(',', $order);
            foreach ($order as $tmp_order) {
                array_push($newOrder, $orderArray[$tmp_order]);
            }
            foreach ($orderArray as $key => $old_order) {
                if (!in_array($old_order, $newOrder)) array_push($newOrder, $orderArray[$key]);
            }

            $order = $newOrder;
        } else {
            $order = $orderArray;
        }

        $ret_val = "";
        foreach ($order as $element) {
            $ret_val .= $element;
        }
        $ret_val .= "<br />";
    }

    if (!in_array('user', $types)) $ret_val .= "<input type='hidden' name='pc_username' value='$pc_username' />";

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}
