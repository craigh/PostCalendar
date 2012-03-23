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
class PostCalendar_CalendarView_FeaturedEventBlock extends PostCalendar_CalendarView_Event
{

    protected $blockVars = array();
    protected $blockInfo;

    function __construct(Zikula_View $view, $requestedDate, $userFilter, $categoryFilter, $blockinfo)
    {
        $this->blockInfo = $blockinfo;
        $this->blockVars = BlockUtil::varsFromContent($blockinfo['content']);
        $this->blockVars['showcountdown'] = empty($this->blockVars['showcountdown']) ? false : true;
        $this->blockVars['hideonexpire'] = empty($this->blockVars['hideonexpire']) ? false : true;
        parent::__construct($view, $requestedDate, $userFilter, $categoryFilter, $this->blockVars['eid']);
    }

    protected function setCacheTag()
    {
        $this->cacheTag = $this->blockInfo['bid'];
    }

    protected function setTemplate()
    {
        $this->template = 'blocks/featuredevent.tpl';
    }

    public function render()
    {
        // caching won't help much in this case because security check comes after
        // fetch from db, so don't use isCached, just fetch after normal routine.
        // is event allowed for this user?
        if (($this->event['sharing'] == PostCalendar_Entity_CalendarEvent::SHARING_PRIVATE
                && $this->event['aid'] != $this->currentUser
                && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))
                || ((!SecurityUtil::checkPermission('PostCalendar::Event', "{$this->event['title']}::{$this->event['eid']}", ACCESS_OVERVIEW))
                || (!CategoryUtil::hasCategoryAccess($this->event['categories'], 'PostCalendar')))) {
            // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
            // or if specific event is permission controlled or if Category is denied
            return false;
        }

        $alleventdates = ModUtil::apiFunc('PostCalendar', 'event', 'getEventOccurances', $this->event); // gets all FUTURE occurances
        // assign next occurance to eventDate
        $this->event['eventDate'] = array_shift($alleventdates);

        $this->event['showcountdown'] = false; // default to false
        if ($this->blockVars['showcountdown']) {
            $datedifference = DateUtil::getDatetimeDiff_AsField(DateUtil::getDatetime(null, '%F'), $this->event['eventDate'], 3);
            $this->event['datedifference'] = round($datedifference);
            $this->event['showcountdown'] = true;
        }
        $this->event['showhiddenwarning'] = false; // default to false
        if ($this->blockVars['hideonexpire'] && $this->event['datedifference'] < 0) {
            //return false;
            $this->event['showhiddenwarning'] = true;
            $this->blockInfo['title'] = NULL;
        }

        $this->view->assign('loaded_event', $this->event);
        $this->view->assign('thisblockid', $this->blockInfo['bid']);

        $this->blockInfo['content'] = $this->view->fetch($this->template);
        return $this->blockInfo;
    }

}