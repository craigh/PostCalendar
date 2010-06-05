<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * get news info for Postcalendar event creation
 *
 * @author  Craig Heydenburg
 * @access  public
 * @args    array(objectid) news id
 * @return  array() event info or false if no desire to publish event
 */
function postcalendar_hooksapi_news_pcevent($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
        return false;
    }
    $args['SQLcache'] = false;
    $article = ModUtil::apiFunc('News', 'user', 'get', $args);

    $eventstatus = 1; // approved
    if ($article['published_status'] != 0) { // article not published yet (draft, etc)
        return false;
    }

    $now = DateUtil::getDatetime(null, '%Y-%m-%d %H:%M:%S');
    $diff = DateUtil::getDatetimeDiff_AsField($now, $article['from'], 6);
    if ($diff > 0) {
        $eventstatus = -1; // hide published events
    }

    // change below based on $article array
    $event = array(
        'title'             => __('News: ', $dom) . $article['title'],
        'hometext'          => ":html:" . __('Article link: ', $dom) . "<a href='" . ModUtil::url('News', 'user', 'display', array('sid' => $article['sid'])) . "'>" . substr($article['hometext'], 0, 32) . "...</a>",
        'aid'               => $article['aid'], // userid of creator
        'time'              => $article['time'], // mysql timestamp YYYY-MM-DD HH:MM:SS
        'informant'         => $article['aid'], // userid of creator
        'eventDate'         => substr($article['from'], 0, 10), // date of event: YYYY-MM-DD
        'duration'          => 3600, // default duration in seconds (not used)
        'startTime'         => substr($article['from'], -8), // time of event: HH:MM:SS
        'alldayevent'       => 1, // yes
        'eventstatus'       => $eventstatus,
        'sharing'           => 3, // global
    );
    return $event;
}