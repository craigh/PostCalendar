<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PostCalendar_Api_Search extends Zikula_AbstractApi
{
    /**
     * Search plugin info
     **/
    public function info()
    {
        return array(
            'title' => 'PostCalendar',
            'functions' => array(
                'PostCalendar' => 'search'));
    }
    
    /**
     * Search form component
     **/
    public function options($args)
    {
        if (SecurityUtil::checkPermission('PostCalendar::', ':*', ACCESS_OVERVIEW)) {
            $renderer = Zikula_View::getInstance('PostCalendar');
            // Create output object - this object will store all of our output so that
            // we can return it easily when required
            $active = (isset($args['active']) && isset($args['active']['PostCalendar'])) || !isset($args['active']);
            $renderer->assign('active', $active);
    
            // assign category info
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
            $renderer->assign('catregistry', $catregistry);
    
            $props = array_keys($catregistry);
            $renderer->assign('firstprop', $props[0]);
    
            return $renderer->fetch('search/options.tpl');
        }
    
        return '';
    }
    
    /**
     * Search plugin main function
     * args expected:
     *     $args[q] (user entered search terms)
     *     $args[searchtype] (AND/OR/EXACT)
     *     $args[searchorder] (newest/oldest/alphabetical)
     *     $args[numlimit] (result limit)
     *     $args[page]
     *     $args[startnum]
     *     $args[categories] (postcalendar specific)
     *     $args[searchstart] (postcalendar specific)
     *     $args[searchend] (postcalendar specific)
     **/
    public function search($args)
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', ':*', ACCESS_OVERVIEW)) {
            return true;
        }
    
        $searchargs = array();
        if (!empty($args['categories'])) {
            $searchargs['filtercats'] = PostCalendar_Api_Event::formatCategoryFilter($args['categories']);
        }
        $searchargs['searchstart'] = isset($args['searchstart']) ? $args['searchstart'] : 0;
        $args['searchend'] = isset($args['searchend']) ? $args['searchend'] : 2;
        $searchargs['searchend'] = ($searchargs['searchstart'] == $args['searchend']) ? 2 : $args['searchend'];
    
        ModUtil::dbInfoLoad('Search');

        // this is a bit of a hacky way to ustilize this API for Doctrine calls.
        // the 'a' prefix is the table alias in CalendarEventRepository
        $where = Search_Api_User::construct_where($args, array(
            'a.title',
            'a.hometext'), null);
        if (!empty($where)) {
            $searchargs['s_keywords'] = trim(substr(trim($where), 1, -1));
        }
    
        $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', $searchargs);
        // $eventsByDate = array(Date[YYYY-MM-DD]=>array(key[int]=>array(assockey[name]=>values)))
        // !Dates exist w/o data
    
        $sessionId = session_id();
    
        // Process the result set and insert into search result table
        foreach ($eventsByDate as $date) {
            if (count($date) > 0) {
                foreach ($date as $event) {
                    $eventDate = DateTime::createFromFormat('Y-m-d', $event['eventDate']);
                    $title = $event['title'] . " (" . $eventDate->format($this->getVar('pcEventDateFormat')) . ")";
                    $start = $event['alldayevent'] ? "12:00:00" : $event['sortTime'];
                    list($h, $m, $s) = explode(':', $start);
                    $created = $eventDate->setTime($h, $m);
                    $items = array('title' => $title,
                                   'text'  => $event['hometext'],
                                   'extra' => $event['eid'],
                                   'created' => $created->format('Y-m-d G:i:s'),
                                   'module'  => 'PostCalendar',
                                   'session' => $sessionId);
                }
                $insertResult = DBUtil::insertObject($items, 'search_result');
                if (!$insertResult) {
                    return LogUtil::registerError($this->__('Error! Could not load items.'));
                }
            }
        }
    
        return true;
    }
    
    /**
     * Do last minute access checking and assign URL to items
     *
     * Access checking is ignored since access check has
     * already been done. But we do add a URL to the found user
     */
    public function search_check($args)
    {
        $datarow = &$args['datarow'];
        $eid = $datarow['extra'];
        $date = str_replace("-", "", substr($datarow['created'], 0, 10));
    
        $datarow['url'] = ModUtil::url('PostCalendar', 'user', 'display', array(
            'date' => $date,
            'eid' => $eid,
            'viewtype' => 'details'));
        // needed: index.php?module=PostCalendar&func=main&Date=20090726&viewtype=details&eid=1718
    
        return true;
    }
} // end class def