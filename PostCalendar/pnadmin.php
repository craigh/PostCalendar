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

require_once dirname(__FILE__) . '/global.php';

/**
 * the main administration function
 * This function is the default function, and is called whenever the
 * module is initiated without defining arguments.
 */
function postcalendar_admin_main()
{
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    return postcalendar_admin_modifyconfig();
}

/**
 * @function    postcalendar_admin_modifyconfig
 * @description present administrator options to change module configuration
 * @return      config template
 */
function postcalendar_admin_modifyconfig()
{
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Turn off template caching here
    $pnRender = pnRender::getInstance('PostCalendar', false);

    return $pnRender->fetch('admin/postcalendar_admin_modifyconfig.htm');
}

/**
 * @function    postcalendar_admin_listapproved
 * @description list all events that have been previously approved
 * @return      list of events
 */
function postcalendar_admin_listapproved()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $args = array();
    $args['type']     = _EVENT_APPROVED;
    $args['function'] = 'listapproved';
    $args['title']    = __('Approved Events Administration', $dom);
    return postcalendar_admin_showlist($args);
}

/**
 * @function    postcalendar_admin_listhidden
 * @description list all events that are currently hidden
 * @return      list of events
 */
function postcalendar_admin_listhidden()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $args = array();
    $args['type']     = _EVENT_HIDDEN;
    $args['function'] = 'listhidden';
    $args['title']    = __('Hidden Events Administration', $dom);
    return postcalendar_admin_showlist($args);
}

/**
 * @function    postcalendar_admin_listqueued
 * @description list all events that are awaiting approval
 * @return      list of events
 */
function postcalendar_admin_listqueued()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    $args = array();
    $args['type']     = _EVENT_QUEUED;
    $args['function'] = 'listqueued';
    $args['title']    = __('Queued Events Administration', $dom);
    return postcalendar_admin_showlist($args);
}

/**
 * @function    postcalendar_admin_showlist
 * @description list events as requested/filtered
 *              send list to template
 * @return      showlist template
 */
function postcalendar_admin_showlist($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // $args should be array with keys 'type', 'function', 'title'
    if (!isset($args['type']) or empty($args['function'])) return false; // $title not required, type can be 1, 0, -1

    // Turn off template caching here
    $pnRender = pnRender::getInstance('PostCalendar', false);

    $offset_increment = _SETTING_HOW_MANY_EVENTS;
    if (empty($offset_increment)) $offset_increment = 15;

    $offset = FormUtil::getPassedValue('offset', 0);
    $sort   = FormUtil::getPassedValue('sort', 'time');
    $sdir   = FormUtil::getPassedValue('sdir', 1);
    $original_sdir = $sdir;
    $sdir = $sdir ? 0 : 1; //if true change to false, if false change to true

    $events = pnModAPIFunc('PostCalendar', 'admin', 'getAdminListEvents',
        array('type' => $args['type'], 'sdir' => $sdir, 'sort' => $sort, 'offset' => $offset,
              'offset_increment' => $offset_increment));

    $pnRender->assign('title', $args['title']);
    $pnRender->assign('function', $args['function']);
    $pnRender->assign('events', $events);
    $pnRender->assign('title_sort_url', pnModUrl('PostCalendar', 'admin', $args['function'], array('sort' => 'title', 'sdir' => $sdir)));
    $pnRender->assign('time_sort_url', pnModUrl('PostCalendar', 'admin', $args['function'], array('sort' => 'time', 'sdir' => $sdir)));
    $pnRender->assign('formactions', array(_ADMIN_ACTION_VIEW => __('View', $dom), _ADMIN_ACTION_APPROVE => __('Approve', $dom),
                        _ADMIN_ACTION_HIDE => __('Hide', $dom),
                        _ADMIN_ACTION_DELETE => __('Delete', $dom)));
    $pnRender->assign('actionselected', _ADMIN_ACTION_VIEW);
    if ($offset > 1) {
        $prevlink = pnModUrl('PostCalendar', 'admin', $args['function'], array('offset' => $offset - $offset_increment, 'sort' => $sort, 'sdir' => $original_sdir));
    } else {
        $prevlink = false;
    }
    $pnRender->assign('prevlink', $prevlink);
    if (count($events) >= $offset_increment) {
        $nextlink = pnModUrl('PostCalendar', 'admin', $args['function'], array('offset' => $offset + $offset_increment, 'sort' => $sort, 'sdir' => $original_sdir));
    } else {
        $nextlink = false;
    }
    $pnRender->assign('nextlink', $nextlink);
    $pnRender->assign('offset_increment', $offset_increment);

    return $pnRender->fetch('admin/postcalendar_admin_showlist.htm');
}

