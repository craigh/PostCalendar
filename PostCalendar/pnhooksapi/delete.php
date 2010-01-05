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
 * delete action on hook
 *
 * @author  Craig Heydenburg
 * @return  boolean    true/false
 * @access  public
 */
function PostCalendar_hooksapi_delete($args)
{
    if ((!isset($args['objectid'])) || ((int)$args['objectid'] <= 0)) return false;
	$module = isset($args['module']) ? strtolower($args['module']) : strtolower(pnModGetName()); // default to active module

    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    return true;
}