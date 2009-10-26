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

/**
 * Initializes a new install of PostCalendar
 *
 * This function will initialize a new installation of PostCalendar.
 * It is accessed via the Zikula Admin interface and should
 * not be called directly.
 *
 * @return  boolean    true/false
 * @access  public
 */
function postcalendar_init()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // create tables
    if (!DBUtil::createTable('postcalendar_events') || !DBUtil::createTable('postcalendar_categories')) {
        return LogUtil::registerError(__('Error! Could not create the table.', $dom));
    }

    // insert default category
    if (!_postcalendar_createdefaultcategory()) {
        return LogUtil::registerError (__('Error! Could not create default category.', $dom));
    }

    // PostCalendar Default Settings
    $defaultsettings = postcalendar_init_getdefaults();
    $result = pnModSetVars('PostCalendar', $defaultsettings);
    if (!$result) {
        return LogUtil::registerError(__('Error! Could not set the default settings for PostCalendar.', $dom));
    }

    postcalendar_init_reset_scribite();

    return true;
}

/**
 * Upgrades an old install of PostCalendar
 *
 * This function is used to upgrade an old version
 * of PostCalendar.  It is accessed via the Zikula
 * Admin interface and should not be called directly.
 *
 * @return boolean    true/false
 * @param  string    $oldversion Version we're upgrading
 * @access  public
 * @copyright    The PostCalendar Team 2009
 */
function postcalendar_upgrade($oldversion)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // We only support upgrade from version 4 and up. Notify users if they have a version below that one.
    if (version_compare($oldversion, '4', '<'))
    {
        $modversion = array('version' => 'unknown');
        // Receive the current version information, where $modversion will be overwritten
        require dirname(__FILE__) . '/pnversion.php';

        // Inform user about error, and how he can upgrade to $modversion['version']
        return LogUtil::registerError(__f('Notice: This version does not support upgrades from PostCalendar 3.x and earlier. Please download and install version 4.0.3  (available from <a href="http://code.zikula.org/soundwebdevelopment/downloads">code.zikula.org/soundwebdevelopment</a>). After upgrading, you can install PostCalendar %version% and perform this upgrade.', $modversion, $dom));
    }

    // change the database. DBUtil + ADODB detect the changes on their own
    // and perform all necessary steps without help from the module author
    if (!DBUtil::changeTable('postcalendar_events') || !DBUtil::changeTable('postcalendar_categories')) {
        return LogUtil::registerError(__('Error! Could not upgrade the tables.', $dom));
    }

    switch ($oldversion) {

        case '4.0.0':
        case '4.0.1':
        case '4.0.2':
        case '4.0.3': // Also support upgrades from PostCalendar 4.03a (http://www.krapohl.info)
            // v4b TS start
            pnModSetVar('PostCalendar', 'pcRepeating', '0');
            pnModSetVar('PostCalendar', 'pcMeeting', '0');
            pnModSetVar('PostCalendar', 'pcAddressbook', '1');
            // v4b TS end
        case '5.0.0':
            pnModSetVar('PostCalendar', 'pcTemplate', 'default');
        case '5.0.1':
            pnModDelVar('PostCalendar', 'pcTemplate');
        case '5.1.0':
            pnModSetVar('PostCalendar', 'pcNotifyAdmin2Admin', '0');
        case '5.5.0':
            postcalendar_init_reset_scribite();
        case '5.5.1':
        case '5.5.2':
        case '5.5.3':
            pnModSetVar('PostCalendar', 'pcAllowCatFilter', '1');
            pnModDelVar('PostCalendar', 'pcDayHighlightColor');
            pnModDelVar('PostCalendar', 'pcAllowSiteWide');
            pnModDelVar('PostCalendar', 'pcAddressbook');
            pnModDelVar('PostCalendar', 'pcMeeting');
            pnModDelVar('PostCalendar', 'pcUseInternationalDates');
        case '5.8.0':
            // no changes
        case '5.8.1':
            pnModDelVar('PostCalendar', 'pcDisplayTopics');
            DBUtil::dropColumn('postcalendar_events', 'pc_comments');
            DBUtil::dropColumn('postcalendar_events', 'pc_counter');
            DBUtil::dropColumn('postcalendar_events', 'pc_recurrfreq');
            DBUtil::dropColumn('postcalendar_events', 'pc_meeting_id');
            DBUtil::dropColumn('postcalendar_events', 'pc_language');
            // pc_topic and pc_catid columns are dropped in the migration process
            // postcalendar_categories table is dropped in the migration process
        //case '6.0.0':
            //placeholder :-)
    }

    // if we get this far - clear the cache
    pnModAPIFunc('PostCalendar', 'admin', 'clearCache');

    return true;
}

/**
 * PostCalendar Default Module Settings
 *
 * @return array An associated array with key=>value pairs of the default module settings
 */
