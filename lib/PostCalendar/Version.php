<?php

/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
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
        $meta['version'] = '8.0.1';

        $meta['securityschema'] = array(
            'PostCalendar::Event' => 'Event Title::Event ID',
            'PostCalendar::' => '::');
        $meta['core_min'] = '1.3.3'; // requires minimum 1.3.3 or later (with updated (>=1.3.5) jquery_datepicker plugin)
        $meta['core_max'] = '1.3.99'; // doesn't work with 1.4.0 (yet)

        $meta['capabilities'] = array();
        $meta['capabilities'][HookUtil::PROVIDER_CAPABLE] = array('enabled' => true);
        $meta['capabilities'][HookUtil::SUBSCRIBER_CAPABLE] = array('enabled' => true);

        return $meta;
    }

    /**
     * Set up hook subscriber and provider bundles 
     */
    protected function setupHookBundles()
    {
        $bundle = new Zikula_HookManager_ProviderBundle($this->name, 'provider.postcalendar.ui_hooks.event', 'ui_hooks', $this->__('PostCalendar Event Maker'));
        $bundle->addServiceHandler('display_view', 'PostCalendar_HookHandlers', 'uiView', 'postcalendar.service');
        $bundle->addServiceHandler('form_edit', 'PostCalendar_HookHandlers', 'uiEdit', 'postcalendar.service');
        $bundle->addServiceHandler('form_delete', 'PostCalendar_HookHandlers', 'uiDelete', 'postcalendar.service');
        $bundle->addServiceHandler('validate_edit', 'PostCalendar_HookHandlers', 'validateEdit', 'postcalendar.service');
        $bundle->addServiceHandler('validate_delete', 'PostCalendar_HookHandlers', 'validateDelete', 'postcalendar.service');
        $bundle->addServiceHandler('process_edit', 'PostCalendar_HookHandlers', 'processEdit', 'postcalendar.service');
        $bundle->addServiceHandler('process_delete', 'PostCalendar_HookHandlers', 'processDelete', 'postcalendar.service');
        $this->registerHookProviderBundle($bundle);

        $bundle = new Zikula_HookManager_SubscriberBundle($this->name, 'subscriber.postcalendar.ui_hooks.events', 'ui_hooks', $this->__('PostCalendar Events'));
        $bundle->addEvent('display_view', 'postcalendar.ui_hooks.events.ui_view');
        $bundle->addEvent('form_edit', 'postcalendar.ui_hooks.events.ui_edit');
        $bundle->addEvent('form_delete', 'postcalendar.ui_hooks.events.ui_delete');
        $bundle->addEvent('validate_edit', 'postcalendar.ui_hooks.events.validate_edit');
        $bundle->addEvent('validate_delete', 'postcalendar.ui_hooks.events.validate_delete');
        $bundle->addEvent('process_edit', 'postcalendar.ui_hooks.events.process_edit');
        $bundle->addEvent('process_delete', 'postcalendar.ui_hooks.events.process_delete');
        $this->registerHookSubscriberBundle($bundle);

        $bundle = new Zikula_HookManager_SubscriberBundle($this->name, 'subscriber.postcalendar.filter_hooks.eventcontent', 'filter_hooks', $this->__('PostCalendar Event Filters'));
        $bundle->addEvent('filter', 'postcalendar.filter_hooks.eventsfilter.filter');
        $this->registerHookSubscriberBundle($bundle);
    }

}