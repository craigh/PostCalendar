{* $Id: postcalendar_user_view_event_details.htm 639 2010-06-30 22:16:08Z craigh $ *}
{checkpermission comp="::" inst=".*" level="ACCESS_ADD" assign="ACCESS_ADD"}
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
            <a href="{modurl modname="PostCalendar" type="event" func="edit" eid=$loaded_event.eid}">{gt text='Edit event'}</a> |
            <a href="{modurl modname="PostCalendar" type="event" func="copy" eid=$loaded_event.eid}">{gt text='Copy event'}</a> |
            <a href="{modurl modname="PostCalendar" type="event" func="delete" eid=$loaded_event.eid}">{gt text='Delete event'}</a>
        </div>
    {/if}
{else}
    <div style='text-align:right;'>
        {assign var="viewtype" value=$smarty.get.viewtype}
        {if ((empty($smarty.get.viewtype)) or (!isset($smarty.get.viewtype)))}
            {modgetvar module="PostCalendar" name="pcDefaultView" assign="viewtype"}
        {/if}
        {formutil_getpassedvalue name="Date" source="get" assign="Date" default=''}
        <a href="{modurl modname="PostCalendar" func="view" viewtype=$viewtype Date=$Date eid=$loaded_event.eid}">{gt text='Return'}</a>
    </div>
{/if}
{modcallhooks hookobject=item hookaction=display hookid=$loaded_event.eid returnurl="index.php?module=PostCalendar&func=view&viewtype=details&eid=`$loaded_event.eid`"}
{include file="user/footer.tpl"}