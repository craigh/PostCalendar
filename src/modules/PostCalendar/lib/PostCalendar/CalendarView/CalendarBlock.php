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
class PostCalendar_CalendarView_CalendarBlock extends PostCalendar_CalendarView_Month
{

    protected $blockVars = array();
    protected $bid;
    protected $todayTemplate;
    protected $upcomingTemplate;
    protected $linksTemplate;

    function __construct(Zikula_View $view, $requestedDate, $userFilter, $categoryFilter, $blockinfo)
    {
        $this->bid = $blockinfo['bid'];
        $this->blockVars = BlockUtil::varsFromContent($blockinfo['content']);
        if (!isset($categoryFilter)) {
            $categoryFilter = $this->blockVars['pcbfiltercats'];
        } 
        parent::__construct($view, $requestedDate, $userFilter, $categoryFilter);
    }

    protected function setCacheTag()
    {
        $this->cacheTag = $this->bid;
    }

    protected function setTemplate()
    {
        $this->template = 'blocks/month.tpl';
        $this->todayTemplate = 'blocks/today.tpl';
        $this->upcomingTemplate = 'blocks/upcoming.tpl';
        $this->linksTemplate = 'blocks/calendarlinks.tpl';
    }

    protected function setDates()
    {
        parent::setDates();
        // modify endDate to selected month range after the graph is already built
        $this->endDate = clone $this->requestedDate;
        $this->endDate
                ->modify("first day of this month")
                ->modify("+" . $this->blockVars['pcbeventsrange'] . " months");
    }

    public function render()
    {
        $output = '';
        $templates_cached = true;
        if ($this->blockVars['pcbshowcalendar']) {
            if (!$this->isCached()) {
                $templates_cached = false;
            }
        }
        if ($this->blockVars['pcbeventoverview']) {
            if (!$this->isCached($this->todayTemplate)) {
                $templates_cached = false;
            }
        }
        if ($this->blockVars['pcbnextevents']) {
            if (!$this->isCached($this->upcomingTemplate)) {
                $templates_cached = false;
            }
        }
        if ($this->blockVars['pcbshowsslinks']) {
            if (!$this->isCached($this->linksTemplate)) {
                $templates_cached = false;
            }
        }
        if ($templates_cached) {
            $output .= $this->view->fetch($this->template);
            $output .= $this->view->fetch($this->todayTemplate);
            $output .= $this->view->fetch($this->upcomingTemplate);
            $output .= $this->view->fetch($this->linksTemplate);
            return $output;
        }

        // Load the events
        $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
                    'start' => $this->startDate,
                    'end' => $this->endDate,
                    'filtercats' => $this->selectedCategories,
                    'date' => $this->requestedDate,
                    'pc_username' => $this->userFilter));
        // create and return template
        $today = new DateTime();
        $countTodaysEvents = count($eventsByDate[$today->format('Y-m-d')]);
        $hideTodaysEvents = ($this->blockVars['pcbhideeventoverview'] && ($countTodaysEvents == 0)) ? true : false;

        $firstClone = clone $this->requestedDate;
        $lastClone = clone $this->requestedDate;
        $this->view
                ->assign('navigation', $this->navigation)
                ->assign('dayDisplay', $this->dayDisplay)
                ->assign('graph', $this->dateGraph)
                ->assign('eventsByDate', $eventsByDate)
                ->assign('todayDate', $today->format('Y-m-d'))
                ->assign('requestedDate', $this->requestedDate->format('Y-m-d'))
                ->assign('firstDayOfMonth', $firstClone->modify("first day of this month")->format('Y-m-d'))
                ->assign('lastDayOfMonth', $lastClone->modify("last day of this month")->format('Y-m-d'))
                ->assign('todaysEvents', $eventsByDate[$today->format('Y-m-d')])
                ->assign('hideTodaysEvents', $hideTodaysEvents)
                ->assign('blockVars', $this->blockVars);

        if ($this->blockVars['pcbshowcalendar']) {
            $output .= $this->view->fetch($this->template);
        }
        if ($this->blockVars['pcbeventoverview']) {
            $output .= $this->view->fetch($this->todayTemplate);
        }
        if ($this->blockVars['pcbnextevents']) {
            $output .= $this->view->fetch($this->upcomingTemplate);
        }
        if ($this->blockVars['pcbshowsslinks']) {
            $output .= $this->view->fetch($this->linksTemplate);
        }

        return $output;
    }

}