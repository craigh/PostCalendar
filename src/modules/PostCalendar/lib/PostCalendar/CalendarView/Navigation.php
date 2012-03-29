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
    private $cacheTag = 'nav';
    private $template = 'user/navigation.tpl';
    protected $view;
    protected $requestedDate;
    protected $userFilter;
    protected $selectedCategories;
    protected $viewtype;
    protected $config;
    protected $viewtypeselector = array();
    
    public function __construct(Zikula_View $view, $requestedDate, $userFilter, $selectedCategories, $viewtype, $config)
    {
        $this->view = $view;
        $this->requestedDate = $requestedDate;
        $this->userFilter = $userFilter;
        $this->selectedCategories = $selectedCategories;
        $this->viewtype = $viewtype;
        $this->config = $config;

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
        $today = new DateTime();
        $this->view->assign('navItems', $this->navItems)
                ->assign('todayDate', $today->format('Ymd'))
                ->assign('currentjumpdate', $this->requestedDate->format('Y-m-d'))
                ->assign('selectedcategories', $this->selectedCategories)
                ->assign('viewtypeselector', $this->viewtypeselector)
                ->assign('func', $this->view->getRequest()->query->get('func', $this->view->getRequest()->request->get('func', 'display')))
                ->assign('viewtypeselected', $this->viewtype);
        return $this->view->fetch($this->template);
    }

}