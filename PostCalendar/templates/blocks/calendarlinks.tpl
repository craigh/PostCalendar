{* $Id: postcalendar_block_calendarlinks.htm 596 2010-06-05 01:14:46Z craigh $ *}
{checkpermission comp="::" inst=".*" level="ACCESS_ADD" assign="ACCESS_ADD"}
<div class="pc_centerblocksubmitlinks">
    {if $ACCESS_ADD}
        [<a href='{modurl modname="PostCalendar" type="event" func="new"}'>{gt text='Create New'}</a>]
        &nbsp;
    {/if}
    [<a href='{modurl modname="Search"}'>{gt text='Search'}</a>]
</div>