<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnaccountapi.php 24581 2008-09-02 19:47:46Z Guite $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Mark West
 * @package Zikula_System_Modules
 * @subpackage Users
 */

/**
 * Return an array of items to show in the your account panel
 *
 * @return   array   array of items, or false on failure
 */
function PostCalendar_accountapi_getall($args)
{
    // load user language file
    pnModLangLoad('PostCalendar', 'user');

    $items = array();

    // show link for users only
    if(!pnUserLoggedIn()) {
        // not logged in
        return $items;
    }
    if(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
    	$items[] = array('url' => pnModURL('PostCalendar', 'user', 'submit'),
                     'title' => _CALSUBMITEVENT,
                     'icon' => 'admin.gif');
		}

    // Return the items
    return $items;
}