function postcalendar_init_getdefaults()
{
    // PostCalendar Default Settings
    $defaults = array(
    'pcTime24Hours'           => _TIMEFORMAT == 24 ? '1' : '0',
    'pcEventsOpenInNewWindow' => '0',
    'pcFirstDayOfWeek'        => '0', /* Sunday */
    'pcUsePopups'             => '0',
    'pcAllowDirectSubmit'     => '0',
    'pcListHowManyEvents'     => '15',
    'pcDisplayTopics'         => '0',
    'pcEventDateFormat'       => '%B %d, %Y', /* American: e.g. July 4, 2010 */
    'pcRepeating'             => '1', /* display repeating options */
    'pcAllowUserCalendar'     => '0',
    'pcTimeIncrement'         => '15',
    'pcDefaultView'           => 'month',
    'pcUseCache'              => '0',
    'pcCacheLifetime'         => '3600',
    'pcNotifyAdmin'           => '1',
    'pcNotifyEmail'           => pnConfigGetVar('adminmail'),
    'pcNotifyAdmin2Admin'     => '0',
    'pcAllowCatFilter'        => '1',
    );

    return $defaults;
}

/**
 * Reset scribite config for PostCalendar module.
 *
 * Since we updated the functionname for creating / editing a new event from func=submit to func=new,
 * scribite doesn't load any editor. If we force it to our new function.
 */
function postcalendar_init_reset_scribite()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // update the scribite
    if (pnModAvailable('scribite') && pnModAPILoad('scribite', 'user') && pnModAPILoad('scribite', 'admin')) {
        $modconfig = pnModAPIFunc('scribite', 'user', 'getModuleConfig', array('modulename' => 'PostCalendar'));
        $mid = false;

        if (count($modconfig)) {
            $modconfig['modfuncs'] = 'new,edit,submit';
            $modconfig['modareas'] = 'description';
            $mid = pnModAPIFunc('scribite', 'admin', 'editmodule', $modconfig);
        } else {
            // create new module in db
            $modconfig = array('modulename' => 'PostCalendar',
                               'modfuncs'   => 'new,edit,submit',
                               'modareas'   => 'description',
                               'modeditor'  => '-');
            $mid = pnModAPIFunc('scribite', 'admin', 'addmodule', $modconfig);
        }

        // Error tracking
        if ($mid === false) {
            //pnModLangLoad('scribite', 'user');
            LogUtil::registerStatus (__('Error! Could not update the configuration.', $dom));
        }
    }
}

/**
 * Deletes an install of PostCalendar
 *
 * This function removes PostCalendar from you
 * Zikula install and should be accessed via
 * the Zikula Admin interface
 *
 * @return  boolean    true/false
 * @access  public
 * @copyright    The PostCalendar Team 2009
 */
function postcalendar_delete()
{
    $result = DBUtil::dropTable('postcalendar_events');
    $result = $result && pnModDelVar('PostCalendar');

    // Delete entries from category registry
    pnModDBInfoLoad ('Categories');
    Loader::loadArrayClassFromModule('Categories', 'CategoryRegistry');
    $registry = new PNCategoryRegistryArray();
    $registry->deleteWhere ('crg_modname=\'PostCalendar\'');

    return $result;
}

/**
 * copied and adapted from News module
 * @author  Mark West
 * migrate old local categories to the categories module
 */
