<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

namespace Zikula\PostCalendarModule\Block;

use Zikula\PostCalendarModule\CalendarView\CalendarViewFeaturedEventBlock;
use BlockUtil;
use CategoryRegistryUtil;
use DateTime;
use ModUtil;
use SecurityUtil;

class FeaturedeventBlock extends \Zikula_Controller_AbstractBlock
{
    /**
     * initialise block
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema('PostCalendar:featuredeventblock:', 'Block title::');
    }
    
    /**
     * get information on block
     */
    public function info()
    {
        return array(
            'text_type'        => $this->__('Featured Event'),
            'module'           => 'PostCalendar',
            'text_type_long'   => $this->__('Featured Event Calendar Block'),
            'allow_multiple'   => true,
            'form_content'     => false,
            'form_refresh'     => false,
            'show_preview'     => true,
            'admin_tableless'  => true);
    }
    
    /**
     * display block
     */
    public function display($blockinfo)
    {
        if (!SecurityUtil::checkPermission('PostCalendar:featuredeventblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
            return;
        }
        if (!ModUtil::available('PostCalendar')) {
            return;
        }

        $date = new DateTime();
        $calendarView = new CalendarViewFeaturedEventBlock($this->view, $date, '', null, $blockinfo);
        $blockinfo = $calendarView->render();
    
        return BlockUtil::themeBlock($blockinfo);
    }
    
    /**
     * modify block settings ..
     */
    public function modify($blockinfo)
    {
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        // Defaults
        if (empty($vars['eid']))           $vars['eid']           = '';
        if (empty($vars['showcountdown'])) $vars['showcountdown'] = 0;
        if (empty($vars['hideonexpire']))  $vars['hideonexpire']  = 0;
    
        $this->view->assign('vars', $vars);
    
        return $this->view->fetch('blocks/featuredevent_modify.tpl');
    }
    
    /**
     * update block settings
     */
    public function update($blockinfo)
    {
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        // alter the corresponding variable
        $vars['eid'] = $this->request->request->get('eid', '');
        $vars['showcountdown'] = $this->request->request->get('showcountdown', '');
        $vars['hideonexpire'] = $this->request->request->get('hideonexpire', '');
    
        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
    
        // clear the block cache
        $this->view->clear_cache('blocks/featuredevent.tpl');
    
        return $blockinfo;
    }
} // end class def