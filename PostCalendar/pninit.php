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
 * @author  Arjen Tebbenhof
 * @return  boolean    true/false
 * @access  public
 */
function PostCalendar_init()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // create tables
    if (!DBUtil::createTable('postcalendar_events')) {
        return LogUtil::registerError(__('Error! Could not create the table.', $dom));
    }

    // insert default category
    if (!_postcalendar_createdefaultcategory('/__SYSTEM__/Modules/PostCalendar')) {
        return LogUtil::registerError(__('Error! Could not create default category.', $dom));
    }

    // PostCalendar Default Settings
    $defaultsettings = postcalendar_init_getdefaults();
    $result = pnModSetVars('PostCalendar', $defaultsettings);
    if (!$result) {
        return LogUtil::registerError(__('Error! Could not set the default settings for PostCalendar.', $dom));
    }

    postcalendar_init_reset_scribite();
    _postcalendar_createdefaultsubcategory();
    _postcalendar_createinstallevent();
    _postcalendar_registermodulehooks();

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
function PostCalendar_upgrade($oldversion)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if (!SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // We only support upgrade from version 4 and up. Notify users if they have a version below that one.
    if (version_compare($oldversion, '4', '<')) {
        $modversion = array(
            'version' => 'unknown');
        // Receive the current version information, where $modversion will be overwritten
        require 'modules/PostCalendar/pnversion.php';

        // Inform user about error, and how he can upgrade to $modversion['version']
        return LogUtil::registerError(__f('Notice: This version does not support upgrades from PostCalendar 3.x and earlier. Please download and install version 4.0.3  (available from <a href="http://code.zikula.org/soundwebdevelopment/downloads">code.zikula.org/soundwebdevelopment</a>). After upgrading, you can install PostCalendar %version% and perform this upgrade.', $modversion, $dom));
    }

    switch ($oldversion) {

        case '4.0.0':
        case '4.0.1':
        case '4.0.2':
        case '4.0.3': // Also support upgrades from PostCalendar 4.03a (http://www.krapohl.info)
            pnModSetVar('PostCalendar', 'pcRepeating', '0');
            pnModSetVar('PostCalendar', 'pcMeeting', '0');
            pnModSetVar('PostCalendar', 'pcAddressbook', '1');
        case '5.0.0':
            pnModSetVar('PostCalendar', 'pcTemplate', 'default');
        case '5.0.1':
            pnModDelVar('PostCalendar', 'pcTemplate');
        case '5.1.0':
            pnModSetVar('PostCalendar', 'pcNotifyAdmin2Admin', '0');
        case '5.5.0':
            if (!postcalendar_init_reset_scribite()) {
                return '5.5.0';
            }
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
        case '5.8.1':
            if (!postcalendar_init_correctserialization()) {
                LogUtil::registerError(__('Error: Could not correct multi-byte serialization.', $dom));
                return '5.8.1';
            }
        case '5.8.2':
            ini_set('max_execution_time', 86400);
            if (!_postcalendar_cull_meetings()) {
                LogUtil::registerError(__('Error: Could not cull meetings.', $dom));
                return '5.8.2';
            }
            if (!$categorymap = _postcalendar_migratecategories()) {
                // attempt to migrate local categories
                LogUtil::registerError(__('Error: Could not migrate categories.', $dom));
                return '5.8.2';
            }
            if (pnModGetVar('PostCalendar', 'pcDisplayTopics')) {
                // if currently using Topics module, attempt to migrate
                if (!$topicmap = _postcalendar_migratetopics()) {
                    LogUtil::registerError(__('Error: Could not migrate topics.', $dom));
                    return '5.8.2';
                }
            } else {
                LogUtil::registerStatus(__('PostCalendar: Topics ignored in upgrade.', $dom));
            }
            // change structure of data to reassociate events with new categories
            // this function upgrades the table defs also to newest
            if (!_postcalendar_transcode_ids($categorymap, $topicmap)) {
                LogUtil::registerError(__('Error: Could not transcode category and/or topic IDs.', $dom));
                return '5.8.2';
            }
            if (!_postcalendar_convert_informant()) {
                LogUtil::registerError(__('Error: Could not convert informant field to uid.', $dom));
                return '5.8.2';
            }
            if (!postcalendar_init_reset_scribite()) {
                return '5.8.2';
            }
            pnModDelVar('PostCalendar', 'pcDisplayTopics');
            pnModDelVar('PostCalendar', 'pcUseCache');
            pnModDelVar('PostCalendar', 'pcCacheLifetime');
            pnModDelVar('PostCalendar', 'pcRepeating');
            pnModSetVar('PostCalendar', 'enablecategorization', true);
            pnModSetVar('PostCalendar', 'enablenavimages', true);
            pnModSetVar('PostCalendar', 'pcNavDateOrder', array(
                'format' => 'MDY',
                'D' => '%e',
                'M' => '%B',
                'Y' => '%Y'));
        case '6.0.0':
            _postcalendar_registermodulehooks();
        case '6.1.0':
            //future development
    }

    // if we get this far - clear the cache
    pnModAPIFunc('PostCalendar', 'admin', 'clearCache');

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
function PostCalendar_delete()
{
    $result = DBUtil::dropTable('postcalendar_events');
    $result = $result && pnModDelVar('PostCalendar');

    // Delete entries from category registry
    pnModDBInfoLoad('Categories');
    DBUtil::deleteWhere('categories_registry', "crg_modname='PostCalendar'");
    DBUtil::deleteWhere('categories_mapobj', "cmo_modname='PostCalendar'");

    return $result;
}

/**
 * PostCalendar Default Module Settings
 *
 * @author Arjen Tebbenhof
 * @return array An associated array with key=>value pairs of the default module settings
 */
function postcalendar_init_getdefaults()
{
    // figure out associated categories and assign default value of 0 (none)
    Loader::loadClass('CategoryRegistryUtil');
    $defaultscats = array();
    $cats = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
    foreach ($cats as $prop => $id) {
        $defaultcats[$prop] = 0;
    }

    // PostCalendar Default Settings
    $defaults = array(
        'pcTime24Hours' => _TIMEFORMAT == 24 ? '1' : '0',
        'pcEventsOpenInNewWindow' => '0',
        'pcFirstDayOfWeek' => '0', // Sunday
        'pcUsePopups' => '0',
        'pcAllowDirectSubmit' => '0',
        'pcListHowManyEvents' => '15',
        'pcEventDateFormat' => '%B %e, %Y', // American: e.g. July 4, 2010
        'pcAllowUserCalendar' => '0', // no group
        'pcTimeIncrement' => '15',
        'pcDefaultView' => 'month',
        'pcNotifyAdmin' => '1',
        'pcNotifyEmail' => pnConfigGetVar('adminmail'),
        'pcNotifyAdmin2Admin' => '0',
        'pcAllowCatFilter' => '1',
        'enablecategorization' => '1',
        'enablenavimages' => '1',
        'pcDefaultCategories' => $defaultcats,
        'pcNavDateOrder' => array(
            'format' => 'MDY',
            'D' => '%e',
            'M' => '%B',
            'Y' => '%Y'));

    return $defaults;
}

/**
 * Reset scribite config for PostCalendar module.
 *
 * @author Arjen Tebbenhof
 * Since we updated the functionname for creating / editing a new event from func=submit to func=new,
 * scribite doesn't load any editor. If we force it to our new function.
 */
function postcalendar_init_reset_scribite()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    // update the scribite
    if (pnModAvailable('scribite') && pnModAPILoad('scribite', 'user') && pnModAPILoad('scribite', 'admin')) {
        $modconfig = pnModAPIFunc('scribite', 'user', 'getModuleConfig', array(
            'modulename' => 'PostCalendar'));
        $mid = false;

        if (count($modconfig)) {
            $modconfig['modfuncs'] = 'new,edit,copy,submit';
            $modconfig['modareas'] = 'description';
            $mid = pnModAPIFunc('scribite', 'admin', 'editmodule', $modconfig);
        } else {
            // create new module in db
            $modconfig = array(
                'modulename' => 'PostCalendar',
                'modfuncs' => 'new,edit,copy,submit',
                'modareas' => 'description',
                'modeditor' => '-');
            $mid = pnModAPIFunc('scribite', 'admin', 'addmodule', $modconfig);
        }

        // Error tracking
        if ($mid === false) {
            LogUtil::registerError(__('Error! Could not update the scribite configuration.', $dom));
        }
    }
}

