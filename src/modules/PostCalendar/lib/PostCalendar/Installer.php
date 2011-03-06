<?php
/**
 * @package     PostCalendar
 * @author      Craig Heydenburg
 * @author      Arjen Tebbenhof
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
     * @return  boolean    true/false
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
    
        $this->_reset_scribite();
        $this->_createdefaultsubcategory();
        $this->_createinstallevent();

        HookUtil::registerHookSubscriberBundles($this->version);
        HookUtil::registerHookProviderBundles($this->version);

        // register handlers
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'get.pending_content', array('PostCalendar_Handlers', 'pendingContent'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'installer.module.uninstalled', array('PostCalendar_HookHandlers', 'moduleDelete'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'module_dispatch.service_links', array('PostCalendar_HookHandlers', 'servicelinks'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'controller.method_not_found', array('PostCalendar_HookHandlers', 'postcalendarhookconfig'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'controller.method_not_found', array('PostCalendar_HookHandlers', 'postcalendarhookconfigprocess'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'user.create', array('PostCalendar_PostCalendarEvent_Users', 'createEvent'));

        return true;
    }
    
    /**
     * Upgrades an old install of PostCalendar
     *
     * This function is used to upgrade an old version
     * of PostCalendar.  It is accessed via the Zikula
     * Admin interface and should not be called directly.
     *
     * @param   string    $oldversion Version we're upgrading
     * @return  boolean    true/false
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
            // TODO
            // THIS MUST BE REDONE
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

                // register handlers
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'get.pending_content', array('PostCalendar_Handlers', 'pendingContent'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'module_dispatch.service_links', array('PostCalendar_HookHandlers', 'servicelinks'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'module_dispatch.service_links', array('PostCalendar_HookHandlers', 'servicelinks'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'controller.method_not_found', array('PostCalendar_HookHandlers', 'postcalendarhookconfig'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'controller.method_not_found', array('PostCalendar_HookHandlers', 'postcalendarhookconfigprocess'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'user.create', array('PostCalendar_PostCalendarEvent_Users', 'createEvent'));

                HookUtil::registerHookSubscriberBundles($this->version);
                HookUtil::registerHookProviderBundles($this->version);

                Content_Installer::updateContentType('PostCalendar');

            case '7.0.0':
                //future development
        }

        return true;
    }
    
    /**
     * Deletes an install of PostCalendar
     *
     * This function removes PostCalendar from you
     * Zikula install and should be accessed via
     * the Zikula Admin interface
     *
     * @return  boolean    true/false
     */
    public function uninstall()
    {
        $result = DBUtil::dropTable('postcalendar_events');
        $result = $result && ModUtil::delVar('PostCalendar');
    
        // Delete entries from category registry
        ModUtil::dbInfoLoad('Categories');
        DBUtil::deleteWhere('categories_registry', "crg_modname='PostCalendar'");
        DBUtil::deleteWhere('categories_mapobj', "cmo_modname='PostCalendar'");

        // unregister handlers
        EventUtil::unregisterPersistentModuleHandlers('PostCalendar');

        HookUtil::unregisterHookSubscriberBundles($this->version);
        HookUtil::unregisterHookProviderBundles($this->version);

        return $result;
    }
    
    /**
     * Reset scribite config for PostCalendar module.
     *
     * Since we updated the functionname for creating / editing a new event from func=submit to func=new,
     * scribite doesn't load any editor. If we force it to our new function.
     *
     * @return boolean
     */
    private function _reset_scribite()
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
     * create the default category tree
     * copied and adapted from News module
     * @author  Mark West?
     * @return boolean
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
     * @return boolean
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

    protected function LegacyContentTypeMap()
    {
        $oldToNew = array(
            'postcalevent' => 'PostCalEvent',
            'postcalevents' => 'PostCalEvents'
        );
        return $oldToNew;
    }

} // end class def