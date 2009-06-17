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
function smarty_function_pc_url($args) 
{
    extract($args); unset($args);
    if(!isset($action)) 
        $action = _SETTING_DEFAULT_VIEW;

    if(empty($print)) 
        $print = false;
    else 
        $print = true;

    $template_view = pnVarCleanFromInput('tplview');
    $viewtype = strtolower(pnVarCleanFromInput('viewtype'));
    $pc_username = pnVarCleanFromInput('pc_username');
    $category = pnVarCleanFromInput('pc_category');
    $topic = pnVarCleanFromInput('pc_topic');
    $popup = pnVarCleanFromInput('popup');
		$today = DateUtil::getDatetime('','%Y%m%d000000');

    if(empty($date)) 
        $Date = postcalendar_getDate();
    else 
        $Date = $date;

    // some extra cleanup if necessary
    $Date = str_replace('-','',$Date);
    
    $pcModInfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    $pcDir = pnVarPrepForOS($pcModInfo['directory']);
    
    switch($action) 
    {
        case 'submit' :
        case 'submit-admin' :
            $link = pnModURL('PostCalendar','event','new',array('tplview'=>$template_view,'Date'=>$Date));
            break;
        
        case 'search' :
            $link = pnModURL('PostCalendar','user','search');
            break;
				case 'today' :
						$link = pnModURL('PostCalendar','user','view',array('tplview'    => $template_view,
                                                                  'viewtype'   => $viewtype,
                                                                  'Date'       => $today,
                                                                  'pc_username'=> $pc_username,
                                                                  'pc_category'=> $category,
                                                                  'pc_topic'   => $topic));
						break;
        case 'day' :
            $link = pnModURL('PostCalendar','user','view',array('tplview'    => $template_view,
                                                                  'viewtype'   => 'day',
                                                                  'Date'       => $Date,
                                                                  'pc_username'=> $pc_username,
                                                                  'pc_category'=> $category,
                                                                  'pc_topic'   => $topic));
            break;
        
        case 'week' :
            $link = pnModURL('PostCalendar','user','view',array('tplview'    => $template_view,
                                                                  'viewtype'   => 'week',
                                                                  'Date'       => $Date,
                                                                  'pc_username'=> $pc_username,
                                                                  'pc_category'=> $category,
                                                                  'pc_topic'   => $topic));
            break;
        
        case 'month' :
            $link = pnModURL('PostCalendar','user','view',array('tplview'    => $template_view,
                                                                  'viewtype'   => 'month',
                                                                  'Date'       => $Date,
                                                                  'pc_username'=> $pc_username,
                                                                  'pc_category'=> $category,
                                                                  'pc_topic'   => $topic));
            break;
        
        case 'year' :
            $link = pnModURL('PostCalendar','user','view',array('tplview'    => $template_view,
                                                                  'viewtype'   => 'year',
                                                                  'Date'       => $Date,
                                                                  'pc_username'=> $pc_username,
                                                                  'pc_category'=> $category,
                                                                  'pc_topic'   => $topic));
            break;
        
        case 'detail' :
            if(isset($eid)) 
            {
                if(_SETTING_OPEN_NEW_WINDOW && !$popup) 
                {
                    $link = "javascript:opencal($eid,'$Date');";
                } 
                else 
                {
                    $link = pnModURL('PostCalendar','user','view',array('Date'    => $Date,
                                                                          'tplview' => $template_view,
                                                                          'viewtype'=> 'details',
                                                                          'eid'     => $eid));
                }
            } 
	    else 
            	$link = '';

            break;
    }

    if($print) 
        $link .= '" target="_blank"';
    elseif (_SETTING_OPEN_NEW_WINDOW && $viewtype == 'details')
        $link .= '" target="csCalendar"';

    echo DataUtil::formatForDisplay($link);
}
?>