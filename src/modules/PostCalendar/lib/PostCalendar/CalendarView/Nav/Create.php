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
class PostCalendar_CalendarView_Nav_Create extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    public function setup()
    {
        $this->viewtype = null;
        $this->imageTitleText = $this->view->__('Submit New Event');
        $this->displayText = $this->view->__('Add');
        $this->displayImageOn = 'add_on.gif';
        $this->displayImageOff = 'add.gif';
    }

    protected function setUrl()
    {
        $this->url = ModUtil::url('PostCalendar', 'event', 'create', array(
                    'date' => $this->date->format('Ymd')));
    }
    
    protected function setAnchorTag()
    {
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            parent::setAnchorTag();
        } else {
            $this->anchorTag = null;
        }
    }

}