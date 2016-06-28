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

class NavAdmin extends AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = null;
        $this->imageTitleText = $this->view->__('Admin');
        $this->displayText = $this->view->__('Admin');
    }

    /**
     * provide the image params
     * @return array
     */
    protected function getImageParams()
    {
        return array(
            'modname' => 'core',
            'set' => 'icons/small',
            'src' => 'configure.png');
    }

    /**
     * Set the Zikula_ModUrl 
     */
    protected function setUrl()
    {
        $this->url = new Zikula_ModUrl('PostCalendar', 'admin', 'listevents', ZLanguage::getLanguageCode());
    }

    /**
     * Set the anchortag
     */
    protected function setAnchorTag()
    {
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            parent::setAnchorTag();
        } else {
            $this->anchorTag = null;
        }
    }

    /**
     * Set the radio input 
     */
    protected function setRadio()
    {
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            parent::setRadio();
        } else {
            $this->radio = null;
        }
    }

}