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
 * initialise block
 */
function postcalendar_featuredeventblock_init()
{
    SecurityUtil::registerPermissionSchema('PostCalendar:featuredeventblock:', 'Block title::');
}

/**
 * get information on block
 */
function postcalendar_featuredeventblock_info()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    return array(
        'text_type'        => 'featuredevent',
        'module'           => __('PostCalendar', $dom),
        'text_type_long'   => __('Featured Event Calendar Block', $dom),
        'allow_multiple'   => true,
        'form_content'     => false,
        'form_refresh'     => false,
        'show_preview'     => true,
        'admin_tableless'  => true);
}

/**
 * display block
 */
function postcalendar_featuredeventblock_display($blockinfo)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!SecurityUtil::checkPermission('PostCalendar:featuredeventblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
        return;
    }
    if (!pnModAvailable('PostCalendar')) {
        return;
    }
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (empty($vars['eid'])) {
        return false;
    }
    $vars['showcountdown'] = empty($vars['showcountdown']) ? false : true;
    $vars['hideonexpire']  = empty($vars['hideonexpire'])  ? false : true;

    // get the event from the DB
    pnModDBInfoLoad('PostCalendar');
    $event = DBUtil::selectObjectByID('postcalendar_events', (int) $vars['eid'], 'eid');
    $event = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);

    // is event allowed for this user?
    if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != pnUserGetVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
        return false;
    }

    $alleventdates = pnModAPIFunc('PostCalendar', 'event', 'geteventdates', $event); // gets all FUTURE occurances
    // assign next occurance to eventDate
    $event['eventDate'] = array_shift($alleventdates);

    if ($vars['showcountdown']) {
        $datedifference = DateUtil::getDatetimeDiff_AsField(DateUtil::getDatetime(null, '%F'), $event['eventDate'], 3);
        $event['datedifference'] = round($datedifference);
        if ($vars['hideonexpire'] && $event['datedifference'] < 0) {
            return false;
        }
        $event['showcountdown'] = true;
    }

    $pnRender = pnRender::getInstance('PostCalendar');

    $pnRender->assign('loaded_event', $event);

    $blockinfo['content'] = $pnRender->fetch('blocks/postcalendar_block_featuredevent.htm');

    return pnBlockThemeBlock($blockinfo);
}

/**
 * modify block settings ..
 */
function postcalendar_featuredeventblock_modify($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    // Defaults
    if (empty($vars['eid']))           $vars['eid']           = '';
    if (empty($vars['showcountdown'])) $vars['showcountdown'] = 0;
    if (empty($vars['hideonexpire']))  $vars['hideonexpire']  = 0;

    $pnRender = pnRender::getInstance('PostCalendar', false); // no caching

    $pnRender->assign('vars', $vars);

    return $pnRender->fetch('blocks/postcalendar_block_featuredevent_modify.htm');
}

/**
 * update block settings
 */
function postcalendar_featuredeventblock_update($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // alter the corresponding variable
    $vars['eid']           = FormUtil::getPassedValue('eid', '', 'POST');
    $vars['showcountdown'] = FormUtil::getPassedValue('showcountdown', '', 'POST');
    $vars['hideonexpire']  = FormUtil::getPassedValue('hideonexpire', '', 'POST');

    // write back the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $pnRender = pnRender::getInstance('PostCalendar');
    $pnRender->clear_cache('blocks/postcalendar_block_featuredevent.htm');

    return $blockinfo;
}
