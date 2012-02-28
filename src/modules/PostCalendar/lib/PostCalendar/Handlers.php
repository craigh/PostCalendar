<?php

/**
 * post pending content to pending_content Event handler
 *
 * @author Craig Heydenburg
 */
class PostCalendar_Handlers {

    public static function pendingContent(Zikula_Event $event)
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $count = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->getEventCount(PostCalendar_Entity_CalendarEvent::QUEUED);
        if ($count > 0) {
            $collection = new Zikula_Collection_Container('PostCalendar');
            $collection->add(new Zikula_Provider_AggregateItem('submission', _n('Calendar event', 'Calendar events', $count, $dom), $count, 'admin', 'listevents'));
            $event->getSubject()->add($collection);
        }
    }
    public static function getTypes(Zikula_Event $event) {
        $types = $event->getSubject();
        $types->add('PostCalendar_ContentType_PostCalEvent');
        $types->add('PostCalendar_ContentType_PostCalEvents');
    }
}