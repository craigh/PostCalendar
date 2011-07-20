<?php
/**
 * @package     PostCalendar
 * @author      Craig Heydenburg
 * @author      Arjen Tebbenhof
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class PostCalendar_Installer extends Zikula_AbstractInstaller
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

        HookUtil::registerSubscriberBundles($this->version->getHookSubscriberBundles());
        HookUtil::registerProviderBundles($this->version->getHookProviderBundles());

        // register handlers
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'get.pending_content', array('PostCalendar_Handlers', 'pendingContent'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'installer.module.uninstalled', array('PostCalendar_HookHandlers', 'moduleDelete'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'module_dispatch.service_links', array('PostCalendar_HookHandlers', 'servicelinks'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'controller.method_not_found', array('PostCalendar_HookHandlers', 'postcalendarhookconfig'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'controller.method_not_found', array('PostCalendar_HookHandlers', 'postcalendarhookconfigprocess'));
        EventUtil::registerPersistentModuleHandler('PostCalendar', 'module.content.gettypes', array('PostCalendar_Handlers', 'getTypes'));

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
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());

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
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'installer.module.uninstalled', array('PostCalendar_HookHandlers', 'moduleDelete'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'module_dispatch.service_links', array('PostCalendar_HookHandlers', 'servicelinks'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'controller.method_not_found', array('PostCalendar_HookHandlers', 'postcalendarhookconfig'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'controller.method_not_found', array('PostCalendar_HookHandlers', 'postcalendarhookconfigprocess'));
                EventUtil::registerPersistentModuleHandler('PostCalendar', 'module.content.gettypes', array('PostCalendar_Handlers', 'getTypes'));

                HookUtil::registerSubscriberBundles($this->version->getHookSubscriberBundles());
                HookUtil::registerProviderBundles($this->version->getHookProviderBundles());

                if (ModUtil::available('Content')) {
                    Content_Installer::updateContentType('PostCalendar');
                }
                $this->removeTableColumnPrefixes();
                // upgrade table structure
                if (!DBUtil::changeTable('postcalendar_events')) {
                    LogUtil::registerError($this->__('Error! Could not upgrade the tables.'));
                    return '6.2.0';
                }

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
        DBUtil::deleteWhere('categories_registry', "modname='PostCalendar'");
        DBUtil::deleteWhere('categories_mapobj', "modname='PostCalendar'");

        // unregister handlers
        EventUtil::unregisterPersistentModuleHandlers('PostCalendar');

        HookUtil::unregisterSubscriberBundles($this->version->getHookSubscriberBundles());
        HookUtil::unregisterProviderBundles($this->version->getHookProviderBundles());

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
            'aid'            => UserUtil::getVar('uid'),
            'time'           => date("Y-m-d H:i:s"),
            'informant'      => UserUtil::getVar('uid'),
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
    
    private function removeTableColumnPrefixes()
    {
        $prefix = $this->serviceManager['prefix'];
        $connection = Doctrine_Manager::getInstance()->getConnection('default');
        $sqlStatements = array();
        // N.B. statements generated with PHPMyAdmin
        $sqlStatements[] = 'RENAME TABLE ' . $prefix . '_postcalendar_events' . " TO `postcalendar_events`";
        // this removes the prefixes but also changes hideonindex to displayonindex and disallowcomments to allowcomments
        // because 'from' and 'to' are reserved sql words, the column names are changed to ffrom and tto respectively
        $sqlStatements[] = "ALTER TABLE `postcalendar_events` 
CHANGE `pc_eid` `eid` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `pc_aid` `aid` VARCHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE `pc_title` `title` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `pc_time` `ttime` DATETIME NULL DEFAULT NULL ,
CHANGE `pc_hometext` `hometext` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `pc_informant` `informant` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE `pc_eventDate` `eventDate` DATE NOT NULL DEFAULT '0000-00-00',
CHANGE `pc_duration` `duration` BIGINT( 20 ) NOT NULL DEFAULT '0',
CHANGE `pc_endDate` `endDate` DATE NOT NULL DEFAULT '0000-00-00',
CHANGE `pc_recurrtype` `recurrtype` TINYINT( 4 ) NOT NULL DEFAULT '0',
CHANGE `pc_recurrspec` `recurrspec` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `pc_startTime` `startTime` VARCHAR(8) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '00:00:00', 
CHANGE `pc_alldayevent` `alldayevent` TINYINT(4) NOT NULL DEFAULT '0', 
CHANGE `pc_location` `location` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `pc_conttel` `conttel` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `pc_contname` `contname` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `pc_contemail` `contemail` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `pc_website` `website` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `pc_fee` `fee` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `pc_eventstatus` `eventstatus` INT(11) NOT NULL DEFAULT '0',
CHANGE `pc_sharing` `sharing` INT( 11 ) NOT NULL DEFAULT '0',
CHANGE `pc_hooked_modulename` `hooked_modulename` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `pc_hooked_objectid` `hooked_objectid` BIGINT( 20 ) NULL DEFAULT '0',
CHANGE `pc_hooked_area` `hooked_area` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `pc_obj_status` `obj_status` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'A',
CHANGE `pc_cr_date` `cr_date` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
CHANGE `pc_cr_uid` `cr_uid` INT( 11 ) NOT NULL DEFAULT '0',
CHANGE `pc_lu_date` `lu_date` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
CHANGE `pc_lu_uid` `lu_uid` INT( 11 ) NOT NULL DEFAULT '0'";
        foreach ($sqlStatements as $sql) {
            $stmt = $connection->prepare($sql);
            try {
                $stmt->execute();
            } catch (Exception $e) {
            }   
        }
    }

    public static function LegacyContentTypeMap()
    {
        $oldToNew = array(
            'postcalevent' => 'PostCalEvent',
            'postcalevents' => 'PostCalEvents'
        );
        return $oldToNew;
    }

} // end class def