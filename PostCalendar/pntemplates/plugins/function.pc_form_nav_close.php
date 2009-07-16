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
function smarty_function_pc_form_nav_close()
{
    // if (_SETTING_OPEN_NEW_WINDOW || isset($args['print'])) $target = 'target="csCalendar"';
    // else $target = '';

    if (!defined('_PC_FORM_DATE')) {
        //not sure these three lines are needed with call to getDate here
        $jumpday   = FormUtil::getPassedValue('jumpday');
        $jumpmonth = FormUtil::getPassedValue('jumpmonth');
        $jumpyear  = FormUtil::getPassedValue('jumpyear');
        $Date      = FormUtil::getPassedValue('Date');
        $Date      = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));
        echo '<input type="hidden" name="Date" value="' . $Date . '" />';
    }
    if (!defined('_PC_FORM_VIEW_TYPE')) {
        echo '<input type="hidden" name="viewtype" value="' . FormUtil::getPassedValue('viewtype') . '" />';
    }
    if (!defined('_PC_FORM_TEMPLATE')) {
        echo '<input type="hidden" name="tplview" value="' . FormUtil::getPassedValue('tplview') . '" />';
    }
    if (!defined('_PC_FORM_USERNAME')) {
        echo '<input type="hidden" name="pc_username" value="' . FormUtil::getPassedValue('pc_username') . '" />';
    }
    if (!defined('_PC_FORM_CATEGORY')) {
        echo '<input type="hidden" name="pc_category" value="' . FormUtil::getPassedValue('pc_category') . '" />';
    }
    if (!defined('_PC_FORM_TOPIC')) {
        echo '<input type="hidden" name="pc_topic" value="' . FormUtil::getPassedValue('pc_topic') . '" />';
    }

    echo '</form>';
}
