<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
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
        $popup       = FormUtil::getPassedValue('popup');
        $pc_username = FormUtil::getPassedValue('pc_username');
        $eid         = FormUtil::getPassedValue('eid');
        $jumpday     = FormUtil::getPassedValue('jumpDay');
        $jumpmonth   = FormUtil::getPassedValue('jumpMonth');
        $jumpyear    = FormUtil::getPassedValue('jumpYear');
        $jumpargs    = array(
            'jumpday' => $jumpday,
            'jumpmonth' => $jumpmonth,
            'jumpyear' => $jumpyear);
        $filtercats  = FormUtil::getPassedValue('postcalendar_events');
        $func        = FormUtil::getPassedValue('func');

        // the following are pulled from getPassedValue unless presented in the $args array (via Content module for example)
        $viewtype    = isset($args['viewtype']) ? strtolower($args['viewtype']) : strtolower(FormUtil::getPassedValue('viewtype', _SETTING_DEFAULT_VIEW));
        $Date        = isset($args['Date']) ? $args['Date'] : FormUtil::getPassedValue('Date', PostCalendar_Util::getDate($jumpargs));
        $prop        = isset($args['prop']) ? $args['prop'] : (string)FormUtil::getPassedValue('prop', null, 'GET');
        $cat         = isset($args['cat']) ? $args['cat'] : (string)FormUtil::getPassedValue('cat', null, 'GET');

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
                $event = DBUtil::selectObjectByID('postcalendar_events', $eid, 'eid');
                $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);

                // is event allowed for this user?
                if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != UserUtil::getVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
                    // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
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
//                    $this->view->assign('TODAY_DATE', DateUtil::getDatetime('', '%Y-%m-%d'));
                    $this->view->assign('TODAY_DATE', date('Y-m-d'));
                    $this->view->assign('DATE', $Date);
                    return $this->view->fetch('user/view_event_details.tpl');
                }
                break;
    
            default:
                $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_OVERVIEW), LogUtil::getErrorMsgPermission());
                $cacheTagDate = $this->computeCacheTagDate($Date, $viewtype);
                $this->view->setCacheId($cacheTagDate . '|' . UserUtil::getVar('uid'));
                if (!$this->view->is_cached('user/view_' . $viewtype . '.tpl')) {
                    $out = ModUtil::apiFunc('PostCalendar', 'user', 'buildView', array(
                        'Date'        => $Date,
                        'viewtype'    => $viewtype,
                        'pc_username' => $pc_username,
                        'filtercats'  => $filtercats,
                        'func'        => $func));
                    foreach ($out as $var => $val) {
                        $this->view->assign($var, $val);
                    }
                }
                return $this->view->fetch('user/view_' . $viewtype . '.tpl');
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
