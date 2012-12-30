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
class PostCalendar_CalendarView_Nav_Create extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = null;
        $this->imageTitleText = $this->view->__('Submit New Event');
        $this->displayText = $this->view->__('Add');
        $this->displayImageOn = 'add_on.gif';
        $this->displayImageOff = 'add.gif';
    }

    /**
     * Set the Zikula_ModUrl
     */
    protected function setUrl()
    {
        $this->url = new Zikula_ModUrl('PostCalendar', 'event', 'create', ZLanguage::getLanguageCode(), array(
                    'date' => $this->date->format('Ymd')));
    }

    /**
     * Set the anchortag 
     */
    protected function setAnchorTag()
    {
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
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
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            parent::setRadio();
        } else {
            $this->radio = null;
        }
    }

}