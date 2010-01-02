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
function PostCalendar_hooksapi_create_news($args)
{
    if ((!isset($args['objectid'])) || ((int)$args['objectid'] <= 0)) return false;

    $article = pnModAPIFunc('News', 'user', 'get', $args);

    $dom = ZLanguage::getModuleDomain('PostCalendar');

    // change below based on $article array
    $event = array (
        'title'          => __('News Event', $dom),
        'hometext'       => __(':text:test', $dom),
        'aid'            => SessionUtil::getVar('uid'),
        'time'           => date("Y-m-d H:i:s"),
        'informant'      => SessionUtil::getVar('uid'),
        'eventDate'      => date('Y-m-d'),
        'duration'       => 3600,
        'recurrtype'     => 0, //norepeat
        'recurrspec'     => 'a:5:{s:17:"event_repeat_freq";s:0:"";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:0:"";}',
        'startTime'      => '01:00:00',
        'alldayevent'    => 1,
        'location'       => 'a:6:{s:14:"event_location";s:0:"";s:13:"event_street1";s:0:"";s:13:"event_street2";s:0:"";s:10:"event_city";s:0:"";s:11:"event_state";s:0:"";s:12:"event_postal";s:0:"";}',
        'eventstatus'    => 1, // approved
        'sharing'        => 3, // global
        'website'        => 'http://code.zikula.org/soundwebdevelopment/wiki/PostCalendar',
        '__CATEGORIES__' => array('Main' => 5/*$cat['id']*/),
        '__META__'       => array('module' => 'PostCalendar'),
    );
    return $event;
}