{checkpermission component="::" instance=".*" level="ACCESS_ADD" assign="ACCESS_ADD"}
{formutil_getpassedvalue name="theme" source="get" assign="theme" default=false}
{assign var="PRINT_VIEW" value=0}
{if $theme eq "Printer"}
    {* page presented in printer theme *}
    {assign var="PRINT_VIEW" value=1}
{/if}
{if $PRINT_VIEW eq false}
    {include file="user/navigation_small.tpl"}
{/if}

{include file="event/view.tpl"}

{if $PRINT_VIEW eq false}
    {if $EVENT_CAN_EDIT}
        <div>
            <a class='z-icon-es-edit' href="{modurl modname="PostCalendar" type="event" func="edit" eid=$loaded_event.eid}">{gt text='Edit event'}</a> |
            <a class='z-icon-es-copy' href="{modurl modname="PostCalendar" type="event" func="copy" eid=$loaded_event.eid}">{gt text='Copy event'}</a> |
            <a class='z-icon-es-delete' href="{modurl modname="PostCalendar" type="event" func="delete" eid=$loaded_event.eid}">{gt text='Delete event'}</a>
        </div>
    {/if}
{else}
    <div style='text-align:right;'>
        {assign var="viewtype" value=$smarty.get.viewtype}
        {if ((empty($smarty.get.viewtype)) or (!isset($smarty.get.viewtype)))}
            {assign var="viewtype" value=$modvars.PostCalendar.pcDefaultView}
        {/if}
        {formutil_getpassedvalue name="Date" source="get" assign="Date" default=''}
        <a href="{modurl modname="PostCalendar" type='user' func='display' viewtype=$viewtype Date=$Date eid=$loaded_event.eid}">{gt text='Return'}</a>
    </div>
{/if}
{modurl modname='PostCalendar' type='user' func='display' viewtype='details' eid=$loaded_event.eid assign='returnurl'}
{notifydisplayhooks eventname='postcalendar.ui_hooks.events.ui_view' id=$loaded_event.eid}
{include file="user/footer.tpl"}