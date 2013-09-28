<?php

/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
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
        try {
            DoctrineHelper::createSchema($this->entityManager, array('PostCalendar_Entity_CalendarEvent',
                'PostCalendar_Entity_EventCategory',
                'PostCalendar_Entity_RecurException'));
        } catch (Exception $e) {
            LogUtil::registerError($this->__f('Error! Could not create tables (%s).', $e->getMessage()));
            return false;
        }

        // insert default category
        try {
            $this->createCategoryTree();
        } catch (Exception $e) {
            LogUtil::registerError($this->__f('Did not create default categories (%s).', $e->getMessage()));
        }


        // PostCalendar Default Settings
        $defaultsettings = PostCalendar_Util::getdefaults();
        $result = $this->setVars($defaultsettings);
        if (!$result) {
            return LogUtil::registerError($this->__('Error! Could not set the default settings for PostCalendar.'));
        }

        $this->createinstallevent();

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

        // We only support upgrade from version 7 and up. Notify users if they have a version below that one.
        if (version_compare($oldversion, '7', '<')) {
            // Inform user about error, and how he can upgrade to $modversion
            $upgradeToVersion = $this->version->getVersion();
            return LogUtil::registerError($this->__f('Notice: This version does not support upgrades from PostCalendar 6.x and earlier. Please see detailed upgrade instructions at <a href="modules/PostCalendar/docs/en/Admin/InstallationAndUpgrade.txt">the local docs</a>). After upgrading, you can install PostCalendar %s and perform this upgrade.', $upgradeToVersion));
        }

        // disable the max execution time in case there are many records and this takes too long
        ini_set('max_execution_time', 0);

        $defaultsettings = PostCalendar_Util::getdefaults();

        switch ($oldversion) {
            case '7.0.0':
            // no changes
            case '7.0.1':
                // set up some manager vars
                $connection = $this->entityManager->getConnection();
                $hookManager = $this->serviceManager->getService('zikula.hookmanager');

                // select partial array of all events for later manipulation
                $sql = "SELECT eid, hooked_area, eventDate, startTime, duration FROM postcalendar_events";
                $objects = $connection->fetchAll($sql);

                // add PostCalendar_Entity_RecurException and PostCalendar_Entity_EventCategory tables
                DoctrineHelper::createSchema($this->entityManager, array('PostCalendar_Entity_EventCategory',
                    'PostCalendar_Entity_RecurException'));
                // update the PostCalendar_Entity_CalendarEvent table
                DoctrineHelper::updateSchema($this->entityManager, array('PostCalendar_Entity_CalendarEvent'));

                // update every event with correct hooked_area and new eventStart and eventEnd values
                $sqls = array();
                foreach ($objects as $object) {
                    $hookedArea = isset($object['hooked_area']) ? $hookManager->getAreaId($object['hooked_area']) : 'null';
                    $eventStart = DateTime::createFromFormat('Y-m-d H:i:s', $object['eventDate'] . " " . $object['startTime']);
                    $eventEnd = clone $eventStart;
                    $eventEnd->modify("+" . $object['duration'] . " seconds");
                    $eid = $object['eid'];
                    $sqls[] = "UPDATE `postcalendar_events` 
                               SET `hooked_area` = $hookedArea, 
                                   `eventStart` = '{$eventStart->format('Y-m-d H:i:s')}', 
                                   `eventEnd` = '{$eventEnd->format('Y-m-d H:i:s')}' 
                               WHERE `postcalendar_events`.`eid`=$eid";
                    if (count($sqls) > 20) {
                        // this only runs the sql on the server every 20 events
                        foreach ($sqls as $sql) {
                            $stmt = $connection->prepare($sql);
                            try {
                                $stmt->execute();
                            } catch (Exception $e) {
                                LogUtil::registerError($e->getMessage());
                            }
                        }
                        $sqls = array();
                    }
                }

                // move relations from categories_mapobj to postcalendar_calendarevent_category
                // then delete old data
                // some remaining sql statements from above may be processed here (less than 20) 
                $sqls[] = "INSERT INTO postcalendar_calendarevent_category (entityId, registryId, categoryId) SELECT obj_id, reg_id, category_id FROM categories_mapobj WHERE modname = 'PostCalendar' AND tablename = 'postcalendar_events'";
                $sqls[] = "DELETE FROM categories_mapobj WHERE modname = 'PostCalendar' AND tablename = 'postcalendar_events'";
                foreach ($sqls as $sql) {
                    $stmt = $connection->prepare($sql);
                    try {
                        $stmt->execute();
                    } catch (Exception $e) {
                        LogUtil::registerError($e->getMessage());
                    }
                }

                // update non-postcalendar modvars where name=postcalendarhookconfig change key from areaname to areaid
                $sql = "SELECT id, value from module_vars WHERE name='postcalendarhookconfig'";
                $objects = $connection->fetchAll($sql);
                $sqls = array();
                foreach ($objects as $object) {
                    $values = unserialize($object['value']);
                    $newValues = array();
                    // update the keys 
                    foreach ($values as $key => $value) {
                        $newValues[$hookManager->getAreaId($key)] = $value;
                    }
                    $sqls[] = "UPDATE module_vars SET value=" . serialize($newValues) . "WHERE id = " . $object['id'];
                }
                // general table updates to correct data
                // update category registry data to change tablename to EntityName
                $sqls[] = "UPDATE categories_registry SET tablename = 'CalendarEvent' WHERE tablename = 'postcalendar_events'";
                // change sharing values - 2's become 0's and  and 4's become 0's
                $sqls[] = "UPDATE postcalendar_events SET sharing = 0 WHERE sharing IN (2, 4)";
                // update hometext values change n/a to :text:n/a
                $sqls[] = "UPDATE postcalendar_events SET hometext = ':text:n/a' WHERE hometext = 'n/a'";
                // change endDate = '0000-00-00' to null
                $sqls[] = "UPDATE postcalendar_events SET endDate = null WHERE endDate = '0000-00-00'";
                foreach ($sqls as $sql) {
                    $stmt = $connection->prepare($sql);
                    try {
                        $stmt->execute();
                    } catch (Exception $e) {
                        LogUtil::registerError($e->getMessage());
                    }
                }

                // update postcalendar modvars
                // convert old pcEventDateFormat to new setting/default
                $this->setVar('pcEventDateFormat', $defaultsettings['pcEventDateFormat']);
                LogUtil::registerStatus($this->__('NOTICE: The PostCalendar date display format has been reset to "Day Month Year", you must manually change it again if desired.'));
                // add pcDateFormats
                $this->setVar('pcDateFormats', $defaultsettings['pcDateFormats']);
                // add pcNavBarType
                $this->setVar('pcNavBarType', $defaultsettings['pcNavBarType']);
                // add pcAllowedViews
                $this->setVar('pcAllowedViews', $defaultsettings['pcAllowedViews']);
                // add pcTimeItExists
                $this->setVar('pcTimeItExists', $defaultsettings['pcTimeItExists']);
                // add pcTimeItMigrateComplete
                // there is no default setting for this to prevent a resetDefault to allow double migration
                $this->setVar('pcTimeItMigrateComplete', false);
                // remove pcNavDateOrder
                $this->delVar('pcNavDateOrder');
                // remove enablecategorization
                $this->delVar('enablecategorization');
                // remove enablelocations
                $this->delVar('enablelocations');

            case '8.0.0':
                $this->setVar('pcPendingContent', $defaultsettings['pcPendingContent']);
                
            case '8.0.1':
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
        //drop the tables
        DoctrineHelper::dropSchema($this->entityManager, array('PostCalendar_Entity_CalendarEvent',
            'PostCalendar_Entity_EventCategory',
            'PostCalendar_Entity_RecurException'));
        $this->delVars();

        // Delete entries from category registry
        CategoryRegistryUtil::deleteEntry('PostCalendar');
        CategoryUtil::deleteCategoriesByPath('/__SYSTEM__/Modules/PostCalendar', 'path');

        // unregister handlers
        EventUtil::unregisterPersistentModuleHandlers('PostCalendar');

        HookUtil::unregisterSubscriberBundles($this->version->getHookSubscriberBundles());
        HookUtil::unregisterProviderBundles($this->version->getHookProviderBundles());

        return true;
    }

    /**
     * create initial calendar event
     */
    private function createinstallevent()
    {
        $cat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar/Events');

        $eventArray = array(
            'title' => $this->__('PostCalendar Installed'),
            'hometext' => ':text:' . $this->__('On this date, the PostCalendar module was installed. Thank you for trying PostCalendar! This event can be safely deleted if you wish.'),
            'alldayevent' => true,
            'eventstatus' => PostCalendar_Entity_CalendarEvent::APPROVED,
            'sharing' => PostCalendar_Entity_CalendarEvent::SHARING_GLOBAL,
            'website' => 'https://github.com/craigh/PostCalendar/wiki',
            'categories' => array(
                'Main' => $cat['id']));

        try {
            $event = new PostCalendar_Entity_CalendarEvent();
            $event->setFromArray($eventArray);
            $this->entityManager->persist($event);
            $this->entityManager->flush();
        } catch (Exception $e) {
            return LogUtil::registerError($e->getMessage());
        }

        return true;
    }

    /**
     * create the category tree
     * @return void
     */
    private function createCategoryTree()
    {
        // create category
        CategoryUtil::createCategory('/__SYSTEM__/Modules', 'PostCalendar', null, $this->__('PostCalendar'), $this->__('Calendar for Zikula'));
        // create subcategory
        CategoryUtil::createCategory('/__SYSTEM__/Modules/PostCalendar', 'Events', null, $this->__('Events'), $this->__('Initial sub-category created on install'), array('color' => '#99ccff'));
        // get the category path to insert PostCalendar categories
        $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar');
        if ($rootcat) {
            // create an entry in the categories registry to the Main property
            if (!CategoryRegistryUtil::insertEntry('PostCalendar', 'CalendarEvent', 'Main', $rootcat['id'])) {
                throw new Zikula_Exception("Cannot insert Category Registry entry.");
            }
        } else {
            $this->throwNotFound("Root category not found.");
        }
    }

    /**
     * Provide legacy ContentType map for upgrade process in Content module
     * @return array 
     */
    public static function LegacyContentTypeMap()
    {
        $oldToNew = array(
            'postcalevent' => 'PostCalEvent',
            'postcalevents' => 'PostCalEvents'
        );
        return $oldToNew;
    }

}