/**
 * this code takes a field, unserialises it mb-safely, then reserialises it
 * This is only required when the previously serialised data contained
 * multi-byte data like German/Spanish characters.
 * for postcalendar, only the serialized 'location' field needs correction
 * the serialized 'recurrspec' field only contains integers for values and the
 * keys are in english with no special characters.
 * @author Drak & Craig Heydenburg
 */
function postcalendar_init_correctserialization()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $prefix = pnConfigGetVar('prefix');
    $Ssql = "SELECT pc_eid, pc_location FROM {$prefix}_postcalendar_events";
    $result = DBUtil::executeSQL($Ssql);
    for (; !$result->EOF; $result->MoveNext()) {
        $oldlocdata = DataUtil::mb_unserialize($result->fields[1]);
        $newlocdata = serialize($oldlocdata);
        $Usql = "UPDATE {$prefix}_postcalendar_events SET pc_location='$newlocdata' WHERE pc_eid=" . $result->fields[0];
        DBUtil::executeSQL($Usql);
    }
    LogUtil::registerStatus(__('PostCalendar: Serialized fields corrected.', $dom));
    return true;
}

/**
 * copied and adapted from News module
 * @author  Mark West?
 * migrate old local categories to the categories module
 */
function _postcalendar_migratecategories()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $prefix = pnConfigGetVar('prefix');

    $sql = "SELECT pc_catid, pc_catname, pc_catcolor, pc_catdesc FROM {$prefix}_postcalendar_categories";
    $result = DBUtil::executeSQL($sql);
    $categories = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $categories[] = $result->fields;
    }

    // create the Main category and entry in the categories registry
    _postcalendar_createdefaultcategory('/__SYSTEM__/Modules/PostCalendar');

    // migrate main categories
    $categorymap = array();
    foreach ($categories as $category) {
        if (!$catid = _postcalendar_createcategory(array(
            'rootpath'    => '/__SYSTEM__/Modules/PostCalendar',
            'name'        => $category[1],
            'displayname' => $category[1],
            'description' => $category[3],
            'attributes'  => array(
                'color' => $category[2])))) {
            LogUtil::registerError(__f('Error! Could not create sub-category (%s).', $category[1], $dom));
        }
        $categorymap[$category[0]] = $catid;
    }

    // drop old table
    DBUtil::dropTable('postcalendar_categories');

    LogUtil::registerStatus(__('PostCalendar: Categories successfully migrated.', $dom));
    return $categorymap;
}

