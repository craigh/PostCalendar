<?php

function smarty_function_jquery_datepicker($params, Zikula_View $view)
{
    $defaultDate = (isset($params['defaultdate'])) ? $params['defaultdate'] : new DateTime();
    $displayElement = (isset($params['displayelement'])) ? $params['displayelement'] : '';
    $valueStorageElement = (isset($params['valuestorageelement'])) ? $params['valuestorageelement'] : '';
    $readOnly = (isset($params['readonly'])) ? $params['readonly'] : true;
    $object = (isset($params['object'])) ? $params['object'] : true;
    $minDate = (isset($params['mindate'])) ? $params['mindate'] : null;
    $maxDate = (isset($params['maxdate'])) ? $params['maxdate'] : null;
    $jQueryTheme = (isset($params['theme'])) ? $params['theme'] : 'ui-lightness';
    $lang = (isset($params['lang'])) ? $params['lang'] : ZLanguage::getLanguageCode();
    $dateDisplayFormat = 'd MM yy';

    $modVars = $view->get_template_vars('modvars');
    $userFormat = $modVars['PostCalendar']['pcDateFormats']['date'];

    PageUtil::addVar("javascript", "jquery");
    PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-ui/jquery-ui-1.8.18.custom.min.js");
    if (empty($lang) || ($lang <> 'en')) {
        PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-ui/i18n/jquery.ui.datepicker-$lang.js");
    }
    PageUtil::addVar("stylesheet", "modules/PostCalendar/style/$jQueryTheme/jquery-ui-1.8.18.custom.css");

    $javascript = "
        jQuery(document).ready(function() {
            jQuery('#$displayElement').datepicker({
                onSelect: function(dateText, inst) {
                        updateFields(this, dateText);
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
                altFormat: 'yy-mm-dd',
                autoSize: true
            });
        });";
    PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");

    $readOnlyHtml = ($readOnly) ? " readonly='readonly'" : "";

    $html = "<input type='text'{$readOnlyHtml} id='$displayElement' name='$displayElement' value='{$defaultDate->format($userFormat)}' />\n
        <input type='hidden' id='$valueStorageElement' name='{$object}[{$valueStorageElement}]' value='{$defaultDate->format('Y-m-d')}' />";

    return $html;
}