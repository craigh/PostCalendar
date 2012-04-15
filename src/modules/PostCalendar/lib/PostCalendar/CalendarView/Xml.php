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
class PostCalendar_CalendarView_Xml extends PostCalendar_CalendarView_List
{
    protected function setTemplate()
    {
        $this->template = 'user/xml.tpl';
    }
    
    protected function setup()
    {
        $this->viewtype = 'xml';
        $this->listMonths = ModUtil::getVar('PostCalendar', 'pcListMonths');

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("-" . $this->listMonths . " months");
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $prevClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
        $nextClone = clone $this->requestedDate;
        $nextClone->modify("+" . $this->listMonths . " months")
                  ->modify("+1 day");
        $this->navigation['next'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $nextClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
    }
}