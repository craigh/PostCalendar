{checkpermission component="::" instance=".*" level="ACCESS_ADD" assign="ACCESS_ADD"}
{modgetvar module='PostCalendar' name='pcEventsOpenInNewWindow' assign='in_new_window'}
{formutil_getpassedvalue name="theme" source="get" assign="theme" default=false}
{assign var="PRINT_VIEW" value=0}
{if $theme eq "Printer"}
    {* page presented in printer theme *}
    {assign var="ACCESS_ADD" value=0}
    {assign var="PRINT_VIEW" value=1}
{/if}
{if $PRINT_VIEW eq false}
{include file="user/navigation.tpl"}
{/if}
{ajaxheader module="PostCalendar" ui=true}
{pc_pagejs_init}
<h2 class="postcalendar_header">
    {if $PRINT_VIEW eq false}<a href="{$PREV_MONTH_URL}">&lt;&lt;</a>{/if}
    {$DATE|pc_date_format:'%B %Y'}
    {if $PRINT_VIEW eq false}<a href="{$NEXT_MONTH_URL}">&gt;&gt;</a>{/if}
</h2>

<div class="calcontainer">
<table class="postcalendar_month">
    {foreach from=$pc_colclasses item=colclassname}
    <col class='{$colclassname}' />
    {/foreach}
    <tr class="daynames">
        {foreach from=$S_LONG_DAY_NAMES item=day}
        <td>{$day}</td>
        {/foreach}
    </tr>
    {* CREATE THE CALENDAR *}
    {foreach name=weeks item=days from=$CAL_FORMAT}
    <tr>
        {foreach name=days item=date from=$days}
        {if $date == $TODAY_DATE}
            {assign var="stylesheet" value="monthtoday"}
        {elseif ($date < $MONTH_START_DATE || $date > $MONTH_END_DATE)}
            {assign var="stylesheet" value="monthoff"}
        {else}
            {assign var="stylesheet" value="monthon"}
        {/if}
        <td class="{$stylesheet}"{if $in_new_window eq false} onclick="window.location.href='{pc_url action=day date=$date}';"{/if}>
            <div class="monthview_daterow">
                <span class="date_number"><a href="{pc_url action=day date=$date}">{$date|date_format:"%e"}</a>
                    {if $smarty.foreach.days.iteration == 1}
                    <a href="{pc_url action=week date=$date}">[{gt text="week"}]</a>
                    {/if}
                </span>
                {if ($ACCESS_ADD eq true) && ($PRINT_VIEW eq false)}
                <span class="new_icon"><a href="{pc_url action=submit date=$date}">{img src='new.gif'}</a></span>
                {/if}
            </div>
            <div class="monthview_events">
                {*sort the events by category so we can make it pretty*}
                {pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$A_EVENTS}
                {assign var="oldCat" value=""}
                {assign var="javascript" value=""}
                {if isset($S_EVENTS)}
                {foreach name=events item=event from=$S_EVENTS.$date}
                    {assign var="cCat" value=$event.catname}
                    {if $oldCat != $cCat}
                        {if $smarty.foreach.events.first != true}
                            </div>
                        {/if}
                        <div style="padding: 1px; color: {$event.cattextcolor}; background-color: {$event.catcolor};">
                            {$event.catname}
                        </div>
                        <div style="padding: 2px; border:solid 1px {$event.catcolor};">
                    {/if}
                    {assign var="title" value=$event.title|strip_tags}
                    {if $event.alldayevent != true}
                        {assign var="timestamp" value=$event.startTime}
                    {else}
                        {assign var="timestamp" value=""}
                    {/if}

                    {assign var="desc" value=$event.hometext|truncate:255:"..."}
                    {if $event.privateicon}{img src='locked.png' modname='core' set='icons/extrasmall' __title="private event" __alt="private event"}{/if}
                    {pc_url full=true action=detail eid=$event.eid date=$date style="font-size: 7pt; text-decoration: none;" title=$event.hometext|safetext display="$timestamp $title"|safehtml}
                    {if $event.commentcount gt 0}
                        {gt text='%s comment left' plural='%s comments left.' count=$event.commentcount tag1=$event.commentcount domain="module_postcalendar" assign="title"}
                        <a href="{modurl modname='PostCalendar' func='main' viewtype='details' eid=$event.eid}#comments" title='{$title}'>
                        {img modname=core src=comment.gif set=icons/extrasmall __alt="Comment" title=$title}</a>
                    {/if}
                    <br />
                    {assign var="oldCat" value=$event.catname}
                    {if $smarty.foreach.events.last}
                        </div>
                    {/if}
                {/foreach}
                {/if}
            </div>
        </td>
        {/foreach}
    </tr>
    {/foreach}
</table>
</div><!-- end calcontainer -->
<div style='text-align:right;'>
    {if $PRINT_VIEW eq true}
        {assign var="viewtype" value=$smarty.get.viewtype}
        {if ((empty($smarty.get.viewtype)) or (!isset($smarty.get.viewtype)))}
            {modgetvar module="PostCalendar" name="pcDefaultView" assign="viewtype"}
        {/if}
        {formutil_getpassedvalue name="Date" source="get" assign="Date" default=''}
        <a href="{modurl modname="PostCalendar" func="main" viewtype=$viewtype Date=$Date}">{gt text='Return'}</a>
    {/if}
</div>
{include file="user/footer.tpl"}