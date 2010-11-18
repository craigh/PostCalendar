{* $Id: postcalendar_search_options.htm 596 2010-06-05 01:14:46Z craigh $ *}
<div>
	<input type="checkbox" id="active_postcalendar" name="active[PostCalendar]" value="1"{if $active} checked="checked"{/if} />
	<label for="active_postcalendar">{gt text="Calendar"}</label>
    <label for="modvar_PostCalendar____CATEGORIES____{$firstprop}_">&nbsp;{gt text='in Categories'}:&nbsp;</label>
    {gt text="All These Categories" assign="allText"}
    {nocache}
    {foreach from=$catregistry key=property item=category}
        <span>{selector_category 
        editLink=0 
        category=$category 
        name="modvar[PostCalendar][__CATEGORIES__][$property]" 
        field="id" 
        defaultValue="0"
        all=1
        allText=$allText
        allValue=0}</span>
    {/foreach}
    {/nocache}
    &nbsp;{gt text="date range:"}
	<label for="modvar_PostCalendar_searchstart">&nbsp;{gt text="from"}</label>
    <select id="modvar_PostCalendar_searchstart" name="modvar[PostCalendar][searchstart]">
        <option value="-10">{gt text='last %s years' tag1='10'}</option>
        <option value="-5">{gt text='last %s years' tag1='5'}</option>
        <option value="-2">{gt text='last %s years' tag1='2'}</option>
        <option value="-1">{gt text='last year'}</option>
        <option value="0" selected="selected">{gt text='now'}</option>
    </select>
	<label for="modvar_PostCalendar_searchend">&nbsp;{gt text="to"}</label>
    <select id="modvar_PostCalendar_searchend" name="modvar[PostCalendar][searchend]">
        <option value="0">{gt text='now'}</option>
        <option value="1">{gt text='next year'}</option>
        <option value="2" selected="selected">{gt text='next %s years' tag1='2'}</option>
        <option value="5">{gt text='next %s years' tag1='5'}</option>
        <option value="10">{gt text='next %s years' tag1='10'}</option>
    </select>
</div>