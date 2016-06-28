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

namespace Zikula\PostCalendarModule\CalendarView;

use Zikula\PostCalendarModule\Entity\CalendarEventEntity;
use BlockUtil;
use CategoryRegistryUtil;
use SecurityUtil;
use UserUtil;
use LogUtil;
use ModUtil;
use CategoryUtil;
use DateTime;
use DataUtil;
use ZLanguage;

class CalendarViewFeaturedEventBlock extends CalendarViewEvent
{

    /**
     * The block vars
     * @var array
     */
    protected $blockVars = array();

    /**
     * The block information
     * @var type 
     */
    protected $blockInfo;

    /**
     * Constructor
     * 
     * @param Zikula_View $view
     * @param DateTime $requestedDate
     * @param integer $userFilter
     * @param array $categoryFilter
     * @param array $blockinfo 
     */
    function __construct(\Zikula_View $view, $requestedDate, $userFilter, $categoryFilter, $blockinfo)
    {
        $this->blockInfo = $blockinfo;
        $this->blockVars = BlockUtil::varsFromContent($blockinfo['content']);
        $this->blockVars['showcountdown'] = empty($this->blockVars['showcountdown']) ? false : true;
        $this->blockVars['hideonexpire'] = empty($this->blockVars['hideonexpire']) ? false : true;
        parent::__construct($view, $requestedDate, $userFilter, $categoryFilter, $this->blockVars['eid']);
    }

    /**
     * Set the cacheTag 
     */
    protected function setCacheTag()
    {
        $this->cacheTag = $this->blockInfo['bid'];
    }

    /**
     * Set the template 
     */
    protected function setTemplate()
    {
        $this->template = 'blocks/featuredevent.tpl';
    }

    /**
     * Setup the view 
     */
    public function setup()
    {
        parent::setup();
        $alleventdates = ModUtil::apiFunc('ZikulaPostCalendarModule', 'event', 'getEventOccurances', array('event' => $this->event)); // gets all FUTURE occurances
        // assign next occurance to eventStart
        $newEventStart = DateTime::createFromFormat('Y-m-d', array_shift($alleventdates));
        if (!empty($newEventStart)) { // createFromFormat returns false on failure
            $this->event['eventStart'] = $newEventStart;
            $today = new DateTime();
            $datedifference = $today->diff($this->event['eventStart']);
            $this->event['datedifference'] = $datedifference->format('%r%a');
        } else {
            $this->event['datedifference'] = -1;
        }

        $this->event['showcountdown'] = $this->blockVars['showcountdown'];

        $this->event['showhiddenwarning'] = false; // default to false
        if ($this->blockVars['hideonexpire'] && $this->event['datedifference'] < 0) {
            //return false;
            $this->event['showhiddenwarning'] = true;
            $this->blockInfo['title'] = NULL;
        }
    }

    /**
     * Render the view
     * 
     * @return mixed boolena/string 
     */
    public function render()
    {
        // caching won't help much in this case because security check comes after
        // fetch from db, so don't use isCached, just fetch after normal routine.
        // is event allowed for this user?
        if (($this->event['sharing'] == CalendarEventEntity::SHARING_PRIVATE
                && $this->event['aid'] != $this->currentUser
                && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))
                || ((!SecurityUtil::checkPermission('PostCalendar::Event', "{$this->event['title']}::{$this->event['eid']}", ACCESS_OVERVIEW))
                || (!CategoryUtil::hasCategoryAccess($this->event['categories'], 'PostCalendar')))) {
            // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
            // or if specific event is permission controlled or if Category is denied
            return false;
        }

        $this->view->assign('loaded_event', $this->event);
        $this->view->assign('thisblockid', $this->blockInfo['bid']);

        $this->blockInfo['content'] = $this->view->fetch($this->template);
        return $this->blockInfo;
    }

}