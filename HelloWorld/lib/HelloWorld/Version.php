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
        $meta['displayname']    = $this->__('HelloWorld');
        $meta['url']            = $this->__(/*!used in URL - nospaces, no special chars, lcase*/'helloworld');
        $meta['description']    = $this->__('Example Zikula Module Hello World!');
        $meta['version']        = '1.0.0';

        $meta['securityschema'] = array(
            'HelloWorld::'      => '::');
        $meta['core_min']       = '1.3.0'; // requires minimum 1.3.0 or later
        //$meta['core_max'] = '1.3.0'; // doesn't work with versions later than x.x.x

        return $meta;
    }
} // end class def