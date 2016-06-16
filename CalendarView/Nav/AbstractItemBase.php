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

namespace Zikula\PostCalendarModule\CalendarView\Nav;

use Zikula\PostCalendarModule\Helper\PostCalendarUtil;
use DateTime;
use ModUtil;
use Zikula_ModUrl;
use ZLanguage;
use SecurityUtil;

abstract class AbstractItemBase
{

    /**
     * Zikula_View instance
     * @var object
     */
    protected $view;

    /**
     * The selected PostCalendar viewtype
     * @var string
     */
    protected $viewtype;

    /**
     * The selected navBarType
     * @see PostCalendar_igation
     * @var string 
     */
    protected $navBarType;

    /**
     * The default PostCalendar viewtype
     * @var string
     */
    protected $defaultViewtype;

    /**
     * DateTime object
     * @var DateTime
     */
    protected $date;

    /**
     * DateTime object for current DateTime
     * @var DateTime 
     */
    protected $today;

    /**
     * Selected userFilter
     * @var integer
     */
    protected $userFilter;

    /**
     * Text to display if images disabled
     * @var string
     */
    protected $displayText;

    /**
     * Location of image for 'inactive' status
     * @var string
     */
    protected $displayImageOff;

    /**
     * Location of image for 'active' status
     * @var string
     */
    protected $displayImageOn;

    /**
     * Display images in navbar?
     * @var boolean
     */
    protected $useDisplayImage = true;

    /**
     * Use 'tooltips' for titles instead of regular html
     * @var type 
     */
    protected $usePopups = false;

    /**
     * Css classes used in rendering of item 
     * @var array 
     */
    protected $cssClasses = array();

    /**
     * Text to display as image title
     * @var string
     */
    protected $imageTitleText;

    /**
     * The rendered image html
     * @var string
     */
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
     * Constructor
     * 
     * @param Zikula_View $view
     * @param boolean $selected
     * @param string $navBarType 
     */
    public function __construct(\Zikula_View $view, $selected, $navBarType)
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
        $this->date = PostCalendarUtil::getDate($jumpargs);
        $this->today = new DateTime();
        $this->userFilter = $this->view->getRequest()->request->get('userfilter', $this->view->getRequest()->query->get('userfilter', null));
        $this->useDisplayImage = (boolean)ModUtil::getVar('PostCalendar', 'enablenavimages');
        $this->usePopups = (boolean)ModUtil::getVar('PostCalendar', 'pcUsePopups');
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

    /**
     * Perform operations after the setup method is performed 
     */
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

    /**
     * Get the required image parameters
     * @return array
     */
    protected function getImageParams()
    {
        return array(
            'modname' => 'PostCalendar',
            'src' => $this->selected ? $this->displayImageOn : $this->displayImageOff,
            'alt' => $this->imageTitleText,
            'title' => $this->usePopups ? '' : $this->imageTitleText);
    }

    /**
     * Set the Zikula_ModUrl object for this item 
     */
    protected function setUrl()
    {
        $this->url = new Zikula_ModUrl('PostCalendar', 'user', 'display', ZLanguage::getLanguageCode(), array(
                    'viewtype' => $this->viewtype,
                    'date' => $this->date->format('Ymd'),
                    'userfilter' => $this->userFilter));
    }

    /**
     * get a url string
     * 
     * @see Zikula_ModUrl
     * @param boolean? $ssl
     * @param boolean? $fqurl
     * @param boolean $forcelongurl
     * @param boolean $forcelang
     * @return string 
     */
    public function getUrl($ssl = null, $fqurl = null, $forcelongurl = false, $forcelang = false)
    {
        return $this->url->getUrl($ssl, $fqurl, $forcelongurl, $forcelang);
    }

    /**
     * Set the anchor tag 
     */
    protected function setAnchorTag()
    {
        $id = strtolower($this->displayText);
        $class = implode(' ', $this->cssClasses);
        $display = $this->useDisplayImage ? $this->imageHtml : $this->displayText;
        $this->anchorTag = "<a href='" . $this->getUrl() . "' id='pcnav_{$id}' class='$class' title='$this->imageTitleText'>$display</a>";
    }

    /**
     * Render the anchortag (e.g. '<a href=...></a>')
     * 
     * @return string 
     */
    public function renderAnchorTag()
    {
        if (isset($this->anchorTag)) {
            return $this->anchorTag;
        }
    }

    /**
     * Set the radio input selector 
     */
    protected function setRadio()
    {
        $id = strtolower($this->displayText);
        $checked = $this->selected ? " checked='checked'" : "";
        $this->radio = "<input type='radio'{$checked} id='pcnav_{$id}' class='pcnav_button' name='viewtype' value='{$id}' />
            <label class='tooltips' title='$this->imageTitleText' for='pcnav_{$id}'>{$this->displayText}</label>
            <input type='hidden' id='pcnav_url_{$id}' value='{$this->getUrl(null, true)}' />";
    }

    /**
     * Render the radio input selector (e.g. '<input type='radio'... />')
     * @return string
     */
    public function renderRadio()
    {
        if (isset($this->radio)) {
            return $this->radio;
        }
    }

    /**
     * Setup the navitem 
     */
    abstract public function setup();
}