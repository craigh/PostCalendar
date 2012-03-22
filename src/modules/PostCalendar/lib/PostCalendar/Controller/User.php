<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class PostCalendar_Controller_User extends Zikula_AbstractController
{
    /**
     * main view functions for end user
     */
    public function main($args)
    {
		$this->redirect(ModUtil::url('PostCalendar', 'user', 'display', $args));
    }

    public function view($args)
    {
		$this->redirect(ModUtil::url('PostCalendar', 'user', 'display', $args));
    }
    
    /**
     * display calendar events in requested viewtype
     */
    public function display($args)
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_OVERVIEW), LogUtil::getErrorMsgPermission());

        // get the vars that were passed in
        $popup = $this->request->query->get('popup', $this->request->request->get('popup', false));
        $pc_username = $this->request->query->get('pc_username', $this->request->request->get('pc_username', ''));
        $eid = $this->request->query->get('eid', $this->request->request->get('eid', 0));
        $filtercats = $this->request->query->get('postcalendar_events', $this->request->request->get('postcalendar_events', null));
        $func = $this->request->query->get('func', $this->request->request->get('func'));
        $jumpargs    = array(
            'jumpday' => $this->request->query->get('jumpDay', $this->request->request->get('jumpDay', null)),
            'jumpmonth' => $this->request->query->get('jumpMonth', $this->request->request->get('jumpMonth', null)),
            'jumpyear' => $this->request->query->get('jumpYear', $this->request->request->get('jumpYear', null)));
        $viewtype = isset($args['viewtype']) ? strtolower($args['viewtype']) : strtolower($this->request->query->get('viewtype', $this->request->request->get('viewtype', _SETTING_DEFAULT_VIEW)));
        $date = isset($args['date']) ? $args['date'] : $this->request->query->get('date', $this->request->request->get('date', PostCalendar_Util::getDate($jumpargs)));
        $prop = isset($args['prop']) ? $args['prop'] : (string)$this->request->query->get('prop', null);
        $cat = isset($args['cat']) ? $args['cat'] : (string)$this->request->query->get('cat', null);
        
        if (empty($filtercats) && !empty($prop) && !empty($cat)) {
            $filtercats['__CATEGORIES__'][$prop] = $cat;
        }
    
        if (empty($date) && empty($viewtype)) {
            return LogUtil::registerArgsError();
        }
        if (!is_object($date)) {
            $date = DateTime::createFromFormat('Ymd', $date);
        }

        $this->view->assign('viewtypeselected', $viewtype);
    
        switch ($viewtype) {
            case 'details':
                $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_READ), LogUtil::getErrorMsgPermission());

                // get the event from the DB
                $event = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->find($eid)->getOldArray();
                $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);

                // is event allowed for this user?
                if (($event['sharing'] == PostCalendar_Entity_CalendarEvent::SHARING_PRIVATE 
                        && $event['aid'] != UserUtil::getVar('uid') 
                        && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN))
                        || ((!SecurityUtil::checkPermission('PostCalendar::Event', "$event[title]::$event[eid]", ACCESS_OVERVIEW))
                        || (!CategoryUtil::hasCategoryAccess($event['__CATEGORIES__'], 'PostCalendar')))) {
                    // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
                    // or if specific event is permission controlled or if Category is denied
                    return LogUtil::registerError($this->__('You do not have permission to view this event.'));
                }

                $this->view->setCacheId($eid);
                // caching won't help much in this case because security check comes 
                // after fetch from db, so don't use is_cached, just fetch after
                // normal routine.

                // since recurrevents are dynamically calculcated, we need to change the date
                // to ensure that the correct/current date is being displayed (rather than the
                // date on which the recurring booking was executed).
                if ($event['recurrtype']) {
                    $event['eventDate'] = $date->format('Ymd');
                }
                $this->view->assign('loaded_event', $event);

                if ($popup == true) {
                    $this->view->assign('popup', $popup);
                    $this->view->display('event/view.tpl');
                    return true; // displays template without theme wrap
                } else {
                    if ((SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADD) && (UserUtil::getVar('uid') == $event['aid'])) || SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
                        $this->view->assign('EVENT_CAN_EDIT', true);
                    } else {
                        $this->view->assign('EVENT_CAN_EDIT', false);
                    }
                    $this->view->assign('TODAY_DATE', date('Y-m-d'));
                    $this->view->assign('DATE', $date);
                    return $this->view->fetch('user/view_event_details.tpl');
                }
                break;

            default:
                $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_OVERVIEW), LogUtil::getErrorMsgPermission());
                $class = 'PostCalendar_CalendarView_' . ucfirst($viewtype);
                $calendar = new $class($this->view, $date, $pc_username, $filtercats);
                return $calendar->render();
                break;
        } // end switch
    }

} // end class def
