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

        // Get the registrered categories for the News module
        Loader::loadClass('CategoryRegistryUtil');
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories ('PostCalendar', 'postcalendar_events');
        $properties = array_keys($catregistry);
        $this->categories = array();
        foreach($properties as $prop) {
            $this->categories[$prop] = $data['category__'.$prop];
        }
    }

    function display() {
        echo "event list";
    }

    function startEditing(&$render) {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        // Get the News categorization setting
        $enablecategorization = pnModGetVar('PostCalendar', 'enablecategorization');
        // Select categories only if enabled for the PostCalendar module, otherwise selector will not be shown in modify template
        if ($enablecategorization) {
            // load the categories system
            if (!Loader::loadClass('CategoryRegistryUtil')) {
                return LogUtil::registerError(__f('Error! Could not load [%s] class.', 'CategoryRegistryUtil', $dom));
            }
            // Get the registered categories for the PostCalendar module
            $catregistry  = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
            $render->assign('catregistry', $catregistry);
        }
        $render->assign('enablecategorization', $enablecategorization);
    }

    function displayEditing() {
        return; // $this->transformCode($this->text, false); // <pre> does not work in IE 7 with the portal javascript
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