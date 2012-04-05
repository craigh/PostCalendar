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
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Calendar Event entity class
 *
 * Annotations define the entity mappings to database.
 *
 * @ORM\Entity(repositoryClass="PostCalendar_Entity_Repository_CalendarEventRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="postcalendar_events",indexes={@ORM\index(name="basic_event", columns={"aid", "eventStart", "eventEnd", "eventstatus", "sharing"})})
 */
class PostCalendar_Entity_CalendarEvent extends Zikula_EntityAccess
{

    const SHARING_PRIVATE = 0;
    const SHARING_PUBLIC = 1;
    const SHARING_GLOBAL = 3;
    const APPROVED = 1;
    const QUEUED = 0;
    const HIDDEN = -1;
    const ALLSTATUS = 100;
    const RECURRTYPE_NONE = 0;
    const RECURRTYPE_REPEAT = 1;
    const RECURRTYPE_REPEAT_ON = 2;

    /**
     * event id field (record id)
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $eid;

    /**
     * Participant's UID
     * duplicate of informant UID
     * 
     * @ORM\Column(length=30)
     */
    private $aid = 2;

    /**
     * Event Title
     * 
     * @ORM\Column(length=150, nullable=true)
     */
    private $title = '';

    /**
     * timestamp for event creation
     * NOT a typo - 'time' is a reserved SQL word
     * set to current DateTime object in constructor
     * 
     * @ORM\Column(type="datetime", name="ttime")
     */
    private $time;

    /**
     * Event description
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    private $hometext = '';

    /**
     * UID of event submittor
     * default 2 = admin
     * 
     * @ORM\Column(length=20)
     */
    private $informant = 2;

    /**
     * Event start date
     * set to current DateTime object in constructor
     * @deprecated since v8.0.0
     * 
     * @ORM\Column(type="date", nullable=true)
     */
    private $eventDate;

    /**
     * Event start date and time
     * set to current DateTime object in constructor
     * 
     * @ORM\Column(type="datetime")
     */
    private $eventStart;

    /**
     * Event end date and time
     * set to current DateTime object in constructor
     * 
     * @ORM\Column(type="datetime")
     */
    private $eventEnd;

    /**
     * event duration
     * length of event in seconds
     * @deprecated since v8.0.0
     * 
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $duration = null;

    /**
     * Event recurrance end date
     * 
     * @ORM\Column(type="date", nullable=true)
     */
    private $endDate = null;

    /**
     * Type of recurrance (0, 1, 2)
     * see const defs in this class
     * 
     * @ORM\Column(type="integer", length=1)
     */
    private $recurrtype = self::RECURRTYPE_NONE;

    /**
     * Serialized recurrance spec
     * 
     * @ORM\Column(type="array")
     */
    private $recurrspec = array("event_repeat_freq" => "",
        "event_repeat_freq_type" => "0",
        "event_repeat_on_num" => "1",
        "event_repeat_on_day" => "0",
        "event_repeat_on_freq" => ""
    );

    /**
     * Event Start time
     * @deprecated since v8.0.0
     * 
     * @ORM\Column(length=8, nullable=true)
     */
    private $startTime = null;

    /**
     * Event All Day or not
     * 
     * @ORM\Column(type="boolean") 
     */
    private $alldayevent = false;

    /**
     * Location of the event
     * 
     * @ORM\Column(type="array") 
     */
    private $location = array("event_location" => "",
        "event_street1" => "",
        "event_street2" => "",
        "event_city" => "",
        "event_state" => "",
        "event_postal" => ""
    );

    /**
     * Telephone of Event Contact
     * 
     * @ORM\Column(length=50, nullable=true)
     */
    private $conttel = '';

    /**
     * Event Contact Name
     * 
     * @ORM\Column(length=50, nullable=true)
     */
    private $contname = '';

    /**
     * Event Contact email
     * 
     * @ORM\Column(nullable=true)
     */
    private $contemail = '';

    /**
     * Event Contact website
     * 
     * @ORM\Column(nullable=true)
     */
    private $website = '';

    /**
     * Event Fee
     * 
     * @ORM\Column(length=50, nullable=true) 
     */
    private $fee = '';

    /**
     * Event status (approved, pending)
     * see const defs in this class
     * 
     * @ORM\Column(type="integer", nullable=true)
     */
    private $eventstatus = self::QUEUED;

    /**
     * Event sharing (private, global)
     * see const defs in this class
     * 
     * @ORM\Column(type="integer")
     */
    private $sharing = self::SHARING_PRIVATE;

    /**
     * Module name of Hook Target
     * 
     * @ORM\Column(length=50, nullable=true) 
     */
    private $hooked_modulename = '';

    /**
     * Object ID of Hook Target
     * 
     * @ORM\Column(type="integer", nullable=true) 
     */
    private $hooked_objectid = 0;

    /**
     * Area ID of Hook Target
     * 
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hooked_area = 0;

    /**
     * @ORM\OneToMany(targetEntity="PostCalendar_Entity_EventCategory", 
     *                mappedBy="entity", cascade={"all"}, 
     *                orphanRemoval=true, indexBy="categoryRegistryId")
     */
    private $categories;

    /**
     * non-persisted properties
     */
    private $privateicon = false;
    private $HTMLorTextVal = 'html';

    /**
     * Constructor 
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $currentDateTime = new DateTime();
        $this->setEventStart(clone $currentDateTime);
        $this->setEventEnd(clone $currentDateTime);
        $this->setTime(clone $currentDateTime);

        $uid = UserUtil::getVar('uid');
        $this->setAid($uid);
        $this->setInformant($uid);
    }

    /**
     * GETTERS AND SETTERS
     */
    public function getEid()
    {
        return $this->eid;
    }

    public function getAid()
    {
        return $this->aid;
    }

    public function setAid($aid)
    {
        $this->aid = $aid;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title = '')
    {
        $this->title = $title;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function setTime($time)
    {
        $this->time = $time;
    }

    public function getHometext()
    {
        return $this->hometext;
    }

    public function setHometext($hometext = '')
    {
        $this->hometext = $hometext;
    }

    public function getInformant()
    {
        return $this->informant;
    }

    public function setInformant($informant)
    {
        $this->informant = $informant;
    }

    /**
     * get Event date
     * @deprecated since v8.0.0
     * @param string $format
     * @return mixed string/null 
     */
    public function getEventDate($format = 'Y-m-d')
    {
        if ($this->eventDate instanceof DateTime) {
            return $this->eventDate->format($format);
        } else {
            return null;
        }
    }

    /**
     * set Event date
     * @deprecated since v8.0.0
     * @param DateTime $eventDate 
     */
    public function setEventDate(DateTime $eventDate)
    {
        $this->eventDate = $eventDate;
    }

    public function getEventStart()
    {
        return $this->eventStart;
    }

    public function setEventStart($eventStart)
    {
        $this->eventStart = $eventStart;
    }

    public function getEventEnd()
    {
        return $this->eventEnd;
    }

    public function setEventEnd($eventEnd)
    {
        $this->eventEnd = $eventEnd;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration = 0)
    {
        $this->duration = $duration;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function setEndDate(DateTime $endDate)
    {
        $this->endDate = $endDate;
    }

    public function getRecurrtype()
    {
        return $this->recurrtype;
    }

    public function setRecurrtype($recurrtype = self::RECURRTYPE_NONE)
    {
        $this->recurrtype = $recurrtype;
    }

    public function getRecurrspec()
    {
        return $this->recurrspec;
    }

    public function setRecurrspec(array $recurrspec)
    {
        $this->recurrspec = $recurrspec;
    }

    public function getStartTime()
    {
        return $this->startTime;
    }

    public function setStartTime($startTime = '00:00:00')
    {
        $this->startTime = $startTime;
    }

    public function getAlldayevent()
    {
        return $this->alldayevent;
    }

    public function setAlldayevent($alldayevent = 0)
    {
        $this->alldayevent = $alldayevent;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation(array $location)
    {
        $this->location = $location;
    }

    public function getConttel()
    {
        return $this->conttel;
    }

    public function setConttel($conttel = '')
    {
        $this->conttel = $conttel;
    }

    public function getContname()
    {
        return $this->contname;
    }

    public function setContname($contname = '')
    {
        $this->contname = $contname;
    }

    public function getContemail()
    {
        return $this->contemail;
    }

    public function setContemail($contemail = '')
    {
        $this->contemail = $contemail;
    }

    public function getWebsite()
    {
        return $this->website;
    }

    public function setWebsite($website = '')
    {
        $this->website = $website;
    }

    public function getFee()
    {
        return $this->fee;
    }

    public function setFee($fee = '')
    {
        $this->fee = $fee;
    }

    public function getEventstatus()
    {
        return $this->eventstatus;
    }

    public function setEventstatus($eventstatus = self::QUEUED)
    {
        $this->eventstatus = $eventstatus;
    }

    public function getSharing()
    {
        return $this->sharing;
    }

    public function setSharing($sharing = self::SHARING_PRIVATE)
    {
        $this->sharing = $sharing;
    }

    public function getHooked_modulename()
    {
        return $this->hooked_modulename;
    }

    public function setHooked_modulename($hooked_modulename = '')
    {
        $this->hooked_modulename = $hooked_modulename;
    }

    public function getHooked_objectid()
    {
        return $this->hooked_objectid;
    }

    public function setHooked_objectid($hooked_objectid = 0)
    {
        $this->hooked_objectid = $hooked_objectid;
    }

    public function getHooked_area()
    {
        return $this->hooked_area;
    }

    public function setHooked_area($hooked_area = 0)
    {
        $this->hooked_area = $hooked_area;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function setCategories(ArrayCollection $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Getters and Setters for non-persisted properties
     */
    public function getPrivateicon()
    {
        return $this->privateicon;
    }

    public function getHTMLorTextVal()
    {
        return $this->HTMLorTextVal;
    }

    /**
     * Configure non-persisted properties for object display
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        $this->privateicon = ($this->sharing == self::SHARING_PRIVATE);
        $this->HTMLorTextVal = substr($this->hometext, 1, 4);
        $this->hometext = substr($this->hometext, 6);
        if ($this->HTMLorTextVal == "text") {
            $this->hometext = nl2br(strip_tags($this->hometext));
        }
    }

    /**
     * Create the object from old array-style setup
     * 
     * @param type $array 
     */
    public function setFromArray($array)
    {
        if (isset($array['title'])) {
            $this->setTitle($array['title']);
        }
        if (isset($array['hometext'])) {
            $this->setHometext($array['hometext']);
        }
        if (isset($array['aid'])) {
            $this->setAid($array['aid']);
        }
        if (isset($array['time'])) {
            $uTime = DateTime::createFromFormat('Y-m-d H:i:s', $array['time']);
            $this->setTime($uTime);
        }
        if (isset($array['informant'])) {
            $this->setInformant($array['informant']);
        }
        if (isset($array['eventStart'])) {
            $this->setEventStart($array['eventStart']);
        }
        if (isset($array['eventEnd'])) {
            $this->setEventEnd($array['eventEnd']);
        }
        if (isset($array['endDate'])) {
            $this->setEndDate($array['endDate']);
        }
        if (isset($array['recurrtype'])) {
            $this->setRecurrtype($array['recurrtype']);
        }
        if (isset($array['recurrspec'])) {
            $this->setRecurrspec($array['recurrspec']);
        }
        if (isset($array['alldayevent'])) {
            $allday = $array['alldayevent'] ? true : false;
            $this->setAlldayevent($allday);
        }
        if (isset($array['location'])) {
            $this->setLocation($array['location']);
        }
        if (isset($array['eventstatus'])) {
            $this->setEventstatus($array['eventstatus']);
        }
        if (isset($array['sharing'])) {
            $this->setSharing($array['sharing']);
        }
        if (isset($array['website'])) {
            $this->setWebsite($array['website']);
        }
        if (isset($array['categories'])) {
            $em = ServiceUtil::getService('doctrine.entitymanager');
            $regIds = CategoryRegistryUtil::getRegisteredModuleCategoriesIds('PostCalendar', 'CalendarEvent');
            foreach ($array['categories'] as $propName => $catId) {
                $category = $em->find('Zikula_Doctrine2_Entity_Category', $catId);
                if ($this->getCategories()->get($regIds[$propName])) {
                    $this->getCategories()->get($regIds[$propName])->setCategory($category);
                } else {
                    $this->getCategories()->set($regIds[$propName], new PostCalendar_Entity_EventCategory($regIds[$propName], $category, $this));
                }
            }
        }
        if (isset($array['contname'])) {
            $this->setContname($array['contname']);
        }
        if (isset($array['conttel'])) {
            $this->setConttel($array['conttel']);
        }
        if (isset($array['contemail'])) {
            $this->setContemail($array['contemail']);
        }
        if (isset($array['fee'])) {
            $this->setFee($array['fee']);
        }
        if (isset($array['hooked_modulename'])) {
            $this->setHooked_modulename($array['hooked_modulename']);
        }
        if (isset($array['hooked_area'])) {
            $this->setHooked_area($array['hooked_area']);
        }
        if (isset($array['hooked_objectid'])) {
            $this->setHooked_objectid($array['hooked_objectid']);
        }
    }

    public function getOldArray()
    {
        $array = parent::toArray();
        unset($array['categories']);
        $regIds = CategoryRegistryUtil::getRegisteredModuleCategoriesIds('PostCalendar', 'CalendarEvent');
        foreach ($regIds as $propName => $regId) {
            $categoryRegistration = $this->getCategories()->get($regId);
            if (is_object($categoryRegistration)) {
                $category = $categoryRegistration->getCategory();
                $array['categories'][$propName] = array('name' => $category->getName(),
                    'id' => (string)$category->getId(),
                    'path' => $category->getPath(),
                    'ipath' => $category->getIPath(),
                    'display_name' => $category->getDisplayName());
                $categoryAttributes = $category->getAttributes();
                foreach ($categoryAttributes as $attr) {
                    $array['categories'][$propName]['attributes'][$attr->getName()] = $attr->getValue();
                }
            }
        }
        $array['time'] = $this->getTime()->format('Y-m-d H:i:s');

        return $array;
    }

}
