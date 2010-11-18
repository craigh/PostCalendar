<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/pntemplates/plugins/function.pc_init_rss_feed.php $
 * @version     $Id: function.pc_init_rss_feed.php 639 2010-06-30 22:16:08Z craigh $
 * @description add rss pagevar if pnRender doesn't mess up the template
 * @return      bool
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_init_rss_feed($args, &$smarty)
{
    if (!ModUtil::getVar('pnRender', 'expose_template')) {
        $rsslink = ModUtil::url('PostCalendar', 'user', 'main', array(
            'viewtype' => 'xml',
            'theme'    => 'rss'));
        $rsslink      = DataUtil::formatForDisplay($rsslink);
        $sitename     = System::getVar('sitename');
        $modinfo      = ModUtil::getInfo(ModUtil::getIdFromName('PostCalendar'));
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
