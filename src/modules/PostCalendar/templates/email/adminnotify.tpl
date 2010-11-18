{* $Id: postcalendar_email_adminnotify.htm 612 2010-06-22 15:15:51Z craigh $ *}
<h2>{gt text="Notice: PostCalendar submission/change"}</h2>

{if $is_update eq true}
	<p>{gt text="The following calendar event has been changed:"}</p>
{else}
	<p>{gt text="The following calendar event has been added:"}</p>
{/if}

<p><a href='{$link|safehtml}'>{gt text="Event"} #{$eid}</a></p>
<hr />
<p>{gt text="A Message from your PostCalendar"} {$modversion}<br />
@ {$smarty.now|date_format:'%Y-%m-%d %H:%M:%S'}<br />
{gt text="from"} {$smarty.server.SERVER_NAME}</p>
