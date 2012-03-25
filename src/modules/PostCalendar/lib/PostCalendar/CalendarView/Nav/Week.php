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
class PostCalendar_CalendarView_Nav_Week extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    public function setup()
    {
        $this->viewtype = 'week';
        $this->imageTitleText = $this->view->__('Week View');
        $this->displayText = $this->view->__('Week');
        $this->displayImageOn = 'week_on.gif';
        $this->displayImageOff = 'week.gif';
    }

}