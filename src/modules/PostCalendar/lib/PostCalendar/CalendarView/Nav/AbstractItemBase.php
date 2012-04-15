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
    protected $navbarType;
    protected $defaultViewtype;

    /**
     * DateTime object
     * @var object
     */
    protected $date;
    protected $today;
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
     * Url object
     * @var Zikula_ModUrl 
     */
    protected $url;

    /**
     * Fully formed anchor tag e.g. <a href=...>foo</a>
     * @var string
     */
    protected $anchorTag;
    
    /**
     * Fully formed radio button html e.g. <input type="radio"...
     * @var type 
     */
    protected $radio;

    /**
     * get a url string
     * @see Zikula_ModUrl
     * @param boolean? $ssl
     * @param boolean? $fqurl
     * @param boolean $forcelongurl
     * @param boolean $forcelang
     * @return string 
     */
    public function getUrl($ssl = null, $fqurl = null, $forcelongurl = false, $forcelang=false)
    {
        return $this->url->getUrl($ssl, $fqurl, $forcelongurl, $forcelang);
    }

    public function renderAnchorTag()
    {
        if (isset($this->anchorTag)) {
            return $this->anchorTag;
        }
    }

    public function __construct(Zikula_View $view, $selected, $navBarType)
    {
        $this->view = $view;
        $this->selected = $selected;
        $this->navBarType = $navBarType;
        include_once $this->view->_get_plugin_filepath('function', 'img');
        $jumpargs = array(
            'date' => $this->view->getRequest()->request->get('date', $this->view->getRequest()->query->get('date', null)),
            'jumpday' => $this->view->getRequest()->request->get('jumpDay', $this->view->getRequest()->query->get('jumpDay', null)),
            'jumpmonth' => $this->view->getRequest()->request->get('jumpMonth', $this->view->getRequest()->query->get('jumpMonth', null)),
            'jumpyear' => $this->view->getRequest()->request->get('jumpYear', $this->view->getRequest()->query->get('jumpYear', null)));
        $this->date = PostCalendar_Util::getDate($jumpargs);
        $this->today = new DateTime();
        $this->userFilter = $this->view->getRequest()->request->get('userfilter', $this->view->getRequest()->query->get('userfilter', null));
        $this->useDisplayImage = (boolean)ModUtil::getVar('PostCalendar', 'enablenavimages');
        $this->usePopups = (boolean)ModUtil::getVar('PostCalendar', 'pcUsePopups');
        $this->openInNewWindow = (boolean)ModUtil::getVar('PostCalendar', 'pcEventsOpenInNewWindow');
        $this->defaultViewtype = ModUtil::getVar('PostCalendar', 'pcDefaultView');
        $this->setup();
        if ($this->navBarType == 'buttonbar') {
            $this->setUrl();
            $this->setRadio();        
        } else {
            $this->postSetup();
            $this->setUrl();
            $this->setAnchorTag();
        }
    }

    private function postSetup()
    {
        $params = $this->getImageParams();
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

    protected function getImageParams()
    {
        return array(
            'modname' => 'PostCalendar',
            'src' => $this->selected ? $this->displayImageOn : $this->displayImageOff,
            'alt' => $this->imageTitleText,
            'title' => $this->usePopups ? '' : $this->imageTitleText);
    }

    protected function setUrl()
    {
        $this->url = new Zikula_ModUrl('PostCalendar', 'user', 'display', ZLanguage::getLanguageCode(), array(
                    'viewtype' => $this->viewtype,
                    'date' => $this->date->format('Ymd'),
                    'userfilter' => $this->userFilter));
    }

    protected function setAnchorTag()
    {
        $class = implode(' ', $this->cssClasses);
        $display = $this->useDisplayImage ? $this->imageHtml : $this->displayText;
        $this->anchorTag = "<a href='" . $this->getUrl() . "' class='$class' title='$this->imageTitleText'>$display</a>";
    }
    
    protected function setRadio()
    {
        $id = strtolower($this->displayText);
        $checked = $this->selected ? " checked='checked'" : "";
        $this->radio = "<input type='radio'{$checked} id='pcnav_{$id}' class='pcnav_button' name='viewtype' value='{$id}' />
            <label for='pcnav_{$id}'>{$this->displayText}</label>
            <input type='hidden' id='pcnav_url_{$id}' value='{$this->getUrl(null, true)}' />";
    }
    
    public function renderRadio()
    {
        if (isset($this->radio)) {
            return $this->radio;
        }
    }

    abstract public function setup();
}