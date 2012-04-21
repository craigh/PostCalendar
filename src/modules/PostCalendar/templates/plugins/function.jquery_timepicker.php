<?php

/**
 * Copyright Zikula Foundation 2012 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Zikula_View
 * @subpackage Template_Plugins
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

/**
 * Inject a jQuery Timepicker plugin to the template.
 * @see https://github.com/trentrichardson/jQuery-Timepicker-Addon
 * 
 * NOTE: This plugin is NOT configured to integrate the datepicker and timepicker
 *     together as one. It only displays the timepicker.
 *
 * Available attributes:
 *  - see inline docblocks of each parameter
 *
 * Examples:
 *
 *  Displays the datepicker with the current time as default:
 *
 *  <samp>{jquery_timepicker displayelement='time'}</samp>
 *
 * @param array       $params All attributes passed to this function from the template.
 * @param Zikula_View $view   Reference to the {@link Zikula_View} object.
 *
 * @return string the html and javascript required to render the timepicker
 */
function smarty_function_jquery_timepicker($params, Zikula_View $view)
{
    /**
     * defaultdate
     * php DateTime object
     * The initial datetime selected and displayed (default: now)
     */
    $defaultDate = (isset($params['defaultdate']) && ($params['defaultdate'] instanceof DateTime)) ? $params['defaultdate'] : new DateTime();
    /**
     * displayelement
     * string (do not include the '#' character)
     * (required) The id text of the html element where the timepicker displays the selection 
     */
    $displayElement = (isset($params['displayelement'])) ? $params['displayelement'] : '';
    /**
     * valuestorageelement
     * string (do not include the '#' character)
     * (optional) the id text of the html element where the selected time will be stored (default null)
     * note: storage format is HH:MM (in 24 hour format)
     */
    $valueStorageElement = (isset($params['valuestorageelement'])) ? $params['valuestorageelement'] : null;
    /**
     * readonly
     * boolean
     * (optional) whether the display field is readonly of active (default: (boolean)true - IS readonly) 
     */
    $readOnly = (isset($params['readonly'])) ? $params['readonly'] : true;
    /**
     * object
     * string
     * (optional) object name for html element names. e.g. name='myObjectName[myVariable]' (default: null) 
     */
    $object = (isset($params['object'])) ? $params['object'] : true;
    /**
     * inlinestyle
     * string
     * contents of html style param - useful for setting display:none on load
     */
    $inlineStyle = (isset($params['inlinestyle'])) ? $params['inlinestyle'] : null;
    /**
     * onclosecallback
     * string
     * (optional) javascript to perform onClose event (default: null) 
     */
    $onCloseCallback = (isset($params['onclosecallback'])) ? $params['onclosecallback'] : null;
    /**
     * theme
     * string
     * (optional) which jquery theme to use for this plugin. Uses JQueryUtil::loadTheme() (default: 'base')
     */
    $jQueryTheme = (isset($params['theme'])) ? $params['theme'] : 'base';
    /**
     * lang
     * string
     * (optional) language of datepicker (default: current system language)
     */
    $lang = (isset($params['lang'])) ? $params['lang'] : ZLanguage::getLanguageCode();
    /**
     * use24hour
     * boolean
     * (optional) use 24 hour time display or 12 hour am/pm (default: false) 
     */
    $use24hour = (isset($params['use24hour'])) ? $params['use24hour'] : false;
    /**
     * stepminute
     * integer
     * (optional) snapto minute interval in minute slider selector (default: 1)
     */
    $stepMinute = (isset($params['stepminute'])) ? $params['stepminute'] : 1;

    // compute formats
    if ($use24hour) {
        $ap = 'false';
        $jqueryTimeFormat = 'h:mm';
        $dateTimeFormat = 'G:i';
    } else {
        $ap = 'true';
        $jqueryTimeFormat = 'h:mm tt';
        $dateTimeFormat = 'g:i a';
    }

    // load required javascripts
    PageUtil::addVar("javascript", "jquery-ui");
    PageUtil::addVar("javascript", "javascript/jQuery-Timepicker-Addon/jquery-ui-timepicker-addon.js");
    if (!empty($lang) && ($lang <> 'en')) {
        PageUtil::addVar("javascript", "javascript/jQuery-Timepicker-Addon/localization/jquery-ui-timepicker-$lang.js");
    }
    JQueryUtil::loadTheme($jQueryTheme);
    PageUtil::addVar("stylesheet", "javascript/jQuery-Timepicker-Addon/jquery-ui-timepicker-addon.css");

    // build the timepicker
    $javascript = "
        jQuery(document).ready(function() {
            jQuery('#$displayElement').timepicker({
                timeFormat: '$jqueryTimeFormat',
                ampm: $ap,";
    if (isset($onCloseCallback)) {
        $javascript .= "
                onClose: function(dateText, inst) {" . $onCloseCallback . "},";
    }
    if (isset($valueStorageElement)) {
        addTimepickerFormatTime();
        $javascript .= "
                onSelect: function(dateText, inst) {
                    jQuery('#$valueStorageElement').attr('value', timepickerFormatTime(jQuery(this).datepicker('getDate')));
                },";
    }
    $javascript .= "
                stepMinute: $stepMinute
            });
        });";
    PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");

    $readOnlyHtml = ($readOnly) ? " readonly='readonly'" : "";
    $inlineStyle = (isset($inlineStyle)) ? " style='$inlineStyle'" : '';

    $html = "<input type='text'{$readOnlyHtml}{$inlineStyle} id='$displayElement' name='{$displayElement}' value='{$defaultDate->format($dateTimeFormat)}' />\n";
    if (isset($valueStorageElement)) {
        $html .="<input type='hidden' id='$valueStorageElement' name='{$object}[{$valueStorageElement}]' value='{$defaultDate->format('G:i')}' />\n";
    }

    return $html;
}

/**
 * add required JS function to page 
 */
function addTimepickerFormatTime()
{
    $javascript = "
        function timepickerFormatTime(date)
        {
            var m = date.getMinutes();
            var h = date.getHours();
            m = m + ''; // convert to string
            h = h + ''; // convert to string
            m = m.length == 1 ? '0' + m : m;
            h = h.length == 1 ? '0' + h : h;
            return h + ':' + m;
        }";
    PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");
}