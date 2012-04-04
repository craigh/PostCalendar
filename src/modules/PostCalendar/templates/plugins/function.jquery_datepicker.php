<?php

function smarty_function_jquery_datepicker($params, Zikula_View $view)
{
    $defaultDate = (isset($params['defaultdate'])) ? $params['defaultdate'] : new DateTime();
    $displayElement = (isset($params['displayelement'])) ? $params['displayelement'] : '';
    $valueStorageElement = (isset($params['valuestorageelement'])) ? $params['valuestorageelement'] : '';
    $class = (isset($params['class'])) ? $params['class'] : 'postcalendar_datepicker';
    $readOnly = (isset($params['readonly'])) ? $params['readonly'] : true;

    $modVars = $view->get_template_vars('modvars');

    PageUtil::addVar("javascript", "jquery");
    PageUtil::addVar("javascript", "modules/PostCalendar/javascript/jquery-ui-1.8.18.custom.min.js");
    PageUtil::addVar("stylesheet", "modules/PostCalendar/style/ui-lightness/jquery-ui-1.8.18.custom.css");
    $javascript = "
        jQuery(document).ready(function() {
            jQuery('#$displayElement').datepicker({
                dateFormat: 'MM d, yy',
                defaultDate: '$defaultDate',
                altField: '#$valueStorageElement',
                altFormat: 'yy-mm-dd',
                autoSize: true
            });
        });";
    PageUtil::addVar("footer", "<script type='text/javascript'>$javascript</script>");

    $readOnlyHtml = ($readOnly) ? " readonly='readonly'" : "";
    
    $html = "<input type='text'{$readOnlyHtml} class='$class' id='$displayElement' name='$displayElement' value='{$defaultDate->format($modVars['PostCalendar']['pcEventDateFormat'])}' />\n
        <input type='hidden' id='$valueStorageElement' name='$valueStorageElement' value='{$defaultDate->format('Y-m-d')}' />";

    return $html;
}