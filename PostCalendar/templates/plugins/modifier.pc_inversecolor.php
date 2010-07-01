<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/pntemplates/plugins/modifier.pc_inversecolor.php $
 * @version     $Id: modifier.pc_inversecolor.php 639 2010-06-30 22:16:08Z craigh $
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_modifier_pc_inversecolor($color)
{
    if (empty($color)) {
        return;
   }
    return ModUtil::apiFunc('PostCalendar', 'event', 'color_inverse', $color);
}