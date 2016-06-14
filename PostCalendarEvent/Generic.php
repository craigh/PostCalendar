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
 * Implements Base class to allow for Event creation in generic case
 */
class PostCalendar_PostCalendarEvent_Generic extends PostCalendar_PostCalendarEvent_AbstractBase
{

    /**
     * Set generic info for Postcalendar event creation
     *
     * @return  boolean success/failure
     */
    public function makeEvent()
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $this->setTitle(__f('New %1$s item (#%2$s)', array($this->getEvent()->getHooked_modulename(), $this->getEvent()->getHooked_objectid()), $dom));
        $text = ":html:" . __f('New %1$s item (#%2$s)', array($this->getEvent()->getHooked_modulename(), $this->getEvent()->getHooked_objectid()), $dom);
        $url = DataUtil::formatForDisplayHTML($this->getHook()->getUrl()->getUrl());
        $text .= isset($url) ? "(<a href='$url'>" . __("Item link", $dom) . "</a>)" : "(" . __("URL not provided", $dom) . ")";
        $this->setHometext($text);
        $date = new DateTime();
        $this->setEventStart($date); // technically unneccessary but left for demonstration purposes
        $this->setEventEnd($date); // technically unneccessary but left for demonstration purposes
        $this->setSharing(PostCalendar_Entity_CalendarEvent::SHARING_GLOBAL);

        return true;
    }

}