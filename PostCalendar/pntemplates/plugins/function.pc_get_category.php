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
function smarty_function_pc_get_category($args, &$smarty)
{
    $category = FormUtil::getPassedValue('pc_category');
    $categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
    $catoptions = "<select name=\"pc_category\">";
    $catoptions .= "<option value=\"\">" . _PC_FILTER_CATEGORY . "</option>";
    $name = '';
    foreach ($categories as $c) {
        if ($category == $c['catid']) {
            $name = $c['catname'];
        }
    }
    //echo urlencode ($name);
    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], urlencode($name));
    } else {
        return urlencode($name);
    }
}
