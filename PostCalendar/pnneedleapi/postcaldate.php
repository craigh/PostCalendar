<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * pcdate needle
 * @param $args['nid'] needle id
 * @return link
 */
function postcalendar_needleapi_postcaldate($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // simple replacement, no need to cache anything
    if (isset($args['nid']) && !empty($args['nid'])) {
        if (substr($args['nid'], 0, 1) != '-') {
            $args['nid'] = '-' . $args['nid'];
        }
        list ($dispose, $date, $displaytype) = explode('-', $args['nid']);
        //validate date format
        if ((empty($date)) || (strlen($date) != 8)) {
            $date = date("Ymd");
        }
        $displaytype = $displaytype ? strtoupper($displaytype) : 'DIL'; // in any order: D (date) I (icon) L (uselink) - default: DIL

        $icon = '';
        $link = '';
        $uselink = false;
        $moddir = ModUtil::getBaseDir($modname = 'PostCalendar');
        if (strpos($displaytype, 'I') !== false) {
            $icon = "<img src='$moddir/pnimages/smallcalicon.jpg' alt='" . __('cal icon', $dom) . "' title='" . __('PostCalendar Date', $dom) . "' /> ";
        }
        if (strpos($displaytype, 'L') !== false) {
            $uselink = true;
        }
        if (strpos($displaytype, 'D') !== false) {
            $link = ModUtil::url('PostCalendar', 'user', 'view', array(
                'viewtype' => 'day',
                'Date'     => $date));
            $linktext = DateUtil::strftime(ModUtil::getVar('PostCalendar', 'pcEventDateFormat'), strtotime($date));
        }

        $linktext = DataUtil::formatForDisplay($linktext);
        if ($uselink) {
            $link   = DataUtil::formatForDisplay($link);
            $result = "$icon<a href='$link'>$linktext</a>";
        } else {
            $result = $icon . $linktext;
        }
    } else {
        $result = __('No needle ID', $dom);
    }
    return $result;
}