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
namespace Zikula\PostCalendarModule\Helper;

/**
 * post pending content to pending_content Event handler
 */
class PostCalendarHandlers
{

    /**
     * Event handler to provide pending content
     * @param Zikula_Event $event 
     */
    public static function pendingContent(Zikula_Event $event)
    {
        if (ModUtil::getVar('PostCalendar', 'pcPendingContent') == 1) {
            $dom = ZLanguage::getModuleDomain('PostCalendar');
            $em = ServiceUtil::getService('doctrine.entitymanager');
            $count = $em->getRepository('\Zikula\PostCalendarModule\Entity\CalendarEventEntity')->getEventCount(CalendarEventEntity::QUEUED);
            if ($count > 0) {
                $collection = new Zikula_Collection_Container('PostCalendar');
                $collection->add(new Zikula_Provider_AggregateItem('submission', _n('Calendar event', 'Calendar events', $count, $dom), $count, 'admin', 'listevents'));
                $event->getSubject()->add($collection);
            }
        }
    }

    /**
     * Event handler to provide Content module ContentTypes
     * @param Zikula_Event $event 
     */
    public static function getTypes(Zikula_Event $event)
    {
        $types = $event->getSubject();
        $types->add('PostCalendar_ContentType_PostCalEvent');
        $types->add('PostCalendar_ContentType_PostCalEvents');
    }

}