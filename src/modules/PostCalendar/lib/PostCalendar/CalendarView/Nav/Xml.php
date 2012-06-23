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
class PostCalendar_CalendarView_Nav_Xml extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    /**
     * Setup the navitem 
     */
    public function setup()
    {
        $this->viewtype = 'xml';
        $this->imageTitleText = $this->view->__('RSS Feed');
        $this->displayText = $this->view->__('RSS');
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
            'src' => 'feed.gif');
    }

    /**
     * Set the Zikula_ModUrl
     */
    protected function setUrl()
    {
        $this->url = new Zikula_ModUrl('PostCalendar', 'user', 'display', ZLanguage::getLanguageCode(), array(
                    'viewtype' => $this->viewtype,
                    'date' => $this->date->format('Ymd'),
                    'theme' => 'rss'));
    }

    /**
     * Set the anchortag 
     */
    protected function setAnchorTag()
    {
        if (!ModUtil::getVar('Theme', 'render_expose_template')) {
            $this->prepRss();
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
        if (!ModUtil::getVar('Theme', 'render_expose_template')) {
            $this->prepRss();
            parent::setRadio();
        } else {
            $this->radio = null;
        }
    }

    /**
     * Setup the page properly to provide an RSS feed 
     */
    private function prepRss()
    {
        $rsslink = $this->getUrl();
        $rsslink = DataUtil::formatForDisplay($rsslink);
        $sitename = System::getVar('sitename');
        $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('PostCalendar'));
        $modname = $modinfo['displayname'];
        $title = DataUtil::formatForDisplay($sitename . " " . $modname);
        $pagevarvalue = "<link rel='alternate' href='$rsslink' type='application/rss+xml' title='$title' />";
        PageUtil::addVar("header", $pagevarvalue);
    }

}