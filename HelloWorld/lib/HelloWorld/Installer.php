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
 * Class to control Installer interface
 */
class HelloWorld_Installer extends Zikula_Installer
{
    /**
     * Initializes a new install
     *
     * This function will initialize a new installation.
     * It is accessed via the Zikula Admin interface and should
     * not be called directly.
     *
     * @return  boolean    true/false
     */
    public function install()
    {
        // create table
        if (!DBUtil::createTable('helloworld')) {
            return LogUtil::registerError($this->__('Error! Could not create the table.'));
        }

        return true;
    }
    
    /**
     * Upgrades an old install
     *
     * This function is used to upgrade an old version
     * of the module.  It is accessed via the Zikula
     * Admin interface and should not be called directly.
     *
     * @param   string    $oldversion Version we're upgrading
     * @return  boolean   true/false
     */
    public function upgrade($oldversion)
    {
        if (!SecurityUtil::checkPermission('HelloWorld::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        switch ($oldversion) {
            case '1.0.0':
                //future development
        }
    
        return true;
    }
    
    /**
     * removes an install
     *
     * This function removes the module from your
     * Zikula install and should be accessed via
     * the Zikula Admin interface
     *
     * @return  boolean    true/false
     */
    public function uninstall()
    {
        $result = DBUtil::dropTable('helloworld');
        $result = $result && $this->delVars();

        return $result;
    }
} // end class def