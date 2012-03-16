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
class PostCalendar_CalendarView_Week extends PostCalendar_CalendarView_AbstractDays
{
    private $dayMap = array(
        self::SUNDAY_IS_FIRST => "Sunday",
        self::MONDAY_IS_FIRST => "Monday",
        self::SATURDAY_IS_FIRST => "Saturday",
    );

    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Ym');
    }

    protected function setTemplate()
    {
        $this->template = 'user/view_weekOOP.tpl';
    }
    
    protected function setDates()
    {
        $this->startDate = clone $this->requestedDate;
        if ($this->requestedDate->format('w') <> $this->firstDayOfWeek) {
            $this->startDate
                ->modify("last " . $this->dayMap[$this->firstDayOfWeek]);
        }
        $this->endDate = clone $this->requestedDate;
        $this->endDate
             ->modify("next " . $this->dayMap[$this->firstDayOfWeek])
             ->modify("-1 day");  
    }

    protected function setup()
    {
        $this->viewtype = 'week';

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("last " . $this->dayMap[$this->firstDayOfWeek]);
        if ($this->requestedDate->format('w') <> $this->firstDayOfWeek) {
            $prevClone->modify("-7 days");
        }
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'Date' => $prevClone->format('Ymd'),
                    'pc_username' => $this->userFilter,
                    'filtercats' => $this->categoryFilter));
        $nextClone = clone $this->requestedDate;
        $nextClone->modify("next " . $this->dayMap[$this->firstDayOfWeek]);
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
                    ->assign('eventsByDate', $eventsByDate)
                    ->assign('selectedcategories', $this->selectedCategories)
                    ->assign('func', $this->view->getRequest()->getGet()->get('func', $this->view->getRequest()->getPost()->get('func', 'display')))
                    ->assign('viewtypeselected', $this->viewtype)
                    ->assign('requestedDate', $this->requestedDate->format('Ymd'));
            // be sure to DataUtil::formatForDisplay in the template - navigation and others?
        }
        return $this->view->fetch($this->template);
    }

}