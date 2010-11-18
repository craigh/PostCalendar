<?php

/**
 * abstract base class to allow other modules to extend the functionality of
 * PostCalendar and create events from within their hooked module.
 *
 * @author craig heydenburg
 */
abstract class PostCalendar_PostCalendarEvent_Base {

    /**
     * Hooked module name
     * @var string
     */
    private $hooked_modulename;
    /**
     * ID of hooked object (e.g. user ID or Story ID)
     * @var integer
     */
    private $hooked_objectid;
    /**
     * Event ID
     * @var integer
     */
    private $eid;
    /**
     * Metadata for category creation
     * @var array
     */
    private $__META__ = array('module' => 'PostCalendar');
    /**
     * event recurrance type
     * norepeat = 0
     * repeat = 1
     * repeat on = 2
     * @var integer
     */
    protected $recurrtype = 0;
    /**
     * serialized array of recurrance information
     * default value = null values for each key
     * @var string
     */
    protected $recurrspec = 'a:5:{s:17:"event_repeat_freq";s:0:"";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:0:"";}';
    /**
     * serialized array of location information
     * default value = null values for each key
     * @var string
     */
    protected $location = 'a:6:{s:14:"event_location";s:0:"";s:13:"event_street1";s:0:"";s:13:"event_street2";s:0:"";s:10:"event_city";s:0:"";s:11:"event_state";s:0:"";s:12:"event_postal";s:0:"";}';
    /**
     * Event title
     * @var string
     */
    protected $title;
    /**
     * Event Description
     * @var string
     */
    protected $hometext;
    /**
     * User ID of event creator
     * @see informant
     * @var integer
     */
    protected $aid;
    /**
     * MySQL timestamp for event creation
     * @var string YYYY-MM-DD 00:00:00
     */
    protected $time;
    /**
     * User ID of event creator
     * @see aid
     * @var integer
     */
    protected $informant;
    /**
     * Event Date
     * @var datetime YYYY-MM-DD
     */
    protected $eventDate;
    /**
     * Event duration in seconds
     * default value = 3600 (one hour)
     * @var integer
     */
    protected $duration = 3600;
    /**
     * Event start time
     * default value = '01:00:00'
     * @var string 00:00:00
     */
    protected $startTime = '01:00:00';
    /**
     * All day event
     * yes = 1
     * no = 0
     * default value = 1 = yes
     * @var integer/boolean
     */
    protected $alldayevent = 1;
    /**
     * Event status
     * approved = 1
     * queued = 0
     * hidden = -1
     * default value = 1 = approved
     * @var integer
     */
    protected $eventstatus = 1;
    /**
     * Event sharing
     * private = 0
     * global = 3
     * default value = 3
     * @var integer
     */
    protected $sharing = 3;
    /**
     * Event categories
     * @var array
     */
    protected $__CATEGORIES__;

    /**
     * Constructor
     * set the hooked module name
     * @param string $module
     */
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

    /**
     *
     * @param integer $hooked_objectid
     */
    public function setHooked_objectid($hooked_objectid) {
        $this->hooked_objectid = $hooked_objectid;
    }

    /**
     *
     * @param integer $eid
     */
    public function setEid($eid) {
        $this->eid = $eid;
    }

    /**
     *
     * @param array $__CATEGORIES__
     */
    public function set__CATEGORIES__($__CATEGORIES__) {
        $this->__CATEGORIES__ = $__CATEGORIES__;
    }

    /**
     *
     * @return array
     */
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