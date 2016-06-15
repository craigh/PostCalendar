<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
use CategoryRegistryUtil;

class PostCalendar_Block_Calendar extends Zikula_Controller_AbstractBlock
{
    /**
     * initialise block
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema('PostCalendar:calendarblock:', 'Block title::');
    }
    
    /**
     * get information on block
     */
    public function info()
    {
        return array(
            'text_type'      => 'PostCalendar',
            'module'         => 'PostCalendar',
            'text_type_long' => $this->__('Calendar Block'),
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
        if (!SecurityUtil::checkPermission('PostCalendar:calendarblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
            return;
        }
        if (!ModUtil::available('PostCalendar')) {
            return;
        }
        $date = new DateTime();
        $calendarView = new PostCalendar_CalendarView_CalendarBlock($this->view, $date, '', null, $blockinfo);
        $blockinfo['content'] = $calendarView->render();
        
        if (empty($blockinfo['content']) && SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            $blockinfo['content'] = $this->__('Error: No block content selected!');
        }

        return BlockUtil::themeBlock($blockinfo);
    }
    
    /**
     * modify block settings ..
     */
    public function modify($blockinfo)
    {
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        // Defaults
        if (empty($vars['pcbshowcalendar']))      $vars['pcbshowcalendar']      = 0;
        if (empty($vars['pcbeventslimit']))       $vars['pcbeventslimit']       = 5;
        if (empty($vars['pcbeventoverview']))     $vars['pcbeventoverview']     = 0;
        if (empty($vars['pcbhideeventoverview'])) $vars['pcbhideeventoverview'] = 0;
        if (empty($vars['pcbnextevents']))        $vars['pcbnextevents']        = 0;
        if (empty($vars['pcbeventsrange']))       $vars['pcbeventsrange']       = 6;
        if (empty($vars['pcbshowsslinks']))       $vars['pcbshowsslinks']       = 0;
        if (empty($vars['pcbfiltercats']))        $vars['pcbfiltercats']        = array();
    
        // load the category registry util
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
        $this->view->assign('catregistry', $catregistry);
    
        $props = array_keys($catregistry);
        $this->view->assign('firstprop', $props[0]);
    
        $this->view->assign('vars', $vars);
    
        return $this->view->fetch('blocks/calendar_modify.tpl');
    }
    
    /**
     * update block settings
     */
    public function update($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        // overwrite with new values
        $vars['pcbshowcalendar'] = $this->request->request->get('pcbshowcalendar', 0);
        $vars['pcbeventslimit'] = $this->request->request->get('pcbeventslimit', 5);
        $vars['pcbeventoverview'] = $this->request->request->get('pcbeventoverview', 0);
        $vars['pcbhideeventoverview'] = $this->request->request->get('pcbhideeventoverview', 0);
        $vars['pcbnextevents'] = $this->request->request->get('pcbnextevents', 0);
        $vars['pcbeventsrange'] = $this->request->request->get('pcbeventsrange', 6);
        $vars['pcbshowsslinks'] = $this->request->request->get('pcbshowsslinks', 0);
        $vars['pcbfiltercats'] = $this->request->request->get('pcbfiltercats'); //array
    
        $this->view->clear_cache('blocks/view_day.tpl');
        $this->view->clear_cache('blocks/view_month.tpl');
        $this->view->clear_cache('blocks/view_upcoming.tpl');
        $this->view->clear_cache('blocks/calendarlinks.tpl');
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
    
        return $blockinfo;
    }
} // end class def