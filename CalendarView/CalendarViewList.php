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

class CalendarViewList extends AbstractDays
{

    /**
     * How many months to view
     * 
     * @var integer
     */
    protected $listMonths = 1;

    /**
     * Set the cacheTag 
     */
    protected function setCacheTag()
    {
        $this->cacheTag = $this->requestedDate->format('Ymd');
    }

    /**
     * Set the template 
     */
    protected function setTemplate()
    {
        $this->template = 'user/list.tpl';
    }

    /**
     * Set the date range of the view 
     */
    protected function setDates()
    {
        $this->startDate = clone $this->requestedDate;
        $this->endDate = clone $this->requestedDate;
        $this->endDate
                ->modify("+" . $this->listMonths . " months");

        $interval = new DateInterval("P1D");
        $datePeriod = new DatePeriod($this->startDate, $interval, $this->endDate);
        $i = 0;
        $week = 0;
        foreach ($datePeriod as $date) {
            $this->dateGraph[$week][$i] = $date->format('Y-m-d');
            $i++;
            if ($i > 6) {
                $i = 0;
                $week++;
            }
        }
    }

    /**
     * Setup the view 
     */
    protected function setup()
    {
        $this->viewtype = 'list';
        $this->listMonths = ModUtil::getVar('ZikulaPostCalendarModule', 'pcListMonths');

        $prevClone = clone $this->requestedDate;
        $prevClone->modify("-" . $this->listMonths . " months");
        $this->navigation['previous'] = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'date' => $prevClone->format('Ymd'),
                    'userfilter' => $this->userFilter,
                    'filtercats' => $this->selectedCategories));
        $nextClone = clone $this->requestedDate;
        $nextClone->modify("+" . $this->listMonths . " months")
                ->modify("+1 day");
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
                        'start' => $this->startDate,
                        'end' => $this->endDate,
                        'filtercats' => $this->selectedCategories,
                        'date' => $this->requestedDate,
                        'userfilter' => $this->userFilter));
            // create and return template
            $this->view
                    ->assign('navBar', $this->navBar)
                    ->assign('navigation', $this->navigation)
                    ->assign('dayDisplay', $this->dayDisplay)
                    ->assign('graph', $this->dateGraph)
                    ->assign('eventsByDate', $eventsByDate)
                    ->assign('startDate', $this->startDate)
                    ->assign('endDate', $this->endDate);
        }
        return $this->view->fetch($this->template);
    }

}