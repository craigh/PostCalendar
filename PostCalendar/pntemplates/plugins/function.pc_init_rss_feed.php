<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @description add rss pagevar if pnRender doesn't mess up the template
 * @return      bool
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_init_rss_feed($args, &$smarty)
{
    if (!pnModGetVar('pnRender', 'expose_template')) {
        $rsslink = pnModURL('PostCalendar', 'user', 'view', array(
            'viewtype' => 'xml',
            'theme'    => 'rss'));
        $rsslink      = DataUtil::formatForDisplay($rsslink);
        $sitename     = pnConfigGetVar('sitename');
        $modinfo      = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
        $modname      = $modinfo['displayname'];
        $title        = DataUtil::formatForDisplay($sitename . " " . $modname);
        $pagevarvalue = "<link rel='alternate' href='$rsslink' type='application/rss+xml' title='$title' />";

        PageUtil::addVar("rawtext", $pagevarvalue);
        $ret_val = true;
    } else {
        $ret_val = false;
    }

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}
