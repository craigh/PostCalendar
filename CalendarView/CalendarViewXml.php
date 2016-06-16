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

namespace Zikula\PostCalendarModule\CalendarView;

use CategoryRegistryUtil;
use SecurityUtil;
use UserUtil;
use LogUtil;
use ModUtil;
use CategoryUtil;
use DateTime;
use DataUtil;
use ZLanguage;
use DateInterval;
use DatePeriod;

class CalendarViewXml extends CalendarViewList
{

    /**
     * Set the template 
     */
    protected function setTemplate()
    {
        $this->template = 'user/xml.tpl';
    }

    /**
     * Setup the view 
     */
    protected function setup()
    {
        $this->viewtype = 'xml';
        $this->listMonths = ModUtil::getVar('ZikulaPostCalendarModule', 'pcListMonths');
    }
    
    /**
     * Render the view
     * @return string 
     */
    public function render()
    {
        if (!$this->isCached()) {
            // Load the events
            $eventsByDate = ModUtil::apiFunc('ZikulaPostCalendarModule', 'event', 'getEvents', array(
                        'start' => $this->startDate,
                        'end' => $this->endDate,
                        'filtercats' => $this->selectedCategories,
                        'date' => $this->requestedDate,
                        'userfilter' => $this->userFilter));
            // create and return template
            $this->view
                    ->assign('eventsByDate', $eventsByDate);
        }
        return $this->view->fetch($this->template);
    }

}