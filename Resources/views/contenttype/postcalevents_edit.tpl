<div id="category" class="z-formrow">
    {formlabel for='category' __text='Only display events in category(s)'}
    {nocache}
    {foreach from=$catregistry key='prop' item='cat'}
        <div class="z-formnote">
            {formcategoryselector 
                category=$cat 
                id="category__$prop" 
                group='data' 
                selectedValue=$data.category__$prop 
                selectionMode='multiple' 
                size='5'}
        </div>
    {/foreach}
    {/nocache}
</div>
<div class="z-formrow">
    {formlabel for='pcbeventslimit' __text='Number of events to display'}
    {formtextinput id='pcbeventslimit' group='data' maxLength=64 mandatory=true}
</div>
<div class="z-formrow">
    {formlabel for='pcbeventsrange' __text='Number of months ahead to query for upcoming events'}
    {formtextinput id='pcbeventsrange' group='data' maxLength=64 mandatory=true}
</div>