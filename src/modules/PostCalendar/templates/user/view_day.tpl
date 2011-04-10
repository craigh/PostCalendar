{checkpermission component="::" instance=".*" level="ACCESS_ADD" assign="ACCESS_ADD"}
{formutil_getpassedvalue name="theme" source="get" assign="theme" default=false}
{assign var="PRINT_VIEW" value=0}
{if $theme eq "Printer"}
    {* page presented in printer theme *}
    {assign var="PRINT_VIEW" value=1}
{/if}
{if $PRINT_VIEW eq false}
{include file="user/navigation.tpl"}
{/if}

{pc_pagejs_init}

<h2 class="postcalendar_header">
    {if $PRINT_VIEW eq false}<a href="{$PREV_DAY_URL}">&lt;&lt;</a>{/if}
    {$DATE|pc_date_format}
    {if $PRINT_VIEW eq false}<a href="{$NEXT_DAY_URL}">&gt;&gt;</a>{/if}
</h2>

<div class="calcontainer">
    <ul class="eventslist">
        {pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$A_EVENTS}
        {foreach name='dates' item='events' key='date' from=$S_EVENTS}
            {pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$A_EVENTS}
            {if isset($events)}
                {foreach name='events' item='event' from=$S_EVENTS.$date}
                    <li class="eventslistitems">
                        <span style="padding: 1px 4px; color: {$event.cattextcolor}; background: {$event.catcolor};">{$event.catname}</span>
                        {if $event.alldayevent != true}{$event.startTime} - {$event.endTime}{else}{gt text='All-day event'}{/if}&nbsp;
                        {if $event.privateicon}{img src='locked.png' modname='core' set='icons/extrasmall' __title="private event" __alt="private event"}{/if}
                        {pc_url full=true action='detail' eid=$event.eid date=$date style="text-decoration: none;" display=$event.title|strip_tags}
                        {if $event.commentcount gt 0}
                            {gt text='%s comment left' plural='%s comments left.' count=$event.commentcount tag1=$event.commentcount domain="module_postcalendar" assign="title"}
                            <a href="{modurl modname='PostCalendar' type='user' func='display' viewtype='details' eid=$event.eid}#comments" title='{$title}'>
                            {img modname='core' src='comment.png' set='icons/extrasmall' __alt="Comment" title=$title}</a>
                        {/if}
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
        {formutil_getpassedvalue name="Date" source="get" assign="Date" default=''}
        <a href="{modurl modname="PostCalendar" type='user' func='display' viewtype=$viewtype Date=$Date}">{gt text='Return'}</a>
    {/if}
</div>
{include file="user/footer.tpl"}