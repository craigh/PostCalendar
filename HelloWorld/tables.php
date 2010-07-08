<?php

function helloworld_tables()
{
    // Initialise table array
    $table = array();

    $table['helloworld'] = DBUtil::getLimitedTablename('helloworld');
    $table['helloworld_column'] = array(
        'id'         => 'hw_eid',  // row ID
        'text'       => 'hw_text', // row text
    );
/**
 * columns removed from previous versions:
 * catid, comments, counter, topic, recurrfreq, endTime, language, meeting_id
 */
    $table['helloworld_column_def'] = array(
        'id'         => 'I(11) UNSIGNED AUTO PRIMARY',      // int(11) unsigned NOT NULL auto_increment
        'text'       => 'C(150) DEFAULT \'\'',              // varchar(150) default ''
    );
    $table['helloworld_db_extra_enable_categorization'] = true;
    $table['helloworld_primary_key_column'] = 'id';

    // add standard data fields
    ObjectUtil::addStandardFieldsToTableDefinition($table['helloworld_column'], 'hw_');
    ObjectUtil::addStandardFieldsToTableDataDefinition($table['helloworld_column_def']);

    return $table;
}
