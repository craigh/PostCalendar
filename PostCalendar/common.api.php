<?php
/**
 * SVN: $Id$
 *
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Revision$
 *
 * PostCalendar::Zikula Events Calendar Module
 * Copyright (C) 2002  The PostCalendar Team
 * http://postcalendar.tv
 * Copyright (C) 2009  Sound Web Development
 * Craig Heydenburg
 * http://code.zikula.org/soundwebdevelopment/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * To read the license please read the docs/license.txt or visit
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

require_once 'modules/PostCalendar/global.php';

//=========================================================================
//  utility functions for postcalendar
//=========================================================================
function PostCalendarSmartySetup(&$smarty)
{
    $smarty->assign('USE_POPUPS', _SETTING_USE_POPUPS);
    $smarty->assign('USE_TOPICS', _SETTING_DISPLAY_TOPICS);
    $smarty->assign('USE_INT_DATES', _SETTING_USE_INT_DATES);
    $smarty->assign('OPEN_NEW_WINDOW', _SETTING_OPEN_NEW_WINDOW);
    $smarty->assign('EVENT_DATE_FORMAT', _SETTING_DATE_FORMAT);
    $smarty->assign('HIGHLIGHT_COLOR', _SETTING_DAY_HICOLOR);
    $smarty->assign('24HOUR_TIME', _SETTING_TIME_24HOUR);
    return true;
}

//=========================================================================
// some old security-related string cleaning functions
// can these be eliminated?
//=========================================================================
function pcVarPrepForDisplay($s)
{
    $s = nl2br(DataUtil::formatForDisplay(postcalendar_removeScriptTags($s)));
    $s = preg_replace('/&amp;(#)?([0-9a-z]+);/i', '&\\1\\2;', $s);
    return $s;
}

function pcVarPrepHTMLDisplay($s)
{
    return DataUtil::formatForDisplayHTML(postcalendar_removeScriptTags($s));
}

function postcalendar_makeValidURL($s)
{
    if (empty($s)) return '';

    if (!preg_match('|^http[s]?:\/\/|i', $s)) $s = 'http://' . $s;

    return $s;
}

function postcalendar_removeScriptTags($in)
{
    return preg_replace("/<script.*?>(.*?)<\/script>/", "", $in);
}

/**
 * pc_clean
 * @param s string text to clean
 * @return string cleaned up text
 */
function pc_clean($s)
{
    $display_type = substr($s, 0, 6);

    if ($display_type == ':text:') $s = substr($s, 6);
    elseif ($display_type == ':html:') $s = substr($s, 6);

    unset($display_type);
    $s = preg_replace('/[\r|\n]/i', '', $s);
    $s = str_replace("'", "\'", $s);
    $s = str_replace('"', '&quot;', $s);
    // ok, now we need to break really long lines
    // we only want to break at spaces to allow for
    // correct interpretation of special characters
    $tmp = explode(' ', $s);
    return join("'+' ", $tmp);
}
//=========================================================================
// end security-related string cleaning functions
//=========================================================================

function sort_byCategoryA($a, $b)
{
    if ($a['catname'] < $b['catname']) return -1;
    elseif ($a['catname'] > $b['catname']) return 1;
}
function sort_byCategoryD($a, $b)
{
    if ($a['catname'] < $b['catname']) return 1;
    elseif ($a['catname'] > $b['catname']) return -1;
}
function sort_byTitleA($a, $b)
{
    if ($a['title'] < $b['title']) return -1;
    elseif ($a['title'] > $b['title']) return 1;
}
function sort_byTitleD($a, $b)
{
    if ($a['title'] < $b['title']) return 1;
    elseif ($a['title'] > $b['title']) return -1;
}
function sort_byTimeA($a, $b)
{
    if ($a['startTime'] < $b['startTime']) return -1;
    elseif ($a['startTime'] > $b['startTime']) return 1;
}
function sort_byTimeD($a, $b)
{
    if ($a['startTime'] < $b['startTime']) return 1;
    elseif ($a['startTime'] > $b['startTime']) return -1;
}
