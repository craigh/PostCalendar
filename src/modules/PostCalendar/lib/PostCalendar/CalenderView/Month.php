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

class PostCalendar_CalendarView_Month extends PostCalendar_CalendarView_AbstractCalendarViewBase
{
    
    protected function setCacheTag()
    {
        $this->cacheTag = $this->date->format('Ym');
    }
    
    protected function setTemplate()
    {
        $this->template = 'user/view_month.tpl';
    }

    protected function setup()
    {
        $this->viewtype = 'month';
    }

    public function render()
    {
        if (!$this->isCached()) {
            
        }
        return $this->view->fetch($this->template);
    }
}
