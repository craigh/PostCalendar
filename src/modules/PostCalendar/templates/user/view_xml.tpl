{pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$A_EVENTS}
{foreach name=dates item=events key=date from=$S_EVENTS}
  {pc_sort_events var="S_EVENTS" sort="time" order="asc" value=$A_EVENTS}
  {if ((isset($S_EVENTS.$date)) && (count($S_EVENTS.$date) gt 0))}
  {foreach name=events item=event from=$S_EVENTS.$date}
<item>
<title>{$date|pc_date_format} : {$event.title|strip_tags} ({$event.catname})</title>
<link>{modurl modname=PostCalendar type=user func=main viewtype=details eid=$event.eid fqurl=true}</link>
<description>{if $event.alldayevent != true}{$event.startTime} - {$event.endTime}{else}{gt text='All-day event'}{/if} {$event.hometext|notifyfilters:'postcalendar.hook.eventsfilter.ui.filter'|strip_tags}</description>
<comments>{modurl modname='PostCalendar' func='main' viewtype='details' eid=$event.eid}#comments</comments>
</item>
  {/foreach}
  {/if}
{/foreach}