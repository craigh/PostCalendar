<?php

function smarty_function_jquery_datepicker($params, Zikula_View $view)
{
    $defaultDate = (isset($params['defaultdate'])) ? $params['defaultdate'] : new DateTime();
    $displayElement = (isset($params['displayelement'])) ? $params['displayelement'] : '';
    $valueStorageElement = (isset($params['valuestorageelement'])) ? $params['valuestorageelement'] : '';
    $valueStorageFormat = (isset($params['valuestorageformat'])) ? $params['valuestorageformat'] : 'Y-m-d';
    $javasscriptDateFormat = str_replace(array('Y', 'm', 'd'), array('yy', 'mm', 'dd'), $valueStorageFormat);
    $readOnly = (isset($params['readonly'])) ? $params['readonly'] : true;
    $object = (isset($params['object'])) ? $params['object'] : null;
    $minDate = (isset($params['mindate'])) ? $params['mindate'] : null;
    $maxDate = (isset($params['maxdate'])) ? $params['maxdate'] : null;
    $jQueryTheme = (isset($params['theme'])) ? $params['theme'] : 'ui-lightness';
    $lang = (isset($params['lang'])) ? $params['lang'] : ZLanguage::getLanguageCode();
    $submitOnSelect = (isset($params['submitonselect']) && ($params['submitonselect'])) ? 'true' : 'false';
    

    $modVars = $view->get_template_vars('modvars');
    $userFormat = $modVars['PostCalendar']['pcDateFormats']['date'];
    $dateDisplayFormat = $modVars['PostCalendar']['pcDateFormats']['javascript'];

    PageUtil::addVar("javascript", "jquery");
    PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-ui/jquery-ui-1.8.18.custom.min.js");
    PageUtil::addVar("javascript", "modules/PostCalendar/javascript/postcalendar-function-updatefields.js");
    if (empty($lang) || ($lang <> 'en')) {
        PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-ui/i18n/jquery.ui.datepicker-$lang.js");
    }
    PageUtil::addVar("stylesheet", "modules/PostCalendar/style/$jQueryTheme/jquery-ui.css");

    $javascript = "
        jQuery(document).ready(function() {
            jQuery('#$displayElement').datepicker({
                onSelect: function(dateText, inst) {
                        updateFields(this, dateText, $submitOnSelect);
                    },
                dateFormat: '$dateDisplayFormat',
                defaultDate: '{$defaultDate->format($userFormat)}',";
    if (isset($minDate)) {
        $javascript .= "
                minDate: '{$minDate->format($userFormat)}',";
    }
    if (isset($maxDate)) {
        $javascript .= "
                maxDate: '{$maxDate->format($userFormat)}',";
    }
    $javascript .= "
                altField: '#$valueStorageElement',
                altFormat: '$javasscriptDateFormat',
                autoSize: true
            });
        });";
    PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");

    $readOnlyHtml = ($readOnly) ? " readonly='readonly'" : "";
    
    $name = isset($object) ? "{$object}[{$valueStorageElement}]" : $valueStorageElement;

    $html = "<input type='text'{$readOnlyHtml} id='$displayElement' name='$displayElement' value='{$defaultDate->format($userFormat)}' />\n
        <input type='hidden' id='$valueStorageElement' name='{$name}' value='{$defaultDate->format($valueStorageFormat)}' />";

    return $html;
}