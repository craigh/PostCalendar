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

include_once 'modules/PostCalendar/pnincludes/DateCalc.class.php';

/**
 * initialise block
 */
function postcalendar_pasteventsblock_init()
{
    SecurityUtil::registerPermissionSchema('PostCalendar:pasteventsblock:', 'Block title::');
}

/**
 * get information on block
 */
function postcalendar_pasteventsblock_info()
{
    return array(
        'text_type'      => 'PostCalendar',
        'module'         => 'PostCalendar',
        'text_type_long' => 'Past Events Block',
        'allow_multiple' => true,
        'form_content'   => false,
        'form_refresh'   => false,
        'show_preview'   => true);
}

/**
 * display block
 */
function postcalendar_pasteventsblock_display($blockinfo)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!SecurityUtil::checkPermission('PostCalendar:pasteventsblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
        return;
    }
    if (!ModUtil::available('PostCalendar')) {
        return;
    }

    // today's date
    $Date = DateUtil::getDatetime('', '%Y%m%d%H%M%S');

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    $pcbeventsrange = (int) $vars['pcbeventsrange'];
    $pcbfiltercats  = $vars['pcbfiltercats'];

    // setup the info to build this
    $the_year  = (int) substr($Date, 0, 4);
    $the_month = (int) substr($Date, 4, 2);
    $the_day   = (int) substr($Date, 6, 2);

    $tpl = pnRender::getInstance('PostCalendar');

    // If block is cached, return cached version
    $tpl->cache_id = $blockinfo['bid'] . ':' . UserUtil::getVar('uid');
    if ($tpl->is_cached('blocks/postcalendar_block_pastevents.htm')) {
        $blockinfo['content'] = $tpl->fetch('blocks/postcalendar_block_pastevents.htm');
        return pnBlockThemeBlock($blockinfo);
    }

    if ($pcbeventsrange == 0) {
        $starting_date = '1/1/1970';
    } else {
        $starting_date = date('m/d/Y', mktime(0, 0, 0, $the_month - $pcbeventsrange, $the_day, $the_year));
    }
    $ending_date   = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_day - 1, $the_year)); // yesterday

    $filtercats['__CATEGORIES__'] = $pcbfiltercats; //reformat array
    $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
        'start'      => $starting_date,
        'end'        => $ending_date,
        'filtercats' => $filtercats,
        'sort'       => 'DESC'));

    $tpl->assign('A_EVENTS',   $eventsByDate);
    $tpl->assign('DATE',       $Date);

    $blockinfo['content'] = $tpl->fetch('blocks/postcalendar_block_pastevents.htm');

    return pnBlockThemeBlock($blockinfo);
}

/**
 * modify block settings ..
 */
function postcalendar_pasteventsblock_modify($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    // Defaults
    if (empty($vars['pcbeventsrange'])) $vars['pcbeventsrange'] = 6;
    if (empty($vars['pcbfiltercats']))  $vars['pcbfiltercats']  = array();

    $pnRender = pnRender::getInstance('PostCalendar', false); // no caching

    // load the category registry util
    $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
    $pnRender->assign('catregistry', $catregistry);

    $props = array_keys($catregistry);
    $pnRender->assign('firstprop', $props[0]);

    $pnRender->assign('vars', $vars);

    return $pnRender->fetch('blocks/postcalendar_block_pastevents_modify.htm');
}

/**
 * update block settings
 */
function postcalendar_pasteventsblock_update($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // overwrite with new values
    $vars['pcbeventsrange'] = FormUtil::getPassedValue('pcbeventsrange', 6);
    $vars['pcbfiltercats']  = FormUtil::getPassedValue('pcbfiltercats'); //array

    $pnRender = pnRender::getInstance('PostCalendar');
    $pnRender->clear_cache('blocks/postcalendar_block_pastevents.htm');
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    return $blockinfo;
}
