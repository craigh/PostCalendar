<?php
/**
 * SVN: $Id$
 *
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Revision$
 *
 * PostCalendar::Zikula Events Calendar Module
 * Copyright (C) 2002  The PostCalendar Team
 * http://postcalendar.tv
 * Copyright (C) 2009  Sound Web Development
 * Craig Heydenburg
 * http://code.zikula.org/soundwebdevelopment/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * To read the license please read the docs/license.txt or visit
 * http://www.gnu.org/copyleft/gpl.html
 *
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
 * @author  Roger Raymond <iansym@yahoo.com>
 * @copyright    The PostCalendar Team 2002
 */
function postcalendar_init()
{
    // create tables
    if (!DBUtil::createTable('postcalendar_events') || !DBUtil::createTable('postcalendar_categories')) {
        return LogUtil::registerError(_CREATETABLEFAILED);
    }

    // insert default category
    $defaultcat = array('catname' => _PC_DEFAUT_CATEGORY_NAME, 'catdesc' => _PC_DEFAUT_CATEGORY_DESCR);
    if (!DBUtil::insertObject($defaultcat, 'postcalendar_categories', 'catid')) {
        return LogUtil::registerError(_CREATEFAILED);
    }

    // PostCalendar Default Settings
    $defaultsettings = postcalendar_init_getdefaults();
    $result = pnModSetVars('PostCalendar', $defaultsettings);
    if (!$result) {
        return LogUtil::registerError(_CREATEFAILED);
    }

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
 * @author  Roger Raymond <iansym@yahoo.com>
 * @copyright    The PostCalendar Team 2002
 */
function postcalendar_upgrade($oldversion)
{
    // We only support upgrade from version 4 and up. Notify users if they have a version below that one.
    if (version_compare($oldversion, '4', '<'))
    {
        $modversion = array('version' => 'unknown');
        // Receive the current version information, where $modversion will be overwritten
        require dirname(__FILE__) . '/pnversion.php';

        // Inform user about error, and how he can upgrade to $modversion['version']
        return LogUtil::registerError(pnML('_PC_VERSIONTOOOLD', $modversion, true));
    }

    // change the database. DBUtil + ADODB detect the changes on their own
    // and perform all necessary steps without help from the module author
    if (!DBUtil::changeTable('postcalendar_events') || !DBUtil::changeTable('postcalendar_categories')) {
        return LogUtil::registerError(_PC_UPGRADETABLESFAILED);
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
    }

    // if we get this far - clear the cache
    pnRender::getInstance('PostCalendar')->clear_all_cache();

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
    'pcFirstDayOfWeek'        => _DATEFIRSTWEEKDAY,
    'pcDayHighlightColor'     => '#FF0000',
    'pcUsePopups'             => '1',
    'pcAllowDirectSubmit'     => '0',
    'pcListHowManyEvents'     => '15',
    'pcDisplayTopics'         => '0',
    'pcEventDateFormat'       => _DATEBRIEF,
    'pcRepeating'             => '0',
    'pcMeeting'               => '0',
    'pcAddressbook'           => pnModAvailable('v4bAddressBook') ? '1' : '0',
    'pcAllowSiteWide'         => '0',
    'pcAllowUserCalendar'     => '1',
    'pcTimeIncrement'         => '15',
    'pcDefaultView'           => 'month',
    'pcUseCache'              => '1',
    'pcCacheLifetime'         => '3600',
    'pcNotifyAdmin'           => '0',
    'pcNotifyEmail'           => pnConfigGetVar('adminmail'),
    'pcNotifyAdmin2Admin'     => '0',
    );

    return $defaults;
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
 * @author  Roger Raymond <iansym@yahoo.com>
 * @copyright    The PostCalendar Team 2002
 */
function postcalendar_delete()
{
    $result = DBUtil::dropTable('postcalendar_events');
    $result = $result && DBUtil::dropTable('postcalendar_categories');
    $result = $result && pnModDelVar('PostCalendar');

    return $result;
}
