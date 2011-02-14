{if !$hideTodaysEvents}
{ajaxheader module="PostCalendar" ui=true}
{pc_pagejs_init}
<div class="postcalendar_block_view_day">
{if $SHOW_TITLE eq 1}
    <h2 class='postcalendar_block_innertitle'>{gt text="Today's events"}</h2>
{/if}
{assign var="eventtotal" value=0}
{assign var="eventcount" value=0}
    <ul class="pc_blocklist">
        <li class="pc_blockdate">
            {$TODAY_DATE|pc_date_format}
        </li>
        {foreach name=eventloop key=id item=event from=$todaysEvents}
            {assign var="eventtotal" value=$eventtotal+1}
            {if $eventcount < $DISPLAY_LIMIT}
                {assign var="eventcount" value=$eventcount+1}
                {if $event.alldayevent != true}
                    {assign var="timestamp" value=$event.startTime}
                {else}
                    {assign var="timestamp" value=""}
                {/if}
                <li class="pc_blockevent">
                    {gt text='private event' assign='p_txt'}
                    {if $event.privateicon}{img src='locked.png' modname='core' set='icons/extrasmall' title=$p_txt alt=$p_txt}{/if}
                    {pc_url full=true class="eventlink" action="detail" eid=$event.eid date=$date title=$event.hometext|notifyfilters:'postcalendar.hook.eventsfilter.ui.filter'|safehtml display="$timestamp `$event.title`"|strip_tags}
                    {if $event.alldayevent != true}&nbsp;({gt text='until'} {$event.endTime}){/if}
                    {if $event.commentcount gt 0}
                        {gt text='%s comment left' plural='%s comments left.' count=$event.commentcount tag1=$event.commentcount assign="title"}
                        <a href="{modurl modname='PostCalendar' func='main' viewtype='details' eid=$event.eid}#comments" title='{$title}'>
                        {gt text='Comment' assign='alt'}                        
                        {img modname=core src=comment.png set=icons/extrasmall alt=$alt title=$title}</a>
                    {/if}
                </li>
            {/if}
        {foreachelse}
                {assign var="eventtotal" value="0"}
                    <li class="pc_blockevent">{gt text='No events scheduled.'}</li>
        {/foreach}
    </ul>
{if ($eventtotal > $DISPLAY_LIMIT)}
  <a href="{pc_url action=day eid=$event.eid date=$TODAY_DATE}">{$eventtotal}&nbsp{$EVENTS_TOTAL_LINK}</a>
{/if}
</div>
{/if}