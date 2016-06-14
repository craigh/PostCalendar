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

    /**
     * The event to view
     * 
     * @var array 
     */
    protected $event = array();

    /**
     * Is the view of this event in popup mode?
     * 
     * @var boolean 
     */
    private $popup = false;

    /**
     * Set the cacheTag 
     */
    protected function setCacheTag()
    {
        // this is unused in this object. It is set to be not null.
        $this->cacheTag = $this->eid;
    }

    /**
     * Set the template 
     */
    protected function setTemplate()
    {
        $this->template = 'user/event.tpl';
    }

    /**
     * Setup the view 
     */
    protected function setup()
    {
        $this->viewtype = 'event';
        $this->popup = $this->view->getRequest()->query->get('popup', $this->view->getRequest()->request->get('popup', false));

        $em = ServiceUtil::getService('doctrine.entitymanager');
        $event = $em->getRepository('PostCalendar_Entity_CalendarEvent')->find($this->eid);
        if (isset($event)) {
            $event = $event->getOldArray();
        } else {
            return LogUtil::registerError($this->view->__('Error: Can not find event.'));
        }
        $this->event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', array(
                    'event' => $event,
                    'currentDate' => $this->requestedDate->format('Y-m-d')));
    }

    /**
     * Override the navBarConfig for event view to hide the filter
     * 
     * @return array 
     */
    protected function getNavBarConfig()
    {
        $parentSettings = parent::getNavBarConfig();
        $newArray = array();
        if (isset($parentSettings['navbartype'])) {
            $newArray['navbartype'] = $parentSettings['navbartype'];
        }
        if (isset($parentSettings['jumpdate'])) {
            $newArray['jumpdate'] = $parentSettings['jumpdate'];
        }
        if (isset($parentSettings['navbar'])) {
            $newArray['navbar'] = $parentSettings['navbar'];
        }
        // hide filter in year view
        $newArray['filter'] = false;
        return $newArray;
    }

    /**
     * Render the view
     * 
     * @return mixed boolean/string 
     */
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

        // create and return template
        $this->view->assign('loaded_event', $this->event);
        $edit = ((SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)
                && (UserUtil::getVar('uid') == $this->event['aid']))
                || SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN));
        $this->view->assign('EVENT_CAN_EDIT', $edit);
        if ($this->popup) {
            $this->view->assign('popup', true)
                    ->display('event/view.tpl');
            return true;
        } else {
            $this->view->assign('navBar', $this->navBar);
            return $this->view->fetch($this->template);
        }
    }

    /**
     * Get the event
     * 
     * @return array 
     */
    public function getEvent()
    {
        return $this->event;
    }

}