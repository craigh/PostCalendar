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
function smarty_function_pc_form_nav_open()
{
    $viewtype = strtolower(FormUtil::getPassedValue('viewtype'));

    if (_SETTING_OPEN_NEW_WINDOW && $viewtype == 'details') $target = 'target="csCalendar"';
    else $target = '';
    $formaction = pnModURL('PostCalendar', 'user', 'view');
    $formaction = DataUtil::formatForDisplay($formaction);
    $fstart = '<form action="' . $formaction . '"' . ' method="post"' . ' enctype="application/x-www-form-urlencoded" ' . $target . '>';

    echo $fstart;
}
