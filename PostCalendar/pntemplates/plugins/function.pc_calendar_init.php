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
/*
 * ----------------------------------------------------------------------
 * Original Author of  Robert Gasch
 * Author Contact: r.gasch@chello.nl, robert.gasch@value4business.com
 * Purpose of file: generate the html to display a calendar input box
 * Copyright: Value4Business GmbH
 * ----------------------------------------------------------------------
 */

/**
 * v4b_calendar_init: include the required files so the calendar can be used
 *
 * @author    Robert Gasch
 * @version     $Id$
 * @param    assign        The smarty variable to assign the resulting menu HTML to
 *
 */
function smarty_function_pc_calendar_init($params, &$smarty)
{
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

    if (isset($params['assign'])) $smarty->assign($params['assign'], $init);
    else return;
}
