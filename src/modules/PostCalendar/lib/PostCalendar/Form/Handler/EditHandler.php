<?php
/**
 * This is the Form event handler file
 * used in the delete event sequence
 **/
class PostCalendar_Form_Handler_EditHandler extends Zikula_Form_AbstractHandler
{
    var $eid;

    function initialize($view)
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            throw new Zikula_Exception_Forbidden(LogUtil::getErrorMsgPermission());
        }

        $this->eid = FormUtil::getPassedValue('eid');

        return true;
    }

    function handleCommand($view, &$args)
    {
        $url = null;

        // Fetch event data from DB to confirm event exists
        $event = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->find($this->eid)->getOldArray();
        if (count($event) == 0) {
            return LogUtil::registerError($this->__f('Error! There are no events with ID %s.', $this->eid));
        }

        if ($args['commandName'] == 'delete') {
            if ((UserUtil::getVar('uid') != $event['informant']) and (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))) {
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
                'viewtype' => _SETTING_DEFAULT_VIEW));
            return $view->redirect($redir);
        } else if ($args['commandName'] == 'cancel') {
            $url = ModUtil::url('PostCalendar', 'user', 'display', array(
                'eid' => $this->eid,
                'viewtype' => 'details',
                'Date' => $event['Date']));
        }

        if ($url != null) {
            /*ModUtil::apiFunc('PageLock', 'user', 'releaseLock', array('lockName' => "HowtoPnFormsRecipe{$this->recipeId}")); */
            return $view->redirect($url);
        }

        return true;
    }
}
