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
 * event needle info
 * @param none
 * @return array()
 */
function postcalendar_needleapi_pcdate_info()
{
    $info = array('module'        => 'PostCalendar', // module name
                  'info'          => 'PCDATE-{date-displaytype}',   // possible needles  
                  'inspect'       => true,
                  //'needle'        => array('http://', 'https://', 'ftp://', 'mailto://'),
                  //'function'      => 'http',
                  //'casesensitive' => false,
                  ); 
    return $info;
}