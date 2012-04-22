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
    if ($modVars['PostCalendar']['pcEventsOpenInNewWindow'] || $modVars['PostCalendar']['pcUsePopups']) {
        // ensure jquery and jquery-ui are loaded
        PageUtil::addVar("javascript", "jquery-ui");
    }
    if ($modVars['PostCalendar']['pcEventsOpenInNewWindow']) {
        $jQueryTheme = 'overcast';
        $jQueryTheme = is_dir("javascript/jquery-ui/themes/$jQueryTheme") ? $jQueryTheme : 'base';
        PageUtil::addVar("stylesheet", "javascript/jquery-ui/themes/$jQueryTheme/jquery-ui.css");
        // sample code taken from http://blog.nemikor.com/category/jquery-ui/jquery-ui-dialog/
        $javascript = "
        jQuery(document).ready(function() {
            var loading = jQuery('<img src=\"images/ajax/large_fine_white.gif\" class=\"loading\" alt=\"loading\">');
            jQuery('a.event_details').each(function() {
                var dialog = jQuery('<div class=\"event_details_popup\"></div>')
                    .append(loading.clone());
                var link = jQuery(this).one('click', function() {
                    dialog
                        .load(link.attr('href'))
                        .dialog({
                            title: '$title',
                            width: 500,
                            height: 400,
                            draggable: false
                        });
                    link.click(function() {
                        dialog.dialog('open');
                        return false;
                    });
                    return false;
                });
            });
        });";
        PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");
    }
    if ($modVars['PostCalendar']['pcUsePopups']) {
        // tipTip jquery plugin from http://code.drewwilson.com/entry/tiptip-jquery-plugin
        PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-plugins/tipTipv13/jquery.tipTip.minified.js");
        PageUtil::addVar("stylesheet", "modules/PostCalendar/javascript/jquery-plugins/tipTipv13/tipTip.css");
        $javascript = "
        jQuery(document).ready(function() {
            jQuery('.tooltips').tipTip({
                delay: 50,
                fadeIn: 50,
                fadeOut: 50
            });
        });";
        PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");
    }
    return;
}
