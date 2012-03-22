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
 * abstract base class to allow other modules to extend the functionality of
 * PostCalendar and create events from within their hooked module.
 */
abstract class PostCalendar_PostCalendarEvent_AbstractBase {

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
     * url of hooked object
     * not required to be provided
     * @var string
     */
    private $hooked_objecturl;
    /**
     * ID of hooked area
     * @var string
     */
    private $hooked_area;
    /**
     * Event ID
     * @var integer
     */
    private $eid;
    /**
     * event recurrance type
     * @var integer
     */
    protected $recurrtype = PostCalendar_Entity_CalendarEvent::RECURRTYPE_NONE;
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
    protected $categories;

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
    abstract public function makeEvent();

    /**
     *
     * @param integer $hooked_objectid
     */
    public function setHooked_objectid($hooked_objectid) {
        $this->hooked_objectid = $hooked_objectid;
    }
    
    public function getHooked_objectid() {
        return $this->hooked_objectid;
    }

    public function setHooked_objecturl($hooked_objecturl) {
        $this->hooked_objecturl = $hooked_objecturl;
    }
    
    public function getHooked_objecturl() {
        return $this->hooked_objecturl;
    }

    public function getHooked_modulename() {
        return $this->hooked_modulename;
    }
    /**
     *
     * @param string $hooked_area 
     */
    public function setHooked_area($hooked_area) {
        $this->hooked_area = $hooked_area;
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
     * @param array $categories
     */
    public function setcategories($categories) {
        $this->categories = $categories;
    }

    /**
     *
     * @return array
     */
    public function toArray() {
        $meta = array();
        $meta['hooked_modulename'] = $this->hooked_modulename;
        $meta['recurrtype'] = $this->recurrtype;
        $meta['hooked_objectid'] = $this->hooked_objectid;
        $meta['hooked_area'] = $this->hooked_area;
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
        $meta['categories'] = $this->categories;
        if (!empty($this->eid)) {
            $meta['eid'] = $this->eid;
        }
        return $meta;
    }

}