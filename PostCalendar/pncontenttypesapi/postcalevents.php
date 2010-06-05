<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class postcalendar_contenttypesapi_postcaleventsPlugin extends contentTypeBase
{
    var $pcbeventsrange;
    var $pcbeventslimit;
    var $categories;

    function getModule() {
        return 'PostCalendar';
    }
    function getName() {
        return 'postcalevents';
    }
    function getTitle() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        return __('PostCalendar Event List', $dom);
    }
    function getDescription() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        return __('Displays a list of PostCalendar events.', $dom);
    }

    function loadData($data) {
        $this->pcbeventsrange = $data['pcbeventsrange'];
        $this->pcbeventslimit = $data['pcbeventslimit'];

        // Get the registrered categories for the module
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories ('PostCalendar', 'postcalendar_events');
        $properties = array_keys($catregistry);
        $this->categories = array();
        foreach($properties as $prop) {
            $this->categories[$prop] = $data['category__'.$prop];
        }
        return;
    }

    function display() {
        $Date       = DateUtil::getDatetime('', '%Y%m%d%H%M%S');
        $the_year   = substr($Date, 0, 4);
        $the_month  = substr($Date, 4, 2);
        $the_day    = substr($Date, 6, 2);

        $starting_date = "$the_month/$the_day/$the_year";
        $ending_date   = date('m/t/Y', mktime(0, 0, 0, $the_month + $this->pcbeventsrange, 1, $the_year));

        $filtercats['__CATEGORIES__'] = $this->categories; //reformat array
        $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
            'start'      => $starting_date,
            'end'        => $ending_date,
            'filtercats' => $filtercats));

        $render = pnRender::getInstance('PostCalendar');
        $render->assign('A_EVENTS',      $eventsByDate);
        $render->assign('DATE',          $Date);
        $render->assign('DISPLAY_LIMIT', $this->pcbeventslimit);

        return $render->fetch('contenttype/postcalevents_view.html');
    }

    function startEditing(&$render) {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        $enablecategorization = ModUtil::getVar('PostCalendar', 'enablecategorization');
        if ($enablecategorization) {
            $catregistry  = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
            $render->assign('catregistry', $catregistry);
        }
        $render->assign('enablecategorization', $enablecategorization);

        return;
    }

    function displayEditing() {
        $cats = array();
        $lang = ZLanguage::getLanguageCode();
        foreach ($this->categories['Main'] as $id) {
            $thiscat = CategoryUtil::getCategoryByID($id);
            $cats[]  = $thiscat['display_name'][$lang];
        }
        $catlist = implode (', ', $cats);
        $dom     = ZLanguage::getModuleDomain('PostCalendar');
        $output  = __('Display event list from catgories', $dom) . '<br />';
        $output .= "$catlist<br />";
        $output .= __f('Maximum %s events.', $this->pcbeventslimit, $dom) . '<br />';
        $output .= __f('Over %s months.', $this->pcbeventsrange, $dom);
        return $output;
    }

    function getDefaultData() {
        return array(
            'pcbeventsrange' => 6,
            'pcbeventslimit' => 5,
            'categories'     => null);
    }

    function getSearchableText() {
        return; // html_entity_decode(strip_tags($this->text));
    }
}

function postcalendar_contenttypesapi_postcalevents($args) {
    return new postcalendar_contenttypesapi_postcaleventsPlugin($args['data']);
}