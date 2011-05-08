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
        $event = DBUtil::selectObjectByID('postcalendar_events', $this->eid, 'eid');
        if (count($event) == 0) {
            return LogUtil::registerError($this->__f('Error! There are no events with ID %s.', $this->eid));
        }

        if ($args['commandName'] == 'delete') {
            if ((UserUtil::getVar('uid') != $event['informant']) and (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))) {
                return LogUtil::registerError($this->__('Sorry! You do not have authorization to delete this event.'));
            }
            $result = DBUtil::deleteObjectByID('postcalendar_events', $this->eid, 'eid');
            if ($result === false) {
                return LogUtil::registerError($this->__("Error! An 'unidentified error' occurred."));
            }
            LogUtil::registerStatus($this->__('Done! The event was deleted.'));

            $this->notifyHooks(new Zikula_ProcessHook('postcalendar.hook.events.process.delete', $this->eid));

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
