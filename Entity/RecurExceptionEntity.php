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
namespace Zikula\PostCalendarModule\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="postcalendar_calendarevent_recurexception")
 */
class RecurExceptionEntity extends \Zikula_EntityAccess
{

    /**
     * exception id field (record id)
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="CalendarEventEntity", inversedBy="recurExceptions")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="eid")
     */
    private $event;

    /**
     * The date of the exception
     * @ORM\Column(type="datetime")
     */
    private $exception;

    /**
     * Constructor
     * 
     * @param integer $event
     * @param DateTime $exception 
     */
    function __construct(DateTime $exception)
    {
        $this->exception = $exception;
    }

    /**
     * get the record id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * set the record id
     * @param integer $id 
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * get the associated event
     * @return CalendarEventEntity 
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set the associated event
     * @param CalendarEventEntity $event 
     */
    public function setEvent(CalendarEventEntity $event)
    {
        $this->event = $event;
    }

    /**
     * get the exception
     * @return DateTime 
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Set the exception
     * @param DateTime $exception 
     */
    public function setException(DateTime $exception)
    {
        $this->exception = $exception;
    }

}
