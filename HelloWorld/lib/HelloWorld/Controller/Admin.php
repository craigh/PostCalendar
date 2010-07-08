<?php
/**
 * Copyright Craig Heydenburg 2010 - HelloWorld
 *
 * HelloWorld
 * Demonstration of Zikula Module
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 */

/**
 * Class to control Admin interface
 */
class HelloWorld_Controller_Admin extends Zikula_Controller
{
    /**
     * the main administration function
     * This function is the default function, and is called whenever the
     * module is initiated without defining arguments.
     */
    public function main()
    {
        if (!SecurityUtil::checkPermission('HelloWorld::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
        return $this->modifyconfig();
    }
    /**
     * @function    modifyconfig
     * @description present administrator options to change module configuration
     * @return      config template
     */
    public function modifyconfig()
    {
        if (!SecurityUtil::checkPermission('HelloWorld::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('HelloWorld'));
        $this->view->assign('version', $modinfo['version']);
    
        return $this->view->fetch('admin/modifyconfig.tpl');
    }
    /**
     * @function    updateconfig
     * @description sets module variables as requested by admin
     * @return      status/error ->back to modify config page
     */
    public function updateconfig()
    {
        if (!SecurityUtil::checkPermission('HelloWorld::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        $showAdminHelloWorld = FormUtil::getPassedValue('showAdminHelloWorld', 0);

        // delete all the old vars
        ModUtil::delVar('HelloWorld');
    
        // set the new variables
        ModUtil::setVar('HelloWorld', 'showAdminHelloWorld', $showAdminHelloWorld);
    
        // Let any other modules know that the modules configuration has been updated
        $this->callHooks('module', 'updateconfig', 'HelloWorld', array(
            'module' => 'HelloWorld'));
    
        // clear the cache
        $this->view->clear_cache();
    
        LogUtil::registerStatus($this->__('Done! Updated the HelloWorld configuration.'));
        return $this->modifyconfig();
    }
    /**
     * @function    info
     * @description present administrator information
     * @return      template
     */
    public function info()
    {
        if (!SecurityUtil::checkPermission('HelloWorld::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('HelloWorld'));
        $this->view->assign('version', $modinfo['version']);
    
        return $this->view->fetch('admin/info.tpl');
    }
    /**
     * @function    postInitialize
     * @description set caching to false for all admin functions
     * @return      null
     */
    public function postInitialize()
    {
        $this->view->setCaching(false);
    }
} // end class def