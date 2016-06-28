{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="config" size="small"}
    <h3>{gt text='Event default values'}</h3>
</div>

<form class="z-form" action="{modurl modname="ZikulaPostCalendarModule" type="admin" func="seteventdefaults"}" method="post" enctype="application/x-www-form-urlencoded">
<div>
	<input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />
    <fieldset>
        <legend>{gt text='General'}</legend>
        <div class="z-formrow">
            <label for="postcalendar_eventdefaults_sharing">{gt text='Sharing'}</label>
            {if $modvars.ZikulaPostCalendarModule.pcAllowUserCalendar}
                <span>{html_options name="postcalendar_eventdefaults[sharing]" id="postcalendar_eventdefaults_sharing" options=$sharingselect selected=$modvars.ZikulaPostCalendarModule.pcEventDefaults.sharing}</span>
            {else}
                <span id="postcalendar_eventdefaults_sharing"><i>{gt text='Global'}</i><input type="hidden" name="postcalendar_eventdefaults[sharing]" value="3" /></span>
            {/if}
        </div>

        <div class="z-formrow">
            <label for="postcalendar_eventdefaults_eventtype">{gt text='Event Type'}</label>
            <span id="postcalendar_eventdefaults_eventtype">
                <input type="radio" name="postcalendar_eventdefaults[alldayevent]" id="postcalendar_eventdefaults_alldayevent1" value="1"{$Selected.allday} />
                <label for="postcalendar_eventdefaults_alldayevent1">{gt text='All-day event'}</label><br />
                <input type="radio" name="postcalendar_eventdefaults[alldayevent]" id="postcalendar_eventdefaults_alldayevent0" value="0"{$Selected.timed} />
                <label for="postcalendar_eventdefaults_alldayevent0">{gt text='Timed event'}</label>
            </span>
        </div>
        <div class="z-formrow">
            <label for="postcalendar_eventdefaults_startTime">{gt text='Start Time'}</label>
            <span id="postcalendar_eventdefaults_startTime">{html_select_time time=`$modvars.ZikulaPostCalendarModule.pcEventDefaults.startTime` display_seconds=false use_24_hours=$modvars.ZikulaPostCalendarModule.pcTime24Hours minute_interval=$modvars.ZikulaPostCalendarModule.pcTimeIncrement field_array="postcalendar_eventdefaults[startTime]" prefix=""}</span>
        </div>
        <div class="z-formrow">
            <label for="postcalendar_eventdefaults_endTime">{gt text='End Time'}</label>
            <span id="postcalendar_eventdefaults_endTime">{html_select_time time=`$endTime` display_seconds=false use_24_hours=$modvars.ZikulaPostCalendarModule.pcTime24Hours minute_interval=$modvars.ZikulaPostCalendarModule.pcTimeIncrement field_array="postcalendar_eventdefaults[endTime]" prefix=""}</span>
        </div>

        <div class="z-formrow">
            <label for="postcalendar_eventdefaults_fee">{gt text='Fee'}</label>
            <input style='margin-left: 1em;' type="text" name="postcalendar_eventdefaults[fee]" id="postcalendar_eventdefaults_fee" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.fee}" />
        </div>
		<div class="z-formrow">
			<label for="postcalendar_eventdefaults_categories">{gt text='Default categories'}</label>
               {gt text="No Default Category" assign="allText"}
               {nocache}
               <span id='postcalendar_eventdefaults_categories'>{foreach from=$catregistry key='property' item='category'}
                   {array_field assign="selectedValue" array=$modvars.ZikulaPostCalendarModule.pcEventDefaults.categories field=$property}
                   {selector_category 
                   editLink=true 
                   category=$category 
                   name="postcalendar_eventdefaults[categories][$property]" 
                   field="id" 
                   selectedValue=$selectedValue 
                   defaultValue="0"
                   all=1
                   allText=$allText
                   allValue=0}
               {/foreach}</span>
               {/nocache}
        </div>
    </fieldset>

    <fieldset>
        <legend>{gt text='Location'}</legend>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_location_event_location">{gt text='Name'}</label>
            <input type="text" name="postcalendar_eventdefaults[location][event_location]" id="postcalendar_eventdefaults_location_event_location" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.location.event_location}" />
        </div>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_location_event_street1">{gt text='Street'}</label>
            <input type="text" name="postcalendar_eventdefaults[location][event_street1]" id="postcalendar_eventdefaults_location_event_street1" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.location.event_street1}" />
        </div>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_location_event_street1">{gt text='Street Line 2'}</label>
            <input type="text" name="postcalendar_eventdefaults[location][event_street2]" id="postcalendar_eventdefaults_location_event_street2" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.location.event_street2}" />
        </div>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_location_event_city">{gt text='City'}</label>
            <input type="text" name="postcalendar_eventdefaults[location][event_city]" id="postcalendar_eventdefaults_location_event_city" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.location.event_city}" />
        </div>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_location_event_state">{gt text='State'}</label>
            <input type="text" name="postcalendar_eventdefaults[location][event_state]" id="postcalendar_eventdefaults_location_event_state" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.location.event_state}" />
        </div>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_location_event_postal">{gt text='Zipcode'}</label>
            <input type="text" name="postcalendar_eventdefaults[location][event_postal]" id="postcalendar_eventdefaults_location_event_postal" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.location.event_postal}" />
        </div>
    </fieldset>
    <fieldset>
        <legend>{gt text='Contact'}</legend>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_contname">{gt text='Name'}</label>
            <input type="text" name="postcalendar_eventdefaults[contname]" id="postcalendar_eventdefaults_contname" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.contname}" />
        </div>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_conttel">{gt text='Phone number'}</label>
            <input type="text" name="postcalendar_eventdefaults[conttel]" id="postcalendar_eventdefaults_conttel" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.conttel}" />
        </div>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_contemail">{gt text='E-mail address'}</label>
            <input type="text" name="postcalendar_eventdefaults[contemail]" id="postcalendar_eventdefaults_contemail" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.contemail}" />
        </div>
		<div class="z-formrow">
            <label for="postcalendar_eventdefaults_website">{gt text='Web site'}</label>
            <input type="text" name="postcalendar_eventdefaults[website]" id="postcalendar_eventdefaults_website" value="{$modvars.ZikulaPostCalendarModule.pcEventDefaults.website}" />
        </div>
    </fieldset>
</div>

<div class="z-buttons z-formbuttons">
    {button src="button_ok.png" set="icons/extrasmall" class='z-btgreen' __alt="Save" __title="Save" __text="Save"}
    <a class='z-btred' href="{modurl modname="ZikulaPostCalendarModule" type="admin" func='listevents'}" title="{gt text="Cancel"}">{img modname='core' src="button_cancel.png" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
</div>

</form>
{adminfooter}