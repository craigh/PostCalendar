<?php
/**
 *  SVN: $Id$
 *
 *  @package         PostCalendar 
 *  @lastmodified    $Date$ 
 *  @modifiedby      $Author$ 
 *  @HeadURL	       $HeadURL$ 
 *  @version         $Revision$ 
 *  
 *  PostCalendar::PostNuke Events Calendar Module
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
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 */
function postcalendar_pntables()
{
    // Initialise table array
    $pntable = array();
	$prefix = pnConfigGetVar('prefix');
    //$prefix = 'Rogue';
	
    $pc_events = $prefix . '_postcalendar_events';
    $pntable['postcalendar_events'] = $pc_events;
    $pntable['postcalendar_events_column'] = array(
        'eid'           => 'pc_eid', 
        'catid'         => 'pc_catid',
        'aid'           => 'pc_aid',       
        'title'         => 'pc_title',     
        'time'          => 'pc_time',      
        'hometext'      => 'pc_hometext',   
        'comments'      => 'pc_comments',   
        'counter'       => 'pc_counter',   
        'topic'         => 'pc_topic',     
        'informant'     => 'pc_informant', 
        'eventDate'     => 'pc_eventDate', 
        'duration'      => 'pc_duration',
        'endDate'       => 'pc_endDate',   
        'recurrtype'    => 'pc_recurrtype',
        'recurrspec'    => 'pc_recurrspec',
        'recurrfreq'    => 'pc_recurrfreq', 
        'startTime'     => 'pc_startTime',  
        'endTime'       => 'pc_endTime',
        'alldayevent'   => 'pc_alldayevent',
        'location'      => 'pc_location',
        'conttel'       => 'pc_conttel',  
        'contname'      => 'pc_contname',  
        'contemail'     => 'pc_contemail', 
        'website'       => 'pc_website',  
        'fee'           => 'pc_fee',
        'eventstatus'   => 'pc_eventstatus',
        'sharing'       => 'pc_sharing',
        'language'      => 'pc_language',
        'meeting_id'    => 'pc_meeting_id' //V4B SB improtant to make and manage meetings
        );
    
    // @since version 3.1
    // new category table
    $pc_categories = $prefix . '_postcalendar_categories';   
    $pntable['postcalendar_categories'] = $pc_categories;
    $pntable['postcalendar_categories_column'] = array(
        'catid'         => 'pc_catid',
        'catname'       => 'pc_catname',
        'catcolor'      => 'pc_catcolor',
        'catdesc'       => 'pc_catdesc'
        );

	// INSERTED FOR VERSION 5.0.1 C HEYDENBURG
	// this is for compatibility with the old Topics Module which has been superceeded
	// in Zikula by the Categories module....
	// This is needed for upgraded sites (from PN 764 -> ZK1+)
	
    // this is a nasty hack, probably wont work with DBUtil,
    // so for future reference, remove the 'tid' line or maybe if this
    // module is updated to use DBUtil, change references to the id to be tid rather
    // than topicid (better idea) - drak
    $pntable['topics'] = pnConfigGetVar('prefix') . '_topics';
    $pntable['topics_column'] = array('topicid'    => 'pn_topicid',
                                       'tid'        => 'pn_topicid',
                                       'topicname'  => 'pn_topicname',
                                       'topicimage' => 'pn_topicimage',
                                       'topictext'  => 'pn_topictext',
                                       'counter'    => 'pn_counter');

    $pntable['related'] = pnConfigGetVar('prefix') . '_related';
    $pntable['related_column'] = array ('rid'  => 'pn_rid',
                                         'tid'  => 'pn_tid',
                                         'name' => 'pn_name',
                                         'url'  => 'pn_url');
    
	return $pntable;
}
?>