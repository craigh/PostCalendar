{if $postcalendar_hide}
    <input type="hidden" value="1" name='postcalendar[optin]' />
    <input type="hidden" value="{$postcalendar_admincatselected|safetext}" name='postcalendar[cats]' />
{else}
<fieldset>
    <legend>{gt text='Associated PostCalendar event' domain="module_postcalendar"}</legend>
    {if $postcalendar_optoverride}
    <div class="z-formrow">
        <label for="postcalendar_optin">{gt text="Create associated PostCalendar event (opt in)" domain="module_postcalendar"}</label>
        <input type="checkbox" value="1" id='postcalendar_optin' name='postcalendar[optin]' checked="checked" />
    </div>
    {else}
        <input type="hidden" value="1" name='postcalendar[optin]' />
    {/if}
    {if isset($postcalendar_catregistry)}
    <div class="z-formrow">
        <label for="postcalendar_cats">{gt text="Assign to PostCalendar categories:" domain="module_postcalendar"}</label>
        {nocache}
        <span>{foreach from=$postcalendar_catregistry key='property' item='category'}
            {array_field assign="selectedValue" array=$postcalendar_selectedcategories field=$property}
            {selector_category
                editLink=false
                category=$category
                name="postcalendar[cats][$property]"
                field="id"
                selectedValue=$selectedValue}
            {/foreach}</span>
        {/nocache}
    </div>
    {else}
        <input type="hidden" value="{$postcalendar_admincatselected|safetext}" name='postcalendar[cats]' />
    {/if}
    <input type="hidden" value="{$postcalendar_eid}" name="postcalendar[eid]" />
</fieldset>
{/if}