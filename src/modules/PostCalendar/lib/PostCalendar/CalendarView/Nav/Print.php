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
class PostCalendar_CalendarView_Nav_Print extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    public function setup()
    {
        $this->viewtype = $this->view->getRequest()->request->get('viewtype', $this->view->getRequest()->query->get('viewtype', $this->defaultViewtype));
        $this->imageTitleText = $this->view->__('Print View');
        $this->displayText = $this->view->__('Print');
    }

    protected function getImageParams()
    {
        return array(
            'modname' => 'core',
            'set' => 'icons/small',
            'src' => 'printer.png');
    }

    protected function setUrl()
    {
        $this->url = new Zikula_ModUrl('PostCalendar', 'user', 'display', ZLanguage::getLanguageCode(), array(
                    'viewtype' => $this->viewtype,
                    'date' => $this->date->format('Ymd'),
                    'pc_username' => $this->userFilter,
                    'theme' => 'Printer'));
    }

}