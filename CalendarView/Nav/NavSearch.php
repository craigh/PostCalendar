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
class NavSearch extends AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = $this->view->getRequest()->request->get('viewtype', $this->view->getRequest()->query->get('viewtype', $this->defaultViewtype));
        $this->imageTitleText = $this->view->__('Search');
        $this->displayText = $this->view->__('Search');
        $this->displayImageOn = 'search.gif';
        $this->displayImageOff = 'search.gif';
    }

    /**
     * Set the Zikula_ModUrl 
     */
    protected function setUrl()
    {
        $this->url = new Zikula_ModUrl('Search', 'user', 'main', ZLanguage::getLanguageCode());
    }

}