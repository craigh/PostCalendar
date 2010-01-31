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
 * @return  boolean    true/false
 * @access  public
 * @args    array(objectid) news id
 * @return  array() event info
 */
function postcalendar_hooksapi_create_news($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
        return false;
    }

    $article = pnModAPIFunc('News', 'user', 'get', $args);

    Loader::loadClass('CategoryUtil');
    $cat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar/Events');

    $dom = ZLanguage::getModuleDomain('PostCalendar');

    // change below based on $article array
    $event = array(
        'title'             => __('News: ', $dom) . $article['title'],
        'hometext'          => ":html:" . __('Article link: ', $dom) . "<a href='" . pnModURL('News', 'user', 'display', array('sid' => $article['sid'])) . "'>" . substr($article['hometext'], 0, 32) . "...</a>",
        'aid'               => $article['aid'],
        'time'              => $article['time'],
        'informant'         => $article['aid'], // change this?
        'eventDate'         => substr($article['from'], 0, 10),
        'duration'          => 3600,
        'recurrtype'        => 0, // norepeat
        'recurrspec'        => 'a:5:{s:17:"event_repeat_freq";s:0:"";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:0:"";}',
        'startTime'         => '01:00:00',
        'alldayevent'       => 1, // yes
        'location'          => 'a:6:{s:14:"event_location";s:0:"";s:13:"event_street1";s:0:"";s:13:"event_street2";s:0:"";s:10:"event_city";s:0:"";s:11:"event_state";s:0:"";s:12:"event_postal";s:0:"";}',
        'eventstatus'       => 1, // approved
        'sharing'           => 3, // global
        'hooked_modulename' => 'news',
        'hooked_objectid'   => $article['sid'],
        '__CATEGORIES__'    => array('Main' => $cat['id']), // CHANGE THIS!
        '__META__'          => array('module' => 'PostCalendar'),
    );
    return $event;
}