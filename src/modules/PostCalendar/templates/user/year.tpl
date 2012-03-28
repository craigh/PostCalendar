{formutil_getpassedvalue name="theme" source="get" assign="theme" default=false}
{assign var="PRINT_VIEW" value=0}
{if $theme eq "Printer"}
	{* page presented in printer theme *}
	{assign var="PRINT_VIEW" value=1}
{/if}
{if $PRINT_VIEW != 1}
{$navBar}
{/if}
<h2 class="postcalendar_header">
    {if $PRINT_VIEW eq false}<a href="{$navigation.previous|safehtml}">&lt;&lt;</a>{/if}
    {$requestedDate|pc_date_format:'Y'}
    {if $PRINT_VIEW eq false}<a href="{$navigation.next|safehtml}">&gt;&gt;</a>{/if}
</h2>

<div class="calcontainer">
	<table id="postcalendar_yearview" style="border-collapse:separate;">  
		{* Loop through each month of the year *}
		{foreach name='months' item='month' key='monthnum' from=$graph}
		{* check to see if we're starting a new row *}
		{if $smarty.foreach.months.iteration %4 eq 1 }
		<tr>
		{/if}
		<td class="postcalendar_yearview_panel">
		{* get the current year we're viewing *}
		{assign var="y" value=$requestedDate|date_format:"%Y"}
		{* figure out what month we are in *}
		{assign var="m" value=$monthnum+1}
		{* make sure we have a preceeding 0 on the month number *}
		{assign var="m" value=$m|string_format:"%02d"}
		{* create our link to the month *}
		<table class="smallmonthtable">
            <col class='weeklink' />
            {foreach from=$dayDisplay.colclass item='colclassname'}
            <col class='{$colclassname}' />
            {/foreach}
			<tr>
				<td class="monthheader" colspan="8">
					<a href="{pc_url action="month" date="$y-$m-01"}">{$monthNames.$monthnum}</a>
				</td>
			</tr>
			<tr class="daynames">
				<td>&nbsp;</td>
				{foreach name='daynames' item='day' from=$dayDisplay.short}
				<td>{$day}</td>
				{/foreach}
			</tr>
			{foreach name='weeks' item='days' from=$month}
			<tr>
				<td>
                    {if isset($days[0])}
					<a href="{pc_url action="week" date=$days[0]}">&gt;</a>
                    {else}
					<a href="{pc_url action="week" date="$y-$m-01"}">&gt;</a>
                    {/if}
				</td>
				{foreach name='day' item='date' from=$days}
                {if !isset($date)}
                <td class='monthoff'></td>
                {else}
                {assign var="themonth" value=$date|date_format:"%m"}
                <td class="monthon">
                    {assign var="classname" value="event-none"}						 		   
                    {if (isset($eventsByDate.$date))}
                        {if $eventsByDate.$date|@count > 2 }
                            {assign var="classname" value="event-three"}
                        {elseif $eventsByDate.$date|@count > 1 }
                            {assign var="classname" value="event-two"}
                        {elseif $eventsByDate.$date|@count > 0 }
                            {assign var="classname" value="event-one"}
                        {/if} 																 		   
                    {/if}
                    {pc_url full=true class=$classname action='day' date=$date title=$eventsByDate.$date|@count display=$date|date_format:"%d"}
                </td>
                {/if}
				{/foreach}{* foreach name=day *}
			</tr>
			{/foreach}{* foreach name=weeks *}
		</table>
		</td>
		{if $smarty.foreach.months.iteration %4 eq 0 }
		</tr>
		{/if}
		{/foreach}{* foreach name=months *}
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