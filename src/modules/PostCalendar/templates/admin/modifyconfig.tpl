{pageaddvar name="javascript" value="jquery"}
{pageaddvar name='javascript' value='modules/PostCalendar/javascript/postcalendar-admin-modifyconfig.js'}
{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="config" size="small"}
    <h3>{gt text='Modify settings'}</h3>
</div>

<form class="z-form" action="{modurl modname="PostCalendar" type="admin" func="updateconfig"}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
	<input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />
    <fieldset>
        <legend>{gt text='General settings'}</legend>
        <div class="z-formrow">
            <b><a href='{modurl modname="PostCalendar" type="admin" func="resetDefaults"}'>{gt text='Reset ALL Settings to Defaults (clears event defaults also)'}</a></b>
        </div>
        <div class="z-formrow">
			<label for="pcAllowDirectSubmit">{gt text='Allow submitted events to be activated without review'}</label>
			<input type="checkbox" value="1" id="pcAllowDirectSubmit" name="pcAllowDirectSubmit"{if $modvars.PostCalendar.pcAllowDirectSubmit eq true} checked="checked"{/if}/>
        </div>
		<div class="z-formrow">
			<label for="enablecategorization">{gt text='Enable categorization of events'}</label>
			<input type="checkbox" value="1" id="enablecategorization" name="enablecategorization"{if $modvars.PostCalendar.enablecategorization eq true} checked="checked"{/if}/>
		</div>
		<div class="z-formrow">
			<label for="pcAllowUserCalendar">{gt text='Group allowed to publish personal calendars'}</label>
            {gt text="No group" assign="nogroup"}
			<span>{selector_group selectedValue=$modvars.PostCalendar.pcAllowUserCalendar defaultValue=0 allValue=0 allText=$nogroup name="pcAllowUserCalendar" id="pcAllowUserCalendar"}
                &nbsp;&nbsp;<a href="{modurl modname="Groups" type="admin" func="view"}">{img src='xedit.png' modname='core' set='icons/extrasmall' __title="Edit groups" __alt="Edit groups"}</a></span>
                <em class="z-formnote z-sub">{gt text="Adds selector to filter in navigation bar."}</em>
		</div>
    </fieldset>
	<fieldset>
        <legend>{gt text='Display settings'}</legend>
		<div class="z-formrow">
			<label for="pcListHowManyEvents">{gt text='Number of events to list on administration pages'}</label>
			<span><input type="text" size="3" value="{$modvars.PostCalendar.pcListHowManyEvents}" id="pcListHowManyEvents" name="pcListHowManyEvents" /></span>
		</div>
		<div class="z-formrow">
			<label for="pcTime24Hours">{gt text='Use 24-hour time format'}</label>
			<input type="checkbox" value="1" id="pcTime24Hours" name="pcTime24Hours"{if $modvars.PostCalendar.pcTime24Hours eq true} checked="checked"{/if}/>
		</div>
		<div class="z-formrow">
			<label for="pcTimeIncrement">{gt text='Time increment for new event creation (1-60 minutes)'}</label>
			<span><input type="text" size="3" value="{$modvars.PostCalendar.pcTimeIncrement}" id="pcTimeIncrement" name="pcTimeIncrement" /></span>
		</div>
		<div class="z-formrow">
			<label for="pcEventsOpenInNewWindow">{gt text='View event details in a pop-up window'}</label>
			<input type="checkbox" value="1" id="pcEventsOpenInNewWindow" name="pcEventsOpenInNewWindow"{if $modvars.PostCalendar.pcEventsOpenInNewWindow eq true} checked="checked"{/if}/>
		</div>
		<div class="z-formrow">
			<label for="pcUsePopups">{gt text="Show event description in javascript tooltip on 'mouseover'"}</label>
			<input type="checkbox" value="1" id="pcUsePopups" name="pcUsePopups"{if $modvars.PostCalendar.pcUsePopups eq true} checked="checked"{/if}/>
		</div>
		<div class="z-formrow">
			<label for="pcEventDateFormat">{gt text='Date Display Format'}</label>
			<span><select size="1" id="pcEventDateFormat" name="pcEventDateFormat">
				<option value="DMY"{if $modvars.PostCalendar.pcEventDateFormat eq "DMY"} selected="selected"{/if}>{gt text='Day Month Year (EUR)'}</option>
				<option value="MDY"{if $modvars.PostCalendar.pcEventDateFormat eq "MDY"} selected="selected"{/if}>{gt text='Month Day, Year (US)'}</option>
				<option value="YMD"{if $modvars.PostCalendar.pcEventDateFormat eq "YMD"} selected="selected"{/if}>{gt text='Year-Month-Day'}</option>
				<option value="-1"{if $modvars.PostCalendar.pcEventDateFormat eq "-1"} selected="selected"{/if}>{gt text='Custom'}</option>
            </select></span>
		</div>
        <div id='manuallySetDateFormats'{if $modvars.PostCalendar.pcEventDateFormat ne "-1"}style="display: none"{/if}>
            <div class="z-formrow">
                <label for="dateformat_date">{gt text='php %s format' tag1='<a href="http://php.net/date" target="_blank">date()</a>'}</label>
                <span><input type="text" value="{$modvars.PostCalendar.pcDateFormats.date}" id="dateformat_date" name="pcDateFormats[date]" /></span>
            </div>
            <div class="z-formrow">
                <label for="dateformat_strftime">{gt text='php %s format' tag1='<a href="http://php.net/strftime" target="_blank">php strftime()</a>'}</label>
                <span><input type="text" value="{$modvars.PostCalendar.pcDateFormats.strftime}" id="dateformat_strftime" name="pcDateFormats[strftime]" /></span>
            </div>
            <div class="z-formrow">
                <label for="dateformat_javascript">{gt text='jquery %s format' tag1='<a href="http://docs.jquery.com/UI/Datepicker/parseDate" taget="_blank">datepicker</a>'}</label>
                <span><input type="text" value="{$modvars.PostCalendar.pcDateFormats.javascript}" id="dateformat_javascript" name="pcDateFormats[javascript]" /></span>
            </div>            
        </div>
		<div class="z-formrow">
			<label for="pcFirstDayOfWeek">{gt text='First day of the week'}</label>
			<span><select size="1" id="pcFirstDayOfWeek" name="pcFirstDayOfWeek">
				<option value="0"{if $modvars.PostCalendar.pcFirstDayOfWeek eq 0} selected="selected"{/if}>{gt text='Sunday'}</option>
				<option value="1"{if $modvars.PostCalendar.pcFirstDayOfWeek eq 1} selected="selected"{/if}>{gt text='Monday'}</option>
				<option value="6"{if $modvars.PostCalendar.pcFirstDayOfWeek eq 6} selected="selected"{/if}>{gt text='Saturday'}</option>
				</select></span>
			
		</div>
		<div class="z-formrow">
			<label for="pcDefaultView">{gt text='Default calendar view'}</label>
			<span><select size="1" id="pcDefaultView" name="pcDefaultView">
				<option value="day"{if $modvars.PostCalendar.pcDefaultView eq "day"} selected="selected"{/if}>{gt text='Day'}</option>
				<option value="week"{if $modvars.PostCalendar.pcDefaultView eq "week"} selected="selected"{/if}>{gt text='Week'}</option>
				<option value="month"{if $modvars.PostCalendar.pcDefaultView eq "month"} selected="selected"{/if}>{gt text='Month'}</option>
				<option value="year"{if $modvars.PostCalendar.pcDefaultView eq "year"} selected="selected"{/if}>{gt text='Year'}</option>
				<option value="list"{if $modvars.PostCalendar.pcDefaultView eq "list"} selected="selected"{/if}>{gt text='List'}</option>
				</select></span>
		</div>
		<div class="z-formrow">
			<label for="pcAllowedViews">{gt text='Views/links available to user'}</label>
			<span><select multiple="multiple" size="5" id="pcAllowedViews" name="pcAllowedViews[]">
				<option value="today"{if in_array('today', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Today link'}</option>
				<option value="day"{if in_array('day', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Day'}</option>
				<option value="week"{if in_array('week', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Week'}</option>
				<option value="month"{if in_array('month', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Month'}</option>
				<option value="year"{if in_array('year', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Year'}</option>
				<option value="list"{if in_array('list', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='List'}</option>
				<option value="create"{if in_array('create', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Create link'}</option>
				<option value="search"{if in_array('search', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Search link'}</option>
				<option value="print"{if in_array('print', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Print'}</option>
				<option value="xml"{if in_array('xml', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Xml/RSS'}</option>
				<option value="event"{if in_array('event', $modvars.PostCalendar.pcAllowedViews)} selected="selected"{/if}>{gt text='Event'}</option>
				</select></span>
                <em class="z-formnote z-sub">{gt text="The create item is also controlled by %s permissions settings." tag1='ACCESS_ADD'}</em>
                <em class="z-formnote z-sub">{gt text="The event view is also controlled by %s permissions settings." tag1='ACCESS_READ'}</em>
		</div>
		<div class="z-formrow">
			<label for="pcListMonths">{gt text='Number of months to display in list/rss view'}</label>
			<span><input type="text" size="3" maxlength="3" value="{$modvars.PostCalendar.pcListMonths}" id="pcListMonths" name="pcListMonths" /></span>
		</div>
        {modavailable modname="Locations" assign="locationsAvailable"}
        {if $locationsAvailable}
        <div class="z-formrow">
			<label for="enablelocations">{gt text='Enable Locations for PostCalendar'}</label>
			<input type="checkbox" value="1" id="enablelocations" name="enablelocations"{if $modvars.PostCalendar.enablelocations eq true} checked="checked"{/if}/>
		</div>
        {/if}
    </fieldset>
	<fieldset>
        <legend>{gt text='Navigation display settings'}</legend>
		<div class="z-formrow">
			<label for="pcNavBarType">{gt text='Navigation bar type'}</label>
			<span><select size="1" id="pcNavBarType" name="pcNavBarType">
				<option value="buttonbar"{if $modvars.PostCalendar.pcNavBarType eq 'buttonbar'} selected="selected"{/if}>{gt text='jQuery Button Bar'}</option>
				<option value="plain"{if $modvars.PostCalendar.pcNavBarType eq 'plain'} selected="selected"{/if}>{gt text='Plain text or images'}</option>
				</select></span>
		</div>
		<div class="z-formrow">
			<label for="pcAllowCatFilter">{gt text='Allow users to filter event display by category'}</label>
			<input type="checkbox" value="1" id="pcAllowCatFilter" name="pcAllowCatFilter"{if $modvars.PostCalendar.pcAllowCatFilter eq true} checked="checked"{/if}/>
		</div>
		<div class="z-formrow">
			<label for="pcFilterYearStart">{gt text='In jump date selector, display'}</label>
			<span><input type="text" size="3" value="{$modvars.PostCalendar.pcFilterYearStart}" id="pcFilterYearStart" name="pcFilterYearStart" />
                 &nbsp;{gt text='year(s) before current year.'}</span>
		</div>
		<div class="z-formrow">
			<label for="pcFilterYearEnd">{gt text='In jump date selector, display'}</label>
			<span><input type="text" size="3" value="{$modvars.PostCalendar.pcFilterYearEnd}" id="pcFilterYearEnd" name="pcFilterYearEnd" />
                 &nbsp;{gt text='year(s) after current year.'}</span>
		</div>
        <div class="z-formrow">
			<label for="enablenavimages">{gt text='Enable images in navigation header'}</label>
			<input type="checkbox" value="1" id="enablenavimages" name="enablenavimages"{if $modvars.PostCalendar.enablenavimages eq true} checked="checked"{/if}/>
		</div>
    </fieldset>
	<fieldset>
        <legend>{gt text='Notification settings'}</legend>
		<div class="z-formrow">
			<label for="pcNotifyPending">{gt text='Notify administrator about pending content in template'}</label>
			<input type="checkbox" value="1" id="pcNotifyPending" name="pcNotifyPending"{if $modvars.PostCalendar.pcNotifyPending eq true} checked="checked"{/if}/>
		</div>
		<div class="z-formrow">
			<label for="pcNotifyAdmin">{gt text='Notify administrator about user event submission/change'}</label>
			<input type="checkbox" value="1" id="pcNotifyAdmin" name="pcNotifyAdmin"{if $modvars.PostCalendar.pcNotifyAdmin eq true} checked="checked"{/if}/>
		</div>
		<div class="z-formrow">
			<label for="pcNotifyAdmin2Admin">{gt text='Notify administrator about administrator event submission/change'}</label>
			<input type="checkbox" value="1" id="pcNotifyAdmin2Admin" name="pcNotifyAdmin2Admin"{if $modvars.PostCalendar.pcNotifyAdmin2Admin eq true} checked="checked"{/if}/>
		</div>
		<div class="z-formrow">
			<label for="pcNotifyEmail">{gt text='E-mail address to send notifications'}</label>
			<span><input type="text" size="30" value="{$modvars.PostCalendar.pcNotifyEmail}" id="pcNotifyEmail" name="pcNotifyEmail" /></span>
		</div>
    </fieldset>
    <div class="z-buttons z-formbuttons">
        {button src="button_ok.png" set="icons/extrasmall" class='z-btgreen' __alt="Save" __title="Save" __text="Save"}
        <a class='z-btred' href="{modurl modname="PostCalendar" type="admin" func='listevents'}" title="{gt text="Cancel"}">{img modname='core' src="button_cancel.png" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
    </div>
    </div>
</form>
{adminfooter}
