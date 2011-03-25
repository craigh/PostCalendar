{include file="admin/menu.tpl"}
{if ($actiontext != "view")}
	<form action="{modurl modname="PostCalendar" type="admin" func="updateevents"}" method="post">
    	<input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />
        <input type="hidden" name="action" value="{$action}">
{/if}
<div class="z-admincontainer">
<div class="z-adminpageicon">{img modname='PostCalendar' src='admin.png'}</div>
<h2>{gt text="Event review"}</h2>
{assign var="popup" value=true}
{foreach from=$alleventinfo key='eid' item='loaded_event'}
	{include file="event/view.tpl"}
    <hr />
	<input type="hidden" name="pc_eid[]" value="{$eid}" />
{/foreach}
</div><!-- /z-admincontainer -->
{if ($actiontext != "view")}
	<div class='z-warningmsg'>{$areyousure}</div>
    <div class="z-buttons z-formbuttons">
        {button src="button_ok.png" set="icons/extrasmall" class='z-btgreen' __alt="Yes" __title="Yes" __text="Yes"}
        <a class='z-btred' href="{modurl modname="PostCalendar" type="admin" func=listevents}" title="{gt text="Cancel"}">{img modname='core' src="button_cancel.png" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
    </div>
	</form>
{/if}