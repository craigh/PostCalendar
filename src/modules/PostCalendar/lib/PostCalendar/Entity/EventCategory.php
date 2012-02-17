<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="postcalendar_calendarevent_category",
 *            uniqueConstraints={@ORM\UniqueConstraint(name="cat_unq",columns={"registryId", "categoryId", "entityId"})})
 */
class PostCalendar_Entity_EventCategory extends Zikula_Doctrine2_Entity_EntityCategory
{
    /**
     * @ORM\ManyToOne(targetEntity="PostCalendar_Entity_CalendarEvent", inversedBy="categories")
     * @ORM\JoinColumn(name="entityId", referencedColumnName="eid")
     * @var PostCalendar_Entity_CalendarEvent
     */
    private $entity;
    
    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
}
