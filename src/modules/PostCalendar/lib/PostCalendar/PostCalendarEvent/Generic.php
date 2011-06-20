<?php

/**
 * Implements Base class to allow for Event creation in generic case
 *
 * @author craig heydenburg
 */
class PostCalendar_PostCalendarEvent_Generic extends PostCalendar_PostCalendarEvent_AbstractBase {

    /**
     * get generic info for Postcalendar event creation
     *
     * @return  array() event info or false if no desire to publish event
     */
    public function makeEvent() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        $date = DateUtil::getDatetime();

        $this->title = __f('New %1$s item (#%2$s)', array($this->getHooked_modulename(), $this->getHooked_objectid()), $dom);
//        $this->hometext = ":text:" .  __f('New %1$s item (#%2$s)', array($this->getHooked_modulename(), $this->getHooked_objectid()), $dom);
        $this->hometext = ":html:" .  __f('New %1$s item (#%2$s)', array($this->getHooked_modulename(), $this->getHooked_objectid()), $dom);
        $url = DataUtil::formatForDisplayHTML($this->getHooked_objecturl());
        $this->hometext .= isset($url) ? "(<a href='$url'>" . __("Item link", $dom) . "</a>)" : "(". __("URL not provided", $dom) . ")";
        $this->aid = $this->getHooked_objectid();
        $this->time = $date; // mysql timestamp YYYY-MM-DD HH:MM:SS
        $this->informant = $this->getHooked_objectid();
        $this->eventDate = substr($date, 0, 10); // date of event: YYYY-MM-DD
        $this->startTime = substr($date, -8); // time of event: HH:MM:SS

        return true;
    }

}