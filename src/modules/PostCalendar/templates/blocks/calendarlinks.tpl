<div class="pc_centerblocksubmitlinks">
    {checkpermissionblock component="PostCalendar::" instance=".*" level="ACCESS_ADD"}
        [<a href='{modurl modname="PostCalendar" type="event" func="create"}'>{gt text='Create New'}</a>]
        &nbsp;
    {/checkpermissionblock}
    [<a href='{modurl modname="Search" type='user' func='form'}'>{gt text='Search'}</a>]
</div>