/**
 * copied and adapted from News module
 * @author  Mark West?
 * migrate old local topics to the categories module
 */
function _postcalendar_migratetopics()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $prefix = pnConfigGetVar('prefix');

    // determine if Topics module has already been migrated
    $topicsidmap = _postcalendar_gettopicsmap(); // returns false if not previously migrated

    if (pnModAvailable('Topics') && (!$topicsidmap)) {
        // if the Topics module is available and topics have not already been moved to categories
        // migrate existing topics to categories
        $sql = "SELECT pn_topicid, pn_topicname, pn_topicimage, pn_topictext FROM {$prefix}_topics";
        $result = DBUtil::executeSQL($sql);
        $topics = array();
        for (; !$result->EOF; $result->MoveNext()) {
            $topics[] = $result->fields;
        }

        // create the Topics category and entry in the categories registry
        _postcalendar_createtopicscategory('/__SYSTEM__/Modules/Topics');

        // get the category path to insert upgraded Topics categories
        Loader::loadClass('CategoryUtil');
        $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/Topics');

        // migrate topic categories
        $topicsmap = array();
        foreach ($topics as $topic) {
            if (!$catid = _postcalendar_createcategory(array(
                'rootpath'    => '/__SYSTEM__/Modules/Topics',
                'name'        => $topic[1],
                'value'       => -1,
                'displayname' => $topic[3],
                'description' => $topic[3],
                'attributes'  => array(
                    'topic_image' => $topic[2])))) {
                LogUtil::registerError(__f('Error! Could not create sub-category (%s).', $topic[1], $dom));
            }
            $topicsmap[$topic[0]] = $catid;
        }

        // After an upgrade we want the legacy topic template variables to point to the Topic property
        pnModSetVar('PostCalendar', 'topicproperty', 'Topic');
    } else {
        $topicsmap = $topicsidmap; // use previously migrated topics
    } // end if ((pnModAvailable('Topics')) AND (!$topicsidmap))

    // don't drop the topics table - this is the job of the topics module

    LogUtil::registerStatus(__('PostCalendar: Topics successfully migrated.', $dom));

    return $topicsmap;
}

