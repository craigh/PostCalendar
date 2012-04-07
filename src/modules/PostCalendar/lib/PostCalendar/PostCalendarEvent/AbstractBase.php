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

/**
 * abstract base class to allow other modules to extend the functionality of
 * PostCalendar and create events from within their hooked module.
 */
abstract class PostCalendar_PostCalendarEvent_AbstractBase
{

    private $hook;
    private $event;

    /**
     * Constructor
     * set the hooked module name
     * @param string $module
     */
    public function __construct(Zikula_ProcessHook $hook)
    {
        $this->hook = $hook;
    }

    public function getHook()
    {
        return $this->hook;
    }

    public function setHook($hook)
    {
        $this->hook = $hook;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function setEvent(PostCalendar_Entity_CalendarEvent $event)
    {
        $this->event = $event;
        $this->event->setHooked_modulename($this->hook->getCaller());
        $this->event->setHooked_objectid($this->hook->getId());
        $this->event->setHooked_area($this->hook->getAreaId());
    }

    public function setCategories($categories)
    {
        $em = ServiceUtil::getService('doctrine.entitymanager');
        $regIds = CategoryRegistryUtil::getRegisteredModuleCategoriesIds('PostCalendar', 'CalendarEvent');
        foreach ($categories as $propName => $catId) {
            $category = $em->find('Zikula_Doctrine2_Entity_Category', $catId);
            if ($this->event->getCategories()->get($regIds[$propName])) {
                $this->event->getCategories()->get($regIds[$propName])->setCategory($category);
            } else {
                $this->event->getCategories()->set($regIds[$propName], new PostCalendar_Entity_EventCategory($regIds[$propName], $category, $this->event));
            }
        }
    }

    /**
     * Magic method to allow instances to get/set properties of the event
     * 
     * @param string $name
     * @param array $arguments 
     */
    public function __call($name, $arguments)
    {
        if (isset($this->event)) {
            $args = isset($arguments[0]) ? $arguments[0] : null;
            $this->event->$name($args);
        }
    }

    /**
     * Set info for Postcalendar event creation
     *
     * Using Setters from CalendarEvent Entity, set values for at least the following class properties
     *     'title'        event title
     *     'hometext'     an event description
     *     'eventStart'   php DateTime
     *     'eventEnd'     php DateTime
     *     'sharing'      likely you should set to PostCalendar_Entity_CalendarEvent::SHARING_GLOBAL (defaults to PRIVATE)
     * The other properties of the event are optional and have default values
     * @see PostCalendar_Entity_CalendarEvent (use setters)
     * @see PostCalendar_PostCalendarEvent_Generic (example)
     * @return  boolean true on success false if no desire to publish event
     */
    abstract public function makeEvent();
    
}