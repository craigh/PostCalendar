{*  $Id: postcalendar_event_deleteeventconfirm.htm 596 2010-06-05 01:14:46Z craigh $  *}
<h2>{gt text="Delete event"}</h2>

{form}

	<div class="row">
		<b>{$loaded_event.title}</b> ({$loaded_event.eventDate|pc_date_format})
	</div>
	
	<p>{gt text="Do you really want to delete this event?"}
	{formbutton commandName="delete" __text="Delete"}{* could include in formbutton call __confirmMessage="Delete" *}
	{formbutton commandName="cancel" __text="CANCEL"}</p>

{/form}