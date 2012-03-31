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

    function __construct($objectId, $areaId, $module, $objectUrl)
    {
        parent::__construct($objectId, $areaId, $module, $objectUrl);

        $entityManager = ServiceUtil::getService('doctrine.entitymanager');
        $pc_event = $entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->find($this->getObjectId())->getOldArray();
        // check for permission and status
        $permission = SecurityUtil::checkPermission('PostCalendar::Event', "$pc_event[title]::$pc_event[eid]", ACCESS_OVERVIEW);
        $private = ($pc_event['sharing'] == 0 && $pc_event['aid'] != UserUtil::getVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN));
        if ($pc_event && $permission && !$private) {
            $this->setObjectAuthor("");
            $date = DateUtil::formatDatetime($pc_event['eventDate'], 'datebrief', false);
            $time = DateUtil::formatDatetime($pc_event['startTime'], 'timebrief', false);
            $this->setObjectDate("$date $time");
            $this->setObjectTitle($pc_event['title']);
            // do not use default objectURL to compensate for shortUrl handling
            $modUrl = new Zikula_ModUrl('PostCalendar', 'user', 'display', System::getVar('language_i18n'), array('viewtype' => 'event', 'eid' => $this->getObjectId()));
            $this->setUrlObject($modUrl);
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
            $link = "$modinfo[displayname] $calEvent: <a href='{$this->getObjectUrl()}'>$title</a>";
            $sub = '';
            if (!empty($date)) {
                $sub .= " $on $date";
            }
            $link .= ( !empty($sub)) ? " (" . trim($sub) . ")" : '';
        }
        return $link;
    }
}