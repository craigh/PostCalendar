{nocache}{pc_pagejs_init type=$navigationObj->navBarType}{/nocache}
{pc_queued_events_notify}
<form action="{modurl modname='PostCalendar' type='user' func='display'}" id='pcnav-form' method="post" enctype="application/x-www-form-urlencoded">
<div class="z-clearfix">
    {if $navigationObj->navBarType == 'buttonbar'}
    <div id="pcnav">
        {if $navigationObj->useFilter}
        <input type='hidden' id='userfilter' name='userfilter' value='{$navigationObj->getUserFilter()}' />
        {checkpermissionblock component="PostCalendar::" instance=".*" level="ACCESS_ADMIN"}
        {assign value=$navigationObj->getUserFilter() var='uid'}
        {if empty($uid)}
            {gt text='@name' assign='uname'}
        {else}
            {usergetvar uid=$uid name='uname' assign='uname'}
        {/if}
        {if !empty($modvars.PostCalendar.pcAllowUserCalendar)}
        <button id='pcnav_usercalendar_button'>{$uname}</button>
        <!-- This is a pop up dialog box -->
        <div id='pcnav_usercalendar_dialog' title='{gt text='View private events of'}:'>
            <ul>
                <li class='pcusercalendar_selector' id='pcusercalendar_GLOBAL'><em>{gt text='Return to Global'}</em></li>
            {foreach from=$privateCalendarUsers key='id' item='name'}
                <li class='pcusercalendar_selector' id='pcusercalendar_{$id}'>{$name}</li>
            {/foreach}
            </ul>
        </div>
        <!-- end dialog -->
        {/if}
        {/checkpermissionblock}
        <input type='text' readonly="readonly" id='pcnav_filterpicker' name='pcnav_filterpicker' value='{gt text='inactive'}' />
        <input id="pcnav_filterpicker_button" type="image" alt="filter" class='tooltips' title='{gt text='filter categories and private/global'}' src='images/icons/extrasmall/filter.png' />
        <!-- This is a pop up dialog box -->
        <div id='pcnav_filterpicker_dialog' title='{gt text='Filter view'}'>
            <h5>{gt text='Categories'}</h5>
            <ul>
            {foreach from=$pcCategories key='regname' item='categories'}
                {foreach from=$categories item='category'}
                <li class='pccategories_selector_{$category.id} pccategories_selector' id='pccat_{$category.id}'>{$category.display_name.$lang}</li>
                {/foreach}
            {/foreach}
            </ul>
            {if !empty($modvars.PostCalendar.pcAllowUserCalendar)}
            {checkgroup gid=$modvars.PostCalendar.pcAllowUserCalendar}
            <h5>{gt text='Visibility'}</h5>
            <ul>
                <li class='pcvisibility_selector' id='pcviz_private'>{gt text='My private events'}</li>
                <li class='pcvisibility_selector' id='pcviz_global'>{gt text='Global events'}</li>
            </ul>
            {/checkgroup}
            {/if}
        </div>
        <!-- end dialog -->
        {/if}
        {if $navigationObj->useJumpDate}
        {assign value='overcast' var='jquerytheme'}
        {jquery_datepicker 
            defaultdate=$navigationObj->requestedDate 
            displayelement='pcnav_datepicker' 
            valuestorageelement='date' 
            valuestorageformat='Ymd' 
            theme=$jquerytheme 
            displayformat_datetime=$modvars.PostCalendar.pcDateFormats.date 
            displayformat_javascript=$modvars.PostCalendar.pcDateFormats.javascript
            autoSize='true' 
            changeMonth='true'
            changeYear='true'
            mindate="-"|cat:$modvars.PostCalendar.pcFilterYearStart|cat:"Y" 
            maxdate="+"|cat:$modvars.PostCalendar.pcFilterYearEnd|cat:"Y"
            onselectcallback='jQuery(this).closest("form").submit();'}
        <input id="pcnav_datepicker_button" type="image" alt="jump" class='tooltips' title='{gt text='jump to date'}' src='modules/PostCalendar/images/icon-calendar.jpg' />
        {/if}
        {if $navigationObj->useNavBar}
        <span id='pcnav_buttonbar'>
        {foreach from=$navigationObj->getNavItems() item='navItem'}
            {$navItem->renderRadio()}
        {/foreach}
        </span>
        {/if}
    </div>
    {else}{*else if $navigationObj->navBarType != 'buttonbar'*}
    {if $navigationObj->useNavBar}
    <div id="postcalendar_nav_right">
        <ul>
            {foreach from=$navigationObj->getNavItems() item='navItem'}
            <li>{$navItem->renderAnchorTag()}</li>
            {/foreach}    
        </ul>
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
    {/if}{*end if $navigationObj->navBarType == 'buttonbar'*}
</div>
</form>
<div>{insert name="getstatusmsg"}</div>
<!-- This is a pop up dialog box -->
<div id='pcnav_ical_dialog' style='display:none; text-align: left;' title='{gt text='Select an iCal feed'}'>
    <p>{gt text='Click to download and import.<br />Copy link to subscribe in your iCal client.'}</p>
    <ul>
    <li><strong><a href='{modurl modname="PostCalendar" type='user' func='display' viewtype='ical'}'>{gt text='All categories'}</a></strong></li>
    <li><!-- blank line -->&nbsp;</li>
    {foreach from=$pcCategories key='regname' item='categories'}
        {foreach from=$categories item='category'}
        <li><a href='{modurl modname="PostCalendar" type='user' func='display' viewtype='ical' prop=$regname cat=$category.id}'>{$category.display_name.$lang}</a></li>
        {/foreach}
    {/foreach}
    </ul>
</div>
<!-- end dialog -->
