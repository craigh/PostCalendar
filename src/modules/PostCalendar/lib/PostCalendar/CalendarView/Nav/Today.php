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
class PostCalendar_CalendarView_Nav_Today extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    public function setup()
    {
        $this->viewtype = $this->view->getRequest()->request->get('viewtype', $this->view->getRequest()->query->get('viewtype', $this->defaultViewtype));
        $this->imageTitleText = $this->view->__('Jump to Today');
        $this->displayText = $this->view->__('Today');
        $this->displayImageOn = 'today.gif';
        $this->displayImageOff = 'today.gif';
    }

    protected function setAnchorTag()
    {
        if (($this->date <> $this->today) && ($this->viewtype <> 'event')) {
            $this->date = clone $this->today;
            parent::setUrl(); // reset with new date
            parent::setAnchorTag();
        } else {
            $this->anchorTag = null;
        }
    }

    protected function setRadio()
    {
        if (($this->date <> $this->today) && ($this->viewtype <> 'event')) {
            $this->date = clone $this->today;
            parent::setUrl(); // reset with new date
            parent::setRadio();
        } else {
            $this->radio = null;
        }
    }

}