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

    /**
     * The block vars
     * @var array
     */
    protected $blockVars = array();

    /**
     * The block id
     * @var integer 
     */
    protected $bid;

    /**
     * Constructor
     * 
     * @param Zikula_View $view
     * @param DateTime $requestedDate
     * @param integer $userFilter
     * @param array $categoryFilter
     * @param array $blockinfo 
     */
    function __construct(Zikula_View $view, $requestedDate, $userFilter, $categoryFilter, $blockinfo)
    {
        $this->bid = $blockinfo['bid'];
        $this->blockVars = BlockUtil::varsFromContent($blockinfo['content']);
        if (!isset($categoryFilter)) {
            $categoryFilter = $this->blockVars['pcbfiltercats'];
        }
        parent::__construct($view, $requestedDate, $userFilter, $categoryFilter);
    }

    /**
     * Set the cacheTag 
     */
    protected function setCacheTag()
    {
        $this->cacheTag = $this->bid;
    }

    /**
     * Set the template 
     */
    protected function setTemplate()
    {
        $this->template = 'blocks/pastevents.tpl';
    }

    /**
     * Set the date range of this view 
     */
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

    /**
     * provide required setup 
     */
    protected function setup()
    {
        
    }

    /**
     * Render the view
     * @return string 
     */
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