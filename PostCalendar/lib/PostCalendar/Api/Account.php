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
class PostCalendar_Api_Account extends Zikula_Api
{
    /**
     * Return an array of items to show in the your account panel
     *
     * @return   array   array of items, or false on failure
     */
    public function getall($args)
    {
        $items = array();
        // show link for users only
        if (!UserUtil::isLoggedIn()) {
            // not logged in
            return $items;
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            $items['1'] = array(
                'url' => ModUtil::url('PostCalendar', 'event', 'new'),
                'title' => $this->__('Submit Event'),
                'icon' => 'admin.png');
        }
    
        // Return the items
        return $items;
    }
} // end class def