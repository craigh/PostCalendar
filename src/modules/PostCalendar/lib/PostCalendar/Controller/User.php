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
        $popup = $this->request->getGet()->get('popup', $this->request->getPost()->get('popup', false));
        $pc_username = $this->request->getGet()->get('pc_username', $this->request->getPost()->get('pc_username', ''));
        $eid = $this->request->getGet()->get('eid', $this->request->getPost()->get('eid', 0));
        $jumpday = $this->request->getGet()->get('jumpDay', $this->request->getPost()->get('jumpDay', null));
        $jumpmonth = $this->request->getGet()->get('jumpMonth', $this->request->getPost()->get('jumpMonth', null));
        $jumpyear = $this->request->getGet()->get('jumpYear', $this->request->getPost()->get('jumpYear', null));
        $filtercats = $this->request->getGet()->get('postcalendar_events', $this->request->getPost()->get('postcalendar_events', null));
        $func = $this->request->getGet()->get('func', $this->request->getPost()->get('func'));
        $jumpargs    = array(
            'jumpday' => $jumpday,
            'jumpmonth' => $jumpmonth,
            'jumpyear' => $jumpyear);
        $viewtype = isset($args['viewtype']) ? strtolower($args['viewtype']) : strtolower($this->request->getGet()->get('viewtype', $this->request->getPost()->get('viewtype', _SETTING_DEFAULT_VIEW)));
        $Date = isset($args['Date']) ? strtolower($args['Date']) : $this->request->getGet()->get('Date', $this->request->getPost()->get('$viewtype', PostCalendar_Util::getDate($jumpargs)));
        $prop = isset($args['prop']) ? $args['prop'] : (string)$this->request->getGet()->get('prop', null);
        $cat = isset($args['cat']) ? $args['cat'] : (string)$this->request->getGet()->get('cat', null);
        
        if (empty($filtercats) && !empty($prop) && !empty($cat)) {
            $filtercats['__CATEGORIES__'][$prop] = $cat;
        }
    
        if (empty($Date) && empty($viewtype)) {
            return LogUtil::registerArgsError();
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
                    $y = substr($Date, 0, 4);
                    $m = substr($Date, 4, 2);
                    $d = substr($Date, 6, 2);
                    $event['eventDate'] = "$y-$m-$d";
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
                    $this->view->assign('DATE', $Date);
                    return $this->view->fetch('user/view_event_details.tpl');
                }
                break;

            default:
                $class = 'PostCalendar_CalendarView_' . ucfirst($viewtype);
                $calendar = new $class($this->view, $Date, $pc_username, $filtercats);
                return $calendar->render();
                break;
        } // end switch
    }

    /**
     * compute the minimal information needed for a cacheid based on viewtype and date
     * @param string $Date
     * @param string $viewtype
     * @return string
     */
    private function computeCacheTagDate($Date, $viewtype)
    {
        switch ($viewtype) {
            case 'year':
                $tag = substr($Date, 0, 4); // year only YYYY
                break;
            case 'month':
                $tag = substr($Date, 0, 6); // year and month YYYYMM
                break;
            case 'week':
                // first day of week
                $Date_Calc = new Date_Calc();
                $tag = $Date_Calc->beginOfWeek(substr($Date, 6, 2), substr($Date, 4, 2), substr($Date, 0, 4), '%Y%m%d');
                break;
            case 'day':
            case 'list':
            case 'xml':
            default:
                $tag = substr($Date, 0, 8); // full date YYYYMMDD
                break;
        }
        return $tag;
    }
} // end class def
