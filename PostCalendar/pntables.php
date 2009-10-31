<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 */
function postcalendar_pntables()
{
    // Initialise table array
    $pntable = array();

    $pc_events = DBUtil::getLimitedTablename('postcalendar_events');
    $pntable['postcalendar_events'] = $pc_events;
    $pntable['postcalendar_events_column'] = array(
                    'eid'         => 'pc_eid',              // event ID
                    'aid'         => 'pc_aid',              // participant's user ID (default:informant UID)
                    'title'       => 'pc_title',            // event title
                    'time'        => 'pc_time',             // record timestamp
                    'hometext'    => 'pc_hometext',         // event description
                    'informant'   => 'pc_informant',        // uname of event submittor
                    'eventDate'   => 'pc_eventDate',        // YYYY-MM-DD event start date
                    'duration'    => 'pc_duration',         // event duration (in seconds)
                    'endDate'     => 'pc_endDate',          // YYYY-MM-DD event end date (optional)
                    'recurrtype'  => 'pc_recurrtype',       // type of recurrance (0,1,2)
                    'recurrspec'  => 'pc_recurrspec',       // (serialized)
                    'startTime'   => 'pc_startTime',        // HH:MM:SS event start time
                    'endTime'     => 'pc_endTime',          // HH:MM:SS event end time (optional)
                    'alldayevent' => 'pc_alldayevent',      // bool event all day or not
                    'location'    => 'pc_location',         // (serialized) event location
                    'conttel'     => 'pc_conttel',          // event contact phone
                    'contname'    => 'pc_contname',         // event contact name
                    'contemail'   => 'pc_contemail',        // event contact email
                    'website'     => 'pc_website',          // event website
                    'fee'         => 'pc_fee',              // event fee
                    'eventstatus' => 'pc_eventstatus',      // event status (approved, pending)
                    'sharing'     => 'pc_sharing',          // event sharing (global, private, etc)
                    'meeting_id'  => 'pc_meeting_id');      // event meeting ID
    $pntable['postcalendar_events_column_def'] = array(
                    'eid'         => 'I(11) UNSIGNED AUTO PRIMARY',      // int(11) unsigned NOT NULL auto_increment
                    'aid'         => 'C(30) NOTNULL DEFAULT \'\'',       // varchar(30) NOT NULL default ''
                    'title'       => 'C(150) DEFAULT \'\'',              // varchar(150) default ''
                    'time'        => 'T',                                // datetime
                    'hometext'    => 'X DEFAULT \'\'',                   // text default ''
                    'informant'   => 'C(20) NOTNULL DEFAULT \'\'',       // varchar(20) NOT NULL default ''
                    'eventDate'   => 'D NOTNULL DEFAULT \'0000-00-00\'', // date NOT NULL default '0000-00-00'
                    'duration'    => 'I8(20) NOTNULL DEFAULT 0',         // bigint(20) NOT NULL default 0
                    'endDate'     => 'D NOTNULL DEFAULT \'0000-00-00\'', // date NOT NULL default '0000-00-00'
                    'recurrtype'  => 'I(1) NOTNULL DEFAULT 0',           // int(1) NOT NULL default 0
                    'recurrspec'  => 'X DEFAULT \'\'',                   // text default ''
                    'startTime'   => 'C(8) DEFAULT \'00:00:00\'',        // time (MySQL only, so now defined as varchar2)
                    'endTime'     => 'C(8) DEFAULT \'00:00:00\'',        // time (MySQL only, so now defined as varchar2)
                    'alldayevent' => 'I(1) NOTNULL DEFAULT 0',           // int(1) NOT NULL default 0
                    'location'    => 'X',                                // text default ''
                    'conttel'     => 'C(50) DEFAULT \'\'',               // varchar(50) default ''
                    'contname'    => 'C(50) DEFAULT \'\'',               // varchar(50) default ''
                    'contemail'   => 'C(255) DEFAULT \'\'',              // varchar(255) default ''
                    'website'     => 'C(255) DEFAULT \'\'',              // varchar(255) default ''
                    'fee'         => 'C(50) DEFAULT \'\'',               // varchar(50) default ''
                    'eventstatus' => 'I NOTNULL DEFAULT 0',              // int(11) NOT NULL default 0
                    'sharing'     => 'I NOTNULL DEFAULT 0',              // int(11) NOT NULL default 0
                    'meeting_id'  => 'I DEFAULT 0');                     // int(11) NULL default 0
    $pntable['postcalendar_events_column_idx'] = array(
                    'basic_event' => array(
                                    'aid',
                                    'eventDate',
                                    'endDate',
                                    'eventstatus',
                                    'sharing'));
    $pntable['postcalendar_events_db_extra_enable_categorization'] = true;
    $pntable['postcalendar_events_primary_key_column'] = 'eid';

    // add standard data fields
    ObjectUtil::addStandardFieldsToTableDefinition ($pntable['postcalendar_events_column'], 'pc_');
    ObjectUtil::addStandardFieldsToTableDataDefinition($pntable['postcalendar_events_column_def']);

    // old tables for upgrade/renaming purposes
    $pntable['postcalendar_categories'] = DBUtil::getLimitedTablename('postcalendar_categories');

    return $pntable;
}
