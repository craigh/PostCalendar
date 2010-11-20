<?php
/**
 * @package     PostCalendar
 * @author      Jusuff (javascript)
 * @copyright   Copyright (c) 2010, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_locations($args, &$smarty)
{
    if (!ModUtil::available('Locations') || !ModUtil::getVar('PostCalendar', 'enablelocations')) {
        return "<input type='hidden' name='postcalendar_events[location][locations_id]' id='postcalendar_events_location_locations_id' value='-1'>";
    }
    $admin = isset($args['admin']) ? $args['admin'] : false;
    $fieldname = $admin ? "postcalendar_eventdefaults" : "postcalendar_events";
    $display = '';

    $dom = ZLanguage::getModuleDomain('PostCalendar');

    $locations = array(-1 => __('Manual entry', $dom));
    $locObj = ModUtil::apiFunc('Locations','user','getLocationsForDropdown');
    foreach ($locObj as $loc) {
        $locations[$loc['value']] = $loc['text'];
    }

    include_once $smarty->_get_plugin_filepath('function', 'html_options');
    $options_array = array(
        'name'     => $fieldname . "[location][locations_id]",
        'id'       => $fieldname . "_location_locations_id",
        'class'    => "z-w90",
        'onChange' => "postcalendar_locations_bridge(this)",
        'options'  => $locations,
        'selected' => '-1');

    $display .= $admin ? "<label for='postcalendar_eventdefaults_location_locations_id'>" . __('Location', $dom) . "</label>" : "";
    $display .= smarty_function_html_options($options_array, $smarty);
    $display .= $admin ? "" : "<br />";

    $pc_loc_javascript = "
        <!--//
        function postcalendar_locations_bridge(x)
        {
            if (x.value != '-1') {
                $$('[name^=" . $fieldname . "[location]]').invoke('disable').invoke('clear');
            } else {
                $$('[name^=" . $fieldname . "[location]]').invoke('enable').invoke('clear');
            }
            x.disabled=false;
        }
        //-->";

    PageUtil::addVar("javascript", "javascript/ajax/prototype.js");
    PageUtil::addVar("rawtext", "<script type='text/javascript'>$pc_loc_javascript</script>");

    return $display;
}