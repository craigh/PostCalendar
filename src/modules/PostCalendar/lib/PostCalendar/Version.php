<?php

/**
 * @package     PostCalendar
 * @author      Craig Heydenburg
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PostCalendar_Version extends Zikula_AbstractVersion
{

    /**
     *
     * @return array module metadata
     */
    public function getMetaData()
    {
        $meta = array();
        $meta['displayname'] = $this->__('PostCalendar');
        $meta['url'] = $this->__(/* !used in URL - nospaces, no special chars, lcase */'postcalendar');
        $meta['description'] = $this->__('Calendar for Zikula');
        $meta['version'] = '7.0.0';

        $meta['securityschema'] = array(
            'PostCalendar::Event' => 'Event Title::Event ID',
            'PostCalendar::' => '::');
        $meta['core_min'] = '1.3.0'; // requires minimum 1.3.0 or later
        //$meta['core_max'] = '1.3.0'; // doesn't work with versions later than x.x.x

        $meta['capabilities'] = array();
        $meta['capabilities'][HookUtil::PROVIDER_CAPABLE] = array('enabled' => true);
        $meta['capabilities'][HookUtil::SUBSCRIBER_CAPABLE] = array('enabled' => true);

        return $meta;
    }

    protected function setupHookBundles()
    {
        $bundle = new Zikula_Version_HookProviderBundle('modulehook_area.postcalendar.event', $this->__('PostCalendar Event Maker'));
        $bundle->addHook('hookhandler.postcalendar.ui.view', 'ui.view', 'PostCalendar_HookHandlers', 'ui_view', 'postcalendar.service', 10);
        $bundle->addHook('hookhandler.postcalendar.ui.edit', 'ui.edit', 'PostCalendar_HookHandlers', 'ui_edit', 'postcalendar.service', 10);
        $bundle->addHook('hookhandler.postcalendar.ui.delete', 'ui.delete', 'PostCalendar_HookHandlers', 'ui_delete', 'postcalendar.service', 10);
        $bundle->addHook('hookhandler.postcalendar.validate.edit', 'validate.edit', 'PostCalendar_HookHandlers', 'validate_edit', 'postcalendar.service', 10);
        $bundle->addHook('hookhandler.postcalendar.validate.delete', 'validate.delete', 'PostCalendar_HookHandlers', 'validate_delete', 'postcalendar.service', 10);
        $bundle->addHook('hookhandler.postcalendar.process.edit', 'process.edit', 'PostCalendar_HookHandlers', 'process_edit', 'postcalendar.service', 10);
        $bundle->addHook('hookhandler.postcalendar.process.delete', 'process.delete', 'PostCalendar_HookHandlers', 'process_delete', 'postcalendar.service', 10);
        $this->registerHookProviderBundle($bundle);

        $bundle = new Zikula_AbstractVersion_HookSubscriberBundle('modulehook_area.postcalendar.events', $this->__('PostCalendar Events'));
        $bundle->addType('ui.view', 'postcalendar.hook.events.ui.view');
        $bundle->addType('ui.edit', 'postcalendar.hook.events.ui.edit');
        $bundle->addType('ui.delete', 'postcalendar.hook.events.ui.delete');
        $bundle->addType('validate.edit', 'postcalendar.hook.events.validate.edit');
        $bundle->addType('validate.delete', 'postcalendar.hook.events.validate.delete');
        $bundle->addType('process.edit', 'postcalendar.hook.events.process.edit');
        $bundle->addType('process.delete', 'postcalendar.hook.events.process.delete');
        $this->registerHookSubscriberBundle($bundle);

        $bundle = new Zikula_AbstractVersion_HookSubscriberBundle('modulehook_area.postcalendar.eventsfilter', $this->__('PostCalendar Event Filters'));
        $bundle->addType('ui.filter', 'postcalendar.hook.eventsfilter.ui.filter');
        $this->registerHookSubscriberBundle($bundle);
    }

}