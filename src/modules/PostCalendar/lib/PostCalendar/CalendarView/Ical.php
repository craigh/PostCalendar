<?php

/**
 * Including the composer autoloader for Sabre-vobject.
 * Not sure if there's a native zikula way to do this.
 */
include __DIR__ . '/../../vendor/sabre-vobject/vendor/autoload.php';

use Sabre\VObject;
use PostCalendar_Entity_CalendarEvent as CalendarEvent;
use PostCalendar_Api_Event as EventAPI;

/**
 * PostCalendar
 *
 * @license MIT
 * @copyright   Copyright (c) 2012, Craig Heydenburg, Sound Web Development
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
class PostCalendar_CalendarView_Ical extends PostCalendar_CalendarView_List
{

    /**
     * Set the template
     */
    protected function setTemplate()
    {
        $this->template = 'user/ical.tpl';
    }

    /**
     * Setup the view
     */
    protected function setup()
    {
        $this->viewtype = 'ical';
        $this->listMonths = ModUtil::getVar('PostCalendar', 'pcListMonths');
    }

    /**
     * Render the view
     * @return string
     */
    public function render()
    {

        if (!$this->isCached()) {

            // Load the events
            $events = ModUtil::apiFunc('PostCalendar', 'event', 'getFlatEvents', array(
                        'start' => $this->startDate,
                        'end' => $this->endDate,
                        'filtercats' => $this->selectedCategories,
                        'date' => $this->requestedDate,
                        'userfilter' => $this->userFilter));

            // Not sure if this is the correct way of doing things.
            header('Content-Type: text/calendar; charset=UTF-8');

            // For easy debugging
            // header('Content-Type: text/plain; charset=UTF-8');

            /* We are doing the actual rendering here. It makes no sense at all to
             * do this in a template */

            $vcal = VObject\Component::create('VCALENDAR');
            $vcal->VERSION = '2.0';
            $vcal->PRODID = "-//Sabre//Sabre VObject " . VObject\Version::VERSION . "//EN";

            foreach ($events as $event) {

                $vevent = VObject\Component::create('VEVENT');
                $vevent->UID = 'postcalendar-' . $event['eid'];
                $vevent->SUMMARY = $event['title'];
                if ($event['hometext']) {
                    $vevent->DESCRIPTION = $event['hometext'];
                }

                // We're overwriting this in the next step
                $vevent->DTSTART = '---';
                $vevent->DTEND = '---';

                if ($vevent['alldayevent']) {
                    $vevent->DTSTART->setDateTime($event['eventStart'], VObject\Property\DateTime::DATE);
                    $vevent->DTEND->setDateTime($event['eventEnd'], VObject\Property\DateTime::DATE);
                } else {
                    $vevent->DTSTART->setDateTime($event['eventStart'], VObject\Property\DateTime::LOCALTZ);
                    $vevent->DTEND->setDateTime($event['eventEnd'], VObject\Property\DateTime::LOCALTZ);
                }

                $location = '';
                foreach ($event['location'] as $locKey => $locVal) {
                    if ($locKey == 'locations_id') {
                        if ($locVal <> -1) {
                            // future support for location module here
                        }
                    } else {
                        if (trim($locVal)) {
                            if ($location) {
                                $location .= '\n';
                            }
                            $location .= $locVal;
                        }
                    }
                }
                if ($location) {
                    $vevent->LOCATION = $location;
                }

                switch ($event['recurrtype']) {

                    case CalendarEvent::RECURRTYPE_NONE :
                    case CalendarEvent::RECURRTYPE_CONTINUOUS :
                        // do nothing
                        break;

                    case CalendarEvent::RECURRTYPE_REPEAT:

                        $freq = null;

                        switch ($event['recurrspec']['event_repeat_freq_type']) {

                            case EventAPI::REPEAT_EVERY_DAY :
                                $freq = 'DAILY';
                                break;
                            case EventAPI::REPEAT_EVERY_WEEK :
                                $freq = 'WEEKLY';
                                break;
                            case EventAPI::REPEAT_EVERY_MONTH :
                                $freq = 'MONTHLY';
                                break;
                            case EventAPI::REPEAT_EVERY_YEAR :
                                $freq = 'YEARLY';
                                break;
                            default :
                                throw new \InvalidArgumentException('Unknown event_repeat_freq_type');
                        }
                        $interval = $event['recurrspec']['event_repeat_freq'];

                        $until = clone $event['endDate'];
                        $until->setTimeZone(new DateTimeZone('UTC'));
                        $until = $until->format('Ymd\\THis\\Z');

                        $vevent->RRULE = 'FREQ=' . $freq . ';INTERVAL=' . $interval . ';UNTIL=' . $until;
                        break;

                    case CalendarEvent::RECURRTYPE_REPEAT_ON :

                        $freq = 'MONTHLY';
                        $interval = $event['recurrspec']['event_repeat_on_freq'];

                        $dayList = array(
                            EventAPI::REPEAT_ON_SUN => 'SU',
                            EventAPI::REPEAT_ON_MON => 'MO',
                            EventAPI::REPEAT_ON_TUE => 'TU',
                            EventAPI::REPEAT_ON_WED => 'WE',
                            EventAPI::REPEAT_ON_THU => 'TH',
                            EventAPI::REPEAT_ON_FRI => 'FR',
                            EventAPI::REPEAT_ON_SAT => 'SA',
                        );
                        $byDay = '';
                        switch ($event['recurrspec']['event_repeat_on_num']) {
                            case EventAPI::REPEAT_ON_1ST :
                            case EventAPI::REPEAT_ON_2ND :
                            case EventAPI::REPEAT_ON_3RD :
                            case EventAPI::REPEAT_ON_4TH :
                                $byDay = '+' . $event['recurrspec']['event_repeat_on_num'];
                                break;
                            case EventAPI::REPEAT_ON_LAST :
                                $byDay = '-1';
                        }
                        $byDay.=$dayList[$event['recurrspec']['event_repeat_on_day']];

                        $until = clone $event['endDate'];
                        $until->setTimeZone(new DateTimeZone('UTC'));
                        $until = $until->format('Ymd\\THis\\Z');

                        $vevent->RRULE = 'FREQ=' . $freq . ';INTERVAL=' . $interval . ';UNTIL=' . $until . ';BYDAY=' . $byDay;
                        break;
                }

                $vcal->add($vevent);
            }

            // I used this for debugging, because the cache was annoying and I
            // wasn't sure how to turn it off.
            // echo $vcal->serialize();
            // die();
            // create and return template
            $this->view
                    ->assign('icalendarData', $vcal->serialize());
        }


        $this->view->display($this->template);
        return true;
    }

}
