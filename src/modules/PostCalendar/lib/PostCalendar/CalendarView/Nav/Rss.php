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
class PostCalendar_CalendarView_Nav_Rss extends PostCalendar_CalendarView_Nav_AbstractItemBase
{

    public function setup()
    {
        $this->viewtype = 'xml';
        $this->imageTitleText = $this->view->__('RSS Feed');
        $this->displayText = $this->view->__('RSS');
    }

    protected function getImageParams()
    {
        return array(
            'modname' => 'PostCalendar',
            'src' => 'feed.gif');
    }

    protected function setUrl()
    {
        $this->url = ModUtil::url('PostCalendar', 'user', 'display', array(
                    'viewtype' => $this->viewtype,
                    'theme' => 'rss'));
    }

    protected function setAnchorTag()
    {
        if (!ModUtil::getVar('Theme', 'render_expose_template')) {
            $rsslink = $this->getUrl();
            $rsslink = DataUtil::formatForDisplay($rsslink);
            $sitename = System::getVar('sitename');
            $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('PostCalendar'));
            $modname = $modinfo['displayname'];
            $title = DataUtil::formatForDisplay($sitename . " " . $modname);
            $pagevarvalue = "<link rel='alternate' href='$rsslink' type='application/rss+xml' title='$title' />";
            PageUtil::addVar("header", $pagevarvalue);
            parent::setAnchorTag();
        } else {
            $this->anchorTag = null;
        }
    }

}