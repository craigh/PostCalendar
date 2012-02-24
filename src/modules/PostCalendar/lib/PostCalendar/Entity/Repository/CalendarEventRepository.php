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
use PostCalendar_Entity_CalendarEvent as CalendarEvent;

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
    public function getEventCount($type = CalendarEvent::APPROVED)
    {
        $dql = "SELECT COUNT(a.eid) FROM PostCalendar_Entity_CalendarEvent a WHERE a.eventstatus = ?1";

        $query = $this->_em->createQuery($dql);
        return $query->setParameter(1, $type)
                ->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }
    
    public function getEventCollection($eventStatus, $startDate, $endDate, $username, $ruserid, $filterCategories)
    {
        $dql = "SELECT a FROM PostCalendar_Entity_CalendarEvent a JOIN a.categories c " .
               "WHERE a.eventstatus = ?1 " .
               "AND (a.endDate >= ?2 " .
               "OR (a.endDate = ?3 AND a.recurrtype <> ?4) " .
               "OR a.eventDate >= ?5) " .
               "AND a.eventDate <= ?6 ";
                
        switch ($username) {
            case self::FILTER_PRIVATE: // show just private events
                $dql .= "AND a.aid = ?7 " .
                        "AND a.sharing = ?8";
                break;
            case self::FILTER_ALL: // show all public/global AND private events
                $dql .= "AND (a.aid = ?7 " .
                        "AND (a.sharing IN (?8, ?9)))";
                break;
            case self::FILTER_GLOBAL: // show all public/global events
            default:
                $dql .= "AND a.sharing = ?7";
        }

        if (isset($filterCategories) && !empty($filterCategories)) {
            // reformat array
            $categories = array_values($filterCategories);
            // add to dql
            $dql .= " AND c.category IN (:categories)";
        }
        
        // generate query
        $query = $this->_em->createQuery($dql);

        // Add query parameters
        $query->setParameters(array(
            1 => $eventStatus,
            2 => $startDate,
            3 => '0000-00-00',
            4 => CalendarEvent::RECURRTYPE_NONE,
            5 => $startDate,
            6 => $endDate));
        switch ($username) {
            case self::FILTER_PRIVATE:
                $query->setParameters(array(
                    7 => $ruserid,
                    8 => CalendarEvent::SHARING_PRIVATE,
                ));
                break;
            case self::FILTER_ALL:
                $query->setParameters(array(
                    7 => $ruserid,
                    8 => CalendarEvent::SHARING_PRIVATE,
                    9 => CalendarEvent::SHARING_GLOBAL,
                ));
                break;
            case self::FILTER_GLOBAL:
            default:
                $query->setParameter(7, CalendarEvent::SHARING_GLOBAL);
        }
        if (isset($categories)) {
            $query->setParameter('categories', $categories);
        }
        
        // events not filtered for search terms
        // events not filtered for permissions

        try {
//            var_dump($query->getDQL());
            $result = $query->getResult();
        } catch (Exception $e) {
            echo "<pre>";
            var_dump($e->getMessage());
            var_dump($query->getDQL());
            var_dump($query->getParameters());
            var_dump($query->getSQL());
            die;
        }
        return $result;
        
    }
}