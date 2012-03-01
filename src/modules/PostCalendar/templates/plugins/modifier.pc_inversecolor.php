<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_modifier_pc_inversecolor($color)
{
    if (empty($color)) {
        return;
    }
    return ModUtil::apiFunc('PostCalendar', 'event', 'color_inverse', $color);
}