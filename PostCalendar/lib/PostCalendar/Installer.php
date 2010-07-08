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

class PostCalendar_Installer extends Zikula_Installer
{
    /**
     * Initializes a new install of PostCalendar
     *
     * This function will initialize a new installation of PostCalendar.
     * It is accessed via the Zikula Admin interface and should
     * not be called directly.
     *
     * @author  Arjen Tebbenhof
     * @return  boolean    true/false
     * @access  public
     */
    public function install()
    {
        // create tables
        if (!DBUtil::createTable('postcalendar_events')) {
            return LogUtil::registerError($this->__('Error! Could not create the table.'));
        }
    
        // insert default category
        if (!$this->_createdefaultcategory()) {
            return LogUtil::registerError($this->__('Error! Could not create default category.'));
        }
    
        // PostCalendar Default Settings
        $defaultsettings = PostCalendar_Util::getdefaults();
        $result = ModUtil::setVars('PostCalendar', $defaultsettings);
        if (!$result) {
            return LogUtil::registerError($this->__('Error! Could not set the default settings for PostCalendar.'));
        }
    
        $this->reset_scribite();
        $this->_createdefaultsubcategory();
        $this->_createinstallevent();
        $this->_registermodulehooks();
    
        return true;
    }
    
    /**
     * Upgrades an old install of PostCalendar
     *
     * This function is used to upgrade an old version
     * of PostCalendar.  It is accessed via the Zikula
     * Admin interface and should not be called directly.
     *
     * @author  Arjen Tebbenhof
     * @return  boolean    true/false
     * @param   string    $oldversion Version we're upgrading
     * @access  public
     * @copyright    The PostCalendar Team 2009
     */
    public function upgrade($oldversion)
    {
        if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        // We only support upgrade from version 4 and up. Notify users if they have a version below that one.
        if (version_compare($oldversion, '6', '<')) {
            $modversion = array(
                'version' => 'unknown');
            // Receive the current version information, where $modversion will be overwritten
            require 'modules/PostCalendar/pnversion.php';
    
            // Inform user about error, and how he can upgrade to $modversion['version']
            return LogUtil::registerError($this->__f('Notice: This version does not support upgrades from PostCalendar 5.x and earlier. Please see detailed upgrade instructions at <a href="http://code.zikula.org/soundwebdevelopment/wiki/PostCalendar#Upgrade">code.zikula.org/soundwebdevelopment</a>). After upgrading, you can install PostCalendar %s and perform this upgrade.', $modversion));
        }
    
        switch ($oldversion) {
    
            case '6.0.0':
                ModUtil::setVar('PostCalendar', 'pcFilterYearStart', 1);
                ModUtil::setVar('PostCalendar', 'pcFilterYearEnd', 2);
            case '6.0.1':
                // no changes
            case '6.0.2':
                if (!$this->_registermodulehooks()) {
                    LogUtil::registerError($this->__('Error! Could not register module hooks.'));
                    return '6.0.1';
                }
                // upgrade table structure
                if (!DBUtil::changeTable('postcalendar_events')) {
                    LogUtil::registerError($this->__('Error! Could not upgrade the tables.'));
                    return '6.0.1';
                }
                ModUtil::setVar('PostCalendar', 'pcListMonths', 12);
            case '6.1.0':
                $oldDefaultCats = ModUtil::getVar('PostCalendar', 'pcDefaultCategories');
                ModUtil::delVar('PostCalendar', 'pcDefaultCategories');
                $defaults = PostCalendar_Util::getdefaults();
                $defaults['pcEventDefaults']['categories'] = $oldDefaultCats;
                ModUtil::setVar('PostCalendar', 'pcEventDefaults', $defaults['pcEventDefaults']);
            case '6.2.0':
                ModUtil::unregisterHook('item', 'new', 'GUI', 'PostCalendar', 'hooks', 'new');
                ModUtil::registerHook('item', 'new', 'GUI', 'PostCalendar', 'hooks', 'newgui');
            case '7.0.0':
                //future development
        }
    
        // if we get this far - clear the cache
        $this->view->clear_cache();
    
        return true;
    }
    
