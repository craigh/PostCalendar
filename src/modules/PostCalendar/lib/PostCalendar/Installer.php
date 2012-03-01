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
                                                                     'PostCalendar_Entity_EventCategory'));
        } catch (Exception $e) {
            echo "<pre>";
            var_dump($e->getMessage());
            die;
            return false;
        }
        
        // insert default category
        if (!$this->createdefaultcategory()) {
            return LogUtil::registerError($this->__('Error! Could not create default category.'));
        }
        $this->createdefaultsubcategory();

        // PostCalendar Default Settings
        $defaultsettings = PostCalendar_Util::getdefaults();
        $result = ModUtil::setVars('PostCalendar', $defaultsettings);
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
            return LogUtil::registerError($this->__f('Notice: This version does not support upgrades from PostCalendar 7.x and earlier. Please see detailed upgrade instructions at <a href="https://github.com/craigh/PostCalendar/wiki/Installation-and-Upgrade">the GitHub site</a>). After upgrading, you can install PostCalendar %s and perform this upgrade.', $upgradeToVersion));
        }

        switch ($oldversion) {
            case '7.0.0':
                // no changes
            case '7.0.1':
                // update category registry data to change tablename to EntityName
                // add category relation table
                // move relations from categories_mapobj to postcalendar_calendarevent_category
                // update the DB tables
                // change sharing values - remove any 2's and 4's
                // update hometext values change n/a to :text:n/a
            case '7.1.0':
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
                                                               'PostCalendar_Entity_EventCategory'));
        ModUtil::delVar('PostCalendar');

        // unregister handlers
        EventUtil::unregisterPersistentModuleHandlers('PostCalendar');

        HookUtil::unregisterSubscriberBundles($this->version->getHookSubscriberBundles());
        HookUtil::unregisterProviderBundles($this->version->getHookProviderBundles());

        return true;
    }

    /**
     * create the default category tree
     * copied and adapted from News module
     * @return boolean
     */
    private function createdefaultcategory()
    {
        if (!$cat = CategoryUtil::createCategory('/__SYSTEM__/Modules', 'PostCalendar', null, $this->__('PostCalendar'), $this->__('Calendar for Zikula'))) {
            return false;
        }
        // get the category path to insert upgraded PostCalendar categories
        $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar');
        if ($rootcat) {
            // create an entry in the categories registry to the Main property
            CategoryRegistryUtil::insertEntry ('PostCalendar', 'CalendarEvent', 'Main', $rootcat['id']);
        } else {
            return false;
        }
        LogUtil::registerStatus($this->__("PostCalendar: 'Main' category created."));
        return true;
    }

    /**
     * create initial calendar event
     */
    private function createinstallevent()
    {
        $cat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar/Events');

        $eventArray = array(
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
            'website'        => 'https://github.com/craigh/PostCalendar/wiki',
            '__CATEGORIES__' => array(
                'Main' => $cat['id']),
            '__META__'       => array(
                'module' => 'PostCalendar'));

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
     * create initial category on first install
     * @return boolean
     */
    private function createdefaultsubcategory()
    {
        if (!$cat = CategoryUtil::createCategory('/__SYSTEM__/Modules/PostCalendar', 'Events', null, $this->__('Events'), $this->__('Initial sub-category created on install'), array('color' => '#99ccff'))) {
            LogUtil::registerError($this->__('Error! Could not create an initial sub-category.'));
            return false;
        }

        LogUtil::registerStatus($this->__("PostCalendar: Initial sub-category created (Events)."));
        return true;
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