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
    {$startDate|pc_date_format} - {$endDate|pc_date_format}
    {if $PRINT_VIEW eq false}<a href="{$navigation.next|safehtml}">&gt;&gt;</a>{/if}
</h2>

<div class="calcontainer">
    {* Loop through the EventsByDate array : This array contains data for each day in the view. *}
    {pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$eventsByDate}
    {assign var="javascript" value=""}
    {foreach name='dates' item='events' key='cdate' from=$S_EVENTS}
        <h3 class="dayheader">
            <a href="{pc_url action='day' date=$cdate}">{$cdate|pc_date_format}</a>
        </h3>
        {* Loop through the events for this day and display the event data *}
        <ul class="eventslist">
            {foreach name='eventloop' key='id' item='event' from=$S_EVENTS.$cdate}
                <li class="eventslistitems pccategories_{$event.catid}{if $event.privateicon} pcviz_private{else} pcviz_global{/if}">
                    {if $event.alldayevent != true}{$event.startTime} - {$event.endTime}{else}{gt text='All-day event'}{/if}&nbsp;
                    {if $event.privateicon}{img src='lock.gif' modname='PostCalendar' __title="private event" __alt="private event"}{/if}
                    {pc_url full=true action='event' eid=$event.eid date=$cdate display=$event.title|strip_tags title=$event.hometext|notifyfilters:'postcalendar.hook.eventsfilter.ui.filter'|safehtml}
                    {if $event.commentcount gt 0}
                        {gt text='%s comment left' plural='%s comments left.' count=$event.commentcount tag1=$event.commentcount domain="module_postcalendar" assign="title"}
                        <a href="{modurl modname='PostCalendar' type='user' func='display' viewtype='event' eid=$event.eid}#comments" title='{$title}'>
                        {img modname='core' src='comment.png' set='icons/extrasmall' __alt="Comment" title=$title}</a>
                    {/if}
                </li>
            {foreachelse}
                <li class="eventslistitems">&nbsp;</li>
            {/foreach}
        </ul>
    {/foreach}
</div><!-- end calcontainer -->
<div style='text-align:right;'>
    {if $PRINT_VIEW eq true}
        {assign var="viewtype" value=$smarty.get.viewtype}
        {if ((empty($smarty.get.viewtype)) or (!isset($smarty.get.viewtype)))}
            {assign var="viewtype" value=$modvars.PostCalendar.pcDefaultView}
        {/if}
        <a href="{modurl modname="PostCalendar" type='user' func='display' viewtype=$viewtype date=$requestedDate}">{gt text='Return'}</a>
    {/if}
</div>
{include file="user/footer.tpl"}