    /**
     * Deletes an install of PostCalendar
     *
     * This function removes PostCalendar from you
     * Zikula install and should be accessed via
     * the Zikula Admin interface
     *
     * @author Arjen Tebbenhof
     * @return  boolean    true/false
     * @access  public
     * @copyright    The PostCalendar Team 2009
     */
    public function uninstall()
    {
        $result = DBUtil::dropTable('postcalendar_events');
        $result = $result && ModUtil::delVar('PostCalendar');
    
        // Delete entries from category registry
        ModUtil::dbInfoLoad('Categories');
        DBUtil::deleteWhere('categories_registry', "crg_modname='PostCalendar'");
        DBUtil::deleteWhere('categories_mapobj', "cmo_modname='PostCalendar'");
    
        return $result;
    }
    
    /**
     * Reset scribite config for PostCalendar module.
     *
     * @author Arjen Tebbenhof
     * Since we updated the functionname for creating / editing a new event from func=submit to func=new,
     * scribite doesn't load any editor. If we force it to our new function.
     */
    public function reset_scribite()
    {
        // update the scribite
        if (ModUtil::available('scribite') && ModUtil::loadApi('scribite', 'user') && ModUtil::loadApi('scribite', 'admin')) {
            $modconfig = ModUtil::apiFunc('scribite', 'user', 'getModuleConfig', array(
                'modulename' => 'PostCalendar'));
            $mid = false;

            if (count($modconfig)) {
                $modconfig['modfuncs'] = 'create,edit,copy,submit';
                $modconfig['modareas'] = 'description';
                $mid = ModUtil::apiFunc('scribite', 'admin', 'editmodule', $modconfig);
            } else {
                // create new module in db
                $modconfig = array(
                    'modulename' => 'PostCalendar',
                    'modfuncs' => 'create,edit,copy,submit',
                    'modareas' => 'description',
                    'modeditor' => '-');
                $mid = ModUtil::apiFunc('scribite', 'admin', 'addmodule', $modconfig);
            }
            // Error tracking
            if ($mid === false) {
                return LogUtil::registerError($this->__('Error! Could not update the scribite configuration.'));
            }
            LogUtil::registerStatus($this->__('PostCalendar: Scribite! associations reset for PostCalendar.'));
        }
        return true;
    }
    
    /**
     * copied and adapted from News module
     * @author  Mark West?
     * create the default category tree
     */
    private function _createdefaultcategory()
    {
        if (!$cat = CategoryUtil::createCategory('/__SYSTEM__/Modules', 'PostCalendar', null, $this->__('PostCalendar'), $this->__('Calendar for Zikula'))) {
            return false;
        }
        // get the category path to insert upgraded PostCalendar categories
        $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar');
        if ($rootcat) {
            // create an entry in the categories registry to the Main property
            CategoryRegistryUtil::insertEntry ('PostCalendar', 'postcalendar_events', 'Main', $rootcat['id']);
        } else {
            return false;
        }
        LogUtil::registerStatus($this->__("PostCalendar: 'Main' category created."));
        return true;
    }
    
    /**
     * create initial calendar event
     * @author Craig Heydenburg
     */
    private function _createinstallevent()
    {
        $cat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar/Events');
    
        $event = array(
            'title'          => $this->__('PostCalendar Installed'),
            'hometext'       => $this->__(':text:On this date, the PostCalendar module was installed. Thank you for trying PostCalendar! This event can be safely deleted if you wish.'),
            'aid'            => SessionUtil::getVar('uid'),
            'time'           => date("Y-m-d H:i:s"),
            'informant'      => SessionUtil::getVar('uid'),
            'eventDate'      => date('Y-m-d'),
            'duration'       => 3600,
            'recurrtype'     => 0,  //norepeat
            'recurrspec'     => 'a:5:{s:17:"event_repeat_freq";s:0:"";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:0:"";}',
            'startTime'      => '01:00:00',
            'alldayevent'    => 1,
            'location'       => 'a:6:{s:14:"event_location";s:0:"";s:13:"event_street1";s:0:"";s:13:"event_street2";s:0:"";s:10:"event_city";s:0:"";s:11:"event_state";s:0:"";s:12:"event_postal";s:0:"";}',
            'eventstatus'    => 1,  // approved
            'sharing'        => 3,  // global
            'website'        => 'http://code.zikula.org/soundwebdevelopment/wiki/PostCalendar',
            '__CATEGORIES__' => array(
                'Main' => $cat['id']),
            '__META__'       => array(
                'module' => 'PostCalendar'));
    
        if (DBUtil::insertObject($event, 'postcalendar_events', 'eid')) {
            LogUtil::registerStatus($this->__("PostCalendar: Installation event created."));
            return true;
        }
    
        return LogUtil::registerError($this->__('Error! Could not create an installation event.'));
    
    }
    
