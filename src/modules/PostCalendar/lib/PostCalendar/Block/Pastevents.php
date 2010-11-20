<?php
/**
 * @package     PostCalendar
 * @author      Craig Heydenburg
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class PostCalendar_Block_Pastevents extends Zikula_Block
{
    /**
     * initialise block
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema('PostCalendar:pasteventsblock:', 'Block title::');
    }
    
    /**
     * get information on block
     */
    public function info()
    {
        return array(
            'text_type'      => 'PostCalendar',
            'module'         => 'PostCalendar',
            'text_type_long' => $this->__('Past Events Block'),
            'allow_multiple' => true,
            'form_content'   => false,
            'form_refresh'   => false,
            'show_preview'   => true);
    }
    
    /**
     * display block
     */
    public function display($blockinfo)
    {
        if (!SecurityUtil::checkPermission('PostCalendar:pasteventsblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
            return;
        }
        if (!ModUtil::available('PostCalendar')) {
            return;
        }
    
        // today's date
        $Date = DateUtil::getDatetime('', '%Y%m%d%H%M%S');
    
        // Get variables from content block
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        $pcbeventsrange = (int) $vars['pcbeventsrange'];
        $pcbfiltercats  = $vars['pcbfiltercats'];
    
        // setup the info to build this
        $the_year  = (int) substr($Date, 0, 4);
        $the_month = (int) substr($Date, 4, 2);
        $the_day   = (int) substr($Date, 6, 2);
    
        // If block is cached, return cached version
        $this->view->cache_id = $blockinfo['bid'] . ':' . UserUtil::getVar('uid');
        if ($this->view->is_cached('blocks/pastevents.tpl')) {
            $blockinfo['content'] = $this->view->fetch('blocks/pastevents.tpl');
            return BlockUtil::themeBlock($blockinfo);
        }
    
        if ($pcbeventsrange == 0) {
            $starting_date = '1/1/1970';
        } else {
            $starting_date = date('m/d/Y', mktime(0, 0, 0, $the_month - $pcbeventsrange, $the_day, $the_year));
        }
        $ending_date   = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_day - 1, $the_year)); // yesterday
    
        $filtercats['__CATEGORIES__'] = $pcbfiltercats; //reformat array
        $eventsByDate = ModUtil::apiFunc('PostCalendar', 'event', 'getEvents', array(
            'start'      => $starting_date,
            'end'        => $ending_date,
            'filtercats' => $filtercats,
            'sort'       => 'DESC'));
    
        $this->view->assign('A_EVENTS',   $eventsByDate);
        $this->view->assign('DATE',       $Date);
    
        $blockinfo['content'] = $this->view->fetch('blocks/pastevents.tpl');
    
        return BlockUtil::themeBlock($blockinfo);
    }
    
    /**
     * modify block settings ..
     */
    public function modify($blockinfo)
    {
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        // Defaults
        if (empty($vars['pcbeventsrange'])) $vars['pcbeventsrange'] = 6;
        if (empty($vars['pcbfiltercats']))  $vars['pcbfiltercats']  = array();
    
        // load the category registry util
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $this->view->assign('catregistry', $catregistry);
    
        $props = array_keys($catregistry);
        $this->view->assign('firstprop', $props[0]);
    
        $this->view->assign('vars', $vars);
    
        return $this->view->fetch('blocks/pastevents_modify.tpl');
    }
    
    /**
     * update block settings
     */
    public function update($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        // overwrite with new values
        $vars['pcbeventsrange'] = FormUtil::getPassedValue('pcbeventsrange', 6);
        $vars['pcbfiltercats']  = FormUtil::getPassedValue('pcbfiltercats'); //array
    
        $this->view->clear_cache('blocks/pastevents.tpl');
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
    
        return $blockinfo;
    }
} // end class def