/**
 * change old category and topic ids to new category ids.
 * update event table
 * @author  Craig Heydenburg
 */
function _postcalendar_transcode_ids($categorymap, $topicsmap)
{
    if ((!isset($categorymap)) && (!isset($topicsmap))) {
        return false;
    }

    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $prefix = pnConfigGetVar('prefix');

    // migrate event category and topic assignments
    // first, associate each event with the new category ids
    $sql = "SELECT pc_eid, pc_catid, pc_topic FROM {$prefix}_postcalendar_events";
    $result = DBUtil::executeSQL($sql);
    // upgrade table structure so categories are usable (this drops all unneeded columns)
    if (!DBUtil::changeTable('postcalendar_events')) {
        return LogUtil::registerError(__('Error! Could not upgrade the tables.', $dom));
    }
    $events = array();
    for (; !$result->EOF; $result->MoveNext()) {
        if (is_array($categorymap)) {
            $catsarray['Main'] = $categorymap[$result->fields[1]];
        }
        if ((is_array($topicsmap)) && (!empty($topicsmap[$result->fields[2]]))) {
            $catsarray['Topic'] = $topicsmap[$result->fields[2]];
        }
        $events[] = array(
            'eid'            => $result->fields[0],
            '__CATEGORIES__' => $catsarray,
            '__META__'       => array(
                'module' => 'PostCalendar'));
    }
    // second, update each event with the new category assignments
    if (DBUtil::updateObjectArray($events, 'postcalendar_events', 'eid')) {
        LogUtil::registerStatus(__('PostCalendar: Category and/or Topic IDs converted.', $dom));
        return true;
    }
    return false;
}

/**
 * copied and adapted from News module
 * @author  Mark West?
 * create the default category tree
 */
function _postcalendar_createdefaultcategory($regpath = '/__SYSTEM__/Modules/Global')
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    // load necessary classes
    Loader::loadClass('CategoryUtil');

    if (!$cat = _postcalendar_createcategory(array(
        'rootpath'    => '/__SYSTEM__/Modules',
        'name'        => 'PostCalendar',
        'displayname' => __('PostCalendar', $dom),
        'description' => __('Calendar for Zikula', $dom)))) {
        return false;
    }

    // get the category path to insert upgraded PostCalendar categories
    $rootcat = CategoryUtil::getCategoryByPath($regpath);
    if ($rootcat) {
        // create an entry in the categories registry to the Main property
        _postcalendar_create_regentry($rootcat, array(
            'modname'  => 'PostCalendar',
            'table'    => 'postcalendar_events',
            'property' => __('Main', $dom)));
    } else {
        return false;
    }

    LogUtil::registerStatus(__("PostCalendar: 'Main' category created.", $dom));
    return true;
}

/**
 * copied and adapted from News module
 * @author  Mark West?
 * create the Topics category tree
 */
function _postcalendar_createtopicscategory($regpath = '/__SYSTEM__/Modules/Topics')
{
    if (!pnModAvailable('Topics')) {
        return false;
    }

    $dom = ZLanguage::getModuleDomain('PostCalendar');

    // load necessary classes
    Loader::loadClass('CategoryUtil');

    // create placeholder for all the migrated topics
    $tCat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/Topics');

    if (!$tCat) {
        // create placeholder for migrated topics
        $lang = ZLanguage::getLanguageCodeLegacy(); // need old three letter code for Topics
        Loader::includeOnce("modules/Topics/lang/{$lang}/version.php"); // load & allow constants here because Topics is legacy
        if (!$cat = _postcalendar_createcategory(array(
            'rootpath'    => '/__SYSTEM__/Modules',
            'name'        => 'Topics',
            'displayname' => _TOPICS_DISPLAYNAME,
            'description' => _TOPICS_DESCRIPTION))) {
            return false;
        }
    }

    // get the category path to insert upgraded categories
    $rootcat = CategoryUtil::getCategoryByPath($regpath);
    if ($rootcat) {
        // create an entry in the categories registry to the Topic property
        _postcalendar_create_regentry($rootcat, array(
            'modname'  => 'PostCalendar',
            'table'    => 'postcalendar_events',
            'property' => 'Topic'));
    } else {
        return false;
    }

    LogUtil::registerStatus(__("PostCalendar: Topics category created.", $dom));
    return true;
}

/**
 * discover if the Topics information is already available in categories
 * if so, return map of old topic id => new category id
 * if not return false
 * @author  Craig Heydenburg
 */
