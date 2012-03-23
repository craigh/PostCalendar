<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class PostCalendar_Controller_User extends Zikula_AbstractController
{
    private $allowedViewtypes = array(
        'event',
        'day',
        'week',
        'month',
        'year',
        'list',
        'xml',
    );
    
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
        $pc_username = $this->request->query->get('pc_username', $this->request->request->get('pc_username', ''));
        $eid = $this->request->query->get('eid', $this->request->request->get('eid', 0));
        $filtercats = $this->request->query->get('pc_categories', $this->request->request->get('pc_categories', null));
        $jumpargs = array(
            'jumpday' => $this->request->query->get('jumpDay', $this->request->request->get('jumpDay', null)),
            'jumpmonth' => $this->request->query->get('jumpMonth', $this->request->request->get('jumpMonth', null)),
            'jumpyear' => $this->request->query->get('jumpYear', $this->request->request->get('jumpYear', null)));
        $viewtype = isset($args['viewtype']) ? strtolower($args['viewtype']) : strtolower($this->request->query->get('viewtype', $this->request->request->get('viewtype', _SETTING_DEFAULT_VIEW)));
        $date = isset($args['date']) ? $args['date'] : $this->request->query->get('date', $this->request->request->get('date', PostCalendar_Util::getDate($jumpargs)));
        $prop = isset($args['prop']) ? $args['prop'] : (string)$this->request->query->get('prop', null);
        $cat = isset($args['cat']) ? $args['cat'] : (string)$this->request->query->get('cat', null);
        
        if (empty($filtercats) && !empty($prop) && !empty($cat)) {
            $filtercats[$prop] = $cat;
        }
    
        if (empty($date) && empty($viewtype)) {
            return LogUtil::registerArgsError();
        }
        
        if (!in_array($viewtype, $this->allowedViewtypes)) {
            return LogUtil::registerError($this->__('Unsupported Viewtype.'));
        }
        
        if (!is_object($date)) {
            $date = DateTime::createFromFormat('Ymd', $date);
        }

        // this is for the navigation
        $this->view->assign('viewtypeselected', $viewtype);
    
        $class = 'PostCalendar_CalendarView_' . ucfirst($viewtype);
        $calendarView = new $class($this->view, $date, $pc_username, $filtercats, $eid);
        return $calendarView->render();
    }

} // end class def
