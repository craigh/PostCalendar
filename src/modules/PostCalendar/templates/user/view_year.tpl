{checkpermission component="PostCalendar::" instance="::" level="ACCESS_ADD" assign="ACCESS_ADD"}
{formutil_getpassedvalue name="theme" source="get" assign="theme" default=false}
{assign var="PRINT_VIEW" value=0}
{if $theme eq "Printer"}
	{* page presented in printer theme *}
	{assign var="PRINT_VIEW" value=1}
{/if}
{if $PRINT_VIEW != 1}
{include file="user/navigation.tpl"}
{/if}
<h2 class="postcalendar_header">
    {if $PRINT_VIEW eq false}<a href="{$PREV_YEAR_URL}">&lt;&lt;</a>{/if}
    {$DATE|date_format:'%Y'}
    {if $PRINT_VIEW eq false}<a href="{$NEXT_YEAR_URL}">&gt;&gt;</a>{/if}
</h2>

{strip}
<div class="calcontainer">
	<table id="postcalendar_yearview" style="border-collapse:separate;">  
		{* Loop through each month of the year *}
		{foreach name='months' item='month' key='monthnum' from=$CAL_FORMAT}
		{* check to see if we're starting a new row *}
		{if $smarty.foreach.months.iteration %4 eq 1 }
		<tr>
		{/if}
		<td class="postcalendar_yearview_panel">
		{* get the current year we're viewing *}
		{assign var="y" value=$DATE|date_format:"%Y"}
		{* figure out what month we are in *}
		{assign var="m" value=$monthnum+1}
		{* make sure we have a preceeding 0 on the month number *}
		{assign var="m" value=$m|string_format:"%02d"}
		{* create our link to the month *}
		<table class="smallmonthtable">
            <col class='weeklink' />
            {foreach from=$pc_colclasses item='colclassname'}
            <col class='{$colclassname}' />
            {/foreach}
			<tr>
				<td class="monthheader" colspan="8">
					<a href="{pc_url action="month" date="$y-$m-01"}">{$A_MONTH_NAMES.$monthnum}</a>
				</td>
			</tr>
			<tr class="daynames">
				<td>&nbsp;</td>
				{foreach name='daynames' item='day' from=$S_SHORT_DAY_NAMES}
				<td>{$day}</td>
				{/foreach}
			</tr>
			{foreach name='weeks' item='days' from=$month}
			<tr>
				<td>
					<a href="{pc_url action="week" date=$days[0]}">&gt;</a>
				</td>
				{foreach name='day' item='date' from=$days}
    				{assign var="themonth" value=$date|date_format:"%m"}
    				{if $date == $TODAY_DATE && $themonth == $smarty.foreach.months.iteration}
    					{assign var="stylesheet" value="monthtoday"}
    				{elseif $themonth == $smarty.foreach.months.iteration}
    					{assign var="stylesheet" value="monthon"}
    				{else}
    					{assign var="stylesheet" value="monthoff"}
    				{/if}
    				<td class="{$stylesheet}">
    					{assign var="classname" value="event-none"}						 		   
    					{if (isset($A_EVENTS.$date))}
        					{if $A_EVENTS.$date|@count > 2 }
        						{assign var="classname" value="event-three"}
        					{elseif $A_EVENTS.$date|@count > 1 }
        						{assign var="classname" value="event-two"}
        					{elseif $A_EVENTS.$date|@count > 0 }
        						{assign var="classname" value="event-one"}
        					{/if} 																 		   
    					{/if}
    					{pc_url full=true class=$classname action='day' date=$date display=$date|date_format:"%d"}
                    </td>
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
{/strip}
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
