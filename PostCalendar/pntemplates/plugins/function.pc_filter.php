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
function smarty_function_pc_filter($args, &$smarty)
{
    extract($args); unset($args);
	
    if(empty($type)) 
    {
        $smarty->trigger_error("pc_filter: missing 'type' parameter");
        return;
    }
	
    $Date = postcalendar_getDate();
    if(!isset($y)) $y = substr($Date,0,4);
    if(!isset($m)) $m = substr($Date,4,2);
    if(!isset($d)) $d = substr($Date,6,2);
    
    $tplview     = pnVarCleanFromInput('tplview');
    $viewtype    = pnVarCleanFromInput('viewtype');
    $pc_username = pnVarCleanFromInput('pc_username');
		
    if(!isset($viewtype)) 
        $viewtype = _SETTING_DEFAULT_VIEW; 
	 
    $types = explode(',',$type);

    $modinfo = pnModGetInfo(pnModGetIDFromName('PostCalendar'));
    $mdir = pnVarPrepForOS($modinfo['directory']);
    unset($modinfo);

    //================================================================
    //	build the username filter pulldown
    //================================================================
    if(in_array('user',$types)) 
    {
        @define('_PC_FORM_USERNAME',true);
				// this is another sport to pull only users that have submitted events!!! not ALL users...
        $users = DBUtil::selectObjectArray ('users', '', 'uname');

        $useroptions  = "<select name=\"pc_username\" class=\"$class\">";
        $useroptions .= "<option value=\"\" class=\"$class\">"._PC_FILTER_USERS."</option>";
        $selected     = ($pc_username == '__PC_ALL__' ? 'selected="selected"' : '');
        $useroptions .= "<option value=\"__PC_ALL__\" class=\"$class\" $selected>"._PC_FILTER_USERS_ALL."</option>";
	foreach ($users as $user)
        {
            $uname = $user['uname'];
            $sel = ($pc_username == $uname ? 'selected="selected"' : '');
            $useroptions .= "<option value=\"$uname\" $sel class=\"$class\">$uname</option>";
        }
        $useroptions .= '</select>';
    }

    //================================================================
    //	build the category filter pulldown
    //================================================================
    if(in_array('category',$types)) 
    {
        @define('_PC_FORM_CATEGORY',true);
        $category   = pnVarCleanFromInput('pc_category');
        $categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
        $catoptions  = "<select name=\"pc_category\" class=\"$class\">";
        $catoptions .= "<option value=\"\" class=\"$class\">"._PC_FILTER_CATEGORY."</option>";
        foreach($categories as $c) 
	{
            $sel = ($category == $c['catid'] ? 'selected="selected"' : '');
            $catoptions .= "<option value=\"$c[catid]\" $sel class=\"$class\">$c[catname]</option>";
        }
        $catoptions .= '</select>';
    }

    //================================================================
    //	build the topic filter pulldown
    //================================================================
    if(in_array('topic',$types) && _SETTING_DISPLAY_TOPICS) 
    {
        @define('_PC_FORM_TOPIC',true);
        $topic  = pnVarCleanFromInput('pc_topic');
        $topics = pnModAPIFunc('PostCalendar','user','getTopics');
        $topoptions  = "<select name=\"pc_topic\" class=\"$class\">";
        $topoptions .= "<option value=\"\" class=\"$class\">"._PC_FILTER_TOPIC."</option>";
        foreach($topics as $t) 
	{
            $sel = ($topic == $t['topicid'] ? 'selected="selected"' : '');
            $topoptions .= "<option value=\"$t[topicid]\" $sel class=\"$class\">$t[topictext]</option>";
        }
        $topoptions .= '</select>';
    } 
    else 
        $topoptions = '';
	
    //================================================================
    //	build it in the correct order
    //================================================================
    if(!isset($label)) 
        $label = _PC_TPL_VIEW_SUBMIT; 

    $submit = "<input type=\"submit\" name=\"submit\" value=\"$label\" class=\"$class\" />";
    $orderArray = array('user'=>$useroptions, 'category'=>$catoptions, 'topic'=>$topoptions, 'jump'=>$submit);

    if(isset($order)) 
    {
        $newOrder = array();
        $order = explode(',',$order);
        foreach($order as $tmp_order) 
                array_push($newOrder,$orderArray[$tmp_order]);

        foreach($orderArray as $key=>$old_order) 
            if(!in_array($old_order,$newOrder)) 
                array_push($newOrder,$orderArray[$key]);

        $order = $newOrder;
    } 
    else 
        $order = $orderArray;
	
    foreach($order as $element) 
        echo $element;

    if(!in_array('user',$types)) 
        echo "<input type='hidden' name='pc_username' value='$pc_username' />";
}
?>