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
class PostCalendar_CalendarView_Year extends PostCalendar_CalendarView_AbstractDays
{

    /**
     * Set the cacheTag 
     */
    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Y');
    }

    /**
     * Set the template 
     */
    protected function setTemplate()
    {
        $this->template = 'user/year.tpl';
    }

    /**
     * Set the date range of this view 
     */
    protected function setDates()
    {
        $this->startDate = clone $this->requestedDate;
        $this->startDate
                ->modify("first day of January");
        $this->endDate = clone $this->startDate;
        $this->endDate
                ->modify("+1 year");

        $dayAdjustmentMap = array(self::SUNDAY_IS_FIRST => null,
            self::MONDAY_IS_FIRST => '-1',
            self::SATURDAY_IS_FIRST => '+1');
        $interval = new DateInterval("P1D");
        $datePeriod = new DatePeriod($this->startDate, $interval, $this->endDate);
        $dayOfWeekTracker = 0;
        $week = 0;
        $month = 0;
        foreach ($datePeriod as $date) {
            $dayOfWeek = clone $date;
            if (isset($dayAdjustmentMap[$this->firstDayOfWeek])) {
                $dayOfWeek->modify($dayAdjustmentMap[$this->firstDayOfWeek] . " days");
            }
            // add blank days to beginning of display
            while ($dayOfWeekTracker < $dayOfWeek->format('w')) {
                $this->dateGraph[$month][$week][$dayOfWeekTracker] = null;
                $dayOfWeekTracker++;
            }
            $this->dateGraph[$month][$week][$dayOfWeek->format('w')] = $date->format('Y-m-d');
            $dayOfWeekTracker++;
            if ($dayOfWeek->format('w') == 6) {
                // new week
                $dayOfWeekTracker = 0;
                $week++;
            }
            if ($date->format('d') == $date->format('t')) {
                // add blank days to end of display
                while (($dayOfWeekTracker > 0) && ($dayOfWeekTracker <= 6)) {
                    $this->dateGraph[$month][$week][$dayOfWeekTracker] = null;
                    $dayOfWeekTracker++;
                }
                // new month
                $month++;
                $week = 0;
                $dayOfWeekTracker = 0;
            }
        }
    }

    /**
     * Setup the view 
     */
    protected function setup()
    {
        $this->viewtype = 'year';

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("first day of January")->modify("-1 year");
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $prevClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
        $nextClone = clone $prevClone;
        $nextClone->modify("+2 years");
        $this->navigation['next'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $nextClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
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
     * @return string 
     */
    public function render()
    {
        if (!$this->isCached()) {
            // Load the events
            $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
                        'start' => $this->startDate,
                        'end' => $this->endDate,
                        'filtercats' => $this->selectedCategories,
                        'date' => $this->requestedDate,
                        'userfilter' => $this->userFilter));
            // create and return template
            $this->view
                    ->assign('navBar', $this->navBar)
                    ->assign('navigation', $this->navigation)
                    ->assign('dayDisplay', $this->dayDisplay)
                    ->assign('monthNames', explode(" ", $this->__('January February March April May June July August September October November December')))
                    ->assign('graph', $this->dateGraph)
                    ->assign('eventsByDate', $eventsByDate)
                    ->assign('requestedDate', $this->requestedDate->format('Y-m-d'));
        }
        return $this->view->fetch($this->template);
    }

}