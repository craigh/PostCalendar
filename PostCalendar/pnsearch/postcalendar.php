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

$search_modules[] = array('title' => 'PostCalendar', 'func_search' => 'search_postcalendar', 'func_opt' => 'search_postcalendar_opt');

function search_postcalendar_opt()
{
    if (!pnModAvailable('PostCalendar') || !pnModAPILoad('PostCalendar', 'user')) { //may not need the modload line
        return '';
    }

    $title = _SEARCH_POSTCALENDAR;
    $categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
    $cat_options = '';
    foreach ($categories as $category) {
        $cat_options .= "<option value=\"$category[catid]\">$category[catname]</option>";
    }
    unset($categories);

    if (_SETTING_DISPLAY_TOPICS) {
        $topics = pnModAPIFunc('PostCalendar', 'user', 'getTopics');
        $top_options = '<select name="pc_topic"><option value="">' . _SRCHALLTOPICS . '</option>';
        foreach ($topics as $topic) {
            $top_options .= "<option value=\"$topic[id]\">$topic[text]</option>";
        }
        $top_options .= '</select>';
        unset($topics);
    }

    if (pnSecAuthAction(0, 'PostCalendar::', '.*', ACCESS_OVERVIEW)) {
        $_SRCHALLCATEGORIES = _SRCHALLCATEGORIES;
        $output = <<<EOF
<table border="0" width="100%">
    <tr>
        <td>
            <input type="checkbox" name="active_postcalendar" id="active_postcalendar" value="1" checked>
            <label for="active_postcalendar">$title</label>
            <select name="pc_category">
                <option value="">$_SRCHALLCATEGORIES</option>
                $cat_options
            </select>
            $top_options
        </td>
    </tr>
</table>
EOF;
    }

    return $output;
}

/**
 * search events
 */
function search_postcalendar()
{
    $active = FormUtil::getPassedValue('active_postcalendar');
    if (!isset($active)) return false;

    if (!pnModAvailable('PostCalendar') || !pnModAPILoad('PostCalendar', 'user')) return '';

    if (!(bool) PC_ACCESS_OVERVIEW) return '';

    //$tpl = new pnRender();
    $tpl = pnRender::getInstance('PostCalendar');
    pnModAPIFunc('PostCalendar','user','SmartySetup', $tpl);
    /* Trim as needed */
    $func = FormUtil::getPassedValue('func');
    $template_view = FormUtil::getPassedValue('tplview');
    if (!$template_view) $template_view = 'month';
    $tpl->assign('FUNCTION', $func);
    $tpl->assign('TPL_VIEW', $template_view);
    /* end */

    $k = FormUtil::getPassedValue('q');
    $k_andor = FormUtil::getPassedValue('bool');
    $pc_category = FormUtil::getPassedValue('pc_category');
    $pc_topic = FormUtil::getPassedValue('pc_topic');

    //=================================================================
    //  Perform the search if we have data
    //=================================================================
    $sqlKeywords = '';
    $keywords = explode(' ', $k);
    //$k_andor = ($k_andor ? ' AND ' : ' OR ');
    $k_andor = ' AND ';

    // build our search query
    foreach ($keywords as $word) {
        $word = pnVarPrepForStore($word);

        if (!empty($sqlKeywords)) $sqlKeywords .= " $k_andor ";

        $sqlKeywords .= '(';
        $sqlKeywords .= "pc_title LIKE '%$word%' OR ";
        $sqlKeywords .= "pc_hometext LIKE '%$word%' ";
        //$sqlKeywords .= "OR pc_location LIKE '%$word%'";
        $sqlKeywords .= ') ';
    }

    if (!empty($pc_category)) {
        $s_category = "tbl.pc_catid = '$pc_category'";
    }

    if (!empty($pc_topic)) {
        $s_topic = "pc_topic = '$pc_topic'";
    }

    $searchargs = array();
    if (!empty($sqlKeywords)) $searchargs['s_keywords'] = $sqlKeywords;
    if (!empty($s_category)) $searchargs['s_category'] = $s_category;
    if (!empty($s_topic)) $searchargs['s_topic'] = $s_topic;

    $eventsByDate = pnModAPIFunc('PostCalendar','event','getEvents',$searchargs);
    $tpl->assign_by_ref('A_EVENTS', $eventsByDate);
    $tpl->caching = false;

    return $tpl->fetch('search/postcalendar_search_plugin.html');
}
