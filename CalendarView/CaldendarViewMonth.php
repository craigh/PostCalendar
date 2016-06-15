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

namespace Zikula\PostCalendarModule\CalendarView;
class CalendarViewMonth extends AbstractDays
{

    /**
     * Set the cacheTag 
     */
    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Ym');
    }

    /**
     * Set the template 
     */
    protected function setTemplate()
    {
        $this->template = 'user/month.tpl';
    }

    /**
     * Set the date range of the view 
     */
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

    /**
     * Setup the view 
     */
    protected function setup()
    {
        $this->viewtype = 'month';

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("first day of previous month");
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $prevClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
        $nextClone = clone $this->requestedDate;
        $nextClone->modify("first day of next month");
        $this->navigation['next'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $nextClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
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
                        'start' => clone $this->startDate,
                        'end' => clone $this->endDate,
                        'filtercats' => $this->selectedCategories,
                        'date' => $this->requestedDate,
                        'userfilter' => $this->userFilter));
            // create and return template
            $firstClone = clone $this->requestedDate;
            $lastClone = clone $this->requestedDate;
            $today = new DateTime();
            $this->view
                    ->assign('navBar', $this->navBar)
                    ->assign('navigation', $this->navigation)
                    ->assign('dayDisplay', $this->dayDisplay)
                    ->assign('graph', $this->dateGraph)
                    ->assign('eventsByDate', $eventsByDate)
                    ->assign('todayDate', $today->format('Y-m-d'))
                    ->assign('requestedDate', $this->requestedDate->format('Y-m-d'))
                    ->assign('firstDayOfMonth', $firstClone->modify("first day of this month")->format('Y-m-d'))
                    ->assign('lastDayOfMonth', $lastClone->modify("last day of this month")->format('Y-m-d'));
        }
        return $this->view->fetch($this->template);
    }

}