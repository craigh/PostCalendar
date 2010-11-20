<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 */
function postcalendar_tables()
{
    // Initialise table array
    $table = array();

    $pc_events = DBUtil::getLimitedTablename('postcalendar_events');
    $table['postcalendar_events'] = $pc_events;
    $table['postcalendar_events_column'] = array(
        'eid'         => 'pc_eid',              // event ID
        'aid'         => 'pc_aid',              // participant's user ID (default:informant UID)
        'title'       => 'pc_title',            // event title
        'time'        => 'pc_time',             // record timestamp
        'hometext'    => 'pc_hometext',         // event description
        'informant'   => 'pc_informant',        // uid of event submittor
        'eventDate'   => 'pc_eventDate',        // YYYY-MM-DD event start date
        'duration'    => 'pc_duration',         // event duration (in seconds)
        'endDate'     => 'pc_endDate',          // YYYY-MM-DD event end date (optional)
        'recurrtype'  => 'pc_recurrtype',       // type of recurrance (0,1,2)
        'recurrspec'  => 'pc_recurrspec',       // (serialized)
        'startTime'   => 'pc_startTime',        // HH:MM:SS event start time
        'alldayevent' => 'pc_alldayevent',      // bool event all day or not
        'location'    => 'pc_location',         // (serialized) event location
        'conttel'     => 'pc_conttel',          // event contact phone
        'contname'    => 'pc_contname',         // event contact name
        'contemail'   => 'pc_contemail',        // event contact email
        'website'     => 'pc_website',          // event website
        'fee'         => 'pc_fee',              // event fee
        'eventstatus' => 'pc_eventstatus',      // event status (approved, pending)
        'sharing'     => 'pc_sharing',          // event sharing (global, private, etc)
        'hooked_modulename' => 'pc_hooked_modulename', // module name hooked to PC
        'hooked_objectid'   => 'pc_hooked_objectid',   // object id hooked to PC
    );
/**
 * columns removed from previous versions:
 * catid, comments, counter, topic, recurrfreq, endTime, language, meeting_id
 */
    $table['postcalendar_events_column_def'] = array(
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
        'alldayevent' => 'I(1) NOTNULL DEFAULT 0',           // int(1) NOT NULL default 0
        'location'    => 'X',                                // text default ''
        'conttel'     => 'C(50) DEFAULT \'\'',               // varchar(50) default ''
        'contname'    => 'C(50) DEFAULT \'\'',               // varchar(50) default ''
        'contemail'   => 'C(255) DEFAULT \'\'',              // varchar(255) default ''
        'website'     => 'C(255) DEFAULT \'\'',              // varchar(255) default ''
        'fee'         => 'C(50) DEFAULT \'\'',               // varchar(50) default ''
        'eventstatus' => 'I NOTNULL DEFAULT 0',              // int(11) NOT NULL default 0
        'sharing'     => 'I NOTNULL DEFAULT 0',              // int(11) NOT NULL default 0
        'hooked_modulename' => 'C(50) DEFAULT \'\'',         // added version 6.1
        'hooked_objectid'   => 'I(11) DEFAULT 0',            // added version 6.1
    );
    $table['postcalendar_events_column_idx'] = array(
        'basic_event' => array(
            'aid',
            'eventDate',
            'endDate',
            'eventstatus',
            'sharing'));
    $table['postcalendar_events_db_extra_enable_categorization'] = true;
    $table['postcalendar_events_primary_key_column'] = 'eid';

    // add standard data fields
    ObjectUtil::addStandardFieldsToTableDefinition($table['postcalendar_events_column'], 'pc_');
    ObjectUtil::addStandardFieldsToTableDataDefinition($table['postcalendar_events_column_def']);

    // old tables for upgrade/renaming purposes
    $table['postcalendar_categories'] = DBUtil::getLimitedTablename('postcalendar_categories');

    return $table;
}
