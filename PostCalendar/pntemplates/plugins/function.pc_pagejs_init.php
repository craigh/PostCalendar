<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
/**
 * pc_pagejs_init: include the required javascript in header if needed
 *
 * @author Craig Heydenburg
 * @param  none
 */
function smarty_function_pc_pagejs_init($params, &$smarty)
{
    unset($params);
    if (_SETTING_USE_POPUPS) PageUtil::addVar("javascript", "modules/PostCalendar/pnjavascript/postcalendar_overlibconfig.js");
    if (_SETTING_OPEN_NEW_WINDOW && !_SETTING_USE_POPUPS) PageUtil::addVar("javascript", "modules/PostCalendar/pnjavascript/postcalendar_jspopup.js");
    return;
}
