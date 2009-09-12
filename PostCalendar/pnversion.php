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
$modversion['name']           = _PC_MODULENAME;
$modversion['id']             = '$Revision$'; // svn revision #
$modversion['version']        = '5.8-dev';
$modversion['description']    = _PC_MODULEDESCRIPTION;
$modversion['credits']        = 'docs/credits.txt';
$modversion['changelog']      = 'http://code.zikula.org/soundwebdevelopment/';
$modversion['license']        = 'http://www.gnu.org/copyleft/gpl.html';
$modversion['official']       = 0;
$modversion['author']         = 'Craig Heydenburg';
$modversion['contact']        = 'http://code.zikula.org/soundwebdevelopment/';
$modversion['admin']          = 1;
$modversion['user']           = 1;
$modversion['securityschema'] = array(
                'PostCalendar::Event' => 'Event Title::Event ID',
                'PostCalendar::Category' => 'Category Name::Category ID',
                'PostCalendar::Topic' => 'Topic Name::Topic ID',
                'PostCalendar::User' => 'User Name::User ID',
                'PostCalendar::' => '::');
$modversion['dependencies']   = array(
                array('modname' => 'Topics',
                    'minversion' => '1.0',
                    'maxversion' => '',
                    'status' => PNMODULE_DEPENDENCY_REQUIRED));
