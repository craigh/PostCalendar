{if !$hideTodaysEvents}
{pc_pagejs_init}
<div class="postcalendar_block_view_day">
{if $blockVars.pcbshowcalendar eq 1}
    <h2 class='postcalendar_block_innertitle'>{gt text="Today's events"}</h2>
{/if}
{assign var="eventtotal" value=0}
{assign var="eventcount" value=0}
    <ul class="pc_blocklist">
        <li class="pc_blockdate">
            {$todayDate|pc_date_format}
        </li>
        {foreach name='eventloop' key='id' item='event' from=$todaysEvents}
            {assign var="eventtotal" value=$eventtotal+1}
            {if $eventcount < $blockVars.pcbeventslimit}
                {assign var="eventcount" value=$eventcount+1}
                {if $event.alldayevent != true}
                    {assign var="timestamp" value=$event.startTime}
                {else}
                    {assign var="timestamp" value=""}
                {/if}
                <li class="pc_blockevent">
                    {gt text='private event' assign='p_txt'}
                    {if $event.privateicon}{img src='lock.gif' modname='PostCalendar' title=$p_txt alt=$p_txt}{/if}
                    {pc_url full=true class="eventlink" action="event" eid=$event.eid date=$event.eventStart title=$event.hometext|notifyfilters:'postcalendar.filter_hooks.eventsfilter.filter'|strip_tags|safehtml display="$timestamp `$event.title`"|strip_tags}
                    {if $event.alldayevent != true}&nbsp;({gt text='until'} {$event.endTime}){/if}
                </li>
            {/if}
        {foreachelse}
            {assign var="eventtotal" value="0"}
            <li class="pc_blockevent">{gt text='No events scheduled.'}</li>
        {/foreach}
    </ul>
{if ($eventtotal > $blockVars.pcbeventslimit)}
  <a href="{pc_url action='day' eid=$event.eid date=$todayDate}">{$eventtotal}&nbsp{gt text="total events to view"}</a>
{/if}
</div>
{/if}