{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="view" size="small"}
    <h3>{gt text='Event list'}</h3>
</div>

<form class="z-form" action="{modurl modname="PostCalendar" type="admin" func="listevents"}" method="post" enctype="application/x-www-form-urlencoded">
    <fieldset id="postcalendar_listfilter"{if $filter_active} class='filteractive'{/if}>
        {if $filter_active}{gt text='active' assign=filteractive}{else}{gt text='inactive" assign=filteractive}{/if}
        <legend>{gt text='Filter %1$s, %2$s event listed' plural='Filter %1$s, %2$s events listed' count=$total_events tag1=$filteractive tag2=$total_events}</legend>
        <input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />
        <input type="hidden" name="offset" value="{$offset}" />
        <input type="hidden" name="sort" value="{$sort}" />
        <input type="hidden" name="sdir" value="{$sdir}" />
        <label for="listtype">{gt text='Status'}</label>
        {html_options id='listtype' name='listtype' options=$listtypes selected=$listtypeselected}
        &nbsp;&nbsp;
        <label for="categoryfilter">{gt text='Categories'}</label>
        <span id='categoryfilter'>{include file='event/filtercats.tpl'}</span>
        &nbsp;&nbsp;
        <span class="z-nowrap z-buttons">
            <input type="submit" class='z-bt-filter' value="{gt text="Filter"}" />
            <a href="{modurl modname="PostCalendar" type='admin' func='listevents' listtype='100'}" title="{gt text="Clear"}">{img modname='core' src="button_cancel.png" set="icons/extrasmall" __alt="Clear" __title="Clear"} {gt text="Clear Filter"}</a>
        </span>
    </fieldset>
</form>
<form id='pc_form_bulkaction' class="z-form" action="{modurl modname="postcalendar" type="admin" func="adminevents"}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
        <input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />
        <input type="hidden" name="listtype" value="{$listtypeselected}" />
        <table class="z-datatable">
            <thead>
                <tr>
                    <th class='z-w5'></th>
                    <th class='z-w30'><a class='{$sortcolclasses.title}' href='{$title_sort_url|safetext}'>{gt text='Title'}</a></th>
                    <th class='z-w15'><a class='{$sortcolclasses.eventStart}' href='{$eventStart_sort_url|safetext}'>{gt text='Event Date'}</a></th>
                    <th class='z-w20'>{gt text='Categories'}</th>
                    <th class='z-w20'><a class='{$sortcolclasses.time}' href='{$time_sort_url|safetext}'>{gt text='Time stamp'}</a></th>
                    <th class='z-w10'>{gt text='Actions'}</th>
                </tr>
            </thead>
            <tbody>
            {section name=event loop=$events}
                <tr class="{cycle values="z-odd,z-even"}">
                    <td class='z-w5'><input type="checkbox" value="{$events[event].eid}" id="events_{$events[event].eid}" name="events[]" /></td>
                    <td class='z-w30'>{$events[event].title|safetext}
                        {if $events[event].privateicon}{img src='lock.gif' modname='PostCalendar' __title="private event" __alt="private event" class='tooltips'}{/if}
                        {if !empty($events[event].recurrspec.exceptions)}
                            {img modname='PostCalendar' src='exceptions.png' __title="event repeats with exceptions" class='tooltips'}
                        {elseif $events[event].recurrtype > 0}
                            {img modname='PostCalendar' src='repeat.png' __title="event repeats" class='tooltips'}
                        {/if}
                    </td>
                    <td class='z-w15'>{$events[event].eventStart|pc_date_format:$modvars.PostCalendar.pcDateFormats.date}</td>
                    <td class='z-w20'>{assignedcategorieslist item=$events[event]}</td>
                    <td class='z-w20'>{$events[event].time}</td>
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
            {img modname='core' set='icons/extrasmall' src='2uparrow.png' __alt='doubleuparrow'}<a href="javascript:void(0);" id="select_all">{gt text="Check all"}</a> / <a href="javascript:void(0);" id="deselect_all">{gt text="Uncheck all"}</a>
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
        {pager rowcount=$total_events limit=$modvars.PostCalendar.pcListHowManyEvents posvar='offset'}
    </div>
</form>
{adminfooter}

<script type="text/javascript">
    Zikula.UI.Tooltips($$('.tooltips'));
</script>