<?php
/**
 * This is the event handler file
 **/
class PostCalendar_Form_Handler_editHandler
{
    var $eid;

    function initialize(&$render)
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }

        $this->eid = FormUtil::getPassedValue('eid');

        return true;
    }

    function handleCommand(&$render, &$args)
    {
        $url = null;

        // Fetch event data from DB to confirm event exists
        $event = DBUtil::selectObjectByID('postcalendar_events', $this->eid, 'eid');
        if (count($event) == 0) {
            return LogUtil::registerError($this->__f('Error! There are no events with ID %s.', $this->eid));
        }

        if ($args['commandName'] == 'delete') {
            if ((SessionUtil::getVar('uid') != $event['informant']) and (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))) {
                return LogUtil::registerError($this->__('Sorry! You do not have authorization to delete this event.'));
            }
            $result = DBUtil::deleteObjectByID('postcalendar_events', $this->eid, 'eid');
            if ($result === false) {
                return LogUtil::registerError($this->__("Error! An 'unidentified error' occurred."));
            }
            LogUtil::registerStatus($this->__('Done! The event was deleted.'));
            $this->callHooks('item', 'delete', $this->eid, array(
                'module' => 'PostCalendar'));

            $redir = ModUtil::url('PostCalendar', 'user', 'view', array(
                'viewtype' => _SETTING_DEFAULT_VIEW));
            return $render->redirect($redir);
        } else if ($args['commandName'] == 'cancel') {
            $url = ModUtil::url('PostCalendar', 'user', 'view', array(
                'eid' => $this->eid,
                'viewtype' => 'details',
                'Date' => $event['Date']));
        }

        if ($url != null) {
            /*ModUtil::apiFunc('PageLock', 'user', 'releaseLock', array('lockName' => "HowtoPnFormsRecipe{$this->recipeId}")); */
            return $render->redirect($url);
        }

        return true;
    }
}
