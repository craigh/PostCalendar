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
    {$requestedDate->format($modvars.PostCalendar.pcDateFormats.date)}
    {if $PRINT_VIEW eq false}<a href="{$navigation.next|safehtml}">&gt;&gt;</a>{/if}
</h2>

<div class="calcontainer">
    <ul class="eventslist">
        {pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$eventsByDate}
        {foreach name='dates' item='events' key='date' from=$S_EVENTS}
            {if isset($events)}
                {foreach name='events' item='event' from=$S_EVENTS.$date}
                    <li class="eventslistitems pccategories_{$event.catid}{if $event.privateicon} pcviz_private{else} pcviz_global{/if}">
                        {if $event.alldayevent != true}{$event.startTime} - {$event.endTime}{else}{gt text='All-day event'}{/if}&nbsp;
                        {if $event.privateicon}{img src='lock.gif' modname='PostCalendar' __title="private event" __alt="private event"}{/if}
                        {pc_url full=true action='event' eid=$event.eid date=$date style="text-decoration: none;" title=$event.hometext|notifyfilters:'postcalendar.hook.eventsfilter.ui.filter'|strip_tags|safehtml display=$event.title|strip_tags}
                    </li>
                {foreachelse}
                    <li><b>{gt text='No events scheduled.'}</b></li>
                {/foreach}{* /foreach events *}
            {/if}{* /if events *}
        {/foreach}{* /foreach dates *}
    </ul>
</div><!-- end calcontainer -->
<div style='text-align:right;'>
    {if $PRINT_VIEW eq true}
        {assign var="viewtype" value=$smarty.get.viewtype}
        {if ((empty($smarty.get.viewtype)) or (!isset($smarty.get.viewtype)))}
            {assign var="viewtype" value=$modvars.PostCalendar.pcDefaultView}
        {/if}
        {formutil_getpassedvalue name="date" source="get" assign="date" default=''}
        <a href="{modurl modname="PostCalendar" type='user' func='display' viewtype=$viewtype Date=$date}">{gt text='Return'}</a>
    {/if}
</div>
{include file="user/footer.tpl"}