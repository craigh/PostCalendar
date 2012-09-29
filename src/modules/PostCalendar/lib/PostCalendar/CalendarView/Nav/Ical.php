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
class PostCalendar_CalendarView_Nav_Ical extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = 'ical';
        $this->imageTitleText = $this->view->__('iCal Feed');
        $this->displayText = $this->view->__('iCal');
    }

    /**
     * provide the image params
     * 
     * @return array 
     */
    protected function getImageParams()
    {
        return array(
            'modname' => 'PostCalendar',
            'src' => 'ical.png');
    }
}