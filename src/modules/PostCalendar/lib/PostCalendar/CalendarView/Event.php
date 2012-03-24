<?php

/**
 * PostCalendar
 * 
 * @license MIT
 * @copyright   Copyright (c) 2012, Craig Heydenburg, Sound Web Development
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
class PostCalendar_CalendarView_Event extends PostCalendar_CalendarView_AbstractCalendarViewBase
{
    protected $event = array();

    private $popup = false;

    protected function setCacheTag()
    {
        // this is unused in this object. It is set to be not null.
        $this->cacheTag = $this->eid;
    }

    protected function setTemplate()
    {
        $this->template = 'user/event.tpl';
    }

    protected function setup()
    {
        $this->viewtype = 'event';
        $this->popup = $this->view->getRequest()->query->get('popup', $this->view->getRequest()->request->get('popup', false));
        
        $em = ServiceUtil::getService('doctrine.entitymanager');
        $event = $em->getRepository('PostCalendar_Entity_CalendarEvent')->find($this->eid)->getOldArray();
        $this->event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);
    }

    public function render()
    {
        // caching won't help much in this case because security check comes after
        // fetch from db, so don't use isCached, just fetch after normal routine.

        // is event allowed for this user?
        if (($this->event['sharing'] == PostCalendar_Entity_CalendarEvent::SHARING_PRIVATE
                && $this->event['aid'] != $this->currentUser
                && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))
                || ((!SecurityUtil::checkPermission('PostCalendar::Event', "{$this->event['title']}::{$this->event['eid']}", ACCESS_OVERVIEW))
                || (!CategoryUtil::hasCategoryAccess($this->event['categories'], 'PostCalendar')))) {
            // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
            // or if specific event is permission controlled or if Category is denied
            return LogUtil::registerError($this->view->__('You do not have permission to view this event.'));
        }

        // since recurrevents are dynamically calculated, we need to change the date
        // to ensure that the correct/current date is being displayed (rather than the
        // date on which the recurring booking was executed).
        if ($this->event['recurrtype']) {
            $this->event['eventDate'] = $this->requestedDate->format('Ymd');
        }
        // create and return template
        $this->view->assign('loaded_event', $this->event);
        if ($this->popup) {
            $this->view->assign('popup', true)
                    ->display('event/view.tpl');
            return true;
        } else {
            $edit = ((SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)
                    && (UserUtil::getVar('uid') == $this->event['aid']))
                    || SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN));
            $this->view->assign('EVENT_CAN_EDIT', $edit);
            return $this->view->fetch($this->template);
        }
    }

    public function getEvent()
    {
        return $this->event;
    }

}