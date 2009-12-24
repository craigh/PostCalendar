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

/**
 * Return an array of items to show in the your account panel
 *
 * @return   array   array of items, or false on failure
 */
function PostCalendar_accountapi_getall($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    $items = array();
    // show link for users only
    if (!pnUserLoggedIn()) {
        // not logged in
        return $items;
    }
    if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
        $items['1'] = array('url' => pnModURL('PostCalendar', 'event', 'new'),
                        'title' => __('Submit Event', $dom),
                        'icon' => 'admin.png');
    }

    // Return the items
    return $items;
}
