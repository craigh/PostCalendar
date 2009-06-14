<?php
@define('__POSTCALENDAR__','PostCalendar');
/**
 *  SVN: $Id$
 *
 *  @package         PostCalendar 
 *  @lastmodified    $Date$ 
 *  @modifiedby      $Author$ 
 *  @HeadURL	       $HeadURL$ 
 *  @version         $Revision$ 
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

//=========================================================================
//  Require utility classes
//=========================================================================
$pcModInfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
$pcDir = pnVarPrepForOS($pcModInfo['directory']);
require_once("modules/$pcDir/common.api.php");
unset($pcModInfo,$pcDir);

/**
 * Get available admin panel links
 *
 * @return array array of admin links
 */
function postcalendar_adminapi_getlinks()
{
	// Define an empty array to hold the list of admin links
	$links = array();
	
	// Load the admin language file
	// This allows this API to be called outside of the module
	pnModLangLoad('PostCalendar', 'admin');
	
	/**********************************************************************************/
	@define('_AM_VAL',   1);
	@define('_PM_VAL',   2);
	
	@define('_EVENT_APPROVED',1);
	@define('_EVENT_QUEUED', 0);
	@define('_EVENT_HIDDEN',-1);
	/**********************************************************************************/
	
	// Check the users permissions to each avaiable action within the admin panel
	// and populate the links array if the user has permission
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		$links[] = array('url' => pnModURL('PostCalendar', 'admin', 'modifyconfig'), 'text' => _EDIT_PC_CONFIG_GLOBAL);
	}
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		$links[] = array('url' => pnModURL('PostCalendar', 'admin', 'categories'), 'text' => _EDIT_PC_CONFIG_CATEGORIES);
	}
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADD)) {
		$links[] = array('url' => pnModURL('PostCalendar', 'admin', 'submit'), 'text' => _PC_CREATE_EVENT);
	}
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		$links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listapproved'), 'text' => _PC_VIEW_APPROVED);
	}
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		$links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listhidden'), 'text' => _PC_VIEW_HIDDEN);
	}
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		$links[] = array('url' => pnModURL('PostCalendar', 'admin', 'listqueued'), 'text' => _PC_VIEW_QUEUED);
	}
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		$links[] = array('url' => pnModURL('PostCalendar', 'admin', 'manualClearCache'), 'text' => _PC_CLEAR_CACHE);
	}
	if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
		$links[] = array('url' => pnModURL('PostCalendar', 'admin', 'testSystem'), 'text' => _PC_TEST_SYSTEM);
	}
	
	// Return the links array back to the calling function
	return $links;
}
function postcalendar_adminapi_getAdminListEvents($args) 
{
    extract($args);

    $where = "WHERE pc_eventstatus=$type";
    if ($sort)
    {
        if ($sdir == 0)
            $sort .= ' DESC';
        elseif ($sdir == 1)
            $sort .= ' ASC';
    }

    return DBUtil::selectObjectArray ('postcalendar_events', $where, $sort, $offset, $offset_increment, false);
}

function postcalendar_adminapi_buildHourSelect($args) 
{
    extract($args);
    $time24hours = pnModGetVar(__POSTCALENDAR__,'time24hours');
    
    if(!isset($hour)){
        $hour = $time24hours ? date('H') : date('h'); 
    }
    
    $output = new pnHTML();
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    
    $options = array();
	if($time24hours) {
        for($i = 0; $i < 24; $i++) {
            $sel = false;
            if($i == $hour) {
                $sel = true;
            }
            $options[$i]['id']       = $i;
            $options[$i]['selected'] = $sel;
            $options[$i]['name']     = $i < 10 ? '0'.$i : $i;  
        }
    } else {
        for($i = 0; $i < 12; $i++) {
            $sel = false;
            if($i == $hour) {
                $sel = true;
            }
            $options[$i]['id']       = $i+1;
            $options[$i]['selected'] = $sel;
            $options[$i]['name']     = $i+1 < 10 ? '0'.$i+1 : $i+1;     
        }
    }
    
    $output->FormSelectMultiple('pc_hour',$options);
    return $output->GetOutput();
}

function postcalendar_adminapi_buildMinSelect($args) 
{
    extract($args);
    
    if(!isset($min)){
        $min = date('i'); 
    }
    
    $output = new pnHTML();
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    
    $options = array();
    for ($i = 0; $i <= 45; $i+5) {
        $options[$i]['id']       = $i;
        $options[$i]['selected'] = false;
        $options[$i]['name']     = $i < 10 ? '0'.$i+1 : $i+1;            
    }
    
    $output->FormSelectMultiple('pc_min',$options);
    return $output->GetOutput();
}

function postcalendar_adminapi_buildAMPMSelect($args) 
{   
    extract($args);
    
    $output = new pnHTML();
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    
    $options = array();
    if(pnModGetVar(__POSTCALENDAR__,'time24hours')) {
        return false;
    } else {
        $options[0]['id']        = 'AM';
        $options[0]['selected']  = '';
        $options[0]['name']      = 'AM';
        $options[1]['id']        = 'PM';
        $options[1]['selected']  = '';
        $options[1]['name']      = 'PM';
    }
    
    $output->FormSelectMultiple('pc_ampm',$options);
    return $output->GetOutput();
}

function postcalendar_adminapi_clearCache()
{
	$pnRender = pnRender::getInstance('PostCalendar'); //	PostCalendarSmartySetup not needed
	$res = $pnRender->clear_all_cache();

	return $res;
}

?>