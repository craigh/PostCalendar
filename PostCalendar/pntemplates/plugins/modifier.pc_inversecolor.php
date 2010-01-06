<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_modifier_pc_inversecolor($color)
{
    if (empty($color)) {
        return;
    }
    return pnModAPIFunc('PostCalendar', 'event', 'color_inverse', $color);
}