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
    $class = !empty($args['class']) ? ' class="'.$args['class'].'"' : '';
    $label = isset($args['label']) ? $args['label'] : __('change', $dom);
    $order = isset($args['order']) ? $args['order'] : null;

    $jumpday   = FormUtil::getPassedValue('jumpDay');
    $jumpmonth = FormUtil::getPassedValue('jumpMonth');
    $jumpyear  = FormUtil::getPassedValue('jumpYear');
    $Date      = FormUtil::getPassedValue('Date');
    $jumpargs  = array('Date'=>$Date,'jumpday'=>$jumpday,'jumpmonth'=>$jumpmonth,'jumpyear'=>$jumpyear);
    $Date      = pnModAPIFunc('PostCalendar','user','getDate',$jumpargs);    

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
    define ('IS_ADMIN', SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN));
    $allowedgroup = pnModGetVar('PostCalendar', 'pcAllowUserCalendar');
    $uid = pnUserGetVar('uid'); $uid = empty($uid) ? 1 : $uid;
    $ingroup = $allowedgroup > 0 ? pnModAPIFunc('Groups','user','isgroupmember',array('uid'=>$uid, 'gid'=>$allowedgroup)) : false;

    if ($ingroup || ($allowedgroup && IS_ADMIN)) {
        if (in_array('user', $types)) {
            //define array of filter options
            $filteroptions = array(
                _PC_FILTER_GLOBAL  => __('Global Events', $dom) ." ". __('Only', $dom),
                _PC_FILTER_PRIVATE => __('My Events', $dom) ." ". __('Only', $dom),
                _PC_FILTER_ALL     => __('Global Events', $dom) ." + ". __('My Events', $dom),
            );
            // if user is admin, add list of users with private events
            if (IS_ADMIN) {
                $joinInfo = array(array('join_table'         => 'users',
                                        'join_field'         => 'uname',
                                        'object_field_name'  => 'username',
                                        'compare_field_table'=> 'aid',
                                        'compare_field_join' => 'uid'));
                $users = DBUtil::selectExpandedFieldArray('postcalendar_events', $joinInfo, 'aid', null, null, true, 'aid');
                $users = array_flip($users); // returned results are backward... 
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

        $smarty->assign('enablecategorization', pnModGetVar('PostCalendar', 'enablecategorization'));
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
        $submit = "<input type='submit' name='submit' value='$label' $class />";
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
    }

    if (!in_array('user', $types)) $ret_val .= "<input type='hidden' name='pc_username' value='$pc_username' />";

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}
