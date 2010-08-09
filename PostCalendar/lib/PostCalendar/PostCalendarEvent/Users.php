<?php

/**
 * Implements Base class to allow for Event creation on new user
 *
 * @author craig heydenburg
 */
class PostCalendar_PostCalendarEvent_Users extends PostCalendar_PostCalendarEvent_Base {

    /**
     * get users info for Postcalendar event creation
     *
     * @args    array(objectid) news id
     * @return  array() event info or false if no desire to publish event
     */
    public function makeEvent($args) {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }

        $user = UserUtil::getVars($args['objectid'], true);

        if (ModUtil::available('Profile')) {
            $hometext = ":html:" . __('Profile link: ', $dom) . "<a href='" . ModUtil::url('Profile', 'user', 'view', array('uid' => $user['uid'])) . "'>" . $user['uname'] . "</a>";
        } else {
            $hometext = ":text:" . $user['uname'];
        }

        $this->title = __('New user: ', $dom) . $user['uname'];
        $this->hometext = $hometext;
        $this->aid = $user['uid']; // userid of creator
        $this->time = $user['user_regdate']; // mysql timestamp YYYY-MM-DD HH:MM:SS
        $this->informant = $user['uid']; // userid of creator
        $this->eventDate = substr($user['user_regdate'], 0, 10); // date of event: YYYY-MM-DD
        $this->startTime = substr($user['user_regdate'], -8); // time of event: HH:MM:SS

        return true;
    }

}