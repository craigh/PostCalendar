{* $Id: postcalendar_block_pastevents_modify.htm 596 2010-06-05 01:14:46Z craigh $ *}
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
	<label for="pcbeventsrange">{gt text="Number of months past to query for events"}</label>
	<input id="pcbeventsrange" type="text" maxlength="64" size="5"  value="{$vars.pcbeventsrange}" name="pcbeventsrange" />
    <div class="z-formnote">{gt text="Set to '0' for all events."}</div>
</div>