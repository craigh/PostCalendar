<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

use CategoryRegistryUtil;
use DateTime;

class PostCalendar_ContentType_PostCalEvents extends Content_AbstractContentType
{
    protected $pcbeventsrange;
    protected $pcbeventslimit;
    protected $categories = array();

    public function getTitle() {
        return $this->__('PostCalendar Event List');
    }
    public function getDescription() {
        return $this->__('Displays a list of PostCalendar events.');
    }

    public function loadData(&$data) {
        $this->pcbeventsrange = $data['pcbeventsrange'];
        $this->pcbeventslimit = $data['pcbeventslimit'];

        // Get the registrered categories for the module
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories ('PostCalendar', 'CalendarEvent');
        $properties = array_keys($catregistry);
        $this->categories = array();
        foreach($properties as $prop) {
            if (!empty($data['category__'.$prop])) {
                $this->categories[$prop] = $data['category__'.$prop];
            }
        }
        return;
    }

    public function display() {
        $start = new DateTime();
        $end = new DateTime();
        $end->modify("last day of this month")->modify("+$this->pcbeventsrange months");

        $filtercats = PostCalendar_Api_Event::formatCategoryFilter($this->categories); //reformat array
        $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
            'start'      => $start,
            'end'        => $end,
            'filtercats' => $filtercats));

        $this->view->assign('eventsByDate',      $eventsByDate);
        $this->view->assign('displayLimit', $this->pcbeventslimit);

        return $this->view->fetch($this->getTemplate());
    }

    public function startEditing() {
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
        $this->view->assign('catregistry', $catregistry);

        return;
    }

    public function displayEditing() {
        $cats = array();
        $output = '';
        $lang = ZLanguage::getLanguageCode();
        if ($this->categories) {
            foreach ($this->categories['Main'] as $id) {
                $thiscat = CategoryUtil::getCategoryByID($id);
                $cats[]  = isset($thiscat['display_name'][$lang]) ? $thiscat['display_name'][$lang] : $thiscat['name'];
            }
            $catlist = implode (', ', $cats);
            $output .= $this->__('Display event list from catgories') . '<br />';
            $output .= "$catlist<br />";
        }
        $output .= $this->__f('Maximum %s events.', $this->pcbeventslimit) . '<br />';
        $output .= $this->__f('Over %s months.', $this->pcbeventsrange);
        return $output;
    }

    public function getDefaultData() {
        $defaultdata = array(
            'pcbeventsrange' => 6,
            'pcbeventslimit' => 5,
            'categories'     => null);
        // Get the registered categories for the module
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
        $properties = array_keys($catregistry);

        // set a default category based on page category
        foreach ($properties as $prop) {
            $subcats_fulldata = CategoryUtil::getCategoriesByParentID($catregistry[$prop]);
            $subcats = array();
            foreach ($subcats_fulldata as $subcat_fulldata) {
                $subcats[] = $subcat_fulldata['id'];
            }
            if (in_array($this->getPageCategoryId(), $subcats)) {
                // this awkward array format iswhat $this->loadData() interprets to set category
                $defaultdata['category__' . $prop] = $this->getPageCategoryId();
            }
        }

        return $defaultdata;
    }

    public function getSearchableText() {
        return; // html_entity_decode(strip_tags($this->text));
    }
}