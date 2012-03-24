<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class PostCalendar_ContentType_PostCalEvent extends Content_AbstractContentType
{
    protected $eid; // event id
    protected $showcountdown;
    protected $hideonexpire;

    public function getTitle() {
        return $this->__('PostCalendar Featured Event');
    }
    public function getDescription() {
        return $this->__('Displays one event from PostCalendar.');
    }

    public function loadData(&$data) {
        $this->eid = $data['eid'];
        $this->showcountdown = $data['showcountdown'];
        $this->hideonexpire = $data['hideonexpire'];
    }

    public function display() {
        if (!isset($this->eid) || $this->eid == 0) {
            return LogUtil::RegisterError ($this->__('PostCalendar: No event ID set.'));
        }

        $vars = array('content' => array(
            'showcountdown' => $this->showcountdown,
            'hideonexpire' => $this->hideonexpire,
            'eid' => $this->eid,
        ));
    
        $date = new DateTime();
        $calendarView = new PostCalendar_CalendarView_FeaturedEventBlock($this->view, $date, '', null, serialize($vars));
    
        $this->view->assign('loaded_event', $calendarView->getEvent());
    
        return $this->view->fetch($this->getTemplate());
    }

    public function displayEditing() {
        return $this->__('Display featured event') . ' #' . $this->eid;
    }

    public function getDefaultData() {
        return array(
            'eid'           => 0,
            'hideonexpire'  => 0,
            'showcountdown' => 0);
    }

    public function getSearchableText() {
        return; // html_entity_decode(strip_tags($this->text));
    }

}