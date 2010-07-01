<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PostCalendar_Api_Search extends Zikula_Api
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
            // Create output object - this object will store all of our output so that
            // we can return it easily when required
            $active = (isset($args['active']) && isset($args['active']['PostCalendar'])) || !isset($args['active']);
            $this->renderer->assign('active', $active);
    
            // assign category info
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
            $this->renderer->assign('catregistry', $catregistry);
    
            $props = array_keys($catregistry);
            $this->renderer->assign('firstprop', $props[0]);
    
            return $this->renderer->fetch('search/options.tpl');
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
     *     $args[__CATEGORIES__] (postcalendar specific)
     *     $args[searchstart] (postcalendar specific)
     *     $args[searchend] (postcalendar specific)
     **/
    public function search($args)
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', ':*', ACCESS_OVERVIEW)) {
            return true;
        }
    
        $searchargs = array();
        if (!empty($args['__CATEGORIES__'])) {
            $searchargs['filtercats']['__CATEGORIES__'] = $args['__CATEGORIES__'];
        }
    
        ModUtil::dbInfoLoad('Search');
        $pntable = System::dbGetTables();
        $postcalendartable = $pntable['postcalendar_events'];
        $postcalendarcolumn = $pntable['postcalendar_events_column'];
        $searchTable = $pntable['search_result'];
        $searchColumn = $pntable['search_result_column'];
    
        $where = Search_Api_User::construct_where($args, array(
            $postcalendarcolumn['title'],
            $postcalendarcolumn['hometext']), null);
        if (!empty($where)) {
            $searchargs['s_keywords'] = trim(substr(trim($where), 1, -1));
        }
    
        $searchargs['searchstart'] = (!isset($args['searchstart'])) ? 0 : $args['searchstart'];
        $searchargs['searchend'] = (($args['searchstart'] == $args['searchend']) || (!isset($args['searchend']))) ? 2 : $args['searchend']; // user set both options to 'now'
    
        $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', $searchargs);
        // $eventsByDate = array(Date[YYYY-MM-DD]=>array(key[int]=>array(assockey[name]=>values)))
        // !Dates exist w/o data
    
        $sessionId = session_id();
    
        $insertSql = "INSERT INTO $searchTable
              ($searchColumn[title],
              $searchColumn[text],
              $searchColumn[extra],
              $searchColumn[created],
              $searchColumn[module],
              $searchColumn[session])
            VALUES ";
    
        // Process the result set and insert into search result table
        foreach ($eventsByDate as $date) {
            if (count($date) > 0) {
                foreach ($date as $event) {
                    $title = $event['title'] . " (" . DateUtil::strftime(ModUtil::getVar('PostCalendar', 'pcEventDateFormat'), strtotime($event['eventDate'])) . ")";
                    $start = $event['alldayevent'] ? "12:00:00" : $event['startTime'];
                    $created = $event['eventDate'] . " " . $start;
                    $sql = $insertSql . '('
                        . '\'' . DataUtil::formatForStore($title) . '\', '
                        . '\'' . DataUtil::formatForStore($event['hometext']) . '\', '
                        . '\'' . DataUtil::formatForStore($event['eid']) . '\', '
                        . '\'' . DataUtil::formatForStore($created) . '\', '
                        . '\'' . 'PostCalendar' . '\', '
                        . '\'' . DataUtil::formatForStore($sessionId) . '\')';
                }
                $insertResult = DBUtil::executeSQL($sql);
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
    public function search_check(&$args)
    {
        $datarow = &$args['datarow'];
        $eid = $datarow['extra'];
        $date = str_replace("-", "", substr($datarow['created'], 0, 10));
    
        $datarow['url'] = ModUtil::url('PostCalendar', 'user', 'view', array(
            'Date' => $date,
            'eid' => $eid,
            'viewtype' => 'details'));
        // needed: index.php?module=PostCalendar&func=view&Date=20090726&viewtype=details&eid=1718
    
        return true;
    }
} // end class def