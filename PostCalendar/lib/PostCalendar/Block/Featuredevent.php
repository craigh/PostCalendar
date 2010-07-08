<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PostCalendar_Block_Featuredevent extends Zikula_Block
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
            'text_type'        => 'featuredevent',
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
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        // Defaults
        if (empty($vars['eid'])) {
            return false;
        }
        $vars['showcountdown'] = empty($vars['showcountdown']) ? false : true;
        $vars['hideonexpire']  = empty($vars['hideonexpire'])  ? false : true;
        $event['showhiddenwarning'] = false; // default to false
    
        // get the event from the DB
        ModUtil::dbInfoLoad('PostCalendar');
        $event = DBUtil::selectObjectByID('postcalendar_events', (int) $vars['eid'], 'eid');
        $event = ModUtil::apiFunc('PostCalendar', 'event', 'formateventarrayfordisplay', $event);
    
        // is event allowed for this user?
        if ($event['sharing'] == SHARING_PRIVATE && $event['aid'] != UserUtil::getVar('uid') && !SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) {
            // if event is PRIVATE and user is not assigned event ID (aid) and user is not Admin event should not be seen
            return false;
        }
    
        $alleventdates = ModUtil::apiFunc('PostCalendar', 'event', 'geteventdates', $event); // gets all FUTURE occurances
        // assign next occurance to eventDate
        $event['eventDate'] = array_shift($alleventdates);
    
        if ($vars['showcountdown']) {
            $datedifference = DateUtil::getDatetimeDiff_AsField(DateUtil::getDatetime(null, '%F'), $event['eventDate'], 3);
            $event['datedifference'] = round($datedifference);
            $event['showcountdown'] = true;
        }
        if ($vars['hideonexpire'] && $event['datedifference'] < 0) {
            //return false;
            $event['showhiddenwarning'] = true;
            $blockinfo['title'] = NULL;
        }
    
        $this->view->assign('loaded_event', $event);
        $this->view->assign('thisblockid', $blockinfo['bid']);
    
        $blockinfo['content'] = $this->view->fetch('blocks/featuredevent.tpl');
    
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
        $vars['eid']           = FormUtil::getPassedValue('eid', '', 'POST');
        $vars['showcountdown'] = FormUtil::getPassedValue('showcountdown', '', 'POST');
        $vars['hideonexpire']  = FormUtil::getPassedValue('hideonexpire', '', 'POST');
    
        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
    
        // clear the block cache
        $this->view->clear_cache('blocks/featuredevent.tpl');
    
        return $blockinfo;
    }
} // end class def