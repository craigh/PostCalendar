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
abstract class PostCalendar_CalendarView_AbstractCalendarViewBase extends Zikula_AbstractHelper
{

    const SUNDAY_IS_FIRST = 0;
    const MONDAY_IS_FIRST = 1;
    const SATURDAY_IS_FIRST = 6;

    /**
     * Zikula_View instance
     * @var object 
     */
    protected $view;
    protected $template;
    protected $cacheTag;
    protected $cacheId;
    protected $viewtype;

    /**
     * Reqested date
     * @var DateTime object 
     */
    protected $requestedDate;

    protected $userFilter;
    protected $currentUser;
    protected $selectedCategories = array();
    protected $eid;
    
    protected $navBar;

    /**
     * Array of navigation links
     * @var array 
     */
    protected $navigation = array(
        'previous' => '',
        'next' => '');

    /**
     * collection of calendar events
     * @var collection of objects 
     */
    protected $events;

    public function __construct(Zikula_View $view, $requestedDate, $userFilter, $categoryFilter, $eid = null)
    {
        $this->domain = ZLanguage::getModuleDomain('PostCalendar');
        $this->view = $view;
        $this->currentUser = UserUtil::getVar('uid');
        
        $this->requestedDate = $requestedDate;

        $this->userFilter = $userFilter;
        $this->setSelectedCategories($categoryFilter);
        
        $this->eid = $eid;

        $this->setCacheTag();
        $this->cacheId = $this->cacheTag . '|' . $this->currentUser;
        $this->view->setCacheId($this->cacheId);
        
        $this->setTemplate();

        $this->setup();
        
        $navBar = new PostCalendar_CalendarView_Navigation($this->view, $this->requestedDate, $this->userFilter, $this->selectedCategories, $this->viewtype, $this->getNavBarConfig());
        $this->navBar = $navBar->render();
    }

    abstract protected function setup();

    abstract protected function setTemplate();

    abstract protected function setCacheTag();

    abstract public function render();
    
    /** 
     * optionally provide config date to the navBar
     * override in child class to modify
     * @return array
     */
    protected function getNavBarConfig()
    {
        return array();
    }

    protected function isCached($template = null)
    {
        if (isset($template)) {
            return $this->view->is_cached($this->template);
        } else {
            return (isset($this->template) && $this->view->is_cached($this->template));
        }
    }

    private function setSelectedCategories($filtercats)
    {
        if (is_array($filtercats)) {
            foreach ($filtercats as $propid) {
                if (is_array($propid)) { // select multiple used
                    foreach ($propid as $id) {
                        if ($id > 0) {
                            $this->selectedCategories[] = $id;
                        }
                    }
                } elseif (strstr($propid, ',')) { // category Zikula.UI.SelectMultiple used
                    $ids = explode(',', $propid);
                    // no propid should be '0' in this case
                    foreach ($ids as $id) {
                        $this->selectedCategories[] = $id;
                    }
                } else { // single selectbox used
                    if ($propid > 0) {
                        $this->selectedCategories[] = $propid;
                    }
                }
            }
        }
    }
}