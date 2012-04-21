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
     * Provide the rendered navigation html for CalendarViews 
     */

    /**
     * Array of objects/null containing nav items
     * @var mixed array of PostCalendar_CalendarView_Nav_AbstractItemBase objects or null 
     */
    private $navItems = array();

    /**
     * The template name
     * @var string
     */
    private $template = 'user/navigation.tpl';

    /**
     * Zikula_View instance
     * @var Zikula_View
     */
    private $view;

    /**
     * The requested date
     * @var DateTime
     */
    public $requestedDate;

    /**
     * The selected userFilter
     * @var integer
     */
    private $userFilter;

    /**
     * The selected categories
     * @var array
     */
    private $selectedCategories;

    /**
     * The selected PostCalendar viewtype
     * @var type 
     */
    private $viewtype;

    /**
     * config options for Navigation display
     * @var boolean 
     */
    public $useFilter = true;

    /**
     * Display the jumpdate selector?
     * @var boolean 
     */
    public $useJumpDate = true;

    /**
     * Display the Navbar links?
     * @var boolean 
     */
    public $useNavBar = true;

    /**
     * Navbar type (currently 'plain' or 'buttonbar')
     * @var type 
     */
    public $navBarType = 'plain';

    /**
     * The array to render as options in the viewtype selector
     * @var array
     */
    public $viewtypeselector = array();

    /**
     * Constructor
     * 
     * @param Zikula_View $view
     * @param DateTime $requestedDate
     * @param integer $userFilter
     * @param array $selectedCategories
     * @param string $viewtype
     * @param array $config (optional)
     */
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

        // construct an array of PostCalendar_CalendarView_Nav_AbstractItemBase objects
        // to render as a navbar
        $allowedViews = ModUtil::getVar('PostCalendar', 'pcAllowedViews');
        array_unshift($allowedViews, 'admin'); // add 'admin' view for nav purposes (always available to Admin)
        unset($allowedViews[array_search('event', $allowedViews)]); // remove 'event' view for nav purposes
        foreach ($allowedViews as $navType) {
            $class = 'PostCalendar_CalendarView_Nav_' . ucfirst($navType);
            $this->navItems[] = new $class($this->view, ($navType == $viewtype), $this->navBarType);
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

    /**
     * Render the navigation view
     * 
     * @return string
     */
    public function render()
    {
        // caching shouldn't be used because the date and other filter settings may change
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
        $categories = array();
        // generate css classnames for each category
        $stylesheet = "<style type='text/css'>\n";
        foreach ($catregistry as $regname => $catid) {
            $categories[$regname] = CategoryUtil::getSubCategories($catid);
            foreach ($categories[$regname] as $category) {
                if (isset($category['__ATTRIBUTES__']['color'])) {
                    $stylesheet .= ".pccategories_{$category['id']},\n.pccategories_selector_{$category['id']} {\n";
                    $stylesheet .= "    background-color: {$category['__ATTRIBUTES__']['color']};\n}\n";
                }
            }
        }
        $stylesheet .= "</style>\n";
        if ($this->navBarType == 'buttonbar') {
            PageUtil::addVar("javascript", "jquery");
            PageUtil::addVar("javascript", "jquery-ui");
            PageUtil::addVar("javascript", "modules/PostCalendar/javascript/postcalendar-user-navigation.js");
            PageUtil::addVar("jsgettext", "module_postcalendar_js:PostCalendar");
            $jQueryTheme = 'overcast';
            JQueryUtil::loadTheme($jQueryTheme);
            PageUtil::addVar("stylesheet", "modules/PostCalendar/style/jquery-overrides.css");
            $this->view->assign('pcCategories', $categories);
        }
        PageUtil::addVar('header', $stylesheet);
        $this->view->assign('navigationObj', $this);
        return $this->view->fetch($this->template);
    }

    /**
     * Configure the navigation view
     * 
     * @param array $args 
     */
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
        if (isset($args['navbartype'])) {
            $this->navBarType = $args['navbartype'];
        }
    }

    /**
     * Get the NavItems
     * 
     * @return array of PostCalendar_CalendarView_Nav_AbstractItemBase objects
     */
    public function getNavItems()
    {
        return $this->navItems;
    }

    /**
     * Get the selected categories
     * 
     * @return array 
     */
    public function getSelectedCategories()
    {
        return $this->selectedCategories;
    }

    /**
     * Get the selected PostCalendar viewtype
     * 
     * @return string 
     */
    public function getViewtype()
    {
        return $this->viewtype;
    }

    /**
     * Get the userFilter
     * 
     * @return integer 
     */
    public function getUserFilter()
    {
        return $this->userFilter;
    }

}