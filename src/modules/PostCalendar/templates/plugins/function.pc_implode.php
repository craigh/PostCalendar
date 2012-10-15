<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_implode($args, Zikula_View $view)
{
    $sep   = (!isset($args['seperator']) || empty($args['seperator'])) ? "," : $args['seperator'];
    $value = $args['value'];
    if (!is_array($value)) {
        $value = array(
            (string) $value);
    }
    $valueList = implode($sep, $value);

    if (isset($args['assign'])) {
        $view->assign($args['assign'], $valueList);
    } else {
        return $valueList;
    }
}
