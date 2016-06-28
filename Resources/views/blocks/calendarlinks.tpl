<div class="pc_centerblocksubmitlinks">
    {checkpermissionblock component="PostCalendar::" instance=".*" level="ACCESS_ADD"}
        [<a href='{modurl modname="ZikulaPostCalendarModule" type="event" func="create"}'>{gt text='Create New'}</a>]
        &nbsp;
    {/checkpermissionblock}
    [<a href='{modurl modname="Search" type='user' func='main'}'>{gt text='Search'}</a>]
</div>