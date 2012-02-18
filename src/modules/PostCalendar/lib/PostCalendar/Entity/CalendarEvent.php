<?php
/**
 * PostCalendar
 * 
 * @license MIT
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Tagged object entity class. (repositoryClass="PostCalendar_Entity_Repository_CalendarEventRepository")
 *
 * Annotations define the entity mappings to database.
 *
 * @ORM\Entity
 * @ORM\Table(name="postcalendar_events")
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
     * Participant's UID (default = informant UID)
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
     * 
     * @ORM\Column(type="datetime")
     */
    private $ttime;
    /**
     * Event description
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    private $hometext = '';
    /**
     * UID of event submittor
     * 
     * @ORM\Column(length=20)
     */
    private $informant = 2;
    /**
     * Event start date
     * set to DateTime object in constructor
     * 
     * @ORM\Column(type="date")
     */
    private $eventDate;
    /**
     * event duration
     * 
     * @ORM\Column(type="bigint")
     */
    private $duration = 0;
    /**
     * Event end date
     * set to DateTime object in constructor
     * 
     * @ORM\Column(type="date")
     */
    private $endDate;
    /**
     * Type of recurrance (0, 1, 2)
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
     * 
     * @ORM\Column(length=8)
     */
    private $startTime = '00:00:00';
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
        "event_city"=> "",
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
     * 
     * @ORM\Column(type="integer", nullable=true)
     */
    private $eventstatus = self::QUEUED;
    /**
     * Event sharing (private, global)
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
     * @ORM\Column(length=64, nullable=true)
     */
    private $hooked_area = '';
    /**
     * @ORM\OneToMany(targetEntity="PostCalendar_Entity_EventCategory", 
     *                mappedBy="entity", cascade={"all"}, 
     *                orphanRemoval=true, indexBy="categoryRegistryId")
     */
    private $categories;
    
    /**
     * Constructor 
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->setEventDate(new DateTime());
        $this->setTtime(new DateTime());
        $blankdate = new DateTime();
        $blankdate->setDate(0000, 00, 00);
        $this->setEndDate($blankdate);
        
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

    public function getTtime()
    {
        return $this->ttime;
    }

    public function setTtime($ttime)
    {
        $this->ttime = $ttime;
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

    public function getEventDate($format='Y-m-d')
    {
        return $this->eventDate->format($format);
    }

    public function setEventDate(DateTime $eventDate)
    {
        $this->eventDate = $eventDate;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration = 0)
    {
        $this->duration = $duration;
    }

    public function getEndDate($format='Y-m-d')
    {
        return $this->endDate->format($format);
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

    public function setHooked_area($hooked_area = '')
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
     * Create the object from old array-style setup
     * 
     * @param type $array 
     */
    public function setFromArray($array) {
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
            $uTime = new DateTime();
            $parts = explode(" ", $array['time']);
            list($year, $month, $day) = explode("-", $parts[0]);
            $uTime->setDate($year, $month, $day);
            list($hour, $minute, $second) = explode(":", $parts[1]);
            $uTime->setTime($hour, $minute, $second);
            $this->setTtime($uTime);
        }
        if (isset($array['informant'])) {
            $this->setInformant($array['informant']);
        }
        if (isset($array['eventDate'])) {
            $eventDate = new DateTime();
            list($year, $month, $day) = explode("-", $array['eventDate']);
            $eventDate->setDate($year, $month, $day);
            $this->setEventDate($eventDate);
        }
        if (isset($array['duration'])) {
            $this->setDuration($array['duration']);
        }
        if (isset($array['endDate'])) {
            $endDate = new DateTime();
            list($year, $month, $day) = explode("-", $array['endDate']);
            $endDate->setDate($year, $month, $day);
            $this->setEventDate($endDate);
        }
        if (isset($array['recurrtype'])) {
            $this->setRecurrtype($array['recurrtype']);
        }
        if (isset($array['recurrspec'])) {
            if (DataUtil::is_serialized($array['recurrspec'])) {
                $recurrspec = unserialize($array['recurrspec']);
            } else {
                $recurrspec = $array['recurrspec'];
            }
            $this->setRecurrspec($recurrspec);
        }
        if (isset($array['startTime'])) {
            $this->setStartTime($array['startTime']);
        }
        if (isset($array['alldayevent'])) {
            $allday = $array['alldayevent'] ? true : false;
            $this->setAlldayevent($allday);
        }
        if (isset($array['location'])) {
            if (DataUtil::is_serialized($array['location'])) {
                $location = unserialize($array['location']);
            } else {
                $location = $array['location'];
            }
            $this->setLocation($location);
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
        if (isset($array['__CATEGORIES__'])) {
            $em = ServiceUtil::getService('doctrine.entitymanager');
            foreach ($array['__CATEGORIES__'] as $propName => $catId) {
                $tableName = $em->getClassMetadata(get_class($this))->getTableName();
                $regId = $em->getRepository('Zikula_Doctrine2_Entity_CategoryRegistry')
                    ->findOneBy(array('modname' => 'PostCalendar',
                                    'tablename' => $tableName,
                                    'property' => $propName))
                    ->getId();
                $category = $em->find('Zikula_Doctrine2_Entity_Category', $catId);
                if ($this->getCategories()->get($regId)) {
                    $this->getCategories()->get($regId)->setCategory($category);
                } else {
                    $this->getCategories()->set($regId, new PostCalendar_Entity_EventCategory($regId, $category, $this));
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
        unset($array['reflection']);
        unset ($array['categories']);
        
        $em = ServiceUtil::getService('doctrine.entitymanager');
        $registries = $em->getRepository('Zikula_Doctrine2_Entity_CategoryRegistry')
            ->findBy(array('modname' => 'PostCalendar',
                           'tablename' => 'postcalendar_events'));
        foreach ($registries as $reg) {
            $category = $this->getCategories()->get($reg->getId())->getCategory();
            $array['__CATEGORIES__'][$reg->getProperty()] = array('name' => $category->getName(),
                                                                  'id' => (string)$category->getId());
            $array['__CATEGORIES__']['display_name'] = $category->getDisplayName();
            $categoryAttributes = $category->getAttributes();
            foreach($categoryAttributes as $attr) {
                $array['__CATEGORIES__']['__ATTRIBUTES__'] = array($attr->getName() => $attr->getValue());
            }
        }
        $array['time'] = $this->getTtime()->format('Y-m-d H:i:s');
        //reserialize the arrays
        $array['recurrspec'] = serialize($this->recurrspec);
        $array['location'] = serialize($this->location);
        
        return $array;
    }

}
