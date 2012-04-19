{pageaddvar name='javascript' value='modules/PostCalendar/javascript/postcalendar-event-submit.js'}
{assign value='overcast' var='jquerytheme'}
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

{$navBar}

{if ($loaded_event.preview ne "")}
    <div id="postcalendar_addevent_previewevent">
        <h2 id="previewtitle">{gt text='Event preview'}</h2>
        {include file="event/view.tpl"}
    </div>
{/if}
<form class='z-form' action="{pc_url action="submit" func=$func}" method="post" enctype="application/x-www-form-urlencoded">
<div>
	<input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />

<span style='float:right;'>{img modname='PostCalendar' src='admin.png' __alt="PostCalendar Rocks!"}</span>
<h2 style='border-bottom:1px solid #CCCCCC;text-align:left;padding-top:1em;'>{$titletext}&nbsp;{gt text='calendar event'}</h2>
<div id='postcalendar_addevent' style='padding-top:.5em'>
    <fieldset>
        <legend>{gt text='General settings'}</legend>
        <div class="z-formrow">
            <label for="postcalendar_events_title">{gt text='Title'} <span class='z-mandatorysym'>*{gt text='Required'}</span></label>
            <input type="text" name="postcalendar_events[title]" id="postcalendar_events_title" value="{$loaded_event.title}" />
        </div>

        {if ((isset($users)) AND (is_array($users)) AND ($ingroup))}
        <div class="z-formrow">
            <label for="postcalendar_events_aid">{gt text='For user'} <span class='z-mandatorysym'>*{gt text='Required'}</span></label>
            {html_options name="postcalendar_events[aid]" id="postcalendar_events_aid" options=$users selected=$loaded_event.aid}
        </div>
        {else}
            <input type="hidden" name="postcalendar_events[aid]" value="{$loaded_event.aid}" />
        {/if}

        <div class="z-formrow">
            <label for="postcalendar_events_sharing">{gt text='Sharing'}</label>
            {if $ingroup}
                {html_options name="postcalendar_events[sharing]" id="postcalendar_events_sharing" options=$sharingselect selected=$loaded_event.sharing}
            {else}
                <span id="postcalendar_events_sharing"><i>{gt text='Global'}</i><input type="hidden" name="postcalendar_events[sharing]" value="3" /></span>
            {/if}
        </div>

        <div class="z-formrow">
            <label>{gt text='Starts and Ends'}</label>
            <span>
                {pageaddvar name='javascript' value='modules/PostCalendar/javascript/postcalendar-function-updatefields.js'}
                {if ($loaded_event.alldayevent)}{assign var='inlinestyle' value='display: none;'}{else}{assign var='inlinestyle' value=null}{/if}
                {jquery_datepicker 
                    defaultdate=$loaded_event.eventStart 
                    displayelement='eventstart_display' 
                    object='postcalendar_events' 
                    valuestorageelement='eventstart_date' 
                    maxdate=$loaded_event.eventStart 
                    theme=$jquerytheme 
                    displayformat_datetime=$modvars.PostCalendar.pcDateFormats.date 
                    displayformat_javascript=$modvars.PostCalendar.pcDateFormats.javascript
                    onselectcallback='updateFields(this,dateText);'}
                {jquery_timepicker 
                    defaultdate=$loaded_event.eventStart 
                    displayelement='eventstart_time_display' 
                    object='postcalendar_events' 
                    valuestorageelement='eventstart_time' 
                    inlinestyle=$inlinestyle theme=$jquerytheme 
                    use24hour=$modvars.PostCalendar.pcTime24Hours 
                    stepminute=$modvars.PostCalendar.pcTimeIncrement
                    onclosecallback='updateFields(this,dateText);'}
                <span style='padding:0 1em;'>{gt text='to'}</span>
                {jquery_datepicker 
                    defaultdate=$loaded_event.eventEnd 
                    displayelement='eventend_display' 
                    object='postcalendar_events'  
                    valuestorageelement='eventend_date' 
                    mindate=$loaded_event.eventEnd 
                    theme=$jquerytheme 
                    displayformat_datetime=$modvars.PostCalendar.pcDateFormats.date 
                    displayformat_javascript=$modvars.PostCalendar.pcDateFormats.javascript
                    onselectcallback='updateFields(this,dateText);'}
                {jquery_timepicker 
                    defaultdate=$loaded_event.eventEnd 
                    displayelement='eventend_time_display' 
                    object='postcalendar_events' 
                    valuestorageelement='eventend_time' 
                    inlinestyle=$inlinestyle 
                    theme=$jquerytheme 
                    use24hour=$modvars.PostCalendar.pcTime24Hours 
                    stepminute=$modvars.PostCalendar.pcTimeIncrement
                    onclosecallback='updateFields(this,dateText);'}
            </span>
        </div>
            
        <div class="z-formrow">
            <input type="checkbox" value="1" id="postcalendar_events_alldayevent" name="postcalendar_events[alldayevent]"{if $loaded_event.alldayevent eq true} checked="checked"{/if} />
            <label for="postcalendar_events_alldayevent">{gt text='All day'}</label>
        </div>
        
        <div class="z-formrow">
            <input type="checkbox" value="1" id="postcalendar_events_repeats" name="postcalendar_events[repeats]"{if $loaded_event.repeats eq true} checked="checked"{/if} />
            <label for="postcalendar_events_repeats">{gt text='Event repeats'}</label>
        </div>

        <div id="postcalendar_repetitionsettings" class='z-formnote'{if $loaded_event.repeats eq false} style="display: none;"{/if} class="z-formrow">
            <h5>{gt text='Repetition settings'}</h5>
            <div>
                <input type="radio" name="postcalendar_events[recurrtype]" id="postcalendar_events_recurrtype1" value="1"{$SelectedRepeat} />
                <label for="postcalendar_events_recurrtype1" style='padding-left:1em;'>{gt text='Repeats every'}</label>
                <input type="text" name="postcalendar_events[recurrspec][event_repeat_freq]" value="{$loaded_event.recurrspec.event_repeat_freq}" size="2" />
                {html_options name="postcalendar_events[recurrspec][event_repeat_freq_type]" id="postcalendar_events_repeat_event_repeat_freq_type" options=$repeat_freq_type selected=$loaded_event.recurrspec.event_repeat_freq_type}
            </div>
            <div>
                <input type="radio" name="postcalendar_events[recurrtype]" id="postcalendar_events_recurrtype2" value="2"{$SelectedRepeatOn} />
                <label for="postcalendar_events_recurrtype2" style='padding-left:1em;'>{gt text='Repeats on'}</label>
                {html_options name="postcalendar_events[recurrspec][event_repeat_on_num]" id="postcalendar_events_repeat_event_repeat_on_num" options=$repeat_on_num selected=$loaded_event.recurrspec.event_repeat_on_num}
                {html_options name="postcalendar_events[recurrspec][event_repeat_on_day]" id="postcalendar_events_repeat_event_repeat_on_day" options=$repeat_on_day selected=$loaded_event.recurrspec.event_repeat_on_day}
                &nbsp;{gt text='of the month, every'}&nbsp;
                <input type="text" name="postcalendar_events[recurrspec][event_repeat_on_freq]" id="postcalendar_events_repeat_event_repeat_on_freq" value="{$loaded_event.recurrspec.event_repeat_on_freq}" size="2" />
                {gt text='month(s)'}.
            </div>
            <div>
                <label>{gt text='until'}...</label>
                {jquery_datepicker 
                    defaultdate=$loaded_event.endDate 
                    displayelement='repeat_enddate_display' 
                    object='postcalendar_events' 
                    valuestorageelement='enddate' 
                    mindate=$loaded_event.eventEnd 
                    theme=$jquerytheme
                    displayformat_datetime=$modvars.PostCalendar.pcDateFormats.date 
                    displayformat_javascript=$modvars.PostCalendar.pcDateFormats.javascript
                    onselectcallback='updateFields(this,dateText);'}
            </div>
        </div>

        <div class="z-formrow">
            <label for='postcalendar_events_categories_selector'>{gt text='Category' plural="Categories" count=$cat_count}</label>
            {nocache}
            <span id='postcalendar_events_categories_selector'>
            <ul style='list-style:none;margin:0;'>
            {foreach from=$catregistry key='property' item='category'}
                {array_field assign="selectedValue" array=$loaded_event.categories field=$property}
                <li>{selector_category
                        category=$category
                        name="postcalendar_events[categories][$property]"
                        field="id"
                        selectedValue=$selectedValue
                        defaultValue="0"
                        editLink=0}</li>
            {/foreach}
            </ul>
            </span>
            {/nocache}
        </div>

        <div class="z-formrow">
            <label for="description">{gt text='Description'}</label>
            <textarea id="description" name="postcalendar_events[hometext]">{$loaded_event.hometext}</textarea>
            {if $formattedcontent eq 0}
                {* SCRIBITE NOT IN USE *}
                <input type="checkbox" value="1" id="postcalendar_events_htmlortext" name="postcalendar_events[html_or_text]"{if $loaded_event.HTMLorTextVal eq 'html'} checked="checked"{/if} />
                <label for="postcalendar_events_htmlortext">{gt text='formatted with HTML'}</label>
                <div class="z-formnote z-warningmsg" id="html_warning"{if $loaded_event.HTMLorTextVal eq 'text'} style="display: none;"{/if}><strong>{gt text='Permitted HTML tags'}</strong><br />{pc_allowedhtml}</div>
            {else}
                {* SCRIBITE IN USE *}
                <input type="hidden" name="postcalendar_events[html_or_text]" value="html" />
            {/if}
        </div>

        <div class="z-formrow">
            <label for="postcalendar_events_fee">{gt text='Fee'}</label>
            <input type="text" name="postcalendar_events[fee]" id="postcalendar_events_fee" value="{$loaded_event.fee}" />
        </div>

        <div class="z-formrow">
            <input type="checkbox" value="1" id="postcalendar_events_haslocation"{if $loaded_event.haslocation eq true} checked="checked"{/if} />
            <label for="postcalendar_events_haslocation">{gt text='Event has location'}</label>
        </div>

        <div id='postcalendar_events_haslocation_display'{if $loaded_event.haslocation eq false} style="display: none;"{/if}>
            <h5 class='z-formnote'>{gt text='Location'}</h5>
            {pc_locations}
            <div class="z-formrow">
                <label for="postcalendar_events_location_event_location">{gt text='Name'}</label>
                <input type="text" name="postcalendar_events[location][event_location]" id="postcalendar_events_location_event_location" value="{$loaded_event.location.event_location}" />
            </div>
            <div class="z-formrow">
                <label for="postcalendar_events_location_event_street1">{gt text='Street'}</label>
                <input type="text" name="postcalendar_events[location][event_street1]" id="postcalendar_events_location_event_street1" value="{$loaded_event.location.event_street1}" />
            </div>
            <div class="z-formrow">
                <label>{gt text='Street (line 2)'}</label>
                <input type="text" name="postcalendar_events[location][event_street2]" id="postcalendar_events_location_event_street2" value="{$loaded_event.location.event_street2}" />
            </div>
            <div class="z-formrow">
                <label for="postcalendar_events_location_event_city">{gt text='City'}</label>
                <input type="text" name="postcalendar_events[location][event_city]" id="postcalendar_events_location_event_city" value="{$loaded_event.location.event_city}" />
            </div>
            <div class="z-formrow">
                <label for="postcalendar_events_location_event_state">{gt text='State'}</label>
                <input type="text" name="postcalendar_events[location][event_state]" id="postcalendar_events_location_event_state" value="{$loaded_event.location.event_state}" />
            </div>
            <div class="z-formrow">
                <label for="postcalendar_events_location_event_postal">{gt text='Zipcode'}</label>
                <input type="text" name="postcalendar_events[location][event_postal]" id="postcalendar_events_location_event_postal" value="{$loaded_event.location.event_postal}" />
            </div>
        </div>

        <div class="z-formrow">
            <input type="checkbox" value="1" id="postcalendar_events_hascontact"{if $loaded_event.hascontact eq true} checked="checked"{/if} />
            <label for="postcalendar_events_hascontact">{gt text='Event has contact'}</label>
        </div>
        
        <div id='postcalendar_events_hascontact_display'{if $loaded_event.hascontact eq false} style="display: none;"{/if}>
            <h5 class='z-formnote'>{gt text='Contact'}</h5>
            <div class="z-formrow">
                <label for="postcalendar_events_contname">{gt text='Name'}</label>
                <input type="text" name="postcalendar_events[contname]" id="postcalendar_events_contname" value="{$loaded_event.contname}" />
            </div>
            <div class="z-formrow">
                <label for="postcalendar_events_conttel">{gt text='Phone number'}</label>
                <input type="text" name="postcalendar_events[conttel]" id="postcalendar_events_conttel" value="{$loaded_event.conttel}" />
            </div>
            <div class="z-formrow">
                <label for="postcalendar_events_contemail">{gt text='E-mail address'}</label>
                <input type="text" name="postcalendar_events[contemail]" id="postcalendar_events_contemail" value="{$loaded_event.contemail}" />
            </div>
            <div class="z-formrow">
                <label for="postcalendar_events_website">{gt text='Web site'}</label>
                <input type="text" name="postcalendar_events[website]" id="postcalendar_events_website" value="{$loaded_event.website}" />
            </div>
        </div>
    </fieldset>
</div>

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