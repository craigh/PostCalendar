<?php

function smarty_function_jquery_timepicker($params, Zikula_View $view)
{
    $defaultDate = (isset($params['defaultdate'])) ? $params['defaultdate'] : new DateTime();
    $displayElement = (isset($params['displayelement'])) ? $params['displayelement'] : '';
    $valueStorageElement = (isset($params['valuestorageelement'])) ? $params['valuestorageelement'] : '';
    $readOnly = (isset($params['readonly'])) ? $params['readonly'] : true;
    $object = (isset($params['object'])) ? $params['object'] : true;
    $inlineStyle = (isset($params['inlinestyle'])) ? $params['inlinestyle'] : null;
    $jQueryTheme = (isset($params['theme'])) ? $params['theme'] : 'ui-lightness';
    $lang = (isset($params['lang'])) ? $params['lang'] : ZLanguage::getLanguageCode();

    $modVars = $view->get_template_vars('modvars');
    if ($modVars['PostCalendar']['pcTime24Hours']) {
        $ap = 'false';
        $jqueryTimeFormat = 'h:mm';
        $dateTimeFormat = 'G:i';
    } else {
        $ap = 'true';
        $jqueryTimeFormat = 'h:mm tt';
        $dateTimeFormat = 'g:i a';
    }

    PageUtil::addVar("javascript", "jquery");
    PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-ui/jquery-ui-1.8.18.custom.min.js");
    PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-ui/jquery-ui-timepicker-addon.js");
    if (empty($lang) || ($lang <> 'en')) {
        PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-ui/i18n/jquery-ui-timepicker-$lang.js");
    }
    PageUtil::addVar("stylesheet", "modules/PostCalendar/style/$jQueryTheme/jquery-ui-1.8.18.custom.css");
    PageUtil::addVar("stylesheet", "modules/PostCalendar/style/timepicker.css");

    $javascript = "
        jQuery(document).ready(function() {
            jQuery('#$displayElement').timepicker({
                onClose: function(dateText, inst) {
                        updateFields(this, dateText);
                    },
                timeFormat: '$jqueryTimeFormat',
                ampm: $ap,
                stepMinute: {$modVars['PostCalendar']['pcTimeIncrement']}
            });
        });";
    PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");

    $readOnlyHtml = ($readOnly) ? " readonly='readonly'" : "";
    $inlineStyle = (isset($inlineStyle)) ? " style='$inlineStyle'" : '';

    $html = "<input type='text'{$readOnlyHtml}{$inlineStyle} id='$displayElement' name='{$displayElement}' value='{$defaultDate->format($dateTimeFormat)}' />
        <input type='hidden' id='$valueStorageElement' name='{$object}[{$valueStorageElement}]' value='{$defaultDate->format('G:i')}' />";

    return $html;
}