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
function smarty_function_pc_form_nav_close($args, &$smarty)
{
    $ret_val = "";

    if (!defined('_PC_FORM_DATE')) {
        //not sure these three lines are needed with call to getDate here
        $jumpday   = FormUtil::getPassedValue('jumpday');
        $jumpmonth = FormUtil::getPassedValue('jumpmonth');
        $jumpyear  = FormUtil::getPassedValue('jumpyear');
        $Date      = FormUtil::getPassedValue('Date');
        $Date      = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));
        $ret_val .= '<input type="hidden" name="Date" value="' . $Date . '" />';
    }
    if (!defined('_PC_FORM_VIEW_TYPE')) {
        $ret_val .= '<input type="hidden" name="viewtype" value="' . FormUtil::getPassedValue('viewtype') . '" />';
    }
    if (!defined('_PC_FORM_USERNAME')) {
        $ret_val .= '<input type="hidden" name="pc_username" value="' . FormUtil::getPassedValue('pc_username') . '" />';
    }
    if (!defined('_PC_FORM_CATEGORY')) {
        $ret_val .= '<input type="hidden" name="pc_category" value="' . FormUtil::getPassedValue('pc_category') . '" />';
    }

    $ret_val .= '</form>';

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}
