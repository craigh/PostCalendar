<div class="z-formrow">
	<label for="pcbshowcalendar">{gt text="Display the calendar in the block"}</label>
	<input id="pcbshowcalendar" type="checkbox" {if $vars.pcbshowcalendar eq 1}checked="checked" {/if}value="1" name="pcbshowcalendar" />
</div>
<div class="z-formrow">
	<label for="pcbeventoverview">{gt text="Display today's events in the block"}</label>
	<input id="pcbeventoverview" type="checkbox" {if $vars.pcbeventoverview eq 1}checked="checked" {/if}value="1" name="pcbeventoverview" />
</div>
<div class="z-formrow">
	<label for="pcbhideeventoverview">{gt text="Hide today's events if none"}</label>
	<input id="pcbhideeventoverview" type="checkbox" {if $vars.pcbhideeventoverview eq 1}checked="checked" {/if}value="1" name="pcbhideeventoverview" />
</div>
<div class="z-formrow">
	<label for="pcbnextevents">{gt text="Display upcoming events in the block"}</label>
	<input id="pcbnextevents" type="checkbox" {if $vars.pcbnextevents eq 1}checked="checked" {/if}value="1" name="pcbnextevents" />
</div>
<div class="z-formrow">
    <label for="pcbfiltercats_{$firstprop}_">{gt text='Only display events in category(s)'}</label>
    {gt text="All Categories" assign="allText"}
    {nocache}
    <span>{foreach from=$catregistry key=property item=category}
        {array_field_isset assign="selectedValue" array=$vars.pcbfiltercats field=$property returnValue=1}
        {selector_category 
            editLink=false 
            category=$category 
            name="pcbfiltercats[$property]" 
            field="id" 
            selectedValue=$selectedValue 
            defaultValue="0"
            all=1
            allText=$allText
            allValue=0
            multipleSize=6}
        {/foreach}
    </span>
    {/nocache}
</div>
<div class="z-formrow">
	<label for="pcbshowsslinks">{gt text="Display search/submit links in the block"}</label>
	<input id="pcbshowsslinks" type="checkbox" {if $vars.pcbshowsslinks eq 1}checked="checked" {/if}value="1" name="pcbshowsslinks" />
</div>
<div class="z-formrow">
	<label for="pcbeventslimit">{gt text="Number of events to display"}</label>
	<input id="pcbeventslimit" type="text" maxlength="64" size="5"  value="{$vars.pcbeventslimit}" name="pcbeventslimit" />
</div>
<div class="z-formrow">
	<label for="pcbeventsrange">{gt text="Number of months ahead to query for upcoming events"}</label>
	<input id="pcbeventsrange" type="text" maxlength="64" size="5"  value="{$vars.pcbeventsrange}" name="pcbeventsrange" />
</div>