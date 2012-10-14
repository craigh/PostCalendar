{checkpermission component="PostCalendar::" instance="::" level="ACCESS_ADD" assign="ACCESS_ADD"}
{formutil_getpassedvalue name="theme" source="get" assign="theme" default=false}
{assign var="PRINT_VIEW" value=0}
{if $theme eq "Printer"}
    {* page presented in printer theme *}
    {assign var="ACCESS_ADD" value=0}
    {assign var="PRINT_VIEW" value=1}
{/if}
{if $PRINT_VIEW eq false}
{$navBar}
{/if}
<h2 class="postcalendar_header">
    {if $PRINT_VIEW eq false}<a href="{$navigation.previous|safehtml}">&lt;&lt;</a>{/if}
    {$requestedDate|pc_date_format:'F Y'}
    {if $PRINT_VIEW eq false}<a href="{$navigation.next|safehtml}">&gt;&gt;</a>{/if}
</h2>

<div class="calcontainer">
<table class="postcalendar_month">
    {foreach from=$dayDisplay.colclass item='colclassname'}
    <col class='{$colclassname}' />
    {/foreach}
    <tr class="daynames">
        {foreach from=$dayDisplay.long item='day'}
        <td>{$day}</td>
        {/foreach}
    </tr>
    {* CREATE THE CALENDAR *}
    {foreach name='weeks' item='days' from=$graph}
    <tr>
        {foreach name='days' item='date' from=$days}
        {if $date == $todayDate}
            {assign var="stylesheet" value="monthtoday"}
        {elseif ($date < $firstDayOfMonth || $date > $lastDayOfMonth)}
            {assign var="stylesheet" value="monthoff"}
        {else}
            {assign var="stylesheet" value="monthon"}
        {/if}
        <td class="{$stylesheet}">
            <div class="monthview_daterow">
                <span class="date_number"><a href="{pc_url action=day date=$date}">{$date|date_format:"%e"}</a>
                    {if $smarty.foreach.days.iteration == 1}
                    <a href="{pc_url action='week' date=$date}">[{gt text="week"}]</a>
                    {/if}
                </span>
                {if ($ACCESS_ADD eq true) && ($PRINT_VIEW eq false)}
                <span class="new_icon"><a href="{pc_url action='submit' date=$date}">{img modname='PostCalendar' src='new.gif'}</a></span>
                {/if}
            </div>
            <div class="monthview_events">
                {pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$eventsByDate}
                {if isset($S_EVENTS)}
                {foreach name='events' item='event' from=$S_EVENTS.$date}
                    <div class='pccategories_{$event.catid}{if $event.privateicon} pcviz_private{else} pcviz_global{/if}'>
                    {assign var="title" value=$event.title|strip_tags}
                    {if $event.alldayevent != true}
                        {assign var="timestamp" value=$event.startTime}
                    {else}
                        {assign var="timestamp" value=""}
                        &bull;
                    {/if}
                    {assign var="desc" value=$event.hometext|notifyfilters:'postcalendar.hook.eventsfilter.ui.filter'|safehtml|truncate:255:"..."}
                    {if $event.privateicon}{img src='lock.gif' modname='PostCalendar' __title="private event" __alt="private event"}{/if}
                    {pc_url full=true action='event' eid=$event.eid date=$date style="font-size: 7pt; text-decoration: none;" title=$event.hometext|notifyfilters:'postcalendar.hook.eventsfilter.ui.filter'|strip_tags|safehtml display="$timestamp $title"|safehtml}
                    {if $event.commentcount gt 0}
                        {gt text='%s comment left' plural='%s comments left.' count=$event.commentcount tag1=$event.commentcount domain="module_postcalendar" assign="title"}
                        <a href="{modurl modname='PostCalendar' type='user' func='display' viewtype='event' eid=$event.eid}#comments" title='{$title}'>
                        {img modname='core' src='comment.png' set='icons/extrasmall' __alt="Comment" title=$title}</a>
                    {/if}
                    </div>
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
            {assign var="viewtype" value=$modvars.PostCalendar.pcDefaultView}
        {/if}
        {formutil_getpassedvalue name="date" source="get" assign="date" default=''}
        <a href="{modurl modname="PostCalendar" type='user' func='display' viewtype=$viewtype Date=$date}">{gt text='Return'}</a>
    {/if}
</div>
{include file="user/footer.tpl"}