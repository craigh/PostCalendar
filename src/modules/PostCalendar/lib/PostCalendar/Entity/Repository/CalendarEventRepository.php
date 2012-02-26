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
    const FILTER_GLOBAL = -1; // all public events
    const FILTER_ALL = -2; // all public + my events
    const FILTER_PRIVATE = -3; // just my private events
    
    /**
     * get all associated tags for an object
     * 
     * @return Object Zikula_EntityAccess 
     */
    public function getEventCount($eventStatus = CalendarEvent::APPROVED, $filterCategories = null)
    {
        $dql = "SELECT COUNT(DISTINCT a.eid) FROM PostCalendar_Entity_CalendarEvent a JOIN a.categories c ";
        $where = array();
        if ($eventStatus <> CalendarEvent::ALLSTATUS) {
            $where[] = "a.eventstatus = :status ";
        }
        if (isset($filterCategories) && !empty($filterCategories)) {
            // reformat array
            $categories = array_values($filterCategories);
            // add to dql
            $where[] = "c.category IN (:categories) ";
        }
        if (!empty($where)) {
            $dql .= "WHERE " . implode(' AND ', $where);
        }
        
        $query = $this->_em->createQuery($dql);
        if (isset($categories)) {
            $query->setParameter('categories', $categories);
        }
        if ($eventStatus <> CalendarEvent::ALLSTATUS) {
            $query->setParameter('status', $eventStatus);
        }
        return $query->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }
    
    public function getEventCollection($eventStatus, $startDate, $endDate, $username, $ruserid, $filterCategories, $search)
    {
        $dql = "SELECT a FROM PostCalendar_Entity_CalendarEvent a JOIN a.categories c " .
               "WHERE (a.endDate >= ?2 " .
               "OR (a.endDate = ?3 AND a.recurrtype <> ?4) " .
               "OR a.eventDate >= ?5) " .
               "AND a.eventDate <= ?6 ";

        if ($eventStatus <> CalendarEvent::ALLSTATUS) {
            $dql .= "AND a.eventstatus = ?1 ";
        }
        switch ($username) {
            case self::FILTER_PRIVATE: // show just private events
                $dql .= "AND a.aid = ?7 " .
                        "AND a.sharing = ?8 ";
                break;
            case self::FILTER_ALL: // show all public/global AND private events
                $dql .= "AND (a.aid = ?7 " .
                        "AND (a.sharing IN (?8, ?9))) ";
                break;
            case self::FILTER_GLOBAL: // show all public/global events
            default:
                $dql .= "AND a.sharing = ?7 ";
        }

        if (!empty($search)) {
            $dql .= "AND $search";
        }
        
        if (isset($filterCategories) && !empty($filterCategories)) {
            // reformat array
            $categories = array_values($filterCategories);
            // add to dql
            $dql .= "AND c.category IN (:categories) ";
        }
        
        // generate query
        $query = $this->_em->createQuery($dql);

        // Add query parameters
        $query->setParameters(array(
            2 => $startDate,
            3 => '0000-00-00',
            4 => CalendarEvent::RECURRTYPE_NONE,
            5 => $startDate,
            6 => $endDate));
        if ($eventStatus <> CalendarEvent::ALLSTATUS) {
            $query->setParameter(1, $eventStatus);
        }
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

        // events not filtered for permissions

        try {
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
    
    public function getEventList($eventStatus, $sort, $offset, $amount, $filterCategories)
    {
        $dql = "SELECT a FROM PostCalendar_Entity_CalendarEvent a JOIN a.categories c ";
        $where = array();
        if ($eventStatus <> CalendarEvent::ALLSTATUS) {
            $where[] = "a.eventstatus = :status ";
        }
        if (isset($filterCategories) && !empty($filterCategories)) {
            // reformat array
            $categories = array_values($filterCategories);
            // add to dql
            $where[] = "c.category IN (:categories) ";
        }
        if (!empty($where)) {
            $dql .= "WHERE " . implode(' AND ', $where);
        }
        $dql .= "ORDER BY $sort ";
        // generate query
        $query = $this->_em->createQuery($dql);
        if ($eventStatus <> CalendarEvent::ALLSTATUS) {
            $query->setParameter('status', $eventStatus);
        }
        if (isset($categories)) {
            $query->setParameter('categories', $categories);
        }
        if ($offset > 0) {
            $query->setFirstResult($offset);
        }
        $query->setMaxResults($amount);
        try {
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