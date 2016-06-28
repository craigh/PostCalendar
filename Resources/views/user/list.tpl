{formutil_getpassedvalue name="theme" source="get" assign="theme" default=false}
{assign var="PRINT_VIEW" value=0}
{if $theme eq "Printer"}
    {* page presented in printer theme *}
    {assign var="PRINT_VIEW" value=1}
{/if}
{if $PRINT_VIEW eq false}
{$navBar}
{/if}
<h2 class="postcalendar_header">
    {if $PRINT_VIEW eq false}<a href="{$navigation.previous|safehtml}">&lt;&lt;</a>{/if}
    {gt text='Event list from'} <strong>{$startDate|pc_date_format:$modvars.ZikulaPostCalendarModule.pcDateFormats.date}</strong> {gt text='to'} <strong>{$endDate|pc_date_format:$modvars.ZikulaPostCalendarModule.pcDateFormats.date}</strong>
    {if $PRINT_VIEW eq false}<a href="{$navigation.next|safehtml}">&gt;&gt;</a>{/if}
</h2>

<div class="calcontainer">

    {pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$eventsByDate}
    {foreach name='dates' item='events' key='date' from=$S_EVENTS}

    {* CREATE THE LIST *}
    {*sort the events by category so we can make it pretty*}
    {if ((isset($S_EVENTS.$date)) && (count($S_EVENTS.$date) gt 0))}
    <ul class="eventslist " 
    {if $modvars.ZikulaPostCalendarModule.pcEventsOpenInNewWindow eq false}onclick="window.location.href='{pc_url action=day date=$date}';"{/if}>
    {foreach name='events' item='event' from=$S_EVENTS.$date}
        {if $smarty.foreach.events.first eq true}<li class="dayheader" style='margin-top: .5em;'>{$date|pc_date_format}</li>{/if}
        <li class="eventslistitems pccategories_{$event.catid}{if $event.privateicon} pcviz_private{else} pcviz_global{/if}">
            {if $event.alldayevent != true}{$event.startTime} - {$event.endTime}{else}{gt text='All-day event'}{/if}&nbsp;
            {if $event.privateicon}{img src='lock.gif' modname='ZikulaPostCalendarModule' __title="private event" __alt="private event"}{/if}
            {pc_url full=true action='event' eid=$event.eid date=$date style="text-decoration: none;" title=$event.hometext|notifyfilters:'postcalendar.filter_hooks.eventsfilter.filter'|strip_tags|safehtml display=$event.title|strip_tags}
        </li>
    {/foreach}
    </ul>
    {/if}{* end if isset($S_EVENTS) *}
    {/foreach}

    <div style='padding: .5em'>
        <a href="{$navigation.previous|safehtml}">{img src='previous.png' modname='core' set='icons/extrasmall' __title="previous list" __alt="previous list"}&nbsp;{gt text='previous list'}</a>
        &nbsp;::&nbsp;
        <a href="{$navigation.next|safehtml}">{gt text='next list'}&nbsp;{img src='forward.png' modname='core' set='icons/extrasmall' __title="next list" __alt="next list"}</a>
    </div>
</div><!-- end calcontainer -->
<div style='text-align:right;'>
    {if $PRINT_VIEW eq true}
        {assign var="viewtype" value=$smarty.get.viewtype}
        {if ((empty($smarty.get.viewtype)) or (!isset($smarty.get.viewtype)))}
            {assign var="viewtype" value=$modvars.ZikulaPostCalendarModule.pcDefaultView}
        {/if}
        {formutil_getpassedvalue name="date" source="get" assign="date" default=''}
        <a href="{modurl modname="ZikulaPostCalendarModule" type='user' func='display' viewtype=$viewtype Date=$date}">{gt text='Return'}</a>
    {/if}
</div>
{include file="user/footer.tpl"}
