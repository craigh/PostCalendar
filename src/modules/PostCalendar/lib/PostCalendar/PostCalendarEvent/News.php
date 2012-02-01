<?php

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

//        $now = DateUtil::getDatetime(null, '%Y-%m-%d %H:%M:%S');
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
//        $today = DateUtil::getDatetime(null, '%Y-%m-%d');
        $today = date('Y-m-d');
//        $time = DateUtil::getDatetime(null, '%H:%M:%S');
        $time = date('H:i:s');
        ModUtil::dbInfoLoad('PostCalendar');
        $dbtables = DBUtil::getTables();
        $columns = $dbtables['postcalendar_events_column'];
        $where = "WHERE $columns[hooked_modulename] = 'news'
                  AND $columns[eventstatus] = -1
                  AND $columns[eventDate] <= '$today'
                  AND $columns[startTime] <= '$time'";
        $object = array('eventstatus' => 1);
        DBUtil::updateObject($object, 'postcalendar_events', $where, 'eid');
    }

}