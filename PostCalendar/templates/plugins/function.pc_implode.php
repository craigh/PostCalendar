<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/pntemplates/plugins/function.pc_form_nav_open.php $
 * @version     $Id: function.pc_form_nav_open.php 639 2010-06-30 22:16:08Z craigh $
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_implode($args, &$smarty)
{
    $sep   = (!isset($args['seperator']) || empty($args['seperator'])) ? "," : $args['seperator'];
    $value = $args['value'];
    if (!is_array($value)) {
        $value = array(
            (string) $value);
    }
    $valueList = implode($sep, $value);

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $valueList);
    } else {
        return $valueList;
    }
}
