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
    /**
     * @abstract
     * AbstractCalendarViewBase
     * 
     * An abstract class to construct Calendar View objects.
     */

    const SUNDAY_IS_FIRST = 0;
    const MONDAY_IS_FIRST = 1;
    const SATURDAY_IS_FIRST = 6;

    /**
     * Zikula_View instance
     * @var object 
     */
    protected $view;

    /**
     * the full name (inc directory) of the template to render
     * @var string 
     */
    protected $template;

    /**
     * The template's cacheTag
     * @var string 
     */
    protected $cacheTag;

    /**
     * The template's cacheId (cacheTag|userId)
     * @var string 
     */
    protected $cacheId;

    /**
     * The selected PostCalendar viewtype
     * @var string 
     */
    protected $viewtype;

    /**
     * Reqested date
     * @var DateTime object 
     */
    protected $requestedDate;

    /**
     * Filterview by userId or global/private
     * @var type 
     */
    protected $userFilter;

    /**
     * The current user
     * @var integer 
     */
    protected $currentUser;

    /**
     * Selected catgory ids
     * @var array
     */
    protected $selectedCategories = array();

    /**
     * Event Eid
     * @var integer 
     */
    protected $eid;

    /**
     * The rendered navbar
     * @var string
     */
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

    /**
     * Constructor
     * 
     * @param Zikula_View $view
     * @param DateTime $requestedDate
     * @param integer $userFilter
     * @param array $categoryFilter
     * @param integer $eid 
     */
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

    /**
     * @abstract
     * Setup the calendarView 
     */
    abstract protected function setup();

    /**
     * @abstract
     * Set the template for this calendarView 
     */
    abstract protected function setTemplate();

    /**
     * @abstract
     * Set the cacheTag for this calendarView 
     */
    abstract protected function setCacheTag();

    /**
     * @abstract
     * Render the calendarView 
     */
    abstract public function render();

    /**
     * optionally provide config data to the navBar
     * override in child class to modify
     * @return array
     */
    protected function getNavBarConfig()
    {
        return array(
            'navbartype' => ModUtil::getVar('PostCalendar', 'pcNavBarType'));
    }

    /**
     * Check if this calendarView is cached
     * @param string $template
     * @return boolean 
     */
    protected function isCached($template = null)
    {
        if (isset($template)) {
            return $this->view->is_cached($template);
        } else {
            return (isset($this->template) && $this->view->is_cached($this->template));
        }
    }

    /**
     * Set the selectedCategories based on provided user selections via html select
     * @param array $filtercats 
     */
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