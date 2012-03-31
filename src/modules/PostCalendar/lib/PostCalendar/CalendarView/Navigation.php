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
class PostCalendar_CalendarView_Navigation
{

    /**
     * Array of objects/null containing nav items
     * @var mixed PostCalendar_CalendarView_Nav_AbstractItem or null 
     */
    private $navItems = array();
    private $template = 'user/navigation.tpl';
    private $view;
    public $today;
    public $requestedDate;
    private $userFilter;
    private $selectedCategories;
    private $viewtype;

    /**
     * config options for Navigation display
     * @var boolean 
     */
    public $useFilter = true;
    public $useJumpDate = true;
    public $useNavBar = true;
    public $viewtypeselector = array();

    public function __construct(Zikula_View $view, $requestedDate, $userFilter, $selectedCategories, $viewtype, $config = null)
    {
        $this->view = $view;
        $this->requestedDate = $requestedDate;
        $this->userFilter = $userFilter;
        $this->selectedCategories = $selectedCategories;
        $this->viewtype = $viewtype;
        if (isset($config) && !empty($config)) {
            $this->configure($config);
        }
        $this->today = new DateTime();

        $allowedViews = ModUtil::getVar('PostCalendar', 'pcAllowedViews');
        array_unshift($allowedViews, 'admin'); // add 'admin' view for nav purposes (always available to Admin)
        unset($allowedViews[array_search('event', $allowedViews)]); // remove 'event' view for nav purposes
        foreach ($allowedViews as $navType) {
            $class = 'PostCalendar_CalendarView_Nav_' . ucfirst($navType);
            $this->navItems[] = new $class($this->view, ($navType == $viewtype));
        }
        $viewtypeSelectorData = array('day' => $this->view->__('Day'),
            'week' => $this->view->__('Week'),
            'month' => $this->view->__('Month'),
            'year' => $this->view->__('Year'),
            'list' => $this->view->__('List View'));
        foreach ($viewtypeSelectorData as $key => $text) {
            if (in_array($key, $allowedViews)) {
                $this->viewtypeselector[$key] = $text;
            }
        }
    }

    public function render()
    {
        // caching shouldn't be used because the date and other filter settings may change
        $this->view->assign('navigationObj', $this);
        return $this->view->fetch($this->template);
    }

    private function configure($args)
    {
        if (isset($args['filter'])) {
            $this->useFilter = $args['filter'];
        }
        if (isset($args['jumpdate'])) {
            $this->useJumpDate = $args['jumpdate'];
        }
        if (isset($args['navbar'])) {
            $this->useNavBar = $args['navbar'];
        }
    }

    public function getNavItems()
    {
        return $this->navItems;
    }

    public function getSelectedCategories()
    {
        return $this->selectedCategories;
    }

    public function getViewtype()
    {
        return $this->viewtype;
    }

    public function getUserFilter()
    {
        return $this->userFilter;
    }

}