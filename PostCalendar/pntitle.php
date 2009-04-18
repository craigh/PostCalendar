<?php 
// File: $Id: pntitle.php,v 1.1 2003/11/04 16:50:36 tsmiatek Exp $
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
/**
 * PostNuke Title Hack
 * 
 * Show individual titles at your PostNuke pages. 
 *
 * @package      TitleHack
 * @version      $Id: pntitle.php,v 1.1 2003/11/04 16:50:36 tsmiatek Exp $
 * @author       Joerg Napp <jnapp@users.sourceforge.net>
 * @link         http://lottasophie.sourceforge.net Official Support Site
 * @copyright    Copyright (C) 2003 Joerg Napp
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */ 

 
/**
 * Return the title for the PostCalendar Module
 * 
 * This function returns the meaningful title for the current page
 * 
 * @author       bones
 * @version      $Revision: 1.1 $
 * @return       string   The title for the current page
 */
function PostCalendar_title()
{
    $title = pnConfigGetVar('sitename') .' - ' . _POSTCALENDAR . ' :: ';

    $eid      = FormUtil::getPassedValue('eid');
    $func     = FormUtil::getPassedValue('func', 'main');
    $type     = FormUtil::getPassedValue('type', 'user');
    $viewtype = FormUtil::getPassedValue('viewtype', 'month');

    if ($type == 'admin')
        return $title . _PC_ADMIN_GLOBAL_SETTINGS;

    switch($func)
    {
        case '':
        case 'main':
        case 'view':
            if ($viewtype == 'day')
                $title .= _CALDAYLINK;
            else
            if ($viewtype == 'week')
                $title .= _CALWEEKLINK;
            else
            if ($viewtype == 'month')
                $title .= _CALMONTHLINK;
            else
            if ($viewtype == 'year')
                $title .= _CALYEARLINK;
            else
            if ($viewtype == 'details')
            {
                if ($eid)
		{
                    $event = DBUtil::selectObjectByID ('postcalendar_events', $eid, 'eid');
		    if ($event)
                        $title .= _CALVIEWEVENT . " - $event[eventDate] $event[startTime] - $event[title]";
		}
            }
        break;
    }

    return $title;
} 

?>