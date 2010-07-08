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
function smarty_function_pc_form_nav_open($args, &$smarty)
{
    $formaction = ModUtil::url('PostCalendar', 'user', 'view');
    $formaction = DataUtil::formatForDisplay($formaction);
    $ret_val = '<form action="' . $formaction . '"' . ' method="post"' . ' enctype="application/x-www-form-urlencoded">';

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}
