<?php

/**
 * post pending content to Event handler
 *
 * @author craig
 */
class PostCalendar_Handlers {

    public static function pendingContent(Zikula_Event $event)
    {
        $collection = new Zikula_Collection_Container('PostCalendar');
        $collection->add(new Zikula_Provider_AggregateItem('submission', __('pending event'), 1, 'Admin', 'listqueued'));
        $event->getSubject()->add($collection);
    }

}