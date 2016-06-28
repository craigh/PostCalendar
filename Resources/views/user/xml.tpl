{pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$eventsByDate}
{foreach name='dates' item='events' key='date' from=$S_EVENTS}
  {*pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$eventsByDate*}
  {if ((isset($S_EVENTS.$date)) && (count($S_EVENTS.$date) gt 0))}
  {foreach name='events' item='event' from=$S_EVENTS.$date}
<item>
<title>{$date|pc_date_format} : {$event.title|strip_tags} ({$event.catname})</title>
<link>{modurl modname='ZikulaPostCalendarModule' type='user' func='display' viewtype='event' eid=$event.eid fqurl=true}</link>
<description>{if $event.alldayevent != true}{$event.startTime} - {$event.endTime}{else}{gt text='All-day event'}{/if} {$event.hometext|notifyfilters:'postcalendar.filter_hooks.eventsfilter.filter'|strip_tags}</description>
</item>
  {/foreach}
  {/if}
{/foreach}