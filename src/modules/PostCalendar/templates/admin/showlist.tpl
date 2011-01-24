{include file="admin/menu.tpl"}
<div class="z-admincontainer">
    <div class="z-adminpageicon">{img modname='PostCalendar' src='admin.png'}</div>
    <h2>{gt text="Event List"}</h2>
    <form class="z-form" action="{modurl modname="PostCalendar" type="admin" func="listevents"}" method="post" enctype="application/x-www-form-urlencoded">
        <fieldset id="postcalendar_listfilter">
            <legend>{gt text="Filter"}</legend>
            <input type="hidden" name="authid" value="{insert name="generateauthkey" module="PostCalendar"}" />
            <label for="listtype">{gt text='Status'}</label>
            {html_options id='listtype' name=listtype options=$listtypes selected=$listtypeselected}
            &nbsp;&nbsp;
            <span class="z-nowrap z-buttons">
                <input type="submit" value="{gt text="Filter"}" />
            </span>
        </fieldset>
    </form>
    <form id='pc_form_bulkaction' class="z-form" action="{modurl modname="postcalendar" type="admin" func="adminevents"}" method="post" enctype="application/x-www-form-urlencoded">
        <div>
            <input type="hidden" name="authid" value="{insert name="generateauthkey" module="PostCalendar"}" />
            <table class="z-datatable">
                <thead>
                    <tr>
                        <th class='z-w5'>{gt text='Select'}</th>
                        <th class='z-w35'><a class='{$sortcolclasses.title}' href='{$title_sort_url|safetext}'>{gt text='Title'}</a></th>
                        <th class='z-w15'>{gt text='Event Date'}</th>
                        <th class='z-w20'>{gt text='Categories'}</th>
                        <th class='z-w15'><a class='{$sortcolclasses.time}' href='{$time_sort_url|safetext}'>{gt text='Time stamp'}</a></th>
                        <th class='z-w10'>{gt text='Actions'}</th>
                    </tr>
                </thead>
                <tbody>
				{section name=event loop=$events}
                    <tr class="{cycle values="z-odd,z-even"}">
                        <td class='z-w5'><input type="checkbox" value="{$events[event].eid}" id="events_{$events[event].eid}" name="events[]" /></td>
                        <td class='z-w35'>{$events[event].title|safetext}</td>
                        <td class='z-w15'>{$events[event].eventDate|pc_date_format}</td>
                        <td class='z-w20'>{assignedcategorieslist item=$events[event]}</td>
                        <td class='z-w15'>{$events[event].time}</td>
                        <td class='z-w10'>
                            {assign var='options' value=$events[event].options}
                            {section name='options' loop=$options}
                            <a href="{$options[options].url|safetext}">{img modname='core' set='icons/extrasmall' src=$options[options].image title=$options[options].title alt=$options[options].title class='tooltips'}</a>
                            {/section}
                        </td>
                    </tr>
				{sectionelse}
                    <tr class='z-datatableempty'><td colspan='3'>{gt text='There are no %s events.' tag1=$functionname}</td></tr>
                {/section}
                </tbody>
            </table>
            <div  id='pc_bulkaction_control' style='text-align: left;'>
                {img modname='core' set='icons/extrasmall' src='2uparrow.gif' __alt='doubleuparrow'}<a href="javascript:void(0);" id="select_all">{gt text="Check all"}</a> / <a href="javascript:void(0);" id="deselect_all">{gt text="Uncheck all"}</a>
				{html_options name=action id='pc_bulkaction' options=$formactions selected=$actionselected}
            </div>
            <script type="text/javascript">
                $('select_all').observe('click', function(e){
                    Zikula.toggleInput('pc_form_bulkaction', true);
                    e.stop()
                });
                $('deselect_all').observe('click', function(e){
                    Zikula.toggleInput('pc_form_bulkaction', false);
                    e.stop()
                });
                $('pc_bulkaction').observe('change', function(event){
                    $('pc_form_bulkaction').submit()
                });
            </script>
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

<script type="text/javascript">
    Zikula.UI.Tooltips($$('.tooltips'));
</script>