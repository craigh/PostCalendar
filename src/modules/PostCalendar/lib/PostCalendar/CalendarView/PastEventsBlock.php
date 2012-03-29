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
class PostCalendar_CalendarView_PastEventsBlock extends PostCalendar_CalendarView_AbstractDays
{

    protected $blockVars = array();
    protected $bid;

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
        $this->template = 'blocks/pastevents.tpl';
    }

    protected function setDates()
    {
        $this->startDate = new DateTime();
        if ($this->blockVars['pcbeventsrange'] == 0) {
            $this->startDate->modify("January 1, 1970");
        } else {
            $this->startDate->modify("-{$this->blockVars['pcbeventsrange']} months");
        }
        $this->endDate = new DateTime();
        $this->endDate->modify("-1 day"); // yesterday
    }
    
    protected function setup()
    {
        
    }

    public function render()
    {
        if (!$this->isCached()) {
            // Load the events
            $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
                        'start' => $this->startDate,
                        'end' => $this->endDate,
                        'filtercats' => $this->selectedCategories,
                        'sort' => 'DESC'));
            // create and return template
            $this->view
                    ->assign('eventsByDate', $eventsByDate);
        }

        return $this->view->fetch($this->template);
    }

}