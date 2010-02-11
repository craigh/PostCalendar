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
 * check to see if relevent file is available in PostCalendar/pnhooksapi/ or another location
 *
 * @author  Craig Heydenburg
 * @param   module     module being hooked
 * @param   type       function type (optional) (default 'pcevent')
 * @return  boolean    location or false
 */
function postcalendar_hooksapi_funcisavail($args)
{
    if (!isset($args['module'])) return false;
    $module    = $args['module'];
    $modinfo   = pnModGetInfo(pnModGetIDFromName($module));
    $homearray = array($modinfo['directory'], 'PostCalendar'); // locations to search for the function
    $type      = isset($args['type']) ? $args['type'] : 'pcevent';

    $apidir = "pnhooksapi";
    $func   = "{$module}_{$type}.php";

    foreach ($homearray as $home) {
        $osdir   = DataUtil::formatForOS($home);
        $ostype  = DataUtil::formatForOS($apidir);
        $osfunc  = DataUtil::formatForOS($func);
        $mosfile = "modules/$osdir/$ostype/$osfunc"; // doesn't allow oldstyle 'file' format - must be in a dir
        if (file_exists($mosfile)) {
            return $home;
        }
    }
    return false;
}