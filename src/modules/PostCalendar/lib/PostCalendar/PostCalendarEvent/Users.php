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
     * @param   array(objectid) news id
     * @return  array() event info or false if no desire to publish event
     */
    public function makeEvent($args) {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }

        $eventstatus = -1; // pending
        $user = UserUtil::getVars($args['objectid'], true);
        if (!$user) {
            // user is a pending registration
            $user = UserUtil::getVars($args['objectid'], true, '', true);
        }
        if (($user['activated'] == UserUtil::ACTIVATED_ACTIVE)
                || ($user['activated'] == UserUtil::ACTIVATED_INACTIVE_PWD)
                || ($user['activated'] == UserUtil::ACTIVATED_INACTIVE_TOUPP)
                || ($user['activated'] == UserUtil::ACTIVATED_INACTIVE_PWD_TOUPP)) {
            $eventstatus = 1; // approved
        }
        
        $profileModName = System::getVar('profilemodule', '');
        if (ModUtil::available($profileModName)) {
            $hometext = ":html:" . __('Profile link: ', $dom) . "<a href='" . ModUtil::url($profileModName, 'user', 'view', array('uid' => $user['uid'])) . "'>" . $user['uname'] . "</a>" . " " . __('registered on this day', $dom);
        } else {
            $hometext = ":text:" . $user['uname'] . " " . __('registered on this day', $dom);
        }

        $this->title = __('New user: ', $dom) . $user['uname'];
        $this->hometext = $hometext;
        $this->aid = $user['uid']; // userid of creator
        $this->time = $user['user_regdate']; // mysql timestamp YYYY-MM-DD HH:MM:SS
        $this->informant = $user['uid']; // userid of creator
        $this->eventDate = substr($user['user_regdate'], 0, 10); // date of event: YYYY-MM-DD
        $this->startTime = substr($user['user_regdate'], -8); // time of event: HH:MM:SS
        $this->eventstatus = $eventstatus;

        return true;
    }

    public static function createEvent(Zikula_Event $z_event) {
        $userObj = $z_event->getSubject();
        // does event already exist?
        ModUtil::dbInfoLoad('PostCalendar');
        $where = "WHERE pc_hooked_modulename = 'Users'
                  AND pc_hooked_objectid = '{$userObj['uid']}'";
        $result = DBUtil::selectObject('postcalendar_events', $where);
        if (($result) && ($result['eventstatus'] <> 1)) {
            $obj['eventstatus'] = 1;
            DBUtil::updateObject($obj, 'postcalendar_events', $where, 'eid');
        } else {
            // create event
            $pcEventObject = new PostCalendar_PostCalendarEvent_Users('Users');
            $args = array(
                'objectid' => $userObj['uid']);
            $pcEventObject->makeEvent($args);
            $pcEventObject->setHooked_objectid($userObj['uid']);
            $pcEventObject->set__CATEGORIES__(ModUtil::getVar('Users', 'postcalendar_admincatselected'));
            $pc_event = $pcEventObject->toArray();
            DBUtil::insertObject($pc_event, 'postcalendar_events', 'eid');
        }
    }
}