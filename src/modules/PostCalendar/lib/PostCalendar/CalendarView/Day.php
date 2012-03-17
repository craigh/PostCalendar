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
class PostCalendar_CalendarView_Day extends PostCalendar_CalendarView_AbstractCalendarViewBase
{

    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Ymd');
    }

    protected function setTemplate()
    {
        $this->template = 'user/day.tpl';
    }
    
    protected function setup()
    {
        $this->viewtype = 'day';

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("-1 day");
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'Date' => $prevClone->format('Ymd'),
                    'pc_username' => $this->userFilter,
                    'filtercats' => $this->categoryFilter));
        $nextClone = clone $this->requestedDate;
        $nextClone->modify("+1 day");
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
                'start'       => $this->requestedDate->format('m/d/Y'), // refactor to use full dateTime instance
                'end'         => $this->requestedDate->format('m/d/Y'), // refactor to use full dateTime instance
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
                    ->assign('todayDate', date('Y-m-d'))
                    ->assign('requestedDate', $this->requestedDate->format('Y-m-d'));
            // be sure to DataUtil::formatForDisplay in the template - navigation and others?
        }
        return $this->view->fetch($this->template);
    }

}