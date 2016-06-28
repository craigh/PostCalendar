<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

namespace Zikula\PostCalendarModule\Block;

use Zikula\PostCalendarModule\CalendarView\CalendarViewPastEventsBlock;
use BlockUtil;
use CategoryRegistryUtil;
use DateTime;
use ModUtil;
use SecurityUtil;

class PasteventsBlock extends \Zikula_Controller_AbstractBlock
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
            'text_type'      => $this->__('Past Events Block'),
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
        $date = new DateTime();
        $calendarView = new CalendarViewPastEventsBlock($this->view, $date, '', null, $blockinfo);
        $blockinfo['content'] = $calendarView->render();
    
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
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
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
        $vars['pcbeventsrange'] = $this->request->request->get('pcbeventsrange', 6);
        $vars['pcbfiltercats'] = $this->request->request->get('pcbfiltercats'); //array
    
        $this->view->clear_cache('blocks/pastevents.tpl');
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
    
        return $blockinfo;
    }
} // end class def