/**
 * @function    postcalendar_admin_adminevents
 * @description allows admin to revue selected events then take action
 * @return      adminrevue template
 */
function postcalendar_admin_adminevents()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $action      = FormUtil::getPassedValue('action');
    $events      = FormUtil::getPassedValue('events'); // could be an array or single val
    $thelist     = FormUtil::getPassedValue('thelist');

    if (!isset($events)) {
        LogUtil::registerError(__('Please select an event', $dom));

        // return to where we came from
        switch ($thelist) {
            case 'listqueued':
                return pnModFunc('PostCalendar', 'admin', 'showlist', array('type' => _EVENT_QUEUED,   'function' => 'showlist'));
            case 'listhidden':
                return pnModFunc('PostCalendar', 'admin', 'showlist', array('type' => _EVENT_HIDDEN,   'function' => 'showlist'));
            case 'listapproved':
                return pnModFunc('PostCalendar', 'admin', 'showlist', array('type' => _EVENT_APPROVED, 'function' => 'showlist'));
        }
    }

    $function = '';
    switch ($action) {
        case _ADMIN_ACTION_APPROVE:
            $function = 'approveevents';
            $are_you_sure_text = __('Are you sure you want to approve these events?', $dom);
            break;
        case _ADMIN_ACTION_HIDE:
            $function = 'hideevents';
            $are_you_sure_text = __('Are you sure you want to hide these events?', $dom);
            break;
        case _ADMIN_ACTION_DELETE:
            $function = 'deleteevents';
            $are_you_sure_text = __('Are you sure you want to delete this event?', $dom);
            break;
    }

    // Turn off template caching here
    $pnRender = pnRender::getInstance('PostCalendar', false);
    pnModAPIFunc('PostCalendar','user','SmartySetup', $pnRender);

    $pnRender->assign('function', $function);
    $pnRender->assign('areyousure', $are_you_sure_text);

    if (!is_array($events)) {
        $events = array($events);
    } //create array if not already

    foreach ($events as $eid) {
        // get event info
        $eventitems = pnModAPIFunc('PostCalendar', 'event', 'eventDetail', array('eid' => $eid, 'nopop' => true));
        $alleventinfo[$eid] = $eventitems['A_EVENT'];
    }
    $pnRender->assign('alleventinfo', $alleventinfo);

    return $pnRender->fetch("admin/postcalendar_admin_eventrevue.htm");
}

/**
 * @function    postcalendar_admin_resetDefaults
 * @description reset all module variables to default values as defined in pninit.php
 * @return      status/error ->back to modify config page
 */
function postcalendar_admin_resetDefaults()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $defaults = pnModFunc('PostCalendar', 'init', 'getdefaults');
    if (!count($defaults)) {
        return LogUtil::registerError(__('Error! Could not load default values.', $dom));
    }

    // delete all the old vars
    pnModDelVar('PostCalendar');

    // set the new variables
    pnModSetVars('PostCalendar', $defaults);

    // clear the cache
    pnModAPIFunc('PostCalendar', 'admin', 'clearCache');

    LogUtil::registerStatus(__('Your PostCalendar configuration has been reset to use defaults.', $dom));
    return postcalendar_admin_modifyconfig();
}

