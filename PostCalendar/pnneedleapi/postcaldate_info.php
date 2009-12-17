<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: $
 * @version     $Id: $
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * pcdate needle info
 * @param none
 * @return array()
 */
function postcalendar_needleapi_postcaldate_info()
{
    $info = array('module'        => 'PostCalendar', // module name
                  'info'          => 'POSTCALDATE-{date-displaytype}',   // possible needles  
                  'inspect'       => true,
                  //'needle'        => array('http://', 'https://', 'ftp://', 'mailto://'),
                  //'function'      => 'http',
                  //'casesensitive' => false,
                  ); 
    return $info;
}