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
class PostCalendar_CalendarView_Nav_Year extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    public function setup()
    {
        $this->viewtype = 'year';
        $this->imageTitleText = $this->view->__('Year View');
        $this->displayText = $this->view->__('Year');
        $this->displayImageOn = 'year_on.gif';
        $this->displayImageOff = 'year.gif';
    }

}