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
 * v4b_calendar_build_input_box: generate the html to display a calendar input box
 *
 * @author	Robert Gasch
 * @version     $Id$
 * @param	assign		The smarty variable to assign the resulting menu HTML to
 * @param	dateFormat	The JS Calendar date format to use
 * @param	defaultString   The default string to show for a date which hasn't been entered yet
 * @param	defaultDate     The default date submitted by the form (for a date which hasn't been entered yet)
 * @param	htmlName	The field name of the date field
 * @param	objectName	The object name of the field of the date field (final field name = "$objectName[$htmlName]")
 *
 */
function smarty_function_pc_calendar_build_input_box($params, &$smarty)
{
    $assign = (isset($params['assign']) ? $params['assign'] : 0);
    $dateFormat = $params['dateFormat'];
    $defaultString = $params['defaultString'];
    $defaultDate = $params['defaultDate'];
    $htmlName = $params['htmlName'];
    $objectName = (isset($params['objectName']) ? $params['objectName'] : '');

    if (!$defaultString) $defaultString = '';

    if (!$defaultDate) $defaultDate = '';

    $html = '';

    if (!$htmlName) exit('v4b_calendar_build_input_box: Missing htmlName ...');

    if (!$dateFormat) exit('v4b_calendar_build_input_box: Missing dateFormat...');

    $fieldKey = $htmlName;
    if ($objectName) $fieldKey = $objectName . '[' . $htmlName . ']';

    $triggerName = 'trigger_' . $htmlName;
    $displayName = 'display_' . $htmlName;

    $html .= '<span id="' . $displayName . '">' . $defaultString . '</span>';
    $html .= '&nbsp;';
    $html .= '<input type="hidden" name="' . $fieldKey . '" id="' . $htmlName . '" value="' . $defaultDate . '">';
    $html .= '<img src="javascript/jscalendar/img.gif" id="' . $triggerName . '" style="cursor: pointer; border: 0px solid blue;" title="Date selector" onmouseover="this.style.background=\'blue\';" onmouseout="this.style.background=\'\'" />';

    $html .= '<script type="text/javascript"> Calendar.setup({';
    $html .= 'ifFormat    : "%Y-%m-%d %H:%M:00",'; // universal format, don't change this!
    $html .= 'inputField  : "' . $htmlName . '",';
    $html .= 'displayArea : "' . $displayName . '",';
    $html .= 'daFormat    : "' . $dateFormat . '",';
    $html .= 'button      : "' . $triggerName . '",';
    $html .= 'align       : "Tl",';

    if ($defaultDate) {
        $d = strtotime($defaultDate);
        $d = date('Y/m/d', $d);
        $html .= 'date : "' . $d . '",';
    }

    $html .= 'singleClick : true }); </script>';

    if ($assign) $smarty->assign($assign, $html);
    else return $html;
}

