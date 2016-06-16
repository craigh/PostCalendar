{pageaddvar name='stylesheet' value='@ZikulaPostCalendarModule/Resources/public/css/style.css'}
{pc_queued_events_notify}
{pc_pagejs_init}
<div class="postcalendar_block_view_upcoming">
{counter start=0 assign=eventcount}
{pc_sort_events var="sortedEvents" sort="time" order="asc" value=$eventsByDate}
{foreach name='dates' item='events' key='date' from=$sortedEvents}
    {foreach name='eventloop' key='id' item='event' from=$events}
        {if $eventcount < $displayLimit}
            {if $event.alldayevent != true}
                {assign var="timestamp" value=$event.startTime}
            {else}
                {assign var="timestamp" value=""}
            {/if}
            <ul class="pc_blocklist">
                {if $smarty.foreach.eventloop.iteration eq 1}
                    <li class="pc_blockdate">
                        {$date|pc_date_format}
                    </li>
                {/if}
                <li class="pc_blockevent">
                    {gt text='private event' assign='p_txt' domain='module_postcalendar'}
                    {if $event.privateicon}{img src='lock.gif' modname='ZikulaPostCalendarModule' title=$p_txt alt=$p_txt}{/if}
                    {pc_url full=true class="eventlink" action="event" eid=$event.eid date=$date title=$event.hometext|notifyfilters:'postcalendar.filter_hooks.eventsfilter.filter'|safehtml display="$timestamp `$event.title`"|strip_tags}
                    {if $event.alldayevent != true}&nbsp;({gt text='until' domain="module_postcalendar"} {$event.endTime}){/if}
                </li>
            </ul>
        {/if}
    {counter}
    {/foreach}
{/foreach}

{if $eventcount == 0}
    {gt text='No upcoming events.' domain="module_postcalendar"}
{/if}
</div>