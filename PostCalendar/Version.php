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
class PostCalendar_Version extends Zikula_Version
{
    public function getMetaData()
    {
        $meta = array();
        $meta['name']           = 'PostCalendar';
        $meta['displayname']    = __('PostCalendar', $dom);
        $meta['url']            = __(/*!used in URL - nospaces, no special chars, lcase*/'postcalendar', $dom);
        $meta['description']    = __('Calendar for Zikula', $dom);
        
        $meta['id']             = '$Revision$'; // svn revision #
        $meta['version']        = '7.0.0-dev';
        $meta['credits']        = 'http://code.zikula.org/soundwebdevelopment/wiki/PostCalendarHistoryCredits';
        $meta['changelog']      = 'http://code.zikula.org/soundwebdevelopment/wiki/PostCalendarReleaseNotes';
        $meta['help']           = 'http://code.zikula.org/soundwebdevelopment/';
        $meta['license']        = 'http://www.gnu.org/copyleft/gpl.html';
        $meta['official']       = 0;
        $meta['author']         = 'Craig Heydenburg';
        $meta['contact']        = 'http://code.zikula.org/soundwebdevelopment/';
        $meta['admin']          = 1;
        $meta['user']           = 1;

        $meta['securityschema'] = array(
            'PostCalendar::Event' => 'Event Title::Event ID',
            'PostCalendar::'      => '::');
        $meta['core_min']       = '1.3.0'; // requires minimum 1.3.0 or later
        //$meta['core_max'] = '1.3.0'; // doesn't work with versions later than x.x.x

        return $meta;
    }
}