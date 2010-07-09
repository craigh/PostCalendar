<?php
class PostCalendar_Hookutil
{
    /**
     * convert scheduled events status to APPROVED on their eventDate for hooked news events
     *
     * @author  Craig Heydenburg
     * @return  null
     * @access  public
     */
    public static function scheduler($args)
    {
        $today = DateUtil::getDatetime(null, '%Y-%m-%d');
        $time  = DateUtil::getDatetime(null, '%H:%M:%S');
        $where = "WHERE pc_hooked_modulename = 'news' 
                  AND pc_eventstatus = -1 
                  AND pc_eventDate <= '$today' 
                  AND pc_startTime <= '$time'";
        $object['eventstatus'] = 1;
        DBUtil::updateObject($object, 'postcalendar_events', $where, 'eid');
        return;
    }
    /**
     * get news info for Postcalendar event creation
     *
     * @author  Craig Heydenburg
     * @access  public
     * @args    array(objectid) news id
     * @return  array() event info or false if no desire to publish event
     */
    public function news_pcevent($args)
    {
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
    
        // change below based on $article array
        $event = array(
            'title'             => __('News: ', $dom) . $article['title'],
            'hometext'          => ":html:" . __('Article link: ', $dom) . "<a href='" . ModUtil::url('News', 'user', 'display', array('sid' => $article['sid'])) . "'>" . substr($article['hometext'], 0, 32) . "...</a>",
            'aid'               => $article['aid'], // userid of creator
            'time'              => $article['time'], // mysql timestamp YYYY-MM-DD HH:MM:SS
            'informant'         => $article['aid'], // userid of creator
            'eventDate'         => substr($article['from'], 0, 10), // date of event: YYYY-MM-DD
            'duration'          => 3600, // default duration in seconds (not used)
            'startTime'         => substr($article['from'], -8), // time of event: HH:MM:SS
            'alldayevent'       => 1, // yes
            'eventstatus'       => $eventstatus,
            'sharing'           => 3, // global
        );
        return $event;
    }
    /**
     * get users info for Postcalendar event creation
     *
     * @author  Craig Heydenburg
     * @access  public
     * @args    array(objectid) news id
     * @return  array() event info or false if no desire to publish event
     */
    public function users_pcevent($args)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');

        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return false;
        }
    
        $user = UserUtil::getVars($args['objectid'], true);
    
        // $user['activated'] ??
        // cannot rely on 'activated' attribute as update hook is not called on user update/activation (http://code.zikula.org/core/ticket/1804)
        // user.activated is no longer used (7/7/10) as a new system is in place moving users from one table to another...

        $eventstatus = 1; // approved

        if (ModUtil::modAvail('Profile')) {
            $hometext = ":html:" . __('Profile link: ', $dom) . "<a href='" . ModUtil::url('Profile', 'user', 'view', array('uid' => $user['uid'])) . "'>" . $user['uname'] . "</a>";
        } else {
            $hometext = ":text:" . $user['uname'];
        }
    
        $event = array(
            'title'             => __('New user: ', $dom) . $user['uname'],
            'hometext'          => $hometext,
            'aid'               => $user['uid'], // userid of creator
            'time'              => $user['user_regdate'], // mysql timestamp YYYY-MM-DD HH:MM:SS
            'informant'         => $user['uid'], // userid of creator
            'eventDate'         => substr($user['user_regdate'], 0, 10), // date of event: YYYY-MM-DD
            'duration'          => 3600, // default duration in seconds (not used)
            'startTime'         => substr($user['user_regdate'], -8), // time of event: HH:MM:SS
            'alldayevent'       => 1, // yes
            'eventstatus'       => $eventstatus,
            'sharing'           => 3, // global
        );
        return $event;
    }
} // end class def