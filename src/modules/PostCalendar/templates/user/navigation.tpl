{nocache}{ajaxheader module="PostCalendar" ui=true}{pc_pagejs_init}{/nocache}
{pc_queued_events_notify}
<form action="{modurl modname='PostCalendar' type='user' func='display'}" id='pcnav-form' method="get" enctype="application/x-www-form-urlencoded">
<div class="z-clearfix">
    {if $navigationObj->useNavBar}
    <div id="postcalendar_nav_right">
        {if $navigationObj->navBarType == 'buttonbar'}
        <div id="pcnav-buttonbar">
            {assign value='overcast' var='jquerytheme'}
            {jquery_datepicker defaultdate=$navigationObj->requestedDate displayelement='pcnav_datepicker' valuestorageelement='date' valuestorageformat='Ymd' theme=$jquerytheme submitonselect=true}
            <input id="pcnav_datepicker_button" type="image" alt="jump" title='jump to date' src='modules/PostCalendar/images/icon-calendar.jpg' />
            {foreach from=$navigationObj->getNavItems() item='navItem'}
                {$navItem->renderRadio()}
            {/foreach}
        </div>
        {else}
        <ul>
            {foreach from=$navigationObj->getNavItems() item='navItem'}
            <li>{$navItem->renderAnchorTag()|safehtml}</li>
            {/foreach}    
        </ul>
        {/if}
    </div>
    {/if}
    {if $navigationObj->useFilter || $navigationObj->useJumpDate}
    <div id="postcalendar_nav_left">
        <ul>
            {if $navigationObj->useFilter}
            {gt text="Filter" assign="lbltxt"}
            <li>{pc_filter label=$lbltxt userfilter=$navigationObj->getUserFilter() selectedCategories=$navigationObj->getSelectedCategories()}</li>
            {/if}
            {if $navigationObj->useJumpDate}
            <li>
                {pc_html_select_date time=$navigationObj->requestedDate->format('Y-m-d') prefix="jump" start_year="-"|cat:$modvars.PostCalendar.pcFilterYearStart end_year="+"|cat:$modvars.PostCalendar.pcFilterYearEnd day_format="%d" day_value_format="%02d" month_format='%B' field_order=$modvars.PostCalendar.pcEventDateFormat}
                {if !empty($navigationObj->viewtypeselector)}
                    {html_options name='_viewtype' options=$navigationObj->viewtypeselector selected=$navigationObj->getViewtype()}
                {/if}
                <input type="submit" name="pc_submit" value="{gt text="Jump"}" />
            </li>
            {/if}
        </ul>
    </div>
    {/if}
</div>
</form>
<div>{insert name="getstatusmsg"}</div>