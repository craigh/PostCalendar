<?php
/**
 *	SVN: $Id$
 *
 *  @package     PostCalendar
 *  @author      $Author$
 *  @link	     $HeadURL$
 *  @version     $Revision$
 *
 *  PostCalendar::Zikula Events Calendar Module
 *  Copyright (C) 2002  The PostCalendar Team
 *  http://postcalendar.tv
 *  Copyright (C) 2009  Sound Web Development
 *  Craig Heydenburg
 *  http://code.zikula.org/soundwebdevelopment/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *  To read the license please read the docs/license.txt or visit
 *  http://www.gnu.org/copyleft/gpl.html
 *
 */
/*
 *  ----------------------------------------------------------------------
 *  Original Author of  Robert Gasch
 *  Author Contact: r.gasch@chello.nl, robert.gasch@value4business.com
 *  Purpose of file: generate the html to display a calendar input box
 *  Copyright: Value4Business GmbH
 *  ----------------------------------------------------------------------
 */

/**
 * v4b_calendar_init: include the required files so the calendar can be used
 *
 * @author	Robert Gasch
 * @version     $Id$
 * @param	assign		The smarty variable to assign the resulting menu HTML to
 *
 */
function smarty_function_pc_calendar_init($params, &$smarty)
{
    extract($params);
    unset($params);

    $currentLang = pnSessionGetVar('lang');
    $calLang = 'en';
    $langFile = 'javascript/jscalendar/lang/calendar-en.js';

    if ($currentLang == 'deu') $calLang = 'de';

    if ($calLang != 'en') {
        $lF = 'javascript/jscalendar/lang/calendar-' . $calLang . '.js';
        if (file_exists($lF)) $langFile = $lF;
    }

    PageUtil::addVar("stylesheet", "javascript/jscalendar/calendar-win2k-cold-1.css");
    PageUtil::addVar("javascript", "javascript/jscalendar/calendar.js");
    PageUtil::addVar("javascript", $langFile);
    PageUtil::addVar("javascript", "javascript/jscalendar/calendar-setup.js");

    $init = true;

    if (isset($assign)) $smarty->assign($assign, $init);
    else return;
}
