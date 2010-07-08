<?php
/**
 * Copyright Craig Heydenburg 2010 - HelloWorld
 *
 * HelloWorld
 * Demonstration of Zikula Module
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 */

class HelloWorld_Version extends Zikula_Version
{
    public function getMetaData()
    {
        $meta = array();
        $meta['name']           = 'HelloWorld';
        $meta['displayname']    = $this->__('HelloWorld');
        $meta['url']            = $this->__(/*!used in URL - nospaces, no special chars, lcase*/'helloworld');
        $meta['description']    = $this->__('Example Zikula Module Hello World!');
        
        $meta['version']        = '1.0.0';
        $meta['changelog']      = 'http://code.zikula.org/soundwebdevelopment/';
        $meta['help']           = 'http://code.zikula.org/soundwebdevelopment/';
        $meta['license']        = 'http://www.gnu.org/licenses/lgpl.html';
        $meta['official']       = 0;
        $meta['author']         = 'Craig Heydenburg';
        $meta['contact']        = 'http://code.zikula.org/soundwebdevelopment/';
        $meta['admin']          = 1;
        $meta['user']           = 1;

        $meta['securityschema'] = array(
            'HelloWorld::'      => '::');
        $meta['core_min']       = '1.3.0'; // requires minimum 1.3.0 or later
        //$meta['core_max'] = '1.3.0'; // doesn't work with versions later than x.x.x

        return $meta;
    }
} // end class def