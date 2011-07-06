<?php

/**
 * Tag - a content-tagging module for the Zikukla Application Framework
 * 
 * @license MIT
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
class PostCalendar_TaggedObjectMeta_PostCalendar extends Tag_AbstractTaggedObjectMeta
{

    function __construct($objectId, $areaId, $module, $objectUrl)
    {
        parent::__construct($objectId, $areaId, $module, $objectUrl);

        ModUtil::dbInfoLoad('PostCalendar');
        $pc_event = DBUtil::selectObjectByID('postcalendar_events', $this->getObjectId(), 'eid');
        
        if ($pc_event) {
            $this->setObjectAuthor("");
            $date = DateUtil::formatDatetime($pc_event['eventDate'], 'datebrief', false);
            $time = DateUtil::formatDatetime($pc_event['startTime'], 'timebrief', false);
            $this->setObjectDate("$date $time");
            $this->setObjectTitle($pc_event['title']);
            // do not use default objectURL to compensate for shortUrl handling
            $this->setObjectUrl(ModUtil::url('PostCalendar', 'user', 'display', array('viewtype' => 'details', 'eid' => $this->getObjectId())));
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