<?php
/**
 * @package     PostCalendar
 * @author      Craig Heydenburg
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PostCalendar_Version extends Zikula_Version
{
    /**
     *
     * @return array module metadata
     */
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