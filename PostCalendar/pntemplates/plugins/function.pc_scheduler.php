<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @description update hidden events if hooked by news
 * @return      null
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_scheduler($args, &$smarty)
{
    if (!pnModIsHooked('postcalendar', 'news')) {
        return;
    }
    pnModAPIFunc('PostCalendar', 'hooks', 'scheduler');
    return;
}