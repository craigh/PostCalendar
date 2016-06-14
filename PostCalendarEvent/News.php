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
/**
 * Implements Base class to allow for Event creation on News Story creation
 */
class PostCalendar_PostCalendarEvent_News extends PostCalendar_PostCalendarEvent_AbstractBase {

    /**
     * get news info for Postcalendar event creation
     *
     * @return  boolean
     */
    public function makeEvent() {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        $funcargs = array('objectid' => $this->getEvent()->getHooked_objectid(),
            'SQLcache' => false);
        $article = ModUtil::apiFunc('News', 'user', 'get', $funcargs);

        $eventstatus = 1; // approved
        if ($article['published_status'] != News_Api_User::STATUS_PUBLISHED) { // article not published yet (draft, etc)
            return false;
        }

        $now = date("Y-m-d H:i:s");
        $diff = DateUtil::getDatetimeDiff_AsField($now, $article['from'], 6);
        if ($diff > 0) {
            $eventstatus = -1; // hide published but pending events
        }

        $this->setTitle(__('News: ', $dom) . $article['title']);
        $this->setHometext(":html:" . __('Article link: ', $dom) . "<a href='" . ModUtil::url('News', 'user', 'display', array('sid' => $article['sid'])) . "'>" . substr($article['hometext'], 0, 32) . "...</a>");
        $this->setAid($article['cr_uid']); // userid of creator
        $articleCreated = DateTime::createFromFormat('Y-m-d G:i:s', $article['cr_date']);
        $this->setTime($articleCreated); 
        $this->setInformant($article['cr_uid']); // userid of creator
        $articleDate = DateTime::createFromFormat('Y-m-d G:i:s', $article['from']);
        $this->setEventStart($articleDate);
        $this->setEventEnd($articleDate);
        $this->setEventstatus($eventstatus);
        $this->setSharing(CalendarEventEntity::SHARING_GLOBAL);

        return true;
    }

    /**
     * convert scheduled events status to APPROVED on their eventDate for hooked news events
     */
    public static function scheduler()
    {
        $_em = ServiceUtil::getService('doctrine.entitymanager');
        $dql = "UPDATE CalendarEventEntity a " .
               "SET a.eventstatus = :newstatus " .
               "WHERE a.hooked_modulename = :modname " .
               "AND a.eventstatus = :oldstatus " .
               "AND a.eventStart <= :now";
        $query = $_em->createQuery($dql);
        $query->setParameters(array(
            'newstatus' => CalendarEventEntity::APPROVED,
            'modname' => 'news',
            'oldstatus' => CalendarEventEntity::HIDDEN,
            'now' => new DateTime(),
        ));
        try {
            $query->getResult();
            $_em->clear();
        } catch (Exception $e) {
            LogUtil::registerError($e->getMessage());
        }
    }

}