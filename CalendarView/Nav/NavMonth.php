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

namespace Zikula\PostCalendarModule\CalendarView\Nav;
use Zikula_ModUrl;
use ZLanguage;
use SecurityUtil;
class NavMonth extends AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = 'month';
        $this->imageTitleText = $this->view->__('Month View');
        $this->displayText = $this->view->__('Month');
        $this->displayImageOn = 'month_on.gif';
        $this->displayImageOff = 'month.gif';
    }

}