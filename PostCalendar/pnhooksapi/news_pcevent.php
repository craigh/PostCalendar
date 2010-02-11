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
    $article = pnModAPIFunc('News', 'user', 'get', $args);

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
        'hometext'          => ":html:" . __('Article link: ', $dom) . "<a href='" . pnModURL('News', 'user', 'display', array('sid' => $article['sid'])) . "'>" . substr($article['hometext'], 0, 32) . "...</a>",
        'aid'               => $article['aid'], // userid of creator
        'time'              => $article['time'], // mysql timestamp YYYY-MM-DD HH:MM:SS
        'informant'         => $article['aid'], // userid of creator
        'eventDate'         => substr($article['from'], 0, 10), // date of event: YYYY-MM-DD
        'duration'          => 3600, // default duration in seconds (not used)
        'recurrtype'        => 0, // norepeat
        'recurrspec'        => 'a:5:{s:17:"event_repeat_freq";s:0:"";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:0:"";}', // default recurrance info - serialized (not used)
        'startTime'         => substr($article['from'], -8), // time of event: HH:MM:SS
        'alldayevent'       => 1, // yes
        'location'          => 'a:6:{s:14:"event_location";s:0:"";s:13:"event_street1";s:0:"";s:13:"event_street2";s:0:"";s:10:"event_city";s:0:"";s:11:"event_state";s:0:"";s:12:"event_postal";s:0:"";}', // default location info - serialized (not used)
        'eventstatus'       => $eventstatus,
        'sharing'           => 3, // global
        'hooked_modulename' => 'news',
        'hooked_objectid'   => $article['sid'],
        '__CATEGORIES__'    => $args['hookinfo']['cats'],
        '__META__'          => array('module' => 'PostCalendar'),
    );
    return $event;
}