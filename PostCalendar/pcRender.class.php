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
require_once ('includes/debug.php');
class pcRender extends pnRender
{
    function pcRender()
    {
	$theme = pnVarPrepForOS(pnUserGetTheme());
	pnThemeLoad($theme);
	$osTheme = pnVarPrepForOS($theme);

	global $bgcolor1,$bgcolor2,$bgcolor3,$bgcolor4,$bgcolor5,$bgcolor6,$textcolor1,$textcolor2;
        
	$pcModInfo     = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
	$pcDir         = pnVarPrepForOS($pcModInfo['directory']);
	$pcDisplayName = $pcModInfo['displayname'];
		
	// setup up pcRender configs
	$this->pnRender ('PostCalendar');
	$this->compile_check	= true; 
	$this->force_compile    = true;
	$this->debugging	= true;  
	$this->caching		= false;
	$safe_mode		= ini_get('safe_mode');
	$safe_mode_gid		= ini_get('safe_mode_gid');
	$open_basedir 	        = ini_get('open_basedir');
		
	$use_safe_mode = ((bool)$safe_mode || (bool)$safe_mode_gid || !empty($open_basedir));
	$this->use_sub_dirs = false;

	$this->autoload_filters = array('output' => array('trimwhitespace'));
		
	$lang  = pnUserGetLang();
	$func  = FormUtil::getPassedValue('func');
	$print = FormUtil::getPassedValue('print');

	$template_view = FormUtil::getPassedValue('tplview');
	if (!$template_view) 
	    $template_view = 'month'; 
/*
	$pcTheme = pnModGetVar(__POSTCALENDAR__,'pcTemplate');
	if (!$pcTheme) 
	    $pcTheme='default';
*/
	// assign theme globals
	$this->assign('BGCOLOR1', $bgcolor1);
	$this->assign('BGCOLOR2', $bgcolor2);
	$this->assign('BGCOLOR3', $bgcolor3);
	$this->assign('BGCOLOR4', $bgcolor4);
	$this->assign('BGCOLOR5', $bgcolor5);
	$this->assign('BGCOLOR6', $bgcolor6);
	$this->assign('TEXTCOLOR1', $textcolor1);
	$this->assign('TEXTCOLOR2', $textcolor2);
	$this->assign('USER_LANG', $lang);
	$this->assign('FUNCTION', $func);
	$this->assign('PRINT_VIEW', $print);
	$this->assign('USE_POPUPS', _SETTING_USE_POPUPS);
	$this->assign('USE_TOPICS', _SETTING_DISPLAY_TOPICS);
	$this->assign('USE_INT_DATES', _SETTING_USE_INT_DATES);
	$this->assign('OPEN_NEW_WINDOW', _SETTING_OPEN_NEW_WINDOW);
	$this->assign('EVENT_DATE_FORMAT', _SETTING_DATE_FORMAT);
	$this->assign('HIGHLIGHT_COLOR', _SETTING_DAY_HICOLOR);
	$this->assign('24HOUR_TIME', _SETTING_TIME_24HOUR);
	$this->assign('MODULE_NAME', $pcDisplayName);
	$this->assign('MODULE_DIR', $pcDir);
	$this->assign('ACCESS_NONE', PC_ACCESS_NONE);
	$this->assign('ACCESS_OVERVIEW', PC_ACCESS_OVERVIEW);
	$this->assign('ACCESS_READ', PC_ACCESS_READ);
	$this->assign('ACCESS_COMMENT', PC_ACCESS_COMMENT);
	$this->assign('ACCESS_MODERATE', PC_ACCESS_MODERATE);
	$this->assign('ACCESS_EDIT', PC_ACCESS_EDIT);
	$this->assign('ACCESS_ADD', PC_ACCESS_ADD);
	$this->assign('ACCESS_DELETE', PC_ACCESS_DELETE);
	$this->assign('ACCESS_ADMIN', PC_ACCESS_ADMIN);
	//$this->assign('TPL_NAME', $pcTheme);
	$this->assign('TPL_VIEW', $template_view);
	
	/* NOTE: CAH 4/5/09 - Not sure if image/style and config directories need closing slash '/' */
	//$this->assign('TPL_IMAGE_PATH', "modules/PostCalendar/pntemplates/$pcTheme/images");
	//$this->assign('TPL_STYLE_PATH', "modules/PostCalendar/pntemplates/$pcTheme/style");
	//$this->assign('THEME_PATH', "themes/$osTheme");

	//$this->config_dir = "modules/PostCalendar/pntemplates/$pcTheme/config";
	
	return true;
	}
	/**
    * setup the current instance of the pcRender class and return it back to the module
    */
     function getInstance($module = null, $caching = null, $cache_id = null, $add_core_data = false)
    {
        /**
        * static variable to hold the instance of this object when called in a singleton pattern
        */
        static $instance;
        if (!isset($instance)) {
            $instance = new pcRender($module, $caching);
        }
        if (!is_null($caching)) {
            $instance->caching = $caching;
        }
        if (!is_null($cache_id)) {
            $instance->cache_id = $cache_id;
        }
        if ($module === null) {
            $module = $instance->toplevelmodule;
        }
        if (!array_key_exists($module, $instance->module)) {
            $instance->module[$module] = pnModGetInfo(pnModGetIDFromName($module));
            //$instance->modinfo = pnModGetInfo(pnModGetIDFromName($module));
            $instance->_add_plugins_dir($module);
        }
        if ($add_core_data) {
            $instance->add_core_data();
        }
        return $instance;
    }
}
?>