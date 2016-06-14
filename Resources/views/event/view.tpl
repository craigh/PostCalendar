<div class="postcalendar_event_view">
<h2 class="postcalendar_header">
    {$loaded_event.title|safehtml}
    <span class='postcalendar_eid'>eid# {$loaded_event.eid}</span>
</h2>
<div class="calcontainer">
    <div class="eventtime">
        {$loaded_event.eventStart|pc_date_format:$modvars.PostCalendar.pcDateFormats.date}<br />
        {if $loaded_event.alldayevent != true}
            {$loaded_event.startTime} - {$loaded_event.endTime}<br />
        {else}
            {gt text='All day event'}
        {/if}
    </div>
    <div class="eventdetails">
        <div>
            <h3>{gt text='Description'}:</h3>
            {$loaded_event.hometext|notifyfilters:'postcalendar.filter_hooks.eventsfilter.filter'|safehtml}
        </div>
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
        {if count($loaded_event.categories) gt 0}
        <div class="postcalendar_event_categoryinfo">
            {lang assign="lang"}
            <h3>{gt text='Categorized in'}:</h3>
            <ul>
                {foreach from=$loaded_event.categories key="property" item="category"}
                    {if isset($category.attributes.color)}
                        {assign var='bgcolor' value=$category.attributes.color}
                    {else}
                        {assign var='bgcolor' value='#ffffff'}
                    {/if}
                {if isset($category.display_name.$lang)}
                    {assign var='catname' value=$category.display_name.$lang}
                {else}
                    {assign var='catname' value=$category.name}
                {/if}
                    <li><span style='padding: 0 1em; background-color:{$bgcolor};'>{$catname}</span></li>
                {/foreach}
            </ul>
        </div>
        {/if}
        <div class="postcalendar_event_recurrinfo">
            <h3>{gt text='Event recurrance information'}:</h3>
            <p>{$loaded_event.recurr_sentence}</p>
        </div>
        <div class="postcalendar_event_sharinginfo">
            <h3>{gt text='Event sharing information'}:</h3>
            <p>{$loaded_event.sharing_sentence}</p>
        </div>
    </div>
</div>
{if isset($EVENT_CAN_EDIT) && $EVENT_CAN_EDIT}
<div>
    <a class='z-icon-es-edit' href="{modurl modname="PostCalendar" type="event" func="edit" eid=$loaded_event.eid}">{gt text='Edit event'}</a> |
    <a class='z-icon-es-copy' href="{modurl modname="PostCalendar" type="event" func="copy" eid=$loaded_event.eid}">{gt text='Copy event'}</a> |
    <a class='z-icon-es-delete' href="{modurl modname="PostCalendar" type="event" func="delete" eid=$loaded_event.eid}">{gt text='Delete event'}</a>
</div>
{/if}
</div><!-- end postcalendar_event_view -->