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
class PostCalendar_CalendarView_Month extends PostCalendar_CalendarView_AbstractDays
{

    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Ym');
    }

    protected function setTemplate()
    {
        $this->template = 'user/month.tpl';
    }
    
    protected function setDates()
    {
        $this->startDate = clone $this->requestedDate;
        $this->startDate
             ->modify("first day of this month")
             ->modify("-" . $this->dayDisplay['firstDayOfMonth'] . " days");
        $lastClone = clone $this->requestedDate;
        $lastDayOfMonth = (int)$lastClone->modify("last day of this month")->format("w");
        $this->endDate = clone $this->requestedDate;
        $this->endDate
             ->modify("last day of this month")
             ->modify("+" . ((6 + $this->firstDayOfWeek - $lastDayOfMonth) % 7) . " days")
             ->modify("+1 day");  

        $interval = new DateInterval("P1D");
        $datePeriod = new DatePeriod($this->startDate, $interval, $this->endDate);
        $i = 0;
        $week = 0;
        foreach ($datePeriod as $date) {
            $this->dateGraph[$week][$i] = $date->format('Y-m-d');
            $i++;
            if ($i > 6) {
                $i = 0;
                $week++;
            }
        }
    }

    protected function setup()
    {
        $this->viewtype = 'month';

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("first day of previous month");
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $prevClone->format('Ymd'),
                    'pc_username' => $this->userFilter,
                    'filtercats' => $this->categoryFilter));
        $nextClone = clone $this->requestedDate;
        $nextClone->modify("first day of next month");
        $this->navigation['next'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $nextClone->format('Ymd'),
                    'pc_username' => $this->userFilter,
                    'filtercats' => $this->categoryFilter));
    }

    public function render()
    {
        if (!$this->isCached()) {
            // Load the events
            $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
                'start'       => clone $this->startDate,
                'end'         => clone $this->endDate,
                'filtercats'  => $this->categoryFilter,
                'date'        => $this->requestedDate,
                'pc_username' => $this->userFilter));
            // create and return template
            $firstClone = clone $this->requestedDate;
            $lastClone = clone $this->requestedDate;
            $today = new DateTime();
            $this->view
                    ->assign('navigation', $this->navigation)
                    ->assign('dayDisplay', $this->dayDisplay)
                    ->assign('graph', $this->dateGraph)
                    ->assign('eventsByDate', $eventsByDate)
                    ->assign('selectedcategories', $this->selectedCategories)
                    ->assign('func', $this->view->getRequest()->getGet()->get('func', $this->view->getRequest()->getPost()->get('func', 'display')))
                    ->assign('viewtypeselected', $this->viewtype)
                    ->assign('todayDate', $today->format('Y-m-d'))
                    ->assign('requestedDate', $this->requestedDate->format('Y-m-d'))
                    ->assign('firstDayOfMonth', $firstClone->modify("first day of this month")->format('Y-m-d'))
                    ->assign('lastDayOfMonth', $lastClone->modify("last day of this month")->format('Y-m-d'));
            // be sure to DataUtil::formatForDisplay in the template - navigation and others?
        }
        return $this->view->fetch($this->template);
    }

}