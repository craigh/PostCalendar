<?php

/**
 * Implements Base class to allow for Event creation in generic case
 *
 * @author craig heydenburg
 */
class PostCalendar_PostCalendarEvent_Generic extends PostCalendar_PostCalendarEvent_Base {

    /**
     * get generic info for Postcalendar event creation
     *
     * @param   array(objectid) id
     * @return  array() event info or false if no desire to publish event
     */
    public function makeEvent($args) {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }
        $date = DateUtil::getDatetime();

        $this->title = __f('New %1$s item (#%2$s)', array($args['module'], $args['objectid']), $dom);
        $this->hometext = ":text:" .  __f('New %1$s item (#%2$s)', array($args['module'], $args['objectid']), $dom);
        $this->aid = $args['objectid'];
        $this->time = $date; // mysql timestamp YYYY-MM-DD HH:MM:SS
        $this->informant = $args['objectid'];
        $this->eventDate = substr($date, 0, 10); // date of event: YYYY-MM-DD
        $this->startTime = substr($date, -8); // time of event: HH:MM:SS

        return true;
    }

}