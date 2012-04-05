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
class PostCalendar_PostCalendarEvent_Generic extends PostCalendar_PostCalendarEvent_AbstractBase {

    /**
     * get generic info for Postcalendar event creation
     *
     * @return  array() event info or false if no desire to publish event
     */
    public function makeEvent() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        $date = date('Y-m-d H:i:s');
        $date = new DateTime();

        $this->title = __f('New %1$s item (#%2$s)', array($this->getHooked_modulename(), $this->getHooked_objectid()), $dom);
//        $this->hometext = ":text:" .  __f('New %1$s item (#%2$s)', array($this->getHooked_modulename(), $this->getHooked_objectid()), $dom);
        $this->hometext = ":html:" .  __f('New %1$s item (#%2$s)', array($this->getHooked_modulename(), $this->getHooked_objectid()), $dom);
        $url = DataUtil::formatForDisplayHTML($this->getHooked_objecturl());
        $this->hometext .= isset($url) ? "(<a href='$url'>" . __("Item link", $dom) . "</a>)" : "(". __("URL not provided", $dom) . ")";
        $this->aid = $this->getHooked_objectid();
        $this->time = $date; // mysql timestamp YYYY-MM-DD HH:MM:SS
        $this->informant = $this->getHooked_objectid();
        $this->eventStart = $date;
        $this->eventEnd = $date;

        return true;
    }

}