function _postcalendar_migratecategories()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // load the admin language file
    // pull all data from the old tables
    $tables = pnDBGetTables();
    $sql = "SELECT pc_catid, pc_catname, pc_catcolor, pc_catdesc FROM {$tables[postcalendar_categories]}";

    $result = DBUtil::executeSQL($sql);
    $categories = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $categories[] = $result->fields;
    }

    if (pnModAvailable('Topics')) {
        $sql = "SELECT pn_topicid, pn_topicname, pn_topicimage, pn_topictext FROM {$tables[topics]}";
        $result = DBUtil::executeSQL($sql);
        $topics = array();
        for (; !$result->EOF; $result->MoveNext()) {
            $topics[] = $result->fields;
        }
    }

    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');

    // get the language file
    $lang = pnUserGetLang();

    // create the Main category and entry in the categories registry
    _postcalendar_createdefaultcategory('/__SYSTEM__/Modules/PostCalendar');

    // create the Topics category and entry in the categories registry
    if (pnModAvailable('Topics')) {
        _postcalendar_createtopicscategory('/__SYSTEM__/Modules/Topics');
    }

    // get the category path for which we're going to insert our upgraded PostCalendar categories
    $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar');

    // migrate our main categories
    $categorymap = array();
    foreach ($categories as $category) {
        $cat = new PNCategory ();
        $data = $cat->getData();
        $data['parent_id']               = $rootcat['id'];
        $data['name']                    = $category[1];
        $data['display_name']            = array($lang => $category[1]);
        $data['display_desc']            = array($lang => $category[3]);
        $data['__ATTRIBUTES__']['color'] = $category[2];
        $cat->setData ($data);
        if (!$cat->validate('admin')) {
            return false;
        }
        $cat->insert();
        $cat->update();
        $categorymap[$category[0]] = $cat->getDataField('id');
    }

    if (pnModAvailable('Topics')) {
        // get the category path for which we're going to insert our upgraded Topics categories
        $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/Topics');
    
        // migrate our topic categories
        $topicsmap = array();
        foreach ($topics as $topic) {
            $cat = new PNCategory ();
            $data = $cat->getData();
            $data['parent_id']                     = $rootcat['id'];
            $data['name']                          = $topic[1];
            $data['value']                         = -1;
            $data['display_name']                  = array($lang => $topic[3]);
            $data['display_desc']                  = array($lang => $topic[3]);
            $data['__ATTRIBUTES__']['topic_image'] = $topic[2];
            $cat->setData ($data);
            if (!$cat->validate('admin')) {
                return false;
            }
            $cat->insert();
            $cat->update();
            $topicsmap[$topic[0]] = $cat->getDataField('id');
        }
    
        // After an upgrade we want the legacy topic template variables to point to the Topic property
        pnModSetVar('PostsCalendar', 'topicproperty', 'Topic');
    }

    // migrate page category assignments
    $sql = "SELECT pc_eid, pc_catid, pc_topic FROM {$tables[postcalendar_events]}";
    $result = DBUtil::executeSQL($sql);
    $pages = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $pages[] = array('sid' => $result->fields[0],
                         '__CATEGORIES__' => array('Main' => $categorymap[$result->fields[1]],
                                                   'Topic' => $topicsmap[$result->fields[2]]),
                         '__META__' => array('module' => 'PostCalendar'));
    }
    foreach ($pages as $page) {
        if (!DBUtil::updateObject($page, 'postcalendar_events', '', 'eid')) {
            return LogUtil::registerError (__('Table update failed.', $dom));
        }
    }

    // drop old table
    DBUtil::dropTable('postcalendar_categories');
    // we don't drop the topics table - this is the job of the topics module

    // finally drop the secid column
    DBUtil::dropColumn('postcalendar_events', 'pc_catid');
    DBUtil::dropColumn('postcalendar_events', 'pc_topic');

    return true;
}

/**
 * copied and adapted from News module
 * @author  Mark West
 * create the default category tree
 */
function _postcalendar_createdefaultcategory($regpath = '/__SYSTEM__/Modules/Global')
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');

    // get the language file
    $lang = pnUserGetLang();

    // get the category path for which we're going to insert our place holder category
    $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules');
    $nCat    = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar');

    if (!$nCat) {
        // create placeholder for all our migrated categories
        $cat = new PNCategory ();
        $cat->setDataField('parent_id', $rootcat['id']);
        $cat->setDataField('name', 'PostCalendar');
        $cat->setDataField('display_name', array($lang => __('PostCalendar', $dom)));
        $cat->setDataField('display_desc', array($lang => __('Calendar for Zikula', $dom)));
        if (!$cat->validate('admin')) {
            return false;
        }
        $cat->insert();
        $cat->update();
    }

    // get the category path for which we're going to insert our upgraded PostCalendar categories
    $rootcat = CategoryUtil::getCategoryByPath($regpath);
    if ($rootcat) {
        // create an entry in the categories registry to the Default property
        $registry = new PNCategoryRegistry();
        $registry->setDataField('modname', 'PostCalendar');
        $registry->setDataField('table', 'postcalendar_events');
        $registry->setDataField('property', __('Default', $dom));
        $registry->setDataField('category_id', $rootcat['id']);
        $registry->insert();
    } else {
        return false;
    }

    return true;
}

/**
 * copied and adapted from News module
 * @author  Mark West
 * create the Topics category tree
 */
function _postcalendar_createtopicscategory($regpath = '/__SYSTEM__/Modules/Topics')
{
    if (!pnModAvailable('Topics')) return false;

    // get the language file
    $lang = pnUserGetLang();

    // get the category path for which we're going to insert our place holder category
    $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules');

    // create placeholder for all the migrated topics
    $tCat    = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/Topics');

    if (!$tCat) {
        // create placeholder for all our migrated categories
        $cat = new PNCategory ();
        $cat->setDataField('parent_id', $rootcat['id']);
        $cat->setDataField('name', 'Topics');

        Loader::includeOnce("modules/Topics/lang/{$lang}/version.php");
        $cat->setDataField('display_name', array($lang => _TOPICS_DISPLAYNAME)); // allow constants here because Topics is legacy
        $cat->setDataField('display_desc', array($lang => _TOPICS_DESCRIPTION)); // allow constants here because Topics is legacy
        if (!$cat->validate('admin')) {
            return false;
        }
        $cat->insert();
        $cat->update();
    }

    // get the category path for which we're going to insert our upgraded categories
    $rootcat = CategoryUtil::getCategoryByPath($regpath);
    if ($rootcat) {
        // create an entry in the categories registry to the Topic property
        $registry = new PNCategoryRegistry();
        $registry->setDataField('modname', 'PostCalendar');
        $registry->setDataField('table', 'postcalendar_events');
        $registry->setDataField('property', 'Topic');
        $registry->setDataField('category_id', $rootcat['id']);
        $registry->insert();
    } else {
        return false;
    }

    return true;
}
