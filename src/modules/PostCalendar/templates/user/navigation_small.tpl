{pc_queued_events_notify}
{pc_form_nav_open}
<div class="z-clearfix">
    <div id="postcalendar_nav_right">
        <ul>
            {checkpermissionblock component='PostCalendar::' instance="::" level=ACCESS_ADMIN}
            <li><a href='{modurl modname='PostCalendar' type='admin'}'>{img src=configure.png modname=core set=icons/small __alt="Admin" __title="Admin"}</a></li>
            {/checkpermissionblock}
            <li>{pc_url action='day' full=true navlink=true viewtype=$viewtypeselected}</li>
            <li>{pc_url action='week' full=true navlink=true viewtype=$viewtypeselected}</li>
            <li>{pc_url action='month' full=true navlink=true viewtype=$viewtypeselected}</li>
            <li>{pc_url action='year' full=true navlink=true viewtype=$viewtypeselected}</li>
            <li>{pc_url action='list' full=true navlink=true viewtype=$viewtypeselected}</li>
            <li>{pc_url action='search' full=true navlink=true viewtype=$viewtypeselected}</li>
            <li>{pc_url action='print' full=true navlink=true viewtype=$viewtypeselected}</li>
        </ul>
    </div>
    <div id="postcalendar_nav_left">
        <ul>
            <li>
                {pc_date_select}
                {pc_html_select_date time=$currentjumpdate prefix="jump" start_year="-10" end_year="+10" day_format="%d" day_value_format="%02d" month_format=$dateorderinfo.M field_order=$dateorderinfo.format}
                {html_options name='viewtype' options=$viewtypeselector selected=$viewtypeselected}
                <input type="submit" name="submit" value="{gt text="Jump"}" />
            </li>
        </ul>
    </div>
</div>
</form>
<div>{insert name="getstatusmsg"}</div>