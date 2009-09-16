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
/*
 * @purpose     strip slashes from DB output
 */
function smarty_modifier_pc_cleanVar($string)
{
    //$string = DataUtil::cleanVar($string);
    $string = stripslashes($string);
    return $string;
}
