<?php

/**
 * Implements Base class to allow for Event creation on News Story creation
 *
 * @author craig heydenburg
 */
class PostCalendar_PostCalendarEvent_News extends PostCalendar_PostCalendarEvent_Base {

    /**
     * get news info for Postcalendar event creation
     *
     * @args    array(objectid) news id
     * @return  array() event info or false if no desire to publish event
     */
    public function makeEvent($args) {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }
        $args['SQLcache'] = false;
        $article = ModUtil::apiFunc('News', 'user', 'get', $args);

        $eventstatus = 1; // approved
        if ($article['published_status'] != 0) { // article not published yet (draft, etc)
            return false;
        }

        $now = DateUtil::getDatetime(null, '%Y-%m-%d %H:%M:%S');
        $diff = DateUtil::getDatetimeDiff_AsField($now, $article['from'], 6);
        if ($diff > 0) {
            $eventstatus = -1; // hide published events
        }

        $this->title = __('News: ', $dom) . $article['title'];
        $this->hometext = ":html:" . __('Article link: ', $dom) . "<a href='" . ModUtil::url('News', 'user', 'display', array('sid' => $article['sid'])) . "'>" . substr($article['hometext'], 0, 32) . "...</a>";
        $this->aid = $article['aid']; // userid of creator
        $this->time = $article['time']; // mysql timestamp YYYY-MM-DD HH:MM:SS
        $this->informant = $article['aid']; // userid of creator
        $this->eventDate = substr($article['from'], 0, 10); // date of event: YYYY-MM-DD
        $this->startTime = substr($article['from'], -8); // time of event: HH:MM:SS
        $this->eventstatus = $eventstatus;

        return true;
    }

    /**
     * convert scheduled events status to APPROVED on their eventDate for hooked news events
     */
    public static function scheduler()
    {
        $today = DateUtil::getDatetime(null, '%Y-%m-%d');
        $time = DateUtil::getDatetime(null, '%H:%M:%S');
        $where = "WHERE pc_hooked_modulename = 'news'
                  AND pc_eventstatus = -1
                  AND pc_eventDate <= '$today'
                  AND pc_startTime <= '$time'";
        $object['eventstatus'] = 1;
        DBUtil::updateObject($object, 'postcalendar_events', $where, 'eid');
    }

}