<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
function smarty_function_pc_locations($args, &$smarty)
{
    if (!ModUtil::available('Locations')) {
        return "<input type='hidden' name='postcalendar_events[location][locations_id]' id='postcalendar_events_location_locations_id' value='-1'>";
    }

    $dom = ZLanguage::getModuleDomain('PostCalendar');

    $locations = array(-1 => __('Manual entry', $dom));
    $locObj = ModUtil::apiFunc('Locations','user','getLocationsForDropdown');
    foreach ($locObj as $loc) {
        $locations[$loc['value']] = $loc['text'];
    }

    include_once $smarty->_get_plugin_filepath('function', 'html_options');
    $options_array = array(
        'name'     => "postcalendar_events[location][locations_id]",
        'id'       => "postcalendar_events_location_locations_id",
        'class'    => "postcal90",
        'onChange' => "postcalendar_locations_bridge",
        'options'  => $locations,
        'selected' => '-1');

    $display = smarty_function_html_options($options_array, $smarty);

    $pc_loc_javascript = "
        <!--//
        function postcalendar_locations_bridge()
        {
        }
        //-->";

    PageUtil::addVar("rawtext", "<script type='text/javascript'>$pc_loc_javascript</script>");

    return $display . "<br />";
}