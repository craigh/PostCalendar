{if $loaded_event.showhiddenwarning}
    {checkpermission component="PostCalendar::" instance="::" level="ACCESS_ADMIN" assign="ACCESS_ADMIN"}
    {if $ACCESS_ADMIN}
        <div class="z-warningmsg" style='font-size: 8px;'>{gt text='Administrator only warning: Hidden PostCalendar Featured Event Block (ID# %s). Delete or modify.' tag1=$thisblockid}<br />
        <a href="{modurl modname='Blocks' type='admin' func='view'}">{gt text='Blocks Administration'}</a></div>
    {/if}
{else}
<div class='postcalendar_featuredevent'>
{if $loaded_event.showcountdown}
<div class='eventcountdown'>
    {if $loaded_event.datedifference gt 0}
        {gt text='This event will occur in %s day.' plural='This event will occur in %s days.' count=$loaded_event.datedifference tag1=$loaded_event.datedifference}
    {elseif $loaded_event.datedifference eq 0}
        {gt text='This event occurs today.'}
    {else}
        {gt text='This event has already occured.'}
    {/if}
</div>
{/if}
<h2 class="eventheader">
    {gt text='private event' assign='p_txt'}
    {if $loaded_event.privateicon}{img src='lock.gif' modname='PostCalendar' title=$p_txt alt=$p_txt}{/if}
    {$loaded_event.title|safehtml}
    <a href="{modurl modname='PostCalendar' type='user' func='display' viewtype='event' eid=$loaded_event.eid}" title='{gt text='Full event information'}'>
    {gt text='Full event information' assign='title'}{gt text='Info' assign='alt'}
    {img modname='core' src='info.png' set='icons/extrasmall' alt=$alt title=$title}</a>
    {if $loaded_event.commentcount gt 0}
        {gt text='%s comment left' plural='%s comments left.' count=$loaded_event.commentcount tag1=$loaded_event.commentcount assign="title"}
        {gt text='Comment' assign='alt'}
        <a href="{modurl modname='PostCalendar' type='user' func='display' viewtype='event' eid=$loaded_event.eid}#comments" title='{$title}'>
        {img modname='core' src='comment.png' set='icons/extrasmall' alt=$alt title=$title}</a>
    {/if}
</h2>
<div class="eventtime">
    {$loaded_event.eventStart->format($modvars.PostCalendar.pcDateFormats.date)}<br />
    {if $loaded_event.alldayevent != true}
        {$loaded_event.startTime} - {$loaded_event.endTime}<br />
    {else}
        {gt text='All day event'}
    {/if}
</div>
<div class="eventdetails">
    {if ($loaded_event.hometext) && ($loaded_event.hometext ne "n/a")}
    <div>
        <h3>{gt text='Description'}:</h3>
        {$loaded_event.hometext|notifyfilters:'postcalendar.hook.eventsfilter.ui.filter'|safehtml}
    </div>
    {/if}
    <div>
        {if ($loaded_event.location.event_location) OR ($loaded_event.location.event_street1) OR ($loaded_event.location.event_street2) OR ($loaded_event.location.event_city)}
        <h3>{gt text='Location'}:</h3>
        <span class="location">
            {if $loaded_event.location.event_location}<span class="location_name">{$loaded_event.location.event_location}</span><br />{/if}
            {if $loaded_event.location.event_street1}<span class="location_street1">{$loaded_event.location.event_street1}</span><br />{/if}
            {if $loaded_event.location.event_street2}<span class="location_street2">{$loaded_event.location.event_street2}</span><br />{/if}
            {if $loaded_event.location.event_city}<span class="location_city_state_zip">{$loaded_event.location.event_city}&nbsp;{$loaded_event.location.event_state},&nbsp;{$loaded_event.location.event_postal}</span><br />{/if}
        </span>
        {/if}
        {if ($loaded_event.contname) OR ($loaded_event.conttel) OR ($loaded_event.contemail) OR ($loaded_event.website)}
        <h3>{gt text='Contact information'}:</h3>
        <ul>
            {if $loaded_event.contname}<li>{$loaded_event.contname}</li>{/if}
            {if $loaded_event.conttel}<li>{$loaded_event.conttel}</li>{/if}
            {if $loaded_event.contemail}<li><a href="mailto:{$loaded_event.contemail}">{$loaded_event.contemail}</a></li>{/if}
            {if $loaded_event.website}<li><a href="{$loaded_event.website}" target="_blank">{$loaded_event.website}</a></li>{/if}
        </ul>
        {/if}
        {if $loaded_event.fee}{gt text='Fee'}: {$loaded_event.fee}{/if}
    </div>
</div>
</div> <!-- end postcalendar_featuredevent -->
{/if}