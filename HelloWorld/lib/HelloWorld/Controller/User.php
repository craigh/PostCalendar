<?php
/**
 * Copyright Craig Heydenburg 2010 - HelloWorld
 *
 * HelloWorld
 * Demonstration of Zikula Module
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 */

class HelloWorld_Controller_User extends Zikula_Controller
{
    /**
     * main
     *
     * main view function for end user
     * @access public
     */
    public function main()
    {
        return $this->view();
    }
    
    /**
     * view items
     * This is a standard function to provide an overview of all of the items
     * available from the module.
     */
    public function view()
    {
        if (!SecurityUtil::checkPermission('HelloWorld::', '::', ACCESS_OVERVIEW)) {
            return LogUtil::registerPermissionError();
        }
    
        return $this->view->fetch('user/view.tpl');
    }
} // end class def