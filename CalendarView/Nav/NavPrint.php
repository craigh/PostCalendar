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
class NavPrint extends AbstractItemBase
{
    /**
     * event id
     * @var integer 
     */
    private $eid;

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = $this->view->getRequest()->request->get('viewtype', $this->view->getRequest()->query->get('viewtype', $this->defaultViewtype));
        $this->eid = $this->view->getRequest()->request->get('eid', $this->view->getRequest()->query->get('eid', null));
        $this->imageTitleText = $this->view->__('Print View');
        $this->displayText = $this->view->__('Print');
    }

    /**
     * Provide the image params
     * 
     * @return array 
     */
    protected function getImageParams()
    {
        return array(
            'modname' => 'core',
            'set' => 'icons/small',
            'src' => 'printer.png');
    }

    /**
     * Set the Zikula_ModUrl 
     */
    protected function setUrl()
    {
        $args = array(
            'viewtype' => $this->viewtype,
            'userfilter' => $this->userFilter,
            'theme' => 'Printer');
        if (($this->viewtype == 'event') && (isset($this->eid))) {
            $args['eid'] = $this->eid;
        } else {
            $args['date'] = $this->date->format('Ymd');            
        }
        $this->url = new Zikula_ModUrl('PostCalendar', 'user', 'display', ZLanguage::getLanguageCode(), $args);
    }

}