<?php
/**
 * @package     PostCalendar
 * @author      $Author:$
 * @link        $HeadURL:$
 * @version     $Id:$
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
    return array('text_type'        => 'featuredevent',
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
        return false;
    }
    if (!pnModAvailable('PostCalendar')) {
        return false;
    }
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (empty($vars['eid'])) return false;
    $vars['showcountdown'] = empty($vars['showcountdown']) ? false : true;

    // get the event from the DB
    pnModDBInfoLoad('PostCalendar');
    $event = DBUtil::selectObjectByID('postcalendar_events', (int) $vars['eid'], 'eid');
    $event = pnModAPIFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);

    // is event allowed for this user?
    if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != pnUserGetVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
    // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
        return '';
    }
            
    // since recurrevents are dynamically calculcated, we need to change the date
    // to ensure that the correct/current date is being displayed (rather than the
    // date on which the recurring booking was executed).
    if ($event['recurrtype']) {
        $y = substr($args['Date'], 0, 4);
        $m = substr($args['Date'], 4, 2);
        $d = substr($args['Date'], 6, 2);
        $event['eventDate'] = "$y-$m-$d";
    }

    if ($vars['showcountdown']) {
        $datedifference = DateUtil::getDatetimeDiff_AsField(DateUtil::getDatetime(), $event['eventDate'], 3);
        $event['datedifference'] = $datedifference > 0 ? round($datedifference) : false ;
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
    if (empty($vars['eid'])) $vars['eid'] = '';

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

    // write back the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $pnRender = pnRender::getInstance('PostCalendar');
    $pnRender->clear_cache('PostCalendar_block_featuredevent.htm');

    return $blockinfo;
}
