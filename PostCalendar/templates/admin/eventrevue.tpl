{*  $Id: postcalendar_admin_eventrevue.htm 639 2010-06-30 22:16:08Z craigh $  *}
{include file="admin/menu.tpl"}
{if (!empty($function))}
	<form action="{modurl modname="PostCalendar" type="admin" func=$function}" method="post">
{/if}
<div class="z-admincontainer">
<div class="z-adminpageicon">{img modname='PostCalendar' src='admin.png'}</div>
<h2>{gt text="Event review"}</h2>
{assign var="popup" value=true}
{foreach from=$alleventinfo key=eid item=loaded_event}
	{include file="event/view.tpl"}
    <hr />
	<input type="hidden" name="pc_eid[]" value="{$eid}" />
{/foreach}
</div><!-- /z-admincontainer -->
{if (!empty($function))}
	<div>{$areyousure}</div>
    <div class="z-formbuttons">
        {button src=button_ok.gif set=icons/small __alt="Yes" __title="Yes"}
        <a href="{modurl modname=PostCalendar type=admin func=listqueued}">{img modname=core src=button_cancel.gif set=icons/small __alt="Cancel" __title="Cancel"}</a>
    </div>
	</form>
{/if}