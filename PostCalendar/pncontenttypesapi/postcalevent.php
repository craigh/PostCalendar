<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class postcalendar_contenttypesapi_postcaleventPlugin extends contentTypeBase
{
    var $eid; // event id
    var $showcountdown;
    var $hideonexpire;

    function getModule() {
        return 'PostCalendar';
    }
    function getName() {
        return 'postcalevent';
    }
    function getTitle() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        return __('PostCalendar Featured Event', $dom);
    }
    function getDescription() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        return __('Displays one event from PostCalendar.', $dom);
    }

    function loadData($data) {
        $this->eid = $data['eid'];
        $this->showcountdown = $data['showcountdown'];
        $this->hideonexpire = $data['hideonexpire'];
    }

    function display() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        if (!isset($this->eid) || $this->eid == 0) {
            return LogUtil::RegisterError (__('PostCalendar: No event ID set.', $dom));
        }
        $vars = array();
        $vars['showcountdown'] = empty($this->showcountdown) ? false : true;
        $vars['hideonexpire']  = empty($this->hideonexpire)  ? false : true;
    
        // get the event from the DB
        pnModDBInfoLoad('PostCalendar');
        $event = DBUtil::selectObjectByID('postcalendar_events', (int) $this->eid, 'eid');
        $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);
    
        // is event allowed for this user?
        if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != UserUtil::getVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
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
    
        $pnRender = pnRender::getInstance('PostCalendar');
    
        $pnRender->assign('loaded_event', $event);
    
        return $pnRender->fetch('contenttype/postcalevent_view.html');
    }

    function displayEditing() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        return __('Display featured event', $dom) . ' #' . $this->eid;
    }

    function getDefaultData() {
        return array(
            'eid'           => 0,
            'hideonexpire'  => 0,
            'showcountdown' => 0);
    }

    function getSearchableText() {
        return; // html_entity_decode(strip_tags($this->text));
    }

}

function postcalendar_contenttypesapi_postcalevent($args) {
    return new postcalendar_contenttypesapi_postcaleventPlugin($args['data']);
}