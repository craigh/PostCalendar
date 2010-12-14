<?php
/**
 * This is the Form event handler file
 * used in the delete event sequence
 **/
class PostCalendar_Form_Handler_EditHandler extends Form_Handler
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
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        $url = null;

        // Fetch event data from DB to confirm event exists
        $event = DBUtil::selectObjectByID('postcalendar_events', $this->eid, 'eid');
        if (count($event) == 0) {
            return LogUtil::registerError(__f('Error! There are no events with ID %s.', $this->eid, $dom));
        }

        if ($args['commandName'] == 'delete') {
            if ((SessionUtil::getVar('uid') != $event['informant']) and (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))) {
                return LogUtil::registerError(__('Sorry! You do not have authorization to delete this event.', $dom));
            }
            $result = DBUtil::deleteObjectByID('postcalendar_events', $this->eid, 'eid');
            if ($result === false) {
                return LogUtil::registerError(__("Error! An 'unidentified error' occurred.", $dom));
            }
            LogUtil::registerStatus(__('Done! The event was deleted.', $dom));

            $this->notifyHooks('postcalendar.hook.events.process.delete', $event, $this->eid);

            $redir = ModUtil::url('PostCalendar', 'user', 'main', array(
                'viewtype' => _SETTING_DEFAULT_VIEW));
            return $render->redirect($redir);
        } else if ($args['commandName'] == 'cancel') {
            $url = ModUtil::url('PostCalendar', 'user', 'main', array(
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
