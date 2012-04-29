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
        $defaultView = $this->getVar('pcDefaultView');
        // get the vars that were passed in
        $userFilter = $this->request->query->get('userfilter', $this->request->request->get('userfilter', ''));
        $eid = $this->request->query->get('eid', $this->request->request->get('eid', 0));
        $filtercats = $this->request->query->get('pc_categories', $this->request->request->get('pc_categories', null));
        $jumpargs = array(
            'jumpday' => $this->request->query->get('jumpDay', $this->request->request->get('jumpDay', null)),
            'jumpmonth' => $this->request->query->get('jumpMonth', $this->request->request->get('jumpMonth', null)),
            'jumpyear' => $this->request->query->get('jumpYear', $this->request->request->get('jumpYear', null)));
        $viewtype = isset($args['viewtype']) ? strtolower($args['viewtype']) : strtolower($this->request->query->get('viewtype', $this->request->request->get('viewtype', $defaultView)));
        $date = isset($args['date']) ? $args['date'] : $this->request->query->get('date', $this->request->request->get('date', PostCalendar_Util::getDate($jumpargs)));
        $prop = isset($args['prop']) ? $args['prop'] : (string)$this->request->query->get('prop', null);
        $cat = isset($args['cat']) ? $args['cat'] : (string)$this->request->query->get('cat', null);
        $popup = $this->view->getRequest()->query->get('popup', $this->view->getRequest()->request->get('popup', false));

        if (empty($filtercats) && !empty($prop) && !empty($cat)) {
            $filtercats[$prop] = $cat;
        }

        if (empty($date) && empty($viewtype)) {
            return LogUtil::registerArgsError();
        }

        if ($viewtype == 'event') {
            $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_READ), LogUtil::getErrorMsgPermission());
        }

        if (!is_object($date)) {
            $date = DateTime::createFromFormat('Ymd', $date);
        }
        
        $allowedViews = $this->getVar('pcAllowedViews');
        if ((in_array($viewtype, $allowedViews)) || ($viewtype == 'event' && $popup)) {
            $class = 'PostCalendar_CalendarView_' . ucfirst($viewtype);
        } else {
            LogUtil::registerError($this->__('Attempting to view unauthorized viewtype.'));
            $class = 'PostCalendar_CalendarView_' . ucfirst($defaultView);
        }
        $calendarView = new $class($this->view, $date, $userFilter, $filtercats, $eid);
        return $calendarView->render();
    }

}
