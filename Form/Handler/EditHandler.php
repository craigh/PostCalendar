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
/**
 * This is the Form event handler file
 * used in the delete event sequence
 **/

namespace Zikula\PostCalendarModule\Form\Handler;

use Zikula\PostCalendarModule\Entity\CalendarEventEntity;
use SecurityUtil;
use UserUtil;
use LogUtil;
use ModUtil;

class EditHandler extends \Zikula_Form_AbstractHandler
{
    var $eid;

    function initialize(\Zikula_Form_View $view)
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            throw new Zikula_Exception_Forbidden(LogUtil::getErrorMsgPermission());
        }

        $this->eid = $this->request->query->get('eid');

        return true;
    }

    function handleCommand(\Zikula_Form_View $view, &$args)
    {
        $url = null;

        // Fetch event data from DB to confirm event exists
        $event = $this->entityManager->getRepository('Zikula\PostCalendarModule\Entity\CalendarEventEntity')->find($this->eid);
        $eventArray = $event->getOldArray();
        if (count($event) == 0) {
            return LogUtil::registerError($this->__f('Error! There are no events with ID %s.', $this->eid));
        }

        if ($args['commandName'] == 'delete') {
            if ((UserUtil::getVar('uid') != $eventArray['informant']) and (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))) {
                return LogUtil::registerError($this->__('Sorry! You do not have authorization to delete this event.'));
            }
            try {
                $this->entityManager->remove($event);
                $this->entityManager->flush();
            } catch (Exception $e) {
                return LogUtil::registerError($e->getMessage());
            }
            LogUtil::registerStatus($this->__('Done! The event was deleted.'));

            $this->notifyHooks(new Zikula_ProcessHook('postcalendar.ui_hooks.events.process_delete', $this->eid));

            $redir = ModUtil::url('PostCalendar', 'user', 'display', array(
                'viewtype' => $this->getVar('pcDefaultView')));
            return $view->redirect($redir);
        } else if ($args['commandName'] == 'cancel') {
            $url = ModUtil::url('PostCalendar', 'user', 'display', array(
                'eid' => $this->eid,
                'viewtype' => 'event'));
        }

        if ($url != null) {
            /*ModUtil::apiFunc('PageLock', 'user', 'releaseLock', array('lockName' => "HowtoPnFormsRecipe{$this->recipeId}")); */
            return $view->redirect($url);
        }

        return true;
    }
}
