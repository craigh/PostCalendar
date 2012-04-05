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

class PostCalendar_TaggedObjectMeta_PostCalendar extends Tag_AbstractTaggedObjectMeta
{

    function __construct($objectId, $areaId, $module, $urlString = null, Zikula_ModUrl $urlObject = null)
    {
        parent::__construct($objectId, $areaId, $module, $urlString, $urlObject);

        $entityManager = ServiceUtil::getService('doctrine.entitymanager');
        $pc_event = $entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->find($this->getObjectId())->getOldArray();
        // check for permission and status
        $permission = SecurityUtil::checkPermission('PostCalendar::Event', "$pc_event[title]::$pc_event[eid]", ACCESS_OVERVIEW);
        $private = ($pc_event['sharing'] == 0 && $pc_event['aid'] != UserUtil::getVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN));
        $formats = ModUtil::getVar('PostCalendar', 'pcDateFormats');
        $timeFormat = ModUtil::getVar('PostCalendar', 'pcTime24Hours') ? "G:i" : "g:i a";
        if ($pc_event && $permission && !$private) {
            $this->setObjectAuthor("");
            $this->setObjectDate($pc_event['eventStart']->format($formats['date'] . " " . $timeFormat));
            $this->setObjectTitle($pc_event['title']);
            // do not use default objectURL to compensate for shortUrl handling
        }
    }

    public function setObjectTitle($title)
    {
        $this->title = $title;
    }

    public function setObjectDate($date)
    {
        $this->date = $date;
    }

    public function setObjectAuthor($author)
    {
        $this->author = $author;
    }
    
    public function getPresentationLink()
    {
        $date = $this->getDate();
        $title = $this->getTitle();
        $link = null;
        if (!empty($title)) {
            $dom = ZLanguage::getModuleDomain('Tag');
            $on = __('on', $dom);
            $calEvent = __('Event', $dom);
            $modinfo = ModUtil::getInfoFromName('PostCalendar');
            $urlObj = $this->getUrlObject();
            $link = "$modinfo[displayname] $calEvent: <a href='{$urlObj->getUrl()}'>$title</a>";
            $sub = '';
            if (!empty($date)) {
                $sub .= " $on $date";
            }
            $link .= ( !empty($sub)) ? " (" . trim($sub) . ")" : '';
        }
        return $link;
    }
}