function _postcalendar_gettopicsmap($topicspath = '/__SYSTEM__/Modules/Topics')
{
    Loader::loadClass('CategoryUtil');
    $cat = CategoryUtil::getCategoryByPath($topicspath);
    // if category path doesn't exist or Topics mod not available (can't map)
    if ((empty($cat)) || (!pnModAvailable('Topics'))) {
        return false;
    }

    // get the categories in Topics as an array
    $categories = CategoryUtil::getSubCategoriesForCategory($cat);
    foreach ($categories as $category) {
        $thisid = $category['id'];
        $n_cats[$thisid] = $category['name'];
    }

    // get the topics information into an array
    $prefix = pnConfigGetVar('prefix');
    $sql = "SELECT pn_topicid, pn_topicname FROM {$prefix}_topics";
    $result = DBUtil::executeSQL($sql);
    $topics = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $topics[$result->fields[0]] = $result->fields[1]; // $topics[id] = name
    }

    // map the old topic id to the new topic id
    $topicidmap = array();
    foreach ($topics as $id => $name) {
        $foundkey = array_search($name, $n_cats);
        if ($foundkey !== false) {
            $topicidmap[$id] = $foundkey; // $topicidmap[old_topics_id]=new_cat_id
            unset($topics[$id]); //remove from array
        }
    }

    // if the $topics array is not empty, then all the topics were not found in the categories
    if (!empty($topics)) {
        return false;
    }

    // create an entry in the categories registry to the Topic property
    _postcalendar_create_regentry($cat, array(
        'modname'  => 'PostCalendar',
        'table'    => 'postcalendar_events',
        'property' => 'Topic'));

    // return array map
    return $topicidmap;
}

/**
 * remove duplicate copies of same event with different eid and aid but same meeting_id
 * @author  Craig Heydenburg
 */
function _postcalendar_cull_meetings()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $prefix = pnConfigGetVar('prefix');
    $sql = "SELECT pc_eid, pc_meeting_id FROM {$prefix}_postcalendar_events WHERE pc_meeting_id > 0 ORDER BY pc_meeting_id, pc_eid";
    $result = DBUtil::executeSQL($sql);
    $old_m_id = "NULL";
    for (; !$result->EOF; $result->MoveNext()) {
        $new_m_id = $result->fields[1];
        if (($old_m_id) && ($old_m_id != "NULL") && ($new_m_id > 0) && ($old_m_id == $new_m_id)) {
            DBUtil::deleteObjectByID('postcalendar_events', $result->fields[0], 'eid'); // delete dup event
        }
        $old_m_id = $new_m_id;
    }

    DBUtil::dropColumn('postcalendar_events', 'pc_meeting_id');
    LogUtil::registerStatus(__f('PostCalendar: Meetings culled. %s column dropped', 'pc_meeting_id', $dom));

    return true;
}

/**
 * convert informant column to uid of informant
 * @author Craig Heydenburg
 */
function _postcalendar_convert_informant()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $prefix = pnConfigGetVar('prefix');

    $sql = "UPDATE {$prefix}_postcalendar_events e, {$prefix}_users u
        SET e.pc_informant = u.pn_uid
        WHERE u.pn_uname = e.pc_informant";

    if (!$result = DBUtil::executeSQL($sql)) {
        return false;
    }

    $sql = "UPDATE {$prefix}_postcalendar_events e
        SET e.pc_informant = " . SessionUtil::getVar('uid') . "
        WHERE e.pc_informant = 0"; // seems to select text values only


    if (!$result = DBUtil::executeSQL($sql)) {
        return false;
    }

    LogUtil::registerStatus(__f("PostCalendar: '%s' field converted.", 'informant', $dom));
    return true;
}

/**
 * create initial calendar event
 * @author Craig Heydenburg
 */
function _postcalendar_createinstallevent()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    Loader::loadClass('CategoryUtil');
    $cat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/PostCalendar/Events');

    $event = array(
        'title'          => __('PostCalendar Installed', $dom),
        'hometext'       => __(':text:On this date, the PostCalendar module was installed. Thank you for trying PostCalendar! This event can be safely deleted if you wish.', $dom),
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
        LogUtil::registerStatus(__("PostCalendar: Installation event created.", $dom));
        return true;
    }

    return LogUtil::registerError(__('Error! Could not create an installation event.', $dom));

}

/**
 * create initial category on first install
 * @author Craig Heydenburg
 */
