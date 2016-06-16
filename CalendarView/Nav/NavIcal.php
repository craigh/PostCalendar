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
use Zikula_ModUrl;
use ZLanguage;
use SecurityUtil;
class NavIcal extends AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = 'ical';
        $this->imageTitleText = $this->view->__('iCal Feed');
        $this->displayText = $this->view->__('iCal');
    }

    /**
     * provide the image params
     * 
     * @return array 
     */
    protected function getImageParams()
    {
        return array(
            'modname' => 'PostCalendar',
            'src' => 'ical.png',
            'alt' => $this->displayText,
        );
    }

    /**
     * Set the Zikula_ModUrl 
     */
    protected function setUrl()
    {
        $this->url = new Zikula_ModUrl('PostCalendar', 'user', 'display', ZLanguage::getLanguageCode());
    }
    
    /**
     * Set the anchor tag 
     */
    protected function setAnchorTag()
    {
        $class = implode(' ', $this->cssClasses);
        $display = $this->useDisplayImage ? $this->imageHtml : $this->displayText;
        if ($this->navBarType == "buttonbar") {
            $this->anchorTag = "<a href='" . $this->getUrl() . "' id='pcnav_ical' class='$class' title='$this->imageTitleText'>$display</a>";
        } else {
            $this->anchorTag = "<a href='#pcnav_ical_dialog' id='pcnav_ical' class='$class' title='$this->imageTitleText'>$display</a>";
        }
    }

}