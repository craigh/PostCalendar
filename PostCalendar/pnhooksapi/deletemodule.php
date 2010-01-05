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
 * deletemodule action on hook
 * this function is called when a hooked module is uninstalled
 *
 * @author  Craig Heydenburg
 * @return  boolean    true/false
 * @access  public
 */
function PostCalendar_hooksapi_deletemodule($args)
{
    if ((!isset($args['objectid'])) || ((int)$args['objectid'] <= 0)) return false;
	$module = isset($args['module']) ? strtolower($args['module']) : strtolower(pnModGetName()); // default to active module

    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    return true;
}