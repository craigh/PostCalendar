<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * Multihook needle to display a PostCalendar date as a link
 * @param $args['nid'] needle id
 * @return link
 */
class PostCalendar_Needles_PostCalDate extends Zikula_AbstractHelper
{
    public function info()
    {
        $info = array(
            'module'        => 'PostCalendar', // module name
            'info'          => 'POSTCALDATE-{date-displaytype}', // possible needles
            'inspect'       => true,
            //'needle'        => array('http://', 'https://', 'ftp://', 'mailto://'),
            //'function'      => 'http',
            //'casesensitive' => false,
        );
        return $info;
    }
    
    public static function needle($args)
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
                $icon = "<img src='$moddir/images/smallcalicon.jpg' alt='" . __('cal icon', $dom) . "' title='" . __('PostCalendar Date', $dom) . "' /> ";
            }
            if (strpos($displaytype, 'L') !== false) {
                $uselink = true;
            }
            if (strpos($displaytype, 'D') !== false) {
                $link = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => 'day',
                    'date'     => $date));
                $format = ModUtil::getVar('PostCalendar', 'pcDateFormats');
                $linktext = DateUtil::strftime($format['strftime'], strtotime($date));
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
}