<div class="z-formrow">
    <label for="postcalendar_eid">{gt text="Event ID (eid)"}</label>
    <input id="postcalendar_eid" type="text" name="eid" value="{$vars.eid}" size="5" /><br />
</div>
<div class="z-formrow">
    <label for="postcalendar_showcountdown">{gt text="Show countdown (days)"}</label>
    <input id="postcalendar_showcountdown" type="checkbox" name="showcountdown" value="1" {if $vars.showcountdown}checked="checked"{/if}/>
</div>
<div class="z-formrow">
    <label for="postcalendar_hideonexpire">{gt text="Hide block when countdown expires"}</label>
    <input id="postcalendar_hideonexpire" type="checkbox" name="hideonexpire" value="1" {if $vars.hideonexpire}checked="checked"{/if}/>
</div>