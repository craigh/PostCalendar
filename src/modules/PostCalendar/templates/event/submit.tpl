{if $modvars.PostCalendar.pcAllowUserCalendar}
    {usergetvar name="uid" assign="uid"}
    {modapifunc modname='Groups' type='user' func='isgroupmember' uid=$uid gid=$modvars.PostCalendar.pcAllowUserCalendar assign="ingroup"}
{else}{assign var="ingroup" value=0}{/if}
{if $func eq "edit"}
    {gt text='Edit' assign="titletext"}
    {gt text='Update event' assign="submittext"}
{else}
    {gt text='Create new' assign="titletext"}
    {gt text='Submit event' assign="submittext"}
{/if}

{include file="user/navigation_small.tpl"}

{if ($loaded_event.preview ne "")}
    <div id="postcalendar_addevent_previewevent">
        <h2 id="previewtitle">{gt text='Event preview'}</h2>
        {include file="event/view.tpl"}
    </div>
{/if}
<form class='z-form' action="{pc_url action="submit" func=$func}" method="post" enctype="application/x-www-form-urlencoded">
<div style='border-color:#CCCCCC;border-style:solid;border-width:0 1px 1px;color:inherit;margin-bottom:1.25em;padding:0 1% 5px;width:auto;'>
	<input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />

