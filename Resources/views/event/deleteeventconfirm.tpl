<h2>{gt text="Delete event"}</h2>
{form}
    <div class="row">
        <b>{$loaded_event.title}</b> ({$loaded_event.eventStart|pc_date_format:$modvars.ZikulaPostCalendarModule.pcDateFormats.date})
    </div>
    {notifydisplayhooks eventname='postcalendar.ui_hooks.events.ui_delete' id=$loaded_event.eid}
    <p>{gt text="Do you really want to delete this event?"}
	{formbutton commandName="delete" __text="Delete"}{* could include in formbutton call __confirmMessage="Delete" *}
	{formbutton commandName="cancel" __text="CANCEL"}</p>
{/form}