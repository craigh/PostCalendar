<?php
/**
 * @package     PostCalendar
 * @author      $Author: craigh $
 * @link        $HeadURL: https://code.zikula.org/svn/soundwebdevelopment/trunk/Modules/PostCalendar/Version.php $
 * @version     $Id: Version.php 682 2010-07-06 12:41:15Z craigh $
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PostCalendar_Version extends Zikula_Version
{
    public function getMetaData()
    {
        $meta = array();
        $meta['displayname']    = $this->__('PostCalendar');
        $meta['url']            = $this->__(/*!used in URL - nospaces, no special chars, lcase*/'postcalendar');
        $meta['description']    = $this->__('Calendar for Zikula');
        $meta['version']        = '7.0.0-dev';

        $meta['securityschema'] = array(
            'PostCalendar::Event' => 'Event Title::Event ID',
            'PostCalendar::'      => '::');
        $meta['core_min']       = '1.3.0'; // requires minimum 1.3.0 or later
        //$meta['core_max'] = '1.3.0'; // doesn't work with versions later than x.x.x

        return $meta;
    }
}