/**
 * @function    postcalendar_admin_updateconfig
 * @description sets module variables as requested by admin
 * @return      status/error ->back to modify config page
 */
function postcalendar_admin_updateconfig()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $defaults = pnModFunc('PostCalendar', 'init', 'getdefaults');
    if (!count($defaults)) {
        return LogUtil::registerError(__('Error! Could not load default values.', $dom));
    }

    $settings = array(
    'pcTime24Hours'           => FormUtil::getPassedValue('pcTime24Hours', 0),
    'pcEventsOpenInNewWindow' => FormUtil::getPassedValue('pcEventsOpenInNewWindow', 0),
    'pcFirstDayOfWeek'        => FormUtil::getPassedValue('pcFirstDayOfWeek', $defaults['pcFirstDayOfWeek']),
    'pcUsePopups'             => FormUtil::getPassedValue('pcUsePopups', 0),
    'pcAllowDirectSubmit'     => FormUtil::getPassedValue('pcAllowDirectSubmit', 0),
    'pcListHowManyEvents'     => FormUtil::getPassedValue('pcListHowManyEvents', $defaults['pcListHowManyEvents']),
    'pcDisplayTopics'         => FormUtil::getPassedValue('pcDisplayTopics', 0),
    'pcEventDateFormat'       => FormUtil::getPassedValue('pcEventDateFormat', $defaults['pcEventDateFormat']),
    'pcRepeating'             => FormUtil::getPassedValue('pcRepeating', 0),
    'pcAllowUserCalendar'     => FormUtil::getPassedValue('pcAllowUserCalendar', 0),
    'pcTimeIncrement'         => FormUtil::getPassedValue('pcTimeIncrement', $defaults['pcTimeIncrement']),
    'pcUseCache'              => FormUtil::getPassedValue('pcUseCache', 0),
    'pcCacheLifetime'         => FormUtil::getPassedValue('pcCacheLifetime', $defaults['pcCacheLifetime']),
    'pcDefaultView'           => FormUtil::getPassedValue('pcDefaultView', $defaults['pcDefaultView']),
    'pcNotifyAdmin'           => FormUtil::getPassedValue('pcNotifyAdmin', 0),
    'pcNotifyEmail'           => FormUtil::getPassedValue('pcNotifyEmail', $defaults['pcNotifyEmail']),
    'pcNotifyAdmin2Admin'     => FormUtil::getPassedValue('pcNotifyAdmin2Admin', 0),
    'pcAllowCatFilter'        => FormUtil::getPassedValue('pcAllowCatFilter', 0),
    );

    // delete all the old vars
    pnModDelVar('PostCalendar');

    // set the new variables
    pnModSetVars('PostCalendar', $settings);

    // clear the cache
    pnModAPIFunc('PostCalendar', 'admin', 'clearCache');

    LogUtil::registerStatus(__('Your PostCalendar configuration has been updated.', $dom));
    return postcalendar_admin_modifyconfig();
}

/**
 * @decrecated
 * @function    postcalendar_admin_categories
 * @description display list of PostCalendar categories
 * @return      categories template
 */
function postcalendar_admin_categories()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Turn off template caching here
    $pnRender = pnRender::getInstance('PostCalendar', false);

    $cats = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
    $pnRender->assign('cats', $cats);

    return $pnRender->fetch('admin/postcalendar_admin_categories.htm');
}

/**
 * @decrecated
 * @function    postcalendar_admin_categoriesConfirm
 * @description present review of changes to categories
 * @return      categoriesconfirm template
 */
