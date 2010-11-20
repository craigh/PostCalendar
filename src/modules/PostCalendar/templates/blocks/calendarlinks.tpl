{checkpermission component="::" instance=".*" level="ACCESS_ADD" assign="ACCESS_ADD"}
<div class="pc_centerblocksubmitlinks">
    {if $ACCESS_ADD}
        [<a href='{modurl modname="PostCalendar" type="event" func="create"}'>{gt text='Create New'}</a>]
        &nbsp;
    {/if}
    [<a href='{modurl modname="Search"}'>{gt text='Search'}</a>]
</div>