<span style='float:right;'>{img modname='PostCalendar' src='admin.png' __alt="PostCalendar Rocks!"}</span>
<h2 style='border-bottom:1px solid #CCCCCC;text-align:left;padding-top:1em;'>{$titletext}&nbsp;{gt text='calendar event'}</h2>
<div style='padding-top:.5em'>
    <div id="postcalendar_addevent_leftcol">
        <fieldset>
            <legend>{gt text='General settings'}</legend>
            <label for="postcalendar_events_title"><b>{gt text='Title'}</b></label><span style='color:red;padding-left:5em;'>*{gt text='Required'}</span><br />
            <input type="text" class='z-w90' name="postcalendar_events[title]" id="postcalendar_events_title" value="{$loaded_event.title}" /><br /><br />

            {if ((isset($users)) AND (is_array($users)) AND ($ingroup))}
                <label for="postcalendar_events_aid"><b>{gt text='For user'}</b></label><span style='color:red;padding-left:5em;'>*{gt text='Required'}</span><br />
                {html_options name="postcalendar_events[aid]" id="postcalendar_events_aid" options=$users selected=$loaded_event.aid}<br /><br />
            {else}
                <input type="hidden" name="postcalendar_events[aid]" value="{$loaded_event.aid}" />
            {/if}

            <label for="postcalendar_events_sharing" style="padding-right:1em;"><b>{gt text='Sharing'}</b></label>
            {if $ingroup}
                {html_options name="postcalendar_events[sharing]" id="postcalendar_events_sharing" options=$sharingselect selected=$loaded_event.sharing}<br />
            {else}
                <span id="postcalendar_events_sharing"><i>{gt text='Global'}</i><input type="hidden" name="postcalendar_events[sharing]" value="3" /></span><br />
            {/if}
            <br />

            <span style='padding-right:2em;'><label for="display_eventDate"><b>{gt text='Start date'}</b></label></span>
            {calendarinput display=true hidden=true objectname="postcalendar_events" htmlname="eventDate" ifformat="%Y-%m-%d" dateformat=$modvars.PostCalendar.pcEventDateFormat defaultstring=$loaded_event.eventDatevalue defaultdate=$loaded_event.eventDate}
            <br /><br />
            <b>{gt text='Time'}</b><br />
            <input type="radio" name="postcalendar_events[alldayevent]" id="postcalendar_events_alldayevent1" value="1"{$Selected.allday} /><label for="postcalendar_events_alldayevent1" style='padding-left:1em;'>{gt text='All-day event'}</label>
            <br />
            <input type="radio" name="postcalendar_events[alldayevent]" id="postcalendar_events_alldayevent0" value="0"{$Selected.timed} /><label for="postcalendar_events_alldayevent0" style='padding-left:1em;'>{gt text='Timed event'}</label>
            <br />
            <div style='padding-left: 4em;'>
                {gt text='Start Time'}
                {html_select_time time=`$loaded_event.startTime` display_seconds=false use_24_hours=$modvars.PostCalendar.pcTime24Hours minute_interval=$modvars.PostCalendar.pcTimeIncrement field_array="postcalendar_events[startTime]" prefix=""}<br />
                {gt text='End Time'}
                {html_select_time time=`$loaded_event.endTime` display_seconds=false use_24_hours=$modvars.PostCalendar.pcTime24Hours minute_interval=$modvars.PostCalendar.pcTimeIncrement field_array="postcalendar_events[endTime]" prefix=""}
            </div>
            <br />

            {if $modvars.PostCalendar.enablecategorization}
                <div>
                    <b>{gt text='Category' plural="Categories" count=$cat_count}</b>
                    {nocache}
                    <ul>
                    {foreach from=$catregistry key='property' item='category'}
                        {array_field_isset assign="selectedValue" array=$loaded_event.__CATEGORIES__ field=$property returnValue=1}
                        <li>{selector_category
                                category=$category
                                name="postcalendar_events[__CATEGORIES__][$property]"
                                field="id"
                                selectedValue=$selectedValue
                                defaultValue="0"
                                editLink=0}</li>
                    {/foreach}
                    </ul>
                    {/nocache}
                </div>
            {/if}

            <label for="description"><b>{gt text='Description'}</b></label><br />
            <textarea  rows="30" cols="4" id="description" name="postcalendar_events[hometext]" style="width:90%; height: 12em;">{$loaded_event.hometext}</textarea><br />
            {if $formattedcontent eq 0}
                {* SCRIBITE NOT IN USE *}
                {html_options name="postcalendar_events[html_or_text]" options=$EventHTMLorText selected=$loaded_event.HTMLorTextVal}
                <div class="z-warningmsg" id="html_warning" style="width:77%;"><b>{gt text='Permitted HTML tags'}</b><br />{pc_allowedhtml}</div>
            {else}
                {* SCRIBITE IN USE *}
                <input type="hidden" name="postcalendar_events[html_or_text]" value="html" />
            {/if}
        </fieldset>
    </div>
    <div id="postcalendar_addevent_rightcol">
        <fieldset>
            <legend>{gt text='Repetition settings'}</legend>
            <input type="radio" name="postcalendar_events[recurrtype]" id="postcalendar_events_recurrtype0" value="0" {$SelectedNoRepeat} />
            <label for="postcalendar_events_recurrtype0" style='padding-left:1em;'>{gt text='One-time event'}</label><br />

            <input type="radio" name="postcalendar_events[recurrtype]" id="postcalendar_events_recurrtype1" value="1" {$SelectedRepeat} />
            <label for="postcalendar_events_recurrtype1" style='padding-left:1em;'>{gt text='Repeats every'}</label>
            <input type="text" name="postcalendar_events[repeat][event_repeat_freq]" value="{$loaded_event.repeat.event_repeat_freq}" size="2" />
            {html_options name="postcalendar_events[repeat][event_repeat_freq_type]" id="postcalendar_events_repeat_event_repeat_freq_type" options=$repeat_freq_type selected=$loaded_event.repeat.event_repeat_freq_type}<br />

            <input type="radio" name="postcalendar_events[recurrtype]" id="postcalendar_events_recurrtype2" value="2" {$SelectedRepeatOn} />
            <label for="postcalendar_events_recurrtype2" style='padding-left:1em;'>{gt text='Repeats on'}</label>
            {html_options name="postcalendar_events[repeat][event_repeat_on_num]" id="postcalendar_events_repeat_event_repeat_on_num" options=$repeat_on_num selected=$loaded_event.repeat.event_repeat_on_num}
            {html_options name="postcalendar_events[repeat][event_repeat_on_day]" id="postcalendar_events_repeat_event_repeat_on_day" options=$repeat_on_day selected=$loaded_event.repeat.event_repeat_on_day}<br />
            <span style='padding-left:3em;'>&nbsp;{gt text='of the month, every'}&nbsp;
            <input type="text" name="postcalendar_events[repeat][event_repeat_on_freq]" id="postcalendar_events_repeat_event_repeat_on_freq" value="{$loaded_event.repeat.event_repeat_on_freq}" size="2" />
            {gt text='month(s)'}.</span><br />
            <br />

            <b>{gt text='End date'}</b><br />
            <input type="radio" name="postcalendar_events[endtype]" id="postcalendar_events_endtype1" value="1" {$SelectedEndOn} />
            <span style='padding-left:1em;'>
            {if ((isset($postcalendar_events)) AND ($postcalendar_events.eventDate > $postcalendar_events.endDate))}
                {calendarinput display=true hidden=true objectname="postcalendar_events" htmlname="endDate" ifformat="%Y-%m-%d" dateformat=$modvars.PostCalendar.pcEventDateFormat defaultstring=$loaded_event.eventDatevalue defaultdate=$loaded_event.eventDate}<br />
            {else}
                {calendarinput display=true hidden=true objectname="postcalendar_events" htmlname="endDate" ifformat="%Y-%m-%d" dateformat=$modvars.PostCalendar.pcEventDateFormat defaultstring=$loaded_event.endvalue defaultdate=$loaded_event.endDate}<br />
            {/if}
            </span>
            <input type="radio" name="postcalendar_events[endtype]" id="postcalendar_events_endtype0" value="0" {$SelectedNoEnd} />
            <label for="postcalendar_events_endtype0" style='padding-left:1em;'>{gt text='No end date'}</label>
        </fieldset>
        <fieldset>
            <legend>{gt text='Location'}</legend>
            {pc_locations}
            <label for="postcalendar_events_location_event_location">{gt text='Name'}</label><br />
            <input class='z-w90' type="text" name="postcalendar_events[location][event_location]" id="postcalendar_events_location_event_location" value="{$loaded_event.location_info.event_location}" /><br />
            <label for="postcalendar_events_location_event_street1">{gt text='Street'}</label><br />
            <input class='z-w90' type="text" name="postcalendar_events[location][event_street1]" id="postcalendar_events_location_event_street1" value="{$loaded_event.location_info.event_street1}" /><br />
            <input class='z-w90' type="text" name="postcalendar_events[location][event_street2]" id="postcalendar_events_location_event_street2" value="{$loaded_event.location_info.event_street2}" /><br />
            <table cellspacing="0" style='width:90%;'>
                <tr>
                    <td style='width:40%'><label for="postcalendar_events_location_event_city">{gt text='City'}</label></td>
                    <td style='width:30%'><label for="postcalendar_events_location_event_state">{gt text='State'}</label></td>
                    <td style='width:30%'><label for="postcalendar_events_location_event_postal">{gt text='Zipcode'}</label></td>
                </tr>
                <tr>
                    <td><input class='z-w90' type="text" name="postcalendar_events[location][event_city]" id="postcalendar_events_location_event_city" value="{$loaded_event.location_info.event_city}" /></td>
                    <td><input class='z-w90' type="text" name="postcalendar_events[location][event_state]" id="postcalendar_events_location_event_state" value="{$loaded_event.location_info.event_state}" /></td>
                    <td><input class='z-w90' type="text" name="postcalendar_events[location][event_postal]" id="postcalendar_events_location_event_postal" value="{$loaded_event.location_info.event_postal}" /></td>
                </tr>
            </table>
        </fieldset>
        <fieldset>
            <legend>{gt text='Contact'}</legend>
            <label for="postcalendar_events_contname">{gt text='Name'}</label><br />
            <input class='z-w90' type="text" name="postcalendar_events[contname]" id="postcalendar_events_contname" value="{$loaded_event.contname}" /><br />
            <label for="postcalendar_events_conttel">{gt text='Phone number'}</label><br />
            <input class='z-w90' type="text" name="postcalendar_events[conttel]" id="postcalendar_events_conttel" value="{$loaded_event.conttel}" /><br />
            <label for="postcalendar_events_contemail">{gt text='E-mail address'}</label><br />
            <input class='z-w90' type="text" name="postcalendar_events[contemail]" id="postcalendar_events_contemail" value="{$loaded_event.contemail}" /><br />
            <label for="postcalendar_events_website">{gt text='Web site'}</label><br />
            <input class='z-w90' type="text" name="postcalendar_events[website]" id="postcalendar_events_website" value="{$loaded_event.website}" /><br />
        </fieldset>
        <label for="postcalendar_events_fee"><b>{gt text='Fee'}</b></label>
        <input style='margin-left: 1em;' type="text" name="postcalendar_events[fee]" id="postcalendar_events_fee" value="{$loaded_event.fee}" /><br />
    </div>
