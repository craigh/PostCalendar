{if $smarty.get.viewtype != 'year'}
<div style='float:right;padding-top:3px;'>
    <button id='pcViewCatLegendButton'>{gt text='View category legend'}</button>
    <div id='pcCategoryLegend' style='display:none;'>
        <h5>{gt text='Categories'}</h5>
        <ul>
        {foreach from=$pcCategories key='regname' item='categories'}
            {foreach from=$categories item='category'}
            <li class='pccategories_selector_{$category.id}'>{$category.display_name.$lang}</li>
            {/foreach}
        {/foreach}
        </ul>
    </div>
</div>
{/if}
<div id="postcalendar_footer">
    {img modname='PostCalendar' src='smallcalicon.jpg' __alt="PostCalendar" __title="PostCalendar"}
    <a href="https://github.com/craigh/PostCalendar/wiki" title="{gt text='PostCalendar'}">{gt text='PostCalendar'} v{$modinfo.version}</a>
</div>