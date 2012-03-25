{pc_queued_events_notify}
<form action="{modurl modname='PostCalendar' type='user' func='display'}" method="post" enctype="application/x-www-form-urlencoded">
<div class="z-clearfix">
    <div id="postcalendar_nav_right">
        <ul>
            {foreach from=$navItems item='navItem'}
            <li>{$navItem|safehtml}</li>
            {/foreach}  
        </ul>
    </div>
    <div id="postcalendar_nav_left">
        <ul>
            <li>
                {pc_date_select}
                {pc_html_select_date time=$currentjumpdate prefix="jump" start_year="-"|cat:$modvars.PostCalendar.pcFilterYearStart end_year="+"|cat:$modvars.PostCalendar.pcFilterYearEnd day_format="%d" day_value_format="%02d" month_format='%B' field_order='MDY'}
                {html_options name='viewtype' options=$viewtypeselector selected=$viewtypeselected}
                <input type="submit" name="submit" value="{gt text="Jump"}" />
            </li>
        </ul>
    </div>
</div>
</form>
<div>{insert name="getstatusmsg"}</div>