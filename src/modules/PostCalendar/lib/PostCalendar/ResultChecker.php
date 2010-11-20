<?php
/**
 * @package     PostCalendar
 * @author      Craig Heydenburg
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * @description Internal callback class used to check permissions to each item
 *              borrowed from the News module
 * @author      Jorn Wildt
 */
class Postcalendar_ResultChecker
{
    protected $enablecategorization;

    function __construct()
    {
        $this->enablecategorization = ModUtil::getVar('PostCalendar', 'enablecategorization');
    }

    // This method is called by DBUtil::selectObjectArrayFilter() for each and every search result.
    // A return value of true means "keep result" - false means "discard".
    function checkResult(&$item)
    {
        $ok = SecurityUtil::checkPermission('PostCalendar::Event', "$item[title]::$item[eid]", ACCESS_OVERVIEW);

        if ($this->enablecategorization)
        {
            ObjectUtil::expandObjectWithCategories($item, 'postcalendar_events', 'eid');
            $ok = $ok && CategoryUtil::hasCategoryAccess($item['__CATEGORIES__'],'PostCalendar');
        }

        return $ok;
    }
}