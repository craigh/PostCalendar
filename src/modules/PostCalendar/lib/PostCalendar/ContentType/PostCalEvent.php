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
        $vars = array();
        $vars['showcountdown'] = empty($this->showcountdown) ? false : true;
        $vars['hideonexpire']  = empty($this->hideonexpire)  ? false : true;
    
        // get the event from the DB
        $entityManager = ServiceUtil::getService('doctrine.entitymanager');
        $event = $entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->find($this->eid)->getOldArray();
        $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);
    
        // is event allowed for this user?
        if ($event['sharing'] == PostCalendar_Entity_CalendarEvent::SHARING_PRIVATE && $event['aid'] != UserUtil::getVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
            return false;
        }
    
        $alleventdates = ModUtil::apiFunc('PostCalendar', 'event', 'geteventdates', $event); // gets all FUTURE occurances
        // assign next occurance to eventDate
        $event['eventDate'] = array_shift($alleventdates);
    
        if ($vars['showcountdown']) {
            $datedifference = DateUtil::getDatetimeDiff_AsField(DateUtil::getDatetime(null, '%F'), $event['eventDate'], 3);
            $event['datedifference'] = round($datedifference);
            if ($vars['hideonexpire'] && $event['datedifference'] < 0) {
                return false;
            }
            $event['showcountdown'] = true;
        }
    
        $this->view->assign('loaded_event', $event);
    
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