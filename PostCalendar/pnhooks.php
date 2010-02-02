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
 * postcalendar_hooks_new
 *
 * display PostCalendar related information on hooked new item
 * @param array $args
 * @return string generated html output
 * @access public
 */
function postcalendar_hooks_new($args)
{
    $render = pnRender::getInstance('PostCalendar');

    // load the category registry util
    if (Loader::loadClass('CategoryRegistryUtil')) {
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $render->assign('postcalendar_catregistry', $catregistry);
    }

    return $render->fetch('hooks/postcalendar_hooks_new.htm');
}

/**
 * postcalendar_hooks_modify
 *
 * display PostCalendar related information on hooked modify item
 * @param array $args
 * @return string generated html output
 * @access public
 */
function postcalendar_hooks_modify($args)
{
    echo "<pre>";
    print_r($args);
    echo "</pre>";
    $render = pnRender::getInstance('PostCalendar');
    $render->assign('testvar', "PostCalendar: Modify hook");
    return $render->fetch('hooks/postcalendar_hooks_modify.htm');
}