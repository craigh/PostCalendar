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
class PostCalendar_CalendarView_Nav_List extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = 'list';
        $this->imageTitleText = $this->view->__('List View');
        $this->displayText = $this->view->__('List');
        $this->displayImageOn = 'list_on.gif';
        $this->displayImageOff = 'list.gif';
    }

}