function postcalendar_admin_categoriesConfirm()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Turn off template caching here
    $pnRender = pnRender::getInstance('PostCalendar', false);

    $id       = FormUtil::getPassedValue('id');
    $del      = FormUtil::getPassedValue('del');
    $name     = FormUtil::getPassedValue('name');
    $desc     = FormUtil::getPassedValue('desc');
    $color    = FormUtil::getPassedValue('color');
    $newname  = FormUtil::getPassedValue('newname');
    $newdesc  = FormUtil::getPassedValue('newdesc');
    $newcolor = FormUtil::getPassedValue('newcolor');

    if (is_array($del)) {
        $dels = implode(',', $del);
        $pnRender->assign('delText', __('Delete Categories?', $dom) . $dels . '.');
        $pnRender->assign('dels', $dels);
    }
    $pnRender->assign('id', serialize($id));
    if (!empty($del)) $pnRender->assign('del', serialize($del));
    $pnRender->assign('name', serialize($name));
    $pnRender->assign('desc', serialize($desc));
    $pnRender->assign('color', serialize($color));
    $pnRender->assign('newname', $newname);
    $pnRender->assign('newdesc', $newdesc);
    $pnRender->assign('newcolor', $newcolor);

    return $pnRender->fetch('admin/postcalendar_admin_categoriesconfirm.htm');
}

/**
 * @decrecated
 * @function    postcalendar_admin_categoriesUpdate
 * @description modify categories (add/delete/edit)
 * @return      status/error -> return to categories display
 */
function postcalendar_admin_categoriesUpdate()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $id       = FormUtil::getPassedValue('id');
    $del      = FormUtil::getPassedValue('del');
    $dels     = FormUtil::getPassedValue('dels');
    $name     = FormUtil::getPassedValue('name');
    $desc     = FormUtil::getPassedValue('desc');
    $color    = FormUtil::getPassedValue('color');
    $newname  = FormUtil::getPassedValue('newname');
    $newdesc  = FormUtil::getPassedValue('newdesc');
    $newcolor = FormUtil::getPassedValue('newcolor');

    $id    = unserialize($id);
    $del   = unserialize($del);
    $name  = unserialize($name);
    $desc  = unserialize($desc);
    $color = unserialize($color);

    $modID = $modName = $modDesc = $modColor = array();

    //determine categories to update (not the ones to delete)
    if (isset($id)) {
        foreach ($id as $k => $i) {
            $found = false;
            if (count($del)) {
                foreach ($del as $d) {
                    if ($i == $d) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                array_push($modID, $i);
                array_push($modName, $name[$k]);
                array_push($modDesc, $desc[$k]);
                array_push($modColor, $color[$k]);
            }
        }
    }

    //update categories
    $obj = array();
    foreach ($modID as $k => $id) {
        $obj['catid'] = $id;
        $obj['catname'] = $modName[$k];
        $obj['catdesc'] = $modDesc[$k];
        $obj['catcolor'] = $modColor[$k];
        $res = DBUtil::updateObject($obj, 'postcalendar_categories', '', 'catid');
        if (!$res) {
            LogUtil::registerError(__('Categories update failed.', $dom));
            $action_status = false;
        }
    }

    // delete categories
    if (isset($dels) && $dels) {
        $res = DBUtil::deleteObjectsFromKeyArray(array_flip($del), 'postcalendar_categories', 'catid');
        if (!$res) {
            LogUtil::registerError(__('Category delete failed.', $dom));
            $action_status = false;
        }
    }

    // add category
    if (isset($newname)) {
        $obj['catid'] = '';
        $obj['catname'] = $newname;
        $obj['catdesc'] = $newdesc;
        $obj['catcolor'] = $newcolor;
        $res = DBUtil::insertObject($obj, 'postcalendar_categories', false, 'catid');
        if (!$res) {
            LogUtil::registerError(__('New category creation failed.', $dom));
            $action_status = false;
        }
    }

    if ($action_status) LogUtil::registerStatus(__('Category modified as requested.', $dom)); // category updated/deleted/added
    return postcalendar_admin_categories();
}

/**
 * @function    postcalendar_admin_manualClearCache
 * @description clear pnRender Cache
 * @return      status/error -> return to admin config
 */
