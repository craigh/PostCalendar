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
namespace Zikula\PostCalendarModule\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Zikula\PostCalendarModule\Entity\CalendarEventEntity;

class CalendarEventRepository extends EntityRepository
{
    
    /**
     * All public events 
     */
    const FILTER_GLOBAL = -1;
    /**
     * All public + my events 
     */
    const FILTER_ALL = -2;
    /**
     * Just my private events 
     */
    const FILTER_PRIVATE = -3;
    
    const CalendarEventEntity = '\Zikula\PostCalendarModule\Entity\CalendarEventEntity';

    /**
     * Retrieve filtered count of events
     * This count is not filtered by Permissions
     * 
     * @param integer $eventStatus
     * @param array $categoryFilter
     * 
     * @return Scalar 
     */
    public function getEventCount($eventStatus = CalendarEventEntity::APPROVED, $categoryFilter = null)
    {
        $dql = "SELECT COUNT(DISTINCT a.eid) FROM " . self::CalendarEventEntity . " a JOIN a.categories c ";
        $where = array();
        if ($eventStatus <> CalendarEventEntity::ALLSTATUS) {
            $where[] = "a.eventstatus = :status ";
        }
        if (isset($categoryFilter) && !empty($categoryFilter)) {
            // reformat array
            $categories = array_values($categoryFilter);
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
        if ($eventStatus <> CalendarEventEntity::ALLSTATUS) {
            $query->setParameter('status', $eventStatus);
        }
        return $query->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * Get filtered collection of events for user view
     * This collection is not filtered by Permissions
     * 
     * @param integer $eventStatus
     * @param string $startDate
     * @param string $endDate
     * @param integer $userFilter
     * @param integer $userid
     * @param array $categoryFilter
     * @param string $search
     * 
     * @return Object Collection 
     */
    public function getEventCollection($eventStatus, $startDate, $endDate, $userFilter, $userid, array $categoryFilter, $searchDql)
    {
        $startDate->setTime(0, 0);
        $endDate->setTime(23, 59);
        $dql = "SELECT a FROM " . self::CalendarEventEntity . " a JOIN a.categories c " .
                "WHERE (a.endDate >= :startDate1 " .
                "OR a.eventEnd >= :startDate3 " .
                "OR a.eventStart >= :startDate2) " .
                "AND a.eventStart <= :endDate ";
        
        if ($eventStatus <> CalendarEventEntity::ALLSTATUS) {
            $dql .= "AND a.eventstatus = :status ";
        }
        switch ($userFilter) {
            case self::FILTER_PRIVATE: // show just private events
                $dql .= "AND a.aid = ?7 " .
                        "AND a.sharing = ?8 ";
                break;
            case self::FILTER_ALL: // show all public/global AND private events
                $dql .= "AND ((a.aid = ?7 AND a.sharing = ?8)" .
                        "OR a.sharing = ?9) ";
                break;
            case self::FILTER_GLOBAL: // show all public/global events
            default:
                $dql .= "AND a.sharing = ?7 ";
        }

        if (!empty($searchDql)) {
            $dql .= "AND $searchDql";
        }

        if (isset($categoryFilter) && !empty($categoryFilter)) {
            // reformat array
            $categories = array_values($categoryFilter);
            // add to dql
            $dql .= "AND c.category IN (:categories) ";
        }

        // generate query
        $query = $this->_em->createQuery($dql);

        // Add query parameters
        $query->setParameters(array(
            'startDate1' => $startDate,
            'startDate2' => $startDate,
            'startDate3' => $startDate,
            'endDate' => $endDate));
        if ($eventStatus <> CalendarEventEntity::ALLSTATUS) {
            $query->setParameter('status', $eventStatus);
        }
        switch ($userFilter) {
            case self::FILTER_PRIVATE:
                $query->setParameters(array(
                    7 => $userid,
                    8 => CalendarEventEntity::SHARING_PRIVATE,
                ));
                break;
            case self::FILTER_ALL:
                $query->setParameters(array(
                    7 => $userid,
                    8 => CalendarEventEntity::SHARING_PRIVATE,
                    9 => CalendarEventEntity::SHARING_GLOBAL,
                ));
                break;
            case self::FILTER_GLOBAL:
            default:
                $query->setParameter(7, CalendarEventEntity::SHARING_GLOBAL);
        }
        if (isset($categories)) {
            $query->setParameter('categories', $categories);
        }

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

    /**
     * get filtered collection of events for admin view
     * This collection is not filtered by Permissions
     * 
     * @param integer $eventStatus
     * @param string $sortDir
     * @param integer $offset
     * @param integer $maxResults
     * @param array $categoryFilter
     * 
     * @return Object Collection 
     */
    public function getEventList($eventStatus, $sortDir, $offset, $maxResults, array $categoryFilter)
    {
        $dql = "SELECT a FROM \Zikula\PostCalendarModule\Entity\CalendarEventEntity a JOIN a.categories c ";
        $where = array();
        if ($eventStatus <> CalendarEventEntity::ALLSTATUS) {
            $where[] = "a.eventstatus = :status ";
        }
        if (isset($categoryFilter) && !empty($categoryFilter)) {
            // reformat array
            $categories = array_values($categoryFilter);
            // add to dql
            $where[] = "c.category IN (:categories) ";
        }
        if (!empty($where)) {
            $dql .= "WHERE " . implode(' AND ', $where);
        }
        $dql .= "ORDER BY $sortDir ";
        // generate query
        $query = $this->_em->createQuery($dql);
        if ($eventStatus <> CalendarEventEntity::ALLSTATUS) {
            $query->setParameter('status', $eventStatus);
        }
        if (isset($categories)) {
            $query->setParameter('categories', $categories);
        }
        if ($offset > 0) {
            $query->setFirstResult($offset);
        }
        $query->setMaxResults($maxResults);
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

    /**
     * Change status of events
     * 
     * @param integer $status
     * @param array $eids
     * @return boolean 
     */
    public function updateEventStatus($status, array $eids)
    {
        $dql = "UPDATE " . self::CalendarEventEntity . " a " .
                "SET a.eventstatus = :eventstatus " .
                "WHERE a.eid IN (:eids)";
        $query = $this->_em->createQuery($dql);
        $query->setParameters(array(
            'eventstatus' => $status,
            'eids' => $eids,
        ));
        try {
            $query->getResult();
            $this->_em->clear();
        } catch (Exception $e) {
            echo "<pre>";
            var_dump($e->getMessage());
            var_dump($query->getDQL());
            var_dump($query->getParameters());
            var_dump($query->getSQL());
            die;
        }
        return true;
    }

    /**
     * delete an array of events by eid
     * 
     * @param array $eids
     * @return boolean 
     */
    public function deleteEvents(array $eids)
    {
        foreach ($eids as $eid) {
            $event = $this->_em
                    ->getRepository('CalendarEventEntity')
                    ->findOneBy(array(
                'eid' => $eid));
            $this->_em->remove($event);
        }
        try {
            $this->_em->flush();
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * retrieve an event from hook parameters
     * 
     * @param Zikula_DisplayHook $hook
     * @param integer $eid
     * @return object 
     */
    public function getHookedEvent(Zikula_DisplayHook $hook, $eid = null)
    {
        $dql = "SELECT a FROM " . self::CalendarEventEntity . " a JOIN a.categories c " .
                "WHERE a.hooked_modulename = :modulename " .
                "AND a.hooked_objectid = :objectid " .
                "AND a.hooked_area = :area ";
        if (isset($eid)) {
            $dql .= "AND a.eid = :eid ";
        }
        $query = $this->_em->createQuery($dql);
        $query->setParameters(array(
            'modulename' => $hook->getCaller(),
            'objectid' => $hook->getId(),
            'area' => $hook->getAreaId(),
        ));
        if (isset($eid)) {
            $query->setParameter('eid', $eid);
        }

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
        return isset($result[0]) ? $result[0] : null;
    }

}