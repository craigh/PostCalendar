<?php
/**
 * PostCalendar
 * 
 * @license MIT
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class PostCalendar_Entity_Repository_CalendarEventRepository extends EntityRepository
{
    const FILTER_GLOBAL = -1;
    const FILTER_ALL = -2;
    const FILTER_PRIVATE = -3;

    /**
     * get all associated tags for an object
     * 
     * @return Object Zikula_EntityAccess 
     */
    public function getEventCount($type = PostCalendar_Entity_CalendarEvent::APPROVED)
    {
        $dql = "SELECT COUNT(a.eid) FROM PostCalendar_Entity_CalendarEvent a WHERE a.eventstatus = ?1";

        $em = ServiceUtil::getService('doctrine.entitymanager');
        $query = $em->createQuery($dql);
        return $query->setParameter(1, $type)
                ->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }
}