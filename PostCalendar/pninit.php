<?php
/**
 *	SVN: $Id$
 *
 *  @package     PostCalendar
 *  @author      $Author$
 *  @link	     $HeadURL$
 *  @version     $Revision$
 *
 *  PostCalendar::Zikula Events Calendar Module
 *  Copyright (C) 2002  The PostCalendar Team
 *  http://postcalendar.tv
 *  Copyright (C) 2009  Sound Web Development
 *  Craig Heydenburg
 *  http://code.zikula.org/soundwebdevelopment/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *  To read the license please read the docs/license.txt or visit
 *  http://www.gnu.org/copyleft/gpl.html
 *
 */

/**
 *	Initializes a new install of PostCalendar
 *
 *	This function will initialize a new installation of PostCalendar.
 *	It is accessed via the Zikula Admin interface and should
 *	not be called directly.
 *
 *	@return  boolean	true/false
 *	@access  public
 *	@author  Roger Raymond <iansym@yahoo.com>
 *	@copyright	The PostCalendar Team 2002
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
    pnModSetVar('PostCalendar', 'pcTime24Hours', '0');
    pnModSetVar('PostCalendar', 'pcEventsOpenInNewWindow', '0');
    pnModSetVar('PostCalendar', 'pcUseInternationalDates', '0');
    pnModSetVar('PostCalendar', 'pcFirstDayOfWeek', '0');
    pnModSetVar('PostCalendar', 'pcDayHighlightColor', '#FF0000');
    pnModSetVar('PostCalendar', 'pcUsePopups', '1');
    pnModSetVar('PostCalendar', 'pcDisplayTopics', '0');
    pnModSetVar('PostCalendar', 'pcAllowDirectSubmit', '0');
    pnModSetVar('PostCalendar', 'pcListHowManyEvents', '15');
    pnModSetVar('PostCalendar', 'pcTimeIncrement', '15');
    pnModSetVar('PostCalendar', 'pcAllowSiteWide', '0');
    pnModSetVar('PostCalendar', 'pcAllowUserCalendar', '1');
    pnModSetVar('PostCalendar', 'pcEventDateFormat', '%Y-%m-%d');
    pnModSetVar('PostCalendar', 'pcUseCache', '1');
    pnModSetVar('PostCalendar', 'pcCacheLifetime', '3600');
    pnModSetVar('PostCalendar', 'pcDefaultView', 'month');
    pnModSetVar('PostCalendar', 'pcNotifyAdmin', '0');
    pnModSetVar('PostCalendar', 'pcNotifyEmail', pnConfigGetVar('adminmail'));
    pnModSetVar('PostCalendar', 'pcRepeating', '0');
    pnModSetVar('PostCalendar', 'pcMeeting', '0');
    pnModSetVar('PostCalendar', 'pcAddressbook', '1');
    return true;
}

/**
 *	Upgrades an old install of PostCalendar
 *
 *	This function is used to upgrade an old version
 *	of PostCalendar.  It is accessed via the Zikula
 *	Admin interface and should not be called directly.
 *
 *	@return boolean	true/false
 *	@param  string	$oldversion Version we're upgrading
 *	@access  public
 *	@author  Roger Raymond <iansym@yahoo.com>
 *	@copyright	The PostCalendar Team 2002
 */
