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
 * create action on hook
 *
 * @author  Craig Heydenburg
 * @return  boolean    true/false
 * @access  public
 */
function PostCalendar_hooksapi_create($args)
{
    if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
        return false;
    }
    $module = isset($args['module']) ? strtolower($args['module']) : strtolower(pnModGetName()); // default to active module

    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    if (!_pc_funcisavail($module)) {
        return false;
    }
    $event = pnModAPIFunc('PostCalendar', 'hooks', 'create_' . $module, array(
        'objectid' => $args['objectid']));

    // add correct category information to new event

    // write event to postcal table

    return true;
}

/**
 * check to see if relevent file is available in PostCalendar/pnhooksapi/
 *
 * @author  Craig Heydenburg
 * @return  boolean    true/false
 * @access  private
 */
function _pc_funcisavail($module)
{
    $osdir   = DataUtil::formatForOS('PostCalendar');
    $ostype  = DataUtil::formatForOS('hooks');
    $osfunc  = DataUtil::formatForOS($module);
    $mosfile = "modules/$osdir/pn{$ostype}api/create_{$osfunc}.php";
    if (file_exists($mosfile)) {
        return true;
    } else {
        return false;
    }
}