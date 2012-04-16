<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
/**
 * pc_pagejs_init: include the required javascript in header if needed
 *
 * @param  none
 */
function smarty_function_pc_pagejs_init($params, Zikula_View $view)
{
    unset($params);
    $modVars = $view->get_template_vars('modvars');
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $title = __('PostCalendar Event', $dom);
    if ($modVars['PostCalendar']['pcEventsOpenInNewWindow']) {
        $javascript = "
            $$('.event_details').each(function(link){
                new Zikula.UI.Window(link, {title:'$title'});
            });";
        PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");
    }
    if ($modVars['PostCalendar']['pcUsePopups']) {
        $javascript = "
            Zikula.UI.Tooltips($$('.tooltips'));
            ";
        PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");
    }
    return;
}
