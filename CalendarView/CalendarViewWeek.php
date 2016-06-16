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

use \System;
use CategoryRegistryUtil;
use \SecurityUtil;
use \UserUtil;
use \LogUtil;
use \ModUtil;
use \CategoryUtil;
use \DateTime;
use \DataUtil;
use \ZLanguage;
use DateInterval;
use DatePeriod;

class CalendarViewWeek extends AbstractDays
{

    /**
     * An array of values to use in the computation of DateTime objects 
     * Do not translate!
     * 
     * @var array 
     */
    private $dayMap = array(
        self::SUNDAY_IS_FIRST => "Sunday",
        self::MONDAY_IS_FIRST => "Monday",
        self::SATURDAY_IS_FIRST => "Saturday",
    );

    /**
     * Set the cacheTag 
     */
    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Ym');
    }

    /**
     * Set the template 
     */
    protected function setTemplate()
    {
        $this->template = 'user/week.tpl';
    }

    /**
     * Set the date range of the view 
     */
    protected function setDates()
    {
        $this->startDate = clone $this->requestedDate;
        if ($this->requestedDate->format('w') <> $this->firstDayOfWeek) {
            $this->startDate
                    ->modify("last " . $this->dayMap[$this->firstDayOfWeek]);
        }
        $this->endDate = clone $this->requestedDate;
        $this->endDate
                ->modify("next " . $this->dayMap[$this->firstDayOfWeek])
                ->modify("-1 day");
    }

    /**
     * Setup the view 
     */
    protected function setup()
    {
        $this->viewtype = 'week';

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("last " . $this->dayMap[$this->firstDayOfWeek]);
        if ($this->requestedDate->format('w') <> $this->firstDayOfWeek) {
            $prevClone->modify("-7 days");
        }
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $prevClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
        $nextClone = clone $this->requestedDate;
        $nextClone->modify("next " . $this->dayMap[$this->firstDayOfWeek]);
        $this->navigation['next'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $nextClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
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
                        'start' => clone $this->startDate,
                        'end' => clone $this->endDate,
                        'filtercats' => $this->selectedCategories,
                        'date' => $this->requestedDate,
                        'userfilter' => $this->userFilter));
            // create and return template
            $this->view
                    ->assign('navBar', $this->navBar)
                    ->assign('navigation', $this->navigation)
                    ->assign('eventsByDate', $eventsByDate)
                    ->assign('startDate', $this->startDate->format('Ymd'))
                    ->assign('endDate', $this->endDate->format('Ymd'))
                    ->assign('requestedDate', $this->requestedDate->format('Ymd'));
        }
        return $this->view->fetch($this->template);
    }

}