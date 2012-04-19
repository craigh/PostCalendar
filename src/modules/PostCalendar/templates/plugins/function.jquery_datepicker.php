<?php

function smarty_function_jquery_datepicker($params, Zikula_View $view)
{
    $defaultDate = (isset($params['defaultdate'])) ? $params['defaultdate'] : new DateTime();
    $displayElement = (isset($params['displayelement'])) ? $params['displayelement'] : '';
    $valueStorageElement = (isset($params['valuestorageelement'])) ? $params['valuestorageelement'] : '';
    $valueStorageFormat_dateTime = (isset($params['valuestorageformat'])) ? $params['valuestorageformat'] : 'Y-m-d';
    $valueStorageFormat_javascript = (isset($params['valuestorageformat_javascript'])) ? $params['valuestorageformat_javascript'] : str_replace(array('Y', 'm', 'd'), array('yy', 'mm', 'dd'), $valueStorageFormat_dateTime);
    $displayFormat_dateTime = (isset($params['displayformat_datetime'])) ? $params['displayformat_datetime'] : 'j F Y';
    $displayFormat_javascript = (isset($params['displayformat_javascript'])) ? $params['displayformat_javascript'] : 'd MM yy';
    $readOnly = (isset($params['readonly'])) ? $params['readonly'] : true;
    $object = (isset($params['object'])) ? $params['object'] : null;
    $minDate = (isset($params['mindate'])) ? $params['mindate'] : null;
    $maxDate = (isset($params['maxdate'])) ? $params['maxdate'] : null;
    $jQueryTheme = (isset($params['theme'])) ? $params['theme'] : 'base';
    $lang = (isset($params['lang'])) ? $params['lang'] : ZLanguage::getLanguageCode();
    $submitOnSelect = (isset($params['submitonselect']) && ($params['submitonselect'])) ? 'true' : 'false';
    
    $minDateValue = ($minDate instanceof DateTime) ? $minDate->format($displayFormat_dateTime) : $minDate;
    $maxDateValue = ($maxDate instanceof DateTime) ? $maxDate->format($displayFormat_dateTime) : $maxDate;
        
    PageUtil::addVar("javascript", "jquery-ui");
    if (!empty($lang) && ($lang <> 'en')) {
        PageUtil::addVar("javascript", "javascript/jquery-ui/i18n/jquery.ui.datepicker-$lang.js");
    }
    JQueryUtil::loadTheme($jQueryTheme);

    $javascript = "
        jQuery(document).ready(function() {
            jQuery('#$displayElement').datepicker({
                onSelect: function(dateText, inst) {
                        updateFields(this, dateText, $submitOnSelect);
                    },
                dateFormat: '$displayFormat_javascript',
                defaultDate: '{$defaultDate->format($displayFormat_dateTime)}',";
    if (isset($minDate)) {
        $javascript .= "
                minDate: '$minDateValue',";
    }
    if (isset($maxDate)) {
        $javascript .= "
                maxDate: '$maxDateValue',";
    }
    $javascript .= "
                altField: '#$valueStorageElement',
                altFormat: '$valueStorageFormat_javascript',
                autoSize: true
            });
        });";
    PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");

    $readOnlyHtml = ($readOnly) ? " readonly='readonly'" : "";
    
    $name = isset($object) ? "{$object}[{$valueStorageElement}]" : $valueStorageElement;

    $html = "<input type='text'{$readOnlyHtml} id='$displayElement' name='$displayElement' value='{$defaultDate->format($displayFormat_dateTime)}' />\n
        <input type='hidden' id='$valueStorageElement' name='{$name}' value='{$defaultDate->format($valueStorageFormat_dateTime)}' />";

    return $html;
}