{include file="admin/menu.tpl"}
<div class="z-admincontainer">
    <div class="z-adminpageicon">{img modname='PostCalendar' src='admin.png'}</div>
    <h2>{gt text="$title"}</h2>
    <form class="z-adminform" action="{modurl modname="PostCalendar" type="admin" func="listevents"}" method="post" enctype="application/x-www-form-urlencoded">
        <input type="hidden" name="authid" value="{insert name="generateauthkey" module="PostCalendar"}" />
        {html_options name=listtype options=$listtypes selected=$listtypeselected}
        <input type="submit" value="{gt text="Change Lists"}" />
    </form>
    <form class="z-adminform" action="{modurl modname="postcalendar" type="admin" func="adminevents"}" method="post" enctype="application/x-www-form-urlencoded">
        <div>
            <input type="hidden" name="authid" value="{insert name="generateauthkey" module="PostCalendar"}" />
            <table class="z-datatable">
                <thead>
                    <tr>
                        <th class='z-w10'>{gt text='Select'}</th>
                        <th class='z-w60'><a class='{$sortcolclasses.title}' href='{$title_sort_url|safetext}'>{gt text='Title'}</a></th>
                        <th class='z-w30'><a class='{$sortcolclasses.time}' href='{$time_sort_url|safetext}'>{gt text='Time stamp'}</a></th>
                    </tr>
                </thead>
                <tbody>
				{section name=event loop=$events}
                    <tr class="{cycle values="z-odd,z-even"}">
                        <td class='z-w10'><input type="checkbox" value="{$events[event].eid}" id="events_{$events[event].eid}" name="events[]" /></td>
                        <td class='z-w60'><a href='{modurl modname="PostCalendar" type="event" func="edit" eid=$events[event].eid}' >{$events[event].title|safetext}</a></td>
                        <td class='z-w30'>{$events[event].time}</td>
                    </tr>
				{sectionelse}
                    <tr class='z-datatableempty'><td colspan='3'>{gt text='There are no %s events.' tag1=$functionname}</td></tr>
                {/section}
                </tbody>
            </table>
            <div style='text-align: left;'>
				{html_options name=action options=$formactions selected=$actionselected}
                <input type="submit" value="{gt text="Perform this action"}" />
            </div>
            <div id="listmanipulator" style='text-align: center; background-color:#cccccc; padding:.5em; margin-top:.5em;'>
				{if !empty($prevlink)}
                << <a href="{$prevlink|safetext}">{gt text="Previous"} {$offset_increment} {gt text="Events"}</a>
				{else}
					{gt text="Previous"}
				{/if}
                &nbsp;|&nbsp;
				{if !empty($nextlink)}
                <a href="{$nextlink|safetext}">{gt text="Next"} {$offset_increment} {gt text="Events"}</a> >>
				{else}
					{gt text="Next"}
				{/if}
            </div>
        </div>
    </form>
</div><!-- /z-admincontainer -->