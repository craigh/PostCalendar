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
abstract class PostCalendar_CalendarView_Nav_AbstractItemBase
{

    /**
     * Zikula_View instance
     * @var object
     */
    protected $view;
    protected $viewtype;

    /**
     * DateTime object
     * @var object
     */
    protected $date;
    protected $userFilter;
    protected $displayText;
    protected $displayImageOff;
    protected $displayImageOn;
    protected $useDisplayImage = true;
    protected $usePopups = false;
    protected $openInNewWindow = false;
    protected $cssClasses = array();
    protected $imageTitleText;
    protected $imageHtml;

    /**
     * Is this the currently selected item
     * @var boolean
     */
    protected $selected = false;

    /**
     * The proper link e.g. value of href
     * @var string 
     */
    protected $url;

    /**
     * Fully formed anchor tag e.g. <a href=...>foo</a>
     * @var string
     */
    protected $anchorTag;

    public function getUrl()
    {
        return $this->url;
    }

    public function renderAnchorTag()
    {
        return $this->anchorTag;
    }

    public function __construct(Zikula_View $view, $selected)
    {
        $this->view = $view;
        $this->selected = $selected;
        include_once $this->view->_get_plugin_filepath('function', 'img');
        $jumpargs = array(
            'date' => $this->view->getRequest()->request->get('date', $this->view->getRequest()->query->get('date', null)),
            'jumpday' => $this->view->getRequest()->request->get('jumpDay', $this->view->getRequest()->query->get('jumpDay', null)),
            'jumpmonth' => $this->view->getRequest()->request->get('jumpMonth', $this->view->getRequest()->query->get('jumpMonth', null)),
            'jumpyear' => $this->view->getRequest()->request->get('jumpYear', $this->view->getRequest()->query->get('jumpYear', null)));
        $this->date = PostCalendar_Util::getDate($jumpargs);
        $this->userFilter = $this->view->getRequest()->request->get('pc_username', $this->view->getRequest()->query->get('pc_username', null));
        $this->useDisplayImage = (boolean)ModUtil::getVar('PostCalendar', 'enablenavimages');
        $this->usePopups = (boolean)ModUtil::getVar('PostCalendar', 'pcUsePopups');
        $this->openInNewWindow = (boolean)ModUtil::getVar('PostCalendar', 'pcEventsOpenInNewWindow');
        $this->setup();
        $this->postSetup();
        $this->setUrl();
        $this->setAnchorTag();
    }
    
    private function postSetup()
    {
        $params = array(
            'modname' => 'PostCalendar',
            'src' => $this->selected ? $this->displayImageOn : $this->displayImageOff,
            'alt' => $this->imageTitleText,
            'title' => $this->imageTitleText);
        $this->imageHtml = smarty_function_img($params, $this->view);
        if ($this->useDisplayImage) {
            $this->cssClasses[] = 'postcalendar_nav_img';
        } else {
            if ($this->selected) {
                $this->cssClasses[] = 'postcalendar_nav_text_selected';
            } else {
                $this->cssClasses[] = 'postcalendar_nav_text';
            }
        }
        if ($this->usePopups) {
            $this->cssClasses[] = 'tooltips';
        }
    }

    protected function setUrl()
    {
        $this->url = ModUtil::url('PostCalendar', 'user', 'display', array(
                        'viewtype' => $this->viewtype,
                        'date' => $this->date->format('Ymd'),
                        'pc_username' => $this->userFilter));
    }
    protected function setAnchorTag()
    {
        $class = implode(' ', $this->cssClasses);
        $display = $this->useDisplayImage ? $this->imageHtml : $this->displayText;
        $this->anchorTag = "<a href='" . $this->getUrl() . "' class='$class' title='$this->imageTitleText'>$display</a>";
    }

    abstract public function setup();
}