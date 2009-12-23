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
$dom = ZLanguage::getModuleDomain('PostCalendar');

$modversion['name']           = 'PostCalendar';
$modversion['displayname']    = __('PostCalendar', $dom);
$modversion['url']            = __(/*!used in URL - nospaces, no special chars, lcase*/'postcalendar', $dom);
$modversion['description']    = __('Calendar for Zikula', $dom);

$modversion['id']             = '$Revision$'; // svn revision #
$modversion['version']        = '6.0.0-dev';
$modversion['credits']        = 'http://code.zikula.org/soundwebdevelopment/wiki/PostCalendarHistoryCredits';
$modversion['changelog']      = 'http://code.zikula.org/soundwebdevelopment/wiki/PostCalendarReleaseNotes';
$modversion['help']           = 'http://code.zikula.org/soundwebdevelopment/';
$modversion['license']        = 'http://www.gnu.org/copyleft/gpl.html';
$modversion['official']       = 0;
$modversion['author']         = 'Craig Heydenburg';
$modversion['contact']        = 'http://code.zikula.org/soundwebdevelopment/';
$modversion['admin']          = 1;
$modversion['user']           = 1;
$modversion['securityschema'] = array(
                'PostCalendar::Event' => 'Event Title::Event ID',
                'PostCalendar::' => '::');
