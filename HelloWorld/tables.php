<?php
/**
 * Copyright Craig Heydenburg 2010 - HelloWorld
 *
 * HelloWorld
 * Demonstration of Zikula Module
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 */
function helloworld_tables()
{
    // Initialise table array
    $table = array();

    $table['helloworld'] = DBUtil::getLimitedTablename('helloworld');
    $table['helloworld_column'] = array(
        'id'         => 'hw_eid',  // row ID
        'text'       => 'hw_text', // row text
    );

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
