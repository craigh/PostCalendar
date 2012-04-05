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

        $funcargs = array('objectid' => $this->getHooked_objectid(),
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

        $this->title = __('News: ', $dom) . $article['title'];
        $this->hometext = ":html:" . __('Article link: ', $dom) . "<a href='" . ModUtil::url('News', 'user', 'display', array('sid' => $article['sid'])) . "'>" . substr($article['hometext'], 0, 32) . "...</a>";
        $this->aid = $article['cr_uid']; // userid of creator
        $this->time = $article['cr_date']; // mysql timestamp YYYY-MM-DD HH:MM:SS
        $this->informant = $article['cr_uid']; // userid of creator
        $this->eventStart = DateTime::createFromFormat('Y-m-d G:i:s');
        $this->eventEnd = clone $this->eventStart;
        $this->eventstatus = $eventstatus;

        return true;
    }

    /**
     * convert scheduled events status to APPROVED on their eventDate for hooked news events
     */
    public static function scheduler()
    {
        $_em = ServiceUtil::getService('doctrine.entitymanager');
        $dql = "UPDATE PostCalendar_Entity_CalendarEvent a " .
               "SET a.eventstatus = :newstatus " .
               "WHERE a.hooked_modulename = :modname " .
               "AND a.eventstatus = :oldstatus " .
               "AND a.eventStart <= now()";
        $query = $_em->createQuery($dql);
        $query->setParameters(array(
            'newstatus' => PostCalendar_Entity_CalendarEvent::APPROVED,
            'modname' => 'news',
            'oldstatus' => PostCalendar_Entity_CalendarEvent::HIDDEN,
        ));
        try {
            $query->getResult();
            $_em->clear();
        } catch (Exception $e) {
            LogUtil::registerError($e->getMessage());
        }
    }

}