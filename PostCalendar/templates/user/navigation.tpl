{* $Id: postcalendar_user_navigation.htm 639 2010-06-30 22:16:08Z craigh $ *}
{pc_queued_events_notify}
{pc_form_nav_open}
{pc_init_rss_feed assign="rss"}
<div class="z-clearfix">
    <div id="postcalendar_nav_right">
        <ul>
            {if $DATE|truncate:8:"" ne $TODAY_DATE|replace:'-':''}
                <li>{pc_url action='today' full=true navlink=true}</li>
            {/if}
            <li>{pc_url action='day' full=true navlink=true}</li>
            <li>{pc_url action='week' full=true navlink=true}</li>
            <li>{pc_url action='month' full=true navlink=true}</li>
            <li>{pc_url action='year' full=true navlink=true}</li>
            <li>{pc_url action='list' full=true navlink=true}</li>
            {if $ACCESS_ADD eq true}
                <li>{pc_url action='add' full=true navlink=true}</li>
            {/if}
            <li>{pc_url action='search' full=true navlink=true}</li>
            <li>{pc_url action='print' full=true navlink=true}</li>
            {if $rss}
                <li>{pc_url action='rss' full=true navlink=true}</li>
            {/if}
        </ul>
    </div>
    <div id="postcalendar_nav_left">
        <ul>
            {gt text="Filter" assign="lbltxt"}
            <li>{pc_filter label=$lbltxt class="" type="user,category" order="user,category,jump"}</li>
            <li>
                {pc_date_select}
                {modgetvar module="PostCalendar" name="pcFilterYearStart" assign="pcFilterYearStart"}
                {modgetvar module="PostCalendar" name="pcFilterYearEnd" assign="pcFilterYearEnd"}
                {pc_html_select_date time=$currentjumpdate prefix="jump" start_year="-$pcFilterYearStart" end_year="+$pcFilterYearEnd" day_format="%d" day_value_format="%02d" month_format=$dateorderinfo.M field_order=$dateorderinfo.format}
                {html_options name='viewtype' options=$viewtypeselector selected=$viewtypeselected}
                <input type="submit" name="submit" value="{gt text="Jump"}" />
            </li>
        </ul>
    </div>
</div>
</form>
<div>{insert name="getstatusmsg"}</div>