function postcalendar_upgrade($oldversion)
{
    // note: the comment below is no longer tru - CAH  13 Apr 2009
    /**
     *	Until Zikula fixes the bugs
     *	with the module upgrade we are
     *	going to have to do it ourselves.
     *
     *	Please do not use the Modules admin
     *	to upgrade PostCalendar.  Use the
     *	link provided in the PostCalendar
     *	Admin section.
     */
    //$pcModInfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    //$pcDir = pnVarPrepForOS($pcModInfo['directory']);


    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $events_table = $pntable['postcalendar_events'];
    $cat_table = $pntable['postcalendar_categories'];

    switch ($oldversion) {

        case '3.0':
        case '3.01':
        case '3.02':
        case '3.03':
        case '3.04':

            // we need the Date_Calc class
            require_once ("modules/$pcDir/DateCalc.php");

            // Update PostCalendar Variables
            pnModSetVar('PostCalendar', 'pcTime24Hours', pnModGetVar('PostCalendar', 'time24hours'));
            pnModSetVar('PostCalendar', 'pcEventsOpenInNewWindow', pnModGetVar('PostCalendar', 'eventsopeninnewwindow'));
            pnModSetVar('PostCalendar', 'pcUseInternationalDates', pnModGetVar('PostCalendar', 'useinternationaldates'));
            pnModSetVar('PostCalendar', 'pcFirstDayOfWeek', pnModGetVar('PostCalendar', 'firstdayofweek'));
            pnModSetVar('PostCalendar', 'pcDayHighlightColor', pnModGetVar('PostCalendar', 'dayhighlightcolor'));
            pnModSetVar('PostCalendar', 'pcUsePopups', pnModGetVar('PostCalendar', 'usepopups'));
            pnModSetVar('PostCalendar', 'pcDisplayTopics', pnModGetVar('PostCalendar', 'displaytopics'));
            pnModSetVar('PostCalendar', 'pcAllowDirectSubmit', '0');
            pnModSetVar('PostCalendar', 'pcListHowManyEvents', pnModGetVar('PostCalendar', 'listhowmanyevents'));
            pnModSetVar('PostCalendar', 'pcTimeIncrement', '15');
            pnModSetVar('PostCalendar', 'pcAllowSiteWide', '0');
            pnModSetVar('PostCalendar', 'pcAllowUserCalendar', '1');
            pnModSetVar('PostCalendar', 'pcEventDateFormat', '%Y-%m-%d');
            //			pnModSetVar('PostCalendar', 'pcTemplate', 'default');
            pnModSetVar('PostCalendar', 'pcUseCache', '1');
            pnModSetVar('PostCalendar', 'pcCacheLifetime', '3600');
            pnModSetVar('PostCalendar', 'pcDefaultView', 'month');
            pnModSetVar('PostCalendar', 'pcSafeMode', '0');
            // v4b TS start
            pnModSetVar('PostCalendar', 'pcRepeating', '0');
            pnModSetVar('PostCalendar', 'pcMeeting', '0');
            pnModSetVar('PostCalendar', 'pcAddressbook', '1');

            // alter the events table and change some old columns
            $sql = "ALTER TABLE $events_table
                    ADD pc_catid int(11) default '0' NOT NULL,
					ADD pc_duration bigint(20) default '0' NOT NULL,
                    ADD pc_sharing int(11) default '0' NOT NULL,
                    ADD pc_language varchar(30) default '',
                    ADD pc_meeting_id int(11) NULL default 0,
					CHANGE pc_eid pc_eid int(11) unsigned NOT NULL auto_increment,
                    CHANGE pc_location pc_location text,
                    CHANGE pc_conttel pc_conttel varchar(50),
                    CHANGE pc_contname pc_contname varchar(150),
                    CHANGE pc_contemail pc_contemail varchar(255),
                    CHANGE pc_website pc_website varchar(255),
                    CHANGE pc_fee pc_fee varchar(50),
                    CHANGE pc_recurrspec pc_recurrspec text default ''
                    ";

            $dbconn->Execute($sql);
            if ($dbconn->ErrorNo() != 0) {
                die('event table alter error : ' . $dbconn->ErrorMsg());
                return false;
            }
            // v4b TS end

            // create the new categories table
            $sql = "CREATE TABLE $cat_table (
                    pc_catid int(11) unsigned NOT NULL auto_increment,
                    pc_catname varchar(100) NOT NULL default 'Undefined',
                    pc_catcolor varchar(50) NOT NULL default '#FF0000',
                    pc_catdesc text default '',
                    PRIMARY KEY(pc_catid)
                    )";
            $dbconn->Execute($sql);
            if ($dbconn->ErrorNo() != 0) {
                die('cat table create error : ' . $dbconn->ErrorMsg());
                return false;
            }

            // insert the current hardcoded categories into the new categories table
            $category1 = pnVarPrepForStore(
                pnModGetVar('PostCalendar', 'category1'));
            $category2 = pnVarPrepForStore(pnModGetVar('PostCalendar', 'category2'));
            $category3 = pnVarPrepForStore(pnModGetVar('PostCalendar', 'category3'));
            $category4 = pnVarPrepForStore(pnModGetVar('PostCalendar', 'category4'));
            $category5 = pnVarPrepForStore(pnModGetVar('PostCalendar', 'category5'));

            $inserts = array(
                            "INSERT INTO $cat_table (pc_catid,pc_catname,pc_catcolor) VALUES ('1','$category1','#ff0000')",
                            "INSERT INTO $cat_table (pc_catid,pc_catname,pc_catcolor) VALUES ('2','$category2','#00ff00')",
                            "INSERT INTO $cat_table (pc_catid,pc_catname,pc_catcolor) VALUES ('3','$category3','#0000ff')",
                            "INSERT INTO $cat_table (pc_catid,pc_catname,pc_catcolor) VALUES ('4','$category4','#ffffff')",
                            "INSERT INTO $cat_table (pc_catid,pc_catname,pc_catcolor) VALUES ('5','$category5','#ffcc00')");

            foreach ($inserts as $insert) {
                $dbconn->Execute($insert);
                if ($dbconn->ErrorNo() != 0) {
                    die(
                        'cat table insert error : ' . $dbconn->ErrorMsg());
                    return false;
                }
            }

            // update the current events to reflect the category system change
            $updates = array(
                            "UPDATE $events_table SET pc_catid = 1 WHERE pc_barcolor = 'r' ",
                            "UPDATE $events_table SET pc_catid = 2 WHERE pc_barcolor = 'g' ",
                            "UPDATE $events_table SET pc_catid = 3 WHERE pc_barcolor = 'b' ",
                            "UPDATE $events_table SET pc_catid = 4 WHERE pc_barcolor = 'w' ",
                            "UPDATE $events_table SET pc_catid = 5 WHERE pc_barcolor = 'y' ");

            foreach ($updates as $update) {
                $dbconn->Execute($update);
                if ($dbconn->ErrorNo() != 0) {
                    die(
                        'event table update error : ' . $dbconn->ErrorMsg());
                    return false;
                }
            }

            // alter the events table and drop the old barcolor column
            $sql = "ALTER TABLE $events_table DROP pc_barcolor";
            $dbconn->Execute($sql);
            if ($dbconn->ErrorNo() != 0) {
                die('cat table alter error : ' . $dbconn->ErrorMsg());
                return false;
            }

            // remove the old vars as they are no longer needed
            pnModDelVar('PostCalendar',
                'category1');
            pnModDelVar('PostCalendar', 'category2');
            pnModDelVar('PostCalendar', 'category3');
            pnModDelVar('PostCalendar', 'category4');
            pnModDelVar('PostCalendar', 'category5');
            pnModDelVar('PostCalendar', 'time24hours');
            pnModDelVar('PostCalendar', 'eventsopeninnewwindow');
            pnModDelVar('PostCalendar', 'useinternationaldates');
            pnModDelVar('PostCalendar', 'firstdayofweek');
            pnModDelVar('PostCalendar', 'dayhighlightcolor');
            pnModDelVar('PostCalendar', 'displaytopics');
            pnModDelVar('PostCalendar', 'usepopups');
            pnModDelVar('PostCalendar', 'listhowmanyevents');
            pnModDelVar('PostCalendar', 'allowdirectsubmit');
            pnModDelVar('PostCalendar', 'showeventsinyear');

            //======================================================
            //  now, ideally, we will convert old events to the new
            //  style. this consists of reconfiguring the repeating
            //  events vars.
            //
            //  we need to establish the current repeating
            //  conditions and convert them to the new system
            //======================================================
            //  old repeating defines
            //======================================================
            @define('_EVENT_NONE',      -1);
            @define('_EVENT_DAILY',      0);
            @define('_EVENT_WEEKLY',     1);
            @define('_EVENT_MONTHLY',    2);
            @define('_EVENT_YEARLY',     3);
            @define('_RECUR_SAME_DAY',   0);
            @define('_RECUR_SAME_DATE',  1);
            //======================================================
            //  new repeating defines
            //  $recurrspec['event_repeat']
            //======================================================
            @define('NO_REPEAT',    0);
            @define('REPEAT',       1);
            @define('REPEAT_ON',    2);
            //======================================================
            //  $recurrspec['event_repeat_freq']
            //======================================================
            @define('REPEAT_EVERY',         1);
            @define('REPEAT_EVERY_OTHER',   2);
            @define('REPEAT_EVERY_THIRD',   3);
            @define('REPEAT_EVERY_FOURTH',  4);
            //======================================================
            //  $recurrspec['event_repeat_freq_type']
            //======================================================
            @define('REPEAT_EVERY_DAY',     0);
            @define('REPEAT_EVERY_WEEK',    1);
            @define('REPEAT_EVERY_MONTH',   2);
            @define('REPEAT_EVERY_YEAR',    3);
            //======================================================
            //  $recurrspec['event_repeat_on_num']
            //======================================================
            @define('REPEAT_ON_1ST',    1);
            @define('REPEAT_ON_2ND',    2);
            @define('REPEAT_ON_3RD',    3);
            @define('REPEAT_ON_4TH',    4);
            @define('REPEAT_ON_LAST',   5);
            //======================================================
            //  $recurrspec['event_repeat_on_day']
            //======================================================
            @define('REPEAT_ON_SUN',    0);
            @define('REPEAT_ON_MON',    1);
            @define('REPEAT_ON_TUE',    2);
            @define('REPEAT_ON_WED',    3);
            @define('REPEAT_ON_THU',    4);
            @define('REPEAT_ON_FRI',    5);
            @define('REPEAT_ON_SAT',    6);
            //======================================================
            //  $recurrspec['event_repeat_on_freq']
            //======================================================
            @define('REPEAT_ON_MONTH',  1);
            @define('REPEAT_ON_2MONTH', 2);
            @define('REPEAT_ON_3MONTH', 3);
            @define('REPEAT_ON_4MONTH', 4);
            @define('REPEAT_ON_6MONTH', 6);
            @define('REPEAT_ON_YEAR',   12);
            //======================================================
            //  Set Sharing Paramaters
            //======================================================
            @define('SHARING_PRIVATE',  0);
            @define('SHARING_PUBLIC',   1);
            @define('SHARING_BUSY',     2);
            @define('SHARING_GLOBAL',   3);
            //======================================================
            //  Here's some psuedo-code for the conversion
            //
            //  if _EVENT_NONE
            //      $rtype = NO_REPEAT
            //      $rspec = 0 for all;
            //      $duration = endTime - startTime
            //
            //  if _EVENT_DAILY
            //      $rtype = REPEAT
            //      $rspec = REPEAT_EVERY|REPEAT_EVERY_DAY
            //      $duration = endTime - startTime
            //
            //  if _EVENT_WEEKLY
            //      $rtype = REPEAT
            //      $rspec = REPEAT_EVERY|REPEAT_EVERY_WEEK
            //      $duration = endTime - startTime
            //
            //  if _EVENT_MONTHLY
            //      if _RECUR_SAME_DAY
            //          $rtype = REPEAT_ON
            //          $rspec = REPEAT_ON_NUM|REPEAT_ON_DAY|REPEAT_ON_FREQ
            //      if _RECUR_SAME_DATE
            //          $rtype = REPEAT
            //          $rspec = REPEAT_EVERY|REPEAT_EVERY_MONTH
            //      $duration = endTime - startTime
            //
            //  if _EVENT_YEARLY
            //      if _RECUR_SAME_DAY
            //          $rtype = REPEAT_ON
            //          $rspec = REPEAT_ON_NUM|REPEAT_ON_DAY|REPEAT_ON_FREQ
            //      if _RECUR_SAME_DATE
            //          $rtype = REPEAT
            //          $rspec = REPEAT_EVERY|REPEAT_EVERY_YEAR
            //      $duration = endTime - startTime
            //======================================================
            //  attempt reconfiguration
            //======================================================
            $sql = "SELECT pc_eid, pc_eventDate, pc_startTime, pc_endTime, pc_recurrtype, pc_recurrfreq
                    FROM $events_table";
            $result = $dbconn->Execute($sql);
            if ($dbconn->ErrorNo() != 0) {
                die($dbconn->ErrorMsg());
                return false;
            }
            if (!isset($result)) return false;
            // grab the results and start the conversion
            for (; !$result->EOF; $result->MoveNext()) {
                $recurrspec = array();
                list($eid, $eventdate, $start, $end, $rtype, $rfreq) = $result->fields;

                if ($rtype == null) $rtype = _EVENT_NONE;
                switch ($rtype) {

                    case _EVENT_NONE:
                        $recurrtype = NO_REPEAT;
                        $recurrspec['event_repeat_freq'] = 0;
                        $recurrspec['event_repeat_freq_type'] = 0;
                        $recurrspec['event_repeat_on_num'] = 0;
                        $recurrspec['event_repeat_on_day'] = 0;
                        $recurrspec['event_repeat_on_freq'] = 0;
                        break;

                    case _EVENT_DAILY:
                        $recurrtype = REPEAT;
                        $recurrspec['event_repeat_freq'] = REPEAT_EVERY;
                        $recurrspec['event_repeat_freq_type'] = REPEAT_EVERY_DAY;
                        $recurrspec['event_repeat_on_num'] = 0;
                        $recurrspec['event_repeat_on_day'] = 0;
                        $recurrspec['event_repeat_on_freq'] = 0;
                        break;

                    case _EVENT_WEEKLY:
                        $recurrtype = REPEAT;
                        $recurrspec['event_repeat_freq'] = REPEAT_EVERY;
                        $recurrspec['event_repeat_freq_type'] = REPEAT_EVERY_WEEK;
                        $recurrspec['event_repeat_on_num'] = 0;
                        $recurrspec['event_repeat_on_day'] = 0;
                        $recurrspec['event_repeat_on_freq'] = 0;
                        break;

                    case _EVENT_MONTHLY:
                        if ($rfreq == _RECUR_SAME_DATE) {
                            $recurrtype = REPEAT;
                            $recurrspec['event_repeat_freq'] = REPEAT_EVERY;
                            $recurrspec['event_repeat_freq_type'] = REPEAT_EVERY_MONTH;
                            $recurrspec['event_repeat_on_num'] = 0;
                            $recurrspec['event_repeat_on_day'] = 0;
                            $recurrspec['event_repeat_on_freq'] = 0;
                        } elseif ($rfreq == _RECUR_SAME_DAY) {
                            $recurrtype = REPEAT_ON;
                            list($y, $m, $d) = explode(
                                '-',
                                $eventdate);
                            $recurrspec['event_repeat_freq'] = 0;
                            $recurrspec['event_repeat_freq_type'] = 0;
                            // event day of week
                            $edow = Date_Calc::dayOfWeek(
                                $d,
                                $m,
                                $y);
                            // date of first event day of week
                            $firstDay = Date_Calc::NWeekdayOfMonth(
                                1,
                                $edow,
                                $m,
                                $y,
                                '%Y-%m-%d');
                            // find difference between 1st day and event day
                            list($y2, $m2, $d2) = explode(
                                '-',
                                $firstDay);
                            $diff = Date_Calc::dateDiff(
                                $d,
                                $m,
                                $y,
                                $d2,
                                $m2,
                                $y2);
                            // assuming $diff is going to be a multiple of 7
                            if ($diff > 0) {
                                $diff /= 7;
                            }
                            if ($diff > REPEAT_ON_4TH) {
                                $diff = REPEAT_ON_LAST;
                            }
                            $recurrspec['event_repeat_on_num'] = $diff;
                            $recurrspec['event_repeat_on_day'] = $edow;
                            $recurrspec['event_repeat_on_freq'] = REPEAT_ON_MONTH;
                        }
                        break;

                    case _EVENT_YEARLY:
                        if ($rfreq == _RECUR_SAME_DATE) {
                            $recurrtype = REPEAT;
                            $recurrspec['event_repeat_freq'] = REPEAT_EVERY;
                            $recurrspec['event_repeat_freq_type'] = REPEAT_EVERY_YEAR;
                            $recurrspec['event_repeat_on_num'] = 0;
                            $recurrspec['event_repeat_on_day'] = 0;
                            $recurrspec['event_repeat_on_freq'] = 0;
                        } elseif ($rfreq == _RECUR_SAME_DAY) {
                            $recurrtype = REPEAT_ON;
                            list($y, $m, $d) = explode(
                                '-',
                                $eventdate);
                            $recurrspec['event_repeat_freq'] = 0;
                            $recurrspec['event_repeat_freq_type'] = 0;
                            // event day of week
                            $edow = Date_Calc::dayOfWeek(
                                $d,
                                $m,
                                $y);
                            // date of first event day of week
                            $firstDay = Date_Calc::NWeekdayOfMonth(
                                1,
                                $edow,
                                $m,
                                $y,
                                '%Y-%m-%d');
                            // find difference between 1st day and event day
                            list($y2, $m2, $d2) = explode(
                                '-',
                                $firstDay);
                            $diff = Date_Calc::dateDiff(
                                $d,
                                $m,
                                $y,
                                $d2,
                                $m2,
                                $y2);
                            // assuming $diff is going to be a multiple of 7
                            if ($diff > 0) {
                                $diff /= 7;
                            }
                            if ($diff > REPEAT_ON_4TH) {
                                $diff = REPEAT_ON_LAST;
                            }
                            $recurrspec['event_repeat_on_num'] = $diff;
                            $recurrspec['event_repeat_on_day'] = $edow;
                            $recurrspec['event_repeat_on_freq'] = REPEAT_ON_YEAR;
                        }
                        break;
                }
                // ok, figure out the event's duration
                list($sh, $sm, $ss) = explode(
                    ':', $start);
                list($eh, $em, $es) = explode(':', $end);
                $stime = mktime($sh, $sm, $ss, 1, 1, 1970);
                // if the ending hour is less than the starting hour
                // assume that the event spans to the next day
                if ($eh < $sh) {
                    $etime = mktime($eh, $em, $es, 1, 2, 1970);
                } else {
                    $etime = mktime($eh, $em, $es, 1, 1, 1970);
                }
                $duration = $etime - $stime;
                // prep the vars for the sql statement
                $eid = pnVarPrepForStore(
                    $eid);
                $recurrtype = pnVarPrepForStore($recurrtype);
                $recurrspec = pnVarPrepForStore(serialize($recurrspec));
                // create our sql statement
                $updatesql = "UPDATE $events_table SET
                              pc_aid = '0',
							  pc_recurrtype = $recurrtype,
                              pc_recurrspec = '$recurrspec',
                              pc_duration = $duration,
							  pc_sharing = " . SHARING_GLOBAL . "
                              WHERE pc_eid = $eid";
                // execute our sql statement
                $dbconn->Execute(
                    $updatesql);
                if ($dbconn->ErrorNo() != 0) {
                    die($dbconn->ErrorMsg());
                    return false;
                }
                // next event please
            }
            // all done, proceed with next upgrade step if available/necessary
            return postcalendar_upgrade(
                '3.1');
            break;

        case '3.1':
        case '3.1.1':
        case '3.1.2':
        case '3.1.3':
        case '3.1.4':
        case '3.9.0':
        case '3.9.1':
        case '3.9.2':
            // ading pcSafeMode
            pnModSetVar('PostCalendar',
                'pcSafeMode', '0');
            return postcalendar_upgrade('3.9.3');
            break;

        case '3.9.3':
        case '3.9.3.1':
            // adding indexes
            $sql = "ALTER TABLE $events_table
					ADD INDEX basic_event (pc_catid,pc_aid,pc_eventDate,pc_endDate,pc_eventstatus,pc_sharing,pc_topic)";
            $dbconn->Execute($sql);
            if ($dbconn->ErrorNo() != 0) {
                die($dbconn->ErrorMsg());
                return false;
            }
            // adding indexes
            $sql = "ALTER TABLE $cat_table
					ADD INDEX basic_cat (pc_catname, pc_catcolor)";
            $dbconn->Execute($sql);
            if ($dbconn->ErrorNo() != 0) {
                die($dbconn->ErrorMsg());
                return false;
            }
            return postcalendar_upgrade('3.9.4');
            break;

        case '3.9.4':
        case '3.9.5':
        case '3.9.6':
        case '3.9.7':
        case '3.9.8':
            pnModDelVar('PostCalendar', 'pcSafeMode');
            pnModSetVar('PostCalendar', 'pcNotifyAdmin', '0');
            pnModSetVar('PostCalendar', 'pcNotifyEmail', pnConfigGetVar('adminmail'));
            return postcalendar_upgrade('3.9.9');
            break;

        case '3.9.9':
        case '4.0.0':
        case '4.0.1':
        case '4.0.2':
        case '4.0.3': // Also support upgrades from PostCalendar 4.03a (http://www.krapohl.info)
            // v4b TS start
            pnModSetVar(
                'PostCalendar', 'pcRepeating', '0');
            pnModSetVar('PostCalendar', 'pcMeeting', '0');
            pnModSetVar('PostCalendar', 'pcAddressbook', '1');
            $sql = "ALTER TABLE $events_table
                    ADD pc_meeting_id int(11) NULL default 0";

            $dbconn->Execute($sql);
            if ($dbconn->ErrorNo() != 0) {
                die('event table alter error : ' . $dbconn->ErrorMsg());
                return false;
            }

            // v4b TS end
            return postcalendar_upgrade(
                '5.0.0');
            break;
        case '5.0.0':
            pnModSetVar('PostCalendar', 'pcTemplate', 'default');
            return postcalendar_upgrade('5.0.1');
            break;
        case '5.0.1':
            pnModDelVar('PostCalendar', 'pcTemplate');
            return postcalendar_upgrade('5.1.0');
            break;
        case '5.1.0':
            // change the database. DBUtil + ADODB detect the changes on their own
            // and perform all necessary steps without help from the module author
            if (!DBUtil::changeTable(
                'postcalendar_events') || !DBUtil::changeTable(
                'postcalendar_categories')) {
                return LogUtil::registerError(_PC_UPGRADETABLESFAILED);
            }
        case '5.5.0':
    }

    // if we get this far - load the userapi and clear the cache
    /*
	if(!pnModAPILoad('PostCalendar','user')) {
		return false;
	}
	$tpl =& new pcRender();
	$tpl->clear_all_cache();
	$tpl->clear_compiled_tpl();
	*/
    return true;
}

/**
 *	Deletes an install of PostCalendar
 *
 *	This function removes PostCalendar from you
 *	Zikula install and should be accessed via
 *	the Zikula Admin interface
 *
 *	@return  boolean	true/false
 *	@access  public
 *	@author  Roger Raymond <iansym@yahoo.com>
 *	@copyright	The PostCalendar Team 2002
 */
function postcalendar_delete()
{
    $result = DBUtil::dropTable('postcalendar_events');
    $result = $result && DBUtil::dropTable('postcalendar_categories');
    $result = $result && pnModDelVar('PostCalendar');

    return $result;
}
