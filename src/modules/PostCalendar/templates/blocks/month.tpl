{ajaxheader module="PostCalendar" ui=true}
{pc_pagejs_init}
<div class="postcalendar_block_view_month">
<table class="smallmonthtable">
    <col class='weeklink' />
    {foreach from=$dayDisplay.colclass item=colclassname}
    <col class='{$colclassname}' />
    {/foreach}
    <tr>
        <td class="monthheader" colspan="8">
            <a href="{$navigation.previous|safetext}">&lt;&lt;&nbsp;</a>
            <a href="{pc_url action="month" date=$requestedDate}">{$requestedDate|pc_date_format:'F Y'}</a>
            <a href="{$navigation.next|safetext}">&nbsp;&gt;&gt;</a>
        </td>
    </tr>
    <tr class="daynames">
        <td>&nbsp;</td>
        {foreach name='daynames' item='day' from=$dayDisplay.short}
        <td>{$day}</td>
        {/foreach}
    </tr>
    {foreach name='weeks' item='days' from=$graph}
    <tr>
        <td><a href="{pc_url action='week' date=$days[0]}">&gt;</a></td>
        {foreach name='day' item='date' from=$days}
        {if $date == $todayDate}
            {assign var="stylesheet" value="monthtoday"}
        {elseif ($date < $firstDayOfMonth || $date > $lastDayOfMonth)}
            {assign var="stylesheet" value="monthoff"}
        {else}
            {assign var="stylesheet" value="monthon"}
        {/if}
        <td class="{$stylesheet}">
            {assign var="titles" value=""}
            {assign var="numberofevents" value=$eventsByDate.$date|@count}
            {if $modvars.PostCalendar.pcUsePopups}
                {foreach name='events' item='event' from=$eventsByDate.$date}
                    {if $event.alldayevent != true}
                        {assign var="titles" value="$titles<b>`$event.startTime`-`$event.endTime`</b> `$event.title`<br /><br />"}
                    {else}
                        {assign var="titles" value="$titles`$event.title`<br /><br />"}
                    {/if}
                {/foreach}
            {else}
                {gt text="event" plural="events" count=$numberofevents assign="titlelabel"}
                {assign var="titles" value="`$numberofevents` `$titlelabel`"}
            {/if}
            {if $numberofevents}
                {if $numberofevents > 2}
                    {assign var="classname" value="event-three"}
                {elseif $numberofevents > 1}
                    {assign var="classname" value="event-two"}
                {elseif $numberofevents > 0}
                    {assign var="classname" value="event-one"}
                {else}
                    {assign var="classname" value="event-none"}
                {/if}
                {pc_url full=true class=$classname action="day" date=$date title=$titles|safetext display=$date|date_format:"%e"}
            {else}
                {pc_url full=true class="blockevent-none" action="day" date=$date display=$date|date_format:"%e"}
            {/if}
        </td>
        {/foreach}
    </tr>
    {/foreach}
</table>
</div>