</div>
<div style='clear:both;'></div>

<input type="hidden" name="is_update" value="{$loaded_event.is_update}" />
<input type="hidden" name="postcalendar_events[eid]" value="{if isset($loaded_event.eid)}{$loaded_event.eid}{/if}" />
{if !empty($loaded_event.data_loaded)} <input type="hidden" name="postcalendar_events[data_loaded]" value="{$loaded_event.data_loaded}" />{/if}

{if $func eq "edit"}
    {notifydisplayhooks eventname='postcalendar.ui_hooks.events.ui_edit' id=$loaded_event.eid"}
{else}
    {notifydisplayhooks eventname='postcalendar.ui_hooks.events.ui_edit' id=null"}
{/if}
<div class="z-buttons z-formbuttons">
    {button src="14_layer_visible.png" set="icons/extrasmall" class='z-btblue' __alt="Preview" __title="Preview" __text="Preview" name="form_action" __value="Preview"}
    {button src="button_ok.png" set="icons/extrasmall" class='z-btgreen' __alt="Save" __title="Save" __text="Save" name="form_action" __value="Save"}
    {button src="button_ok.png" set="icons/extrasmall" class='z-btgreen' __alt="Save and Add" __title="Save and Add" __text="Save and Add" name="form_action" __value="Save and Add"}
    <a class='z-btred' href="{modurl modname="PostCalendar" type='user' func='display'}" title="{gt text="Cancel"}">{img modname='core' src="button_cancel.png" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
</div>

</div> <!-- /page container-->
</form>