<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class PostCalendar_Controller_Hooks extends Zikula_Controller
{
    /**
     * postcalendar_hooks_new
     *
     * display PostCalendar related information on hooked new item
     * @param array $args
     * @return string generated html output
     */
    public function newgui($args)
    {
        $thismodule = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module
    
        $postcalendar_admincatselected = ModUtil::getVar($thismodule, 'postcalendar_admincatselected');
        $postcalendar_optoverride = ModUtil::getVar($thismodule, 'postcalendar_optoverride', false);
    
        if (($postcalendar_admincatselected['Main'] > 0) && (!$postcalendar_optoverride)) {
            $postcalendar_hide = true;
        } else {
            $postcalendar_hide = false;
        }
        $this->view->assign('postcalendar_hide', $postcalendar_hide);
    
        if ($postcalendar_admincatselected['Main'] == 0) {
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
            $this->view->assign('postcalendar_catregistry', $catregistry);
        } else {
            $this->view->assign('postcalendar_admincatselected', serialize($postcalendar_admincatselected)); // value assigned by admin
        }
        $this->view->assign('postcalendar_optoverride', $postcalendar_optoverride);
    
        return $this->view->fetch('hooks/new.tpl');
    }
    
    /**
     * postcalendar_hooks_modify
     *
     * display PostCalendar related information on hooked modify item
     * @param array $args
     * @return string generated html output
     */
    public function modify($args)
    {
        if ((!isset($args['objectid'])) || ((int) $args['objectid'] <= 0)) {
            return LogUtil::registerArgsError();
        }
        $module = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module
    
        // get the event
        // Get table info
        $pntable = DBUtil::getTables();
        $cols = $pntable['postcalendar_events_column'];
        // build where statement
        $where = "WHERE " . $cols['hooked_modulename'] . " = '" . DataUtil::formatForStore($module) . "'
                  AND "   . $cols['hooked_objectid']   . " = '" . DataUtil::formatForStore($args['objectid']) . "'";
        $event = DBUtil::selectObject('postcalendar_events', $where);
    
        if ($event) {
            $selectedcategories = array();
            foreach ($event['__CATEGORIES__'] as $prop => $cats) {
                $selectedcategories[$prop] = $cats['id'];
            }
            $eventid = $event['eid'];
        }
    
        $postcalendar_admincatselected = ModUtil::getVar($module, 'postcalendar_admincatselected');
        $postcalendar_optoverride = ModUtil::getVar($module, 'postcalendar_optoverride', false);
    
        if (($postcalendar_admincatselected['Main'] > 0) && (!$postcalendar_optoverride)) {
            $postcalendar_hide = true;
        } else {
            $postcalendar_hide = false;
        }
        $this->view->assign('postcalendar_hide', $postcalendar_hide);
    
        if ($postcalendar_admincatselected['Main'] == 0) {
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
            $this->view->assign('postcalendar_catregistry', $catregistry);
            $this->view->assign('postcalendar_selectedcategories', $selectedcategories);
        } else {
            $this->view->assign('postcalendar_admincatselected', serialize($postcalendar_admincatselected)); // value assigned by admin
        }
        $this->view->assign('postcalendar_optoverride', $postcalendar_optoverride);
    
        $this->view->assign('postcalendar_eid', $eventid);
    
        return $this->view->fetch('hooks/modify.tpl');
    }
    /**
     * postcalendar_hooks_modifyconfig
     *
     * display PostCalendar related information on hooked module admin modify
     * @param array $args
     * @return string generated html output
     */
    public function modifyconfig($args)
    {
        $thismodule = isset($args['extrainfo']['module']) ? strtolower($args['extrainfo']['module']) : strtolower(ModUtil::getName()); // default to active module
    
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'postcalendar_events');
        $this->view->assign('postcalendar_catregistry', $catregistry);
    
        $this->view->assign('postcalendar_optoverride', ModUtil::getVar($thismodule, 'postcalendar_optoverride', false));
        $this->view->assign('postcalendar_admincatselected', ModUtil::getVar($thismodule, 'postcalendar_admincatselected'));
        return $this->view->fetch('hooks/modifyconfig.tpl');
    }
} // end class def