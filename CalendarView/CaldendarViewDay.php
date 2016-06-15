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

class CalendarViewDay extends AbstractCalendarViewBase
{
    /**
     * Display a calendar day and all events 
     */

    /**
     * Set the cacheTag 
     */
    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Ymd');
    }

    /**
     * Set the template name 
     */
    protected function setTemplate()
    {
        $this->template = 'user/day.tpl';
    }
    
    /**
     * Setup the view 
     */
    protected function setup()
    {
        $this->viewtype = 'day';

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("-1 day");
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $prevClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
        $nextClone = clone $this->requestedDate;
        $nextClone->modify("+1 day");
        $this->navigation['next'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $nextClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
    }

    /**
     * Render the view
     * 
     * @return string 
     */
    public function render()
    {
        if (!$this->isCached()) {
            // Load the events
            $start = clone $this->requestedDate;
            $end = clone $this->requestedDate;
            $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
                'start'       => $start,
                'end'         => $end,
                'filtercats'  => $this->selectedCategories,
                'date'        => $this->requestedDate,
                'userfilter' => $this->userFilter));
            // create and return template
            $this->view
                    ->assign('navBar', $this->navBar)
                    ->assign('navigation', $this->navigation)
                    ->assign('eventsByDate', $eventsByDate)
                    ->assign('requestedDate', $this->requestedDate);
        }
        return $this->view->fetch($this->template);
    }

}