function postcalendar_admin_manualClearCache()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    $clear = pnModAPIFunc('PostCalendar', 'admin', 'clearCache');
    if ($clear) {
        LogUtil::registerStatus(__('Smarty Cache has been cleared', $dom));
        return postcalendar_admin_modifyconfig();
    }
    return LogUtil::registerError(__('Smarty Cache was not cleared', $dom), null, pnModURL('PostCalendar', 'admin', 'modifyconfig'));
}

/**
 * @function    postcalendar_admin_testSystem
 * @description show list of information to admin regarding server environment
 * @return      status/error -> return to admin config
 */
function postcalendar_admin_testSystem()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $modinfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    $version = $modinfo['version'];
    unset($modinfo);

    // Turn off template caching here
    $tpl = pnRender::getInstance('PostCalendar', false);
    $infos = array();

    if (phpversion() >= '4.1.0') {
        $__SERVER = $_SERVER;
        $__ENV    = $_ENV;
    } else {
        global $HTTP_SERVER_VARS, $HTTP_ENV_VARS;
        $__SERVER = $HTTP_SERVER_VARS;
        $__ENV    = $HTTP_ENV_VARS;
    }

    if (defined('_PN_VERSION_NUM')) {
        $pnVersion = _PN_VERSION_NUM;
    } else {
        $pnVersion = pnConfigGetVar('Version_Num');
    }

    array_push($infos, array(__('Zikula Version', $dom), $pnVersion));
    array_push($infos, array(__('Sitename', $dom), pnConfigGetVar('sitename')));
    array_push($infos, array(__('URL', $dom), pnGetBaseURL()));
    array_push($infos, array(__('PHP Version', $dom), phpversion()));
    if ((bool) ini_get('safe_mode')) {
        $safe_mode = __('ON', $dom);
    } else {
        $safe_mode = __('OFF', $dom);
    }
    array_push($infos, array('PHP safe_mode', $safe_mode));
    if ((bool) ini_get('safe_mode_gid')) {
        $safe_mode_gid = __('ON', $dom);
    } else {
        $safe_mode_gid = __('OFF', $dom);
    }
    array_push($infos, array('PHP safe_mode_gid', $safe_mode_gid));
    $base_dir = ini_get('open_basedir');
    if (!empty($base_dir)) {
        $open_basedir = "$base_dir";
    } else {
        $open_basedir = "NULL";
    }
    array_push($infos, array(__('PHP open_basedir', $dom), $open_basedir));
    array_push($infos, array('SAPI', php_sapi_name()));
    array_push($infos, array('OS', php_uname()));
    array_push($infos, array(__('WebServer', $dom), $__SERVER['SERVER_SOFTWARE']));
    array_push($infos, array(__('Module dir', $dom), dirname(__FILE__)));

    $modversion = array();
    include dirname(__FILE__) . '/pnversion.php';

    if ($modversion['version'] != $version) {
        LogUtil::registerError(__('New version %s installed but not updated!', $modversion[version], $dom));
    }
    array_push($infos, array(__('Module version', $dom), $version));
    array_push($infos, array(__('Smarty version', $dom), $tpl->_version));
    array_push($infos, array(__('Smarty location', $dom), SMARTY_DIR));
    array_push($infos, array(__('Smarty template dir', $dom), $tpl->template_dir));

    $info = $tpl->compile_dir;
    if (!file_exists($tpl->compile_dir)) {
        LogUtil::registerError(__('Compile dir doesn\'t exist! [%s]', $tpl->compile_dir, $dom));
    } else {
        // dir exists -> check if it's writeable
        if (!is_writeable($tpl->compile_dir)) {
            LogUtil::registerError(__('Compile dir not writeable! [%s]', $tpl->compile_dir, $dom));
        }
    }
    array_push($infos, array(__('Smarty compile dir', $dom), $tpl->compile_dir));

    $info = $tpl->cache_dir;
    if (!file_exists($tpl->cache_dir)) {
        LogUtil::registerError(__('Cache dir doesn\'t exist! [%s]', $tpl->cache_dir, $dom));
    } else {
        // dir exists -> check if it's writeable
        if (!is_writeable($tpl->cache_dir)) {
            LogUtil::registerError(__('Cache dir not writeable! [%s]', $tpl->cache_dir, $dom));
        }
    }
    array_push($infos, array(__('Smarty cache dir', $dom), $tpl->cache_dir));

    $tpl->assign('infos', $infos);
    return $tpl->fetch('admin/postcalendar_admin_systeminfo.htm');
}
/*
 * postcalendar_admin_approveevents
 * update status of events so that they are viewable by users
 *
 */
