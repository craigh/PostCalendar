<?php

function smarty_function_jquery_timepicker($params, Zikula_View $view)
{
    $defaultDate = (isset($params['defaultdate']) && ($params['defaultdate'] instanceof DateTime)) ? $params['defaultdate'] : new DateTime();
    $displayElement = (isset($params['displayelement'])) ? $params['displayelement'] : '';
    $valueStorageElement = (isset($params['valuestorageelement'])) ? $params['valuestorageelement'] : '';
    $readOnly = (isset($params['readonly'])) ? $params['readonly'] : true;
    $object = (isset($params['object'])) ? $params['object'] : true;
    $inlineStyle = (isset($params['inlinestyle'])) ? $params['inlinestyle'] : null;
    $onCloseCallback = (isset($params['onclosecallback'])) ? $params['onclosecallback'] : null;
    $jQueryTheme = (isset($params['theme'])) ? $params['theme'] : 'base';
    $lang = (isset($params['lang'])) ? $params['lang'] : ZLanguage::getLanguageCode();
    $use24hour = (isset($params['use24hour'])) ? $params['use24hour'] : false;
    $stepMinute = (isset($params['stepminute'])) ? $params['stepminute'] : 1;
    
    if ($use24hour) {
        $ap = 'false';
        $jqueryTimeFormat = 'h:mm';
        $dateTimeFormat = 'G:i';
    } else {
        $ap = 'true';
        $jqueryTimeFormat = 'h:mm tt';
        $dateTimeFormat = 'g:i a';
    }
    
    PageUtil::addVar("javascript", "jquery-ui");
    PageUtil::addVar("javascript", "javascript/jQuery-Timepicker-Addon/jquery-ui-timepicker-addon.js");
    if (!empty($lang) && ($lang <> 'en')) {
        PageUtil::addVar("javascript", "javascript/jQuery-Timepicker-Addon/localization/jquery-ui-timepicker-$lang.js");
    }
    JQueryUtil::loadTheme($jQueryTheme);
    PageUtil::addVar("stylesheet", "javascript/jQuery-Timepicker-Addon/jquery-ui-timepicker-addon.css");

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
    if (!empty($valueStorageElement)) {
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