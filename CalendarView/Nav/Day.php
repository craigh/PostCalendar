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
class PostCalendar_CalendarView_Nav_Day extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = 'day';
        $this->imageTitleText = $this->view->__('Day View');
        $this->displayText = $this->view->__('Day');
        $this->displayImageOn = 'day_on.gif';
        $this->displayImageOff = 'day.gif';
    }

}