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
            $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
                        'start' => $this->startDate,
                        'end' => $this->endDate,
                        'filtercats' => $this->selectedCategories,
                        'date' => $this->requestedDate,
                        'userfilter' => $this->userFilter));
            // create and return template
            $this->view
                    ->assign('eventsByDate', $eventsByDate);
        }
        $this->view->display($this->template);
        return true;
    }

}