function postcalendar_admin_approveevents()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    $pc_eid = FormUtil::getPassedValue('pc_eid');
    if (!is_array($pc_eid)) return __('There was an error while processing your request.', $dom);

    // structure array for DB interaction
    $eventarray = array();
    foreach ($pc_eid as $eid) {
        $eventarray[$eid] = array('eid' => $eid, 'eventstatus' => _EVENT_APPROVED);
    }

    // update the DB
    $res = pnModAPIFunc('PostCalendar', 'event', 'update', $eventarray);
    if ($res) {
        LogUtil::registerStatus(__('The event(s) have been approved.', $dom));
    } else {
        LogUtil::registerError(__('There was an error while processing your request.', $dom));
    }

    pnModAPIFunc('PostCalendar', 'admin', 'clearCache');
    return pnModFunc('PostCalendar', 'admin', 'showlist', array('type' => _EVENT_APPROVED, 'function' => 'listapproved', 'title' => __('Approved Events Administration', $dom)));
}

/*
 * postcalendar_admin_hideevents
 * update status of events so that they are hidden from view
 *
 */
function postcalendar_admin_hideevents()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $pc_eid = FormUtil::getPassedValue('pc_eid');
    if (!is_array($pc_eid)) return __('There was an error while processing your request.', $dom);

    // structure array for DB interaction
    $eventarray = array();
    foreach ($pc_eid as $eid) {
        $eventarray[$eid] = array('eid' => $eid, 'eventstatus' => _EVENT_HIDDEN);
    }

    // update the DB
    $res = pnModAPIFunc('PostCalendar', 'event', 'update', $eventarray);
    if ($res) {
        LogUtil::registerStatus(__('The event(s) have been hidden.', $dom));
    } else {
        LogUtil::registerError(__('There was an error while processing your request.', $dom));
    }

    pnModAPIFunc('PostCalendar', 'admin', 'clearCache');
    return pnModFunc('PostCalendar', 'admin', 'showlist', array('type' => _EVENT_APPROVED, 'function' => 'listapproved', 'title' => __('Approved Events Administration', $dom)));
}

/*
 * postcalendar_admin_deleteevents
 * delete array of events
 *
 */
function postcalendar_admin_deleteevents()
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    $pc_eid = FormUtil::getPassedValue('pc_eid');
    if (!is_array($pc_eid)) return __('There was an error while processing your request.', $dom);

    // structure array for DB interaction
    $eventarray = array();
    foreach ($pc_eid as $eid) {
        $eventarray[$eid] = $eid;
    }

    // update the DB
    $res = pnModAPIFunc('PostCalendar', 'event', 'deleteeventarray', $eventarray);
    if ($res) {
        LogUtil::registerStatus(__('Your event has been deleted.', $dom));
    } else {
        LogUtil::registerError(__('There was an error while processing your request.', $dom));
    }

    pnModAPIFunc('PostCalendar', 'admin', 'clearCache');
    return pnModFunc('PostCalendar', 'admin', 'showlist', array('type' => _EVENT_APPROVED, 'function' => 'listapproved', 'title' => __('Approved Events Administration', $dom)));
}