function _postcalendar_createdefaultsubcategory()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if (!$cat = _postcalendar_createcategory(array(
        'rootpath'    => '/__SYSTEM__/Modules/PostCalendar',
        'name'        => 'Events',
        'displayname' => __('Events', $dom),
        'description' => __('Initial sub-category created on install', $dom),
        'attributes'  => array(
            'color' => '#99ccff')))) {
        LogUtil::registerError(__('Error! Could not create an initial sub-category.', $dom));
        return false;
    }

    LogUtil::registerStatus(__("PostCalendar: Initial sub-category created (Events).", $dom));
    return true;
}

/**
 * create category
 * @author Craig Heydenburg
 */
function _postcalendar_createcategory($catarray)
{
    // expecting array(rootpath=>'', name=>'', displayname=>'', description=>'', attributes=>array())
    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');

    // get the language file
    $lang = ZLanguage::getLanguageCode();

    // get the category path to insert category
    $rootcat = CategoryUtil::getCategoryByPath($catarray['rootpath']);
    $nCat = CategoryUtil::getCategoryByPath($catarray['rootpath'] . "/" . $catarray['name']);

    if (!$nCat) {
        $cat = new PNCategory();
        $data = $cat->getData();
        $data['parent_id'] = $rootcat['id'];
        $data['name'] = $catarray['name'];
        if (isset($catarray['value'])) {
            $data['value'] = $catarray['value'];
        }
        $data['display_name'] = array(
            $lang => $catarray['displayname']);
        $data['display_desc'] = array(
            $lang => $catarray['description']);
        if ((isset($catarray['attributes'])) && is_array($catarray['attributes'])) {
            foreach ($catarray['attributes'] as $name => $value) {
                $data['__ATTRIBUTES__'][$name] = $value;
            }
        }
        $cat->setData($data);
        if (!$cat->validate('admin')) {
            return false;
        }
        $cat->insert();
        $cat->update();
        return $cat->getDataField('id');
    }
    return -1;
}
/**
 * create an entry in the categories registry
 * @author Craig Heydenburg
 */
function _postcalendar_create_regentry($rootcat, $data)
{
    // expecting $rootcat - rootcategory info
    // expecting array(modname=>'', table=>'', property=>'')
    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');

    $registry = new PNCategoryRegistry();
    $registry->setDataField('modname',     $data['modname']);
    $registry->setDataField('table',       $data['table']);
    $registry->setDataField('property',    $data['property']);
    $registry->setDataField('category_id', $rootcat['id']);
    $registry->insert();

    return true;
}

/**
 * register module hooks
 * @author Craig Heydenburg
 */
function _postcalendar_registermodulehooks()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');

    /*
    ($hookobject, $hookaction, $hookarea, $hookmodule, $hooktype, $hookfunc)
    $hookobject = 'item', 'category' or 'module'
    $hookaction = 'new' (GUI), 'create' (API), 'modify' (GUI), 'update' (API), 'delete' (API), 'transform', 'display' (GUI), 'modifyconfig', 'updateconfig'
    $hookarea = 'GUI' or 'API'
    $hookmodule = name of the hook module
    $hooktype = name of the hook type (==admin && (area==API) = function is located in pnadminapi.php)
    $hookfunc = name of the hook function
    */

    if (!pnModRegisterHook('item', 'create', 'API', 'PostCalendar', 'hooks', 'create')) {
        return LogUtil::registerError(__f('PostCalendar: Could not register %s hook.', 'create', $dom));
    }
    if (!pnModRegisterHook('item', 'update', 'API', 'PostCalendar', 'hooks', 'update')) {
        return LogUtil::registerError(__f('PostCalendar: Could not register %s hook.', 'update', $dom));
    }
    if (!pnModRegisterHook('item', 'delete', 'API', 'PostCalendar', 'hooks', 'delete')) {
        return LogUtil::registerError(__f('PostCalendar: Could not register %s hook.', 'delete', $dom));
    }

    // register the module delete hook - function called when hooked modules are uninstalled
    if (!pnModRegisterHook('module', 'remove', 'API', 'PostCalendar', 'hooks', 'deletemodule')) {
        return LogUtil::registerError(__f('PostCalendar: Could not register %s hook.', 'deletemodule', $dom));
    }

    LogUtil::registerStatus(__f('PostCalendar: All hooks registered.', $dom));
    return true;
}
