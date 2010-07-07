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
} // end class def