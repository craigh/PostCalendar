<?php

/**
 * abstract base class to allow other modules to extend the functionality of
 * PostCalendar and create events from within their hooked module.
 *
 * @author craig heydenburg
 */
abstract class PostCalendar_PostCalendarEvent_Base {

    private $hooked_modulename;
    private $hooked_objectid;
    private $eid;
    private $__META__ = array('module' => 'PostCalendar');
    private $recurrtype = 0; // norepeat
    private $recurrspec = 'a:5:{s:17:"event_repeat_freq";s:0:"";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:0:"";}'; // default recurrance info - serialized (not used)
    private $location = 'a:6:{s:14:"event_location";s:0:"";s:13:"event_street1";s:0:"";s:13:"event_street2";s:0:"";s:10:"event_city";s:0:"";s:11:"event_state";s:0:"";s:12:"event_postal";s:0:"";}'; // default location info - serialized (not used)
    protected $title;
    protected $hometext;
    protected $aid;
    protected $time;
    protected $informant;
    protected $eventDate;
    protected $duration = 3600; // one hour
    protected $startTime = '01:00:00';
    protected $alldayevent = 1; // yes
    protected $eventstatus = 1; // approved
    protected $sharing = 3; // global
    public $__CATEGORIES__;

    public function __construct($module) {
        $this->hooked_modulename = $module;
    }

    /**
     * get info for Postcalendar event creation
     *
     * @param   array(objectid) id
     * @return  boolean true on success false if no desire to publish event
     *
     * must set values for the following class properties
     *     'title'        event title
     *     'hometext'     an event description
     *     'aid'          userid of creator
     *     'time'         mysql timestamp YYYY-MM-DD HH:MM:SS
     *     'informant'    userid of creator
     *     'eventDate'    date of event: YYYY-MM-DD
     * The following properties are optional and have default values
     *     'duration'     default duration in seconds (set to 3600)
     *     'startTime'    time of event: HH:MM:SS (set to 01:00:00)
     *     'alldayevent'  1 = yes, 0 = no
     *     'eventstatus'  1 = approved, 0 = queued, -1 = hidden
     *     'sharing' =>   3 = global, 0 = private
     */
    abstract public function makeEvent($param);

    public function setHooked_objectid($hooked_objectid) {
        $this->hooked_objectid = $hooked_objectid;
    }

    public function setEid($eid) {
        $this->eid = $eid;
    }

    public function toArray() {
        $meta = array();
        $meta['hooked_modulename'] = $this->hooked_modulename;
        $meta['__META__'] = $this->__META__;
        $meta['recurrtype'] = $this->recurrtype;
        $meta['recurrspec'] = $this->recurrspec;
        $meta['location'] = $this->location;
        $meta['hooked_objectid'] = $this->hooked_objectid;
        $meta['title'] = $this->title;
        $meta['hometext'] = $this->hometext;
        $meta['aid'] = $this->aid;
        $meta['time'] = $this->time;
        $meta['informant'] = $this->informant;
        $meta['eventDate'] = $this->eventDate;
        $meta['duration'] = $this->duration;
        $meta['startTime'] = $this->startTime;
        $meta['alldatevent'] = $this->alldayevent;
        $meta['eventstatus'] = $this->eventstatus;
        $meta['sharing'] = $this->sharing;
        $meta['__CATEGORIES__'] = $this->__CATEGORIES__;
        if (!empty($this->eid)) {
            $meta['eid'] = $this->eid;
        }
        return $meta;
    }

}