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
class PostCalendar_CalendarView_Nav_Rss extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    public function setup()
    {
        $this->viewtype = 'rss';
        $this->imageTitleText = $this->view->__('RSS Feed');
        $this->displayText = $this->view->__('RSS');
        $this->displayImageOn = 'rss_on.gif';
        $this->displayImageOff = 'rss.gif';
    }
    
    protected function getImageParams()
    {
        return array(
            'modname' => 'PostCalendar',
            'src' => 'feed.gif');
    }
    
    protected function setUrl()
    {
        $this->url = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'theme' => 'rss'));
    }
}