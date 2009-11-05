<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/pntables.php $
 * @version     $Id: pntables.php 172 2009-07-16 01:36:32Z craigh $
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * Search plugin info
 **/
function postcalendar_searchapi_info()
{
    return array('title' => 'PostCalendar',
                 'functions' => array('PostCalendar' => 'search'));
}

/**
 * Search form component
 **/
function postcalendar_searchapi_options($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (SecurityUtil::checkPermission('PostCalendar::', ':*', ACCESS_OVERVIEW)) {
        // Create output object - this object will store all of our output so that
        // we can return it easily when required
        $renderer = pnRender::getInstance('PostCalendar');
        $active = (isset($args['active']) && isset($args['active']['PostCalendar'])) || !isset($args['active']);
        $renderer->assign('active', $active);

        // assign category info
        // load the category registry util
        if (!Loader::loadClass('CategoryRegistryUtil')) {
            pn_exit(__f('Error! Unable to load class [%s%]', 'CategoryRegistryUtil'));
        }
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $renderer->assign('catregistry', $catregistry);

        return $renderer->fetch('search/postcalendar_search_options.htm');
    }

    return '';
}

/**
 * Search plugin main function
 * args expected:
    $args[q] (user entered search terms)
    $args[searchtype] (AND/OR/EXACT)
    $args[searchorder] (newest/oldest/alphabetical)
    $args[numlimit] (result limit)
    $args[page]
    $args[startnum]
    $args[category] (postcalendar specific)
 **/
function postcalendar_searchapi_search($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!SecurityUtil::checkPermission('PostCalendar::', ':*', ACCESS_OVERVIEW)) {
        return true;
    }

    $searchargs = array();
    if (!empty($args['postcalendar_events'])) {
        $searchargs['filtercats'] = $args['postcalendar_events'];
    }

    pnModDBInfoLoad('Search');
    $pntable = pnDBGetTables();
    $postcalendartable = $pntable['postcalendar_events'];
    $postcalendarcolumn = $pntable['postcalendar_events_column'];
    $searchTable = $pntable['search_result'];
    $searchColumn = $pntable['search_result_column'];

    $where = search_construct_where($args,
                                    array($postcalendarcolumn['title'],
                                          $postcalendarcolumn['hometext']),
                                    null);
    if (!empty($where)) $searchargs['s_keywords'] = trim(substr(trim($where), 1, -1));

    $eventsByDate = pnModAPIFunc('PostCalendar','event','getEvents',$searchargs);
    // $eventsByDate = array(Date[YYYY-MM-DD]=>array(key[int]=>array(assockey[name]=>values)))
    // !Dates exist w/o data

    $sessionId = session_id();

    $insertSql =
        "INSERT INTO $searchTable
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
                $sql = $insertSql . '('
                   . '\'' . DataUtil::formatForStore($event['title']) . '\', '
                   . '\'' . DataUtil::formatForStore($event['text']) . '\', '
                   . '\'' . DataUtil::formatForStore($event['eid']) . '\', '
                   . '\'' . DataUtil::formatForStore($event['eventDate']) . '\', '
                   . '\'' . 'PostCalendar' . '\', '
                   . '\'' . DataUtil::formatForStore($sessionId) . '\')';
            }
            $insertResult = DBUtil::executeSQL($sql);
            if (!$insertResult) {
                return LogUtil::registerError (__('Error! Could not load items.', $dom));
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
function postcalendar_searchapi_search_check(&$args)
{
    $datarow = &$args['datarow'];
    $eid = $datarow['extra'];
    $date = str_replace("-", "", substr($datarow['created'], 0, 10));

    $datarow['url'] = pnModUrl('PostCalendar', 'user', 'view', array('Date' => $date, 'eid' => $eid, 'viewtype' => 'details'));
    // needed: index.php?module=PostCalendar&func=view&Date=20090726&viewtype=details&eid=1718

    return true;
}

