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
 *                               can be one or more of 'user', 'category', 'topic' (required)
 *                       'class' the classname(s) (optional, default no class)
 *                       'label' the label on the submit button (optional, default _PC_TPL_VIEW_SUBMIT)
 *                       'order' comma separated list of arguments to sort on (optional)
 * @param Smarty $smarty
 */
function smarty_function_pc_filter($args, &$smarty)
{
    if (empty($args['type'])) {
        $smarty->trigger_error("pc_filter: missing 'type' parameter");
        return;
    }
    $class = isset($args['class']) ? 'class="'.$args['class'].'"' : '';
    $label = isset($args['label']) ? $args['label'] : _PC_TPL_VIEW_SUBMIT;
    $order = isset($args['order']) ? $args['order'] : null;

    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');
    $Date      = FormUtil::getPassedValue('Date');
    $Date      = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));    

    if (!isset($y)) $y = substr($Date, 0, 4);
    if (!isset($m)) $m = substr($Date, 4, 2);
    if (!isset($d)) $d = substr($Date, 6, 2);

    $tplview = FormUtil::getPassedValue('tplview');
    $viewtype = FormUtil::getPassedValue('viewtype', _SETTING_DEFAULT_VIEW);
    $pc_username = FormUtil::getPassedValue('pc_username');
    $types = explode(',', $args['type']);

    //================================================================
    // build the username filter pulldown
    //================================================================
    if (in_array('user', $types)) {
        @define('_PC_FORM_USERNAME', true);
        $users = DBUtil::selectFieldArray('postcalendar_events', 'informant', null, null, true, 'aid'); 

        $useroptions = "<select name=\"pc_username\" $class>";
        $useroptions .= "<option value=\"\" $class>" . _PC_FILTER_USERS . "</option>";
        $selected = ($pc_username == '__PC_ALL__' ? 'selected="selected"' : '');
        $useroptions .= "<option value=\"__PC_ALL__\" $class $selected>" . _PC_FILTER_USERS_ALL . "</option>";
        foreach ($users as $k => $v) {
            $sel = ($pc_username == $v ? 'selected="selected"' : '');
            $useroptions .= "<option value=\"$v\" $sel $class>$v</option>";
        }
        $useroptions .= '</select>';
    }

    //================================================================
    // build the category filter pulldown
    //================================================================
    if (in_array('category', $types)) {
        @define('_PC_FORM_CATEGORY', true);
        $category = FormUtil::getPassedValue('pc_category');
        $categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
        $catoptions = "<select name=\"pc_category\" $class>";
        $catoptions .= "<option value=\"\" $class>" . _PC_FILTER_CATEGORY . "</option>";
        foreach ($categories as $c) {
            $sel = ($category == $c['catid'] ? 'selected="selected"' : '');
            $catoptions .= "<option value=\"$c[catid]\" $sel $class>$c[catname]</option>";
        }
        $catoptions .= '</select>';
    }

    //================================================================
    // build the topic filter pulldown
    //================================================================
    if (in_array('topic', $types) && _SETTING_DISPLAY_TOPICS) {
        @define('_PC_FORM_TOPIC', true);
        $topic = FormUtil::getPassedValue('pc_topic');
        $topics = pnModAPIFunc('PostCalendar', 'user', 'getTopics');
        $topoptions = "<select name=\"pc_topic\" $class>";
        $topoptions .= "<option value=\"\" $class>" . _PC_FILTER_TOPIC . "</option>";
        foreach ($topics as $t) {
            $sel = ($topic == $t['topicid'] ? 'selected="selected"' : '');
            $topoptions .= "<option value=\"$t[topicid]\" $sel $class>$t[topictext]</option>";
        }
        $topoptions .= '</select>';
    } else
        $topoptions = '';

    //================================================================
    // build it in the correct order
    //================================================================
    $submit = "<input type=\"submit\" name=\"submit\" value=\"$label\" $class />";
    $orderArray = array('user' => $useroptions, 'category' => $catoptions, 'topic' => $topoptions, 'jump' => $submit);

    if (!is_null($order)) {
        $newOrder = array();
        $order = explode(',', $order);
        foreach ($order as $tmp_order)
            array_push($newOrder, $orderArray[$tmp_order]);

        foreach ($orderArray as $key => $old_order)
            if (!in_array($old_order, $newOrder)) array_push($newOrder, $orderArray[$key]);

        $order = $newOrder;
    } else
        $order = $orderArray;

    foreach ($order as $element)
        echo $element;

    if (!in_array('user', $types)) echo "<input type='hidden' name='pc_username' value='$pc_username' />";
}
