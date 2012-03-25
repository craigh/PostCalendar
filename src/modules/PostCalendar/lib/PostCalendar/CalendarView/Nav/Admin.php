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
class PostCalendar_CalendarView_Nav_Admin extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    public function setup()
    {
        $this->viewtype = null;
        $this->imageTitleText = $this->view->__('Admin');
        $this->displayText = $this->view->__('Admin');
    }

    protected function getImageParams()
    {
        return array(
            'modname' => 'core',
            'set' => 'icons/small',
            'src' => 'configure.png');
    }

    protected function setUrl()
    {
        $this->url = ModUtil::url('PostCalendar', 'admin', 'listevents');
    }

    protected function setAnchorTag()
    {
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            parent::setAnchorTag();
        } else {
            $this->anchorTag = null;
        }
    }

}