    /**
     * create initial category on first install
     * @author Craig Heydenburg
     */
    private function _createdefaultsubcategory()
    {
        if (!$cat = CategoryUtil::createCategory('/__SYSTEM__/Modules/PostCalendar', 'Events', null, $this->__('Events'), $this->__('Initial sub-category created on install'), array('color' => '#99ccff'))) {
            LogUtil::registerError($this->__('Error! Could not create an initial sub-category.'));
            return false;
        }
    
        LogUtil::registerStatus($this->__("PostCalendar: Initial sub-category created (Events)."));
        return true;
    }
    
    /**
     * register module hooks
     * @author Craig Heydenburg
     */
    private function _registermodulehooks()
    {
        /*
        ($hookobject, $hookaction, $hookarea, $hookmodule, $hooktype, $hookfunc)
        $hookobject = 'item', 'category' or 'module'
        $hookaction = 'new' (GUI), 'create' (API), 'modify' (GUI), 'update' (API), 'delete' (API), 'transform', 'display' (GUI), 'modifyconfig', 'updateconfig'
        $hookarea = 'GUI' or 'API'
        $hookmodule = name of the hook module
        $hooktype = name of the hook type (==admin && (area==API) = function is located in pnadminapi.php)
        $hookfunc = name of the hook function
        */
    
        if (!ModUtil::registerHook('item', 'create', 'API', 'PostCalendar', 'hooks', 'create')) {
            return LogUtil::registerError($this->__f('PostCalendar: Could not register %s hook.', 'create'));
        }
        if (!ModUtil::registerHook('item', 'update', 'API', 'PostCalendar', 'hooks', 'update')) {
            return LogUtil::registerError($this->__f('PostCalendar: Could not register %s hook.', 'update'));
        }
        if (!ModUtil::registerHook('item', 'delete', 'API', 'PostCalendar', 'hooks', 'delete')) {
            return LogUtil::registerError($this->__f('PostCalendar: Could not register %s hook.', 'delete'));
        }
        if (!ModUtil::registerHook('item', 'new', 'GUI', 'PostCalendar', 'hooks', 'newgui')) {
            return LogUtil::registerError($this->__f('PostCalendar: Could not register %s hook.', 'new'));
        }
        if (!ModUtil::registerHook('item', 'modify', 'GUI', 'PostCalendar', 'hooks', 'modify')) {
            return LogUtil::registerError($this->__f('PostCalendar: Could not register %s hook.', 'modify'));
        }
        if (!ModUtil::registerHook('module', 'modifyconfig', 'GUI', 'PostCalendar', 'hooks', 'modifyconfig')) {
            return LogUtil::registerError($this->__f('PostCalendar: Could not register %s hook.', 'modifyconfig'));
        }
        if (!ModUtil::registerHook('module', 'updateconfig', 'API', 'PostCalendar', 'hooks', 'updateconfig')) {
            return LogUtil::registerError($this->__f('PostCalendar: Could not register %s hook.', 'updateconfig'));
        }
        if (!ModUtil::registerHook('module', 'remove', 'API', 'PostCalendar', 'hooks', 'deletemodule')) {
            return LogUtil::registerError($this->__f('PostCalendar: Could not register %s hook.', 'deletemodule'));
        }
    
        LogUtil::registerStatus($this->__('PostCalendar: All hooks registered.'));
        return true;
    }
} // end class def