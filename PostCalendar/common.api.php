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
//=========================================================================
// end security-related string cleaning functions
//=========================================================================
