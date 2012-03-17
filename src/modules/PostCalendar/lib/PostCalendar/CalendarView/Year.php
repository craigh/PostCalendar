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

    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Y');
    }

    protected function setTemplate()
    {
        $this->template = 'user/view_year.tpl';
    }
    
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

    protected function setup()
    {
        $this->viewtype = 'year';

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("first day of January")->modify("-1 year");
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'Date' => $prevClone->format('Ymd'),
                    'pc_username' => $this->userFilter,
                    'filtercats' => $this->categoryFilter));
        $nextClone = clone $prevClone;
        $nextClone->modify("+2 years");
        $this->navigation['next'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'Date' => $nextClone->format('Ymd'),
                    'pc_username' => $this->userFilter,
                    'filtercats' => $this->categoryFilter));
    }

    public function render()
    {
        if (!$this->isCached()) {
            // Load the events
            $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
                'start'       => $this->startDate->format('m/d/Y'), // refactor to use full dateTime instance
                'end'         => $this->endDate->format('m/d/Y'), // refactor to use full dateTime instance
                'filtercats'  => $this->categoryFilter,
                'Date'        => $this->requestedDate->format('Ymd'),
                'pc_username' => $this->userFilter));
            // create and return template
            $this->view
                    ->assign('navigation', $this->navigation)
                    ->assign('dayDisplay', $this->dayDisplay)
                    ->assign('monthNames', explode(" ", $this->__('January February March April May June July August September October November December')))
                    ->assign('graph', $this->dateGraph)
                    ->assign('eventsByDate', $eventsByDate)
                    ->assign('selectedcategories', $this->selectedCategories)
                    ->assign('func', $this->view->getRequest()->getGet()->get('func', $this->view->getRequest()->getPost()->get('func', 'display')))
                    ->assign('viewtypeselected', $this->viewtype)
                    ->assign('todayDate', date('Y-m-d'))
                    ->assign('requestedDate', $this->requestedDate->format('Y-m-d'));
            // be sure to DataUtil::formatForDisplay in the template - navigation and others?
        }
        return $this->view->fetch($this->template);
    }

}