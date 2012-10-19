{formutil_getpassedvalue name="theme" source="get" assign="theme" default=false}
{assign var="PRINT_VIEW" value=0}
{if $theme eq "Printer"}
    {* page presented in printer theme *}
    {assign var="PRINT_VIEW" value=1}
{/if}
{if $PRINT_VIEW eq false}
{$navBar}
{/if}

{include file="event/view.tpl"}

{if $PRINT_VIEW eq true}
    <div style='text-align:right;'>
        {assign var="viewtype" value=$smarty.get.viewtype}
        {if ((empty($smarty.get.viewtype)) or (!isset($smarty.get.viewtype)))}
            {assign var="viewtype" value=$modvars.PostCalendar.pcDefaultView}
        {/if}
        {formutil_getpassedvalue name="date" source="get" assign="date" default=''}
        <a href="{modurl modname="PostCalendar" type='user' func='display' viewtype=$viewtype Date=$date eid=$loaded_event.eid}">{gt text='Return'}</a>
    </div>
{/if}
{modurl modname='PostCalendar' type='user' func='display' viewtype='event' eid=$loaded_event.eid assign='returnurl'}
{notifydisplayhooks eventname='postcalendar.ui_hooks.events.ui_view' id=$loaded_event.eid}
{include file="user/footer.tpl"}