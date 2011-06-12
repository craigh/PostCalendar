{nocache}
{ajaxheader module="PostCalendar" ui=true}
{gt text="All These Categories" assign="allText"}
{foreach from=$catregistry key='property' item='category'}
    {array_field assign="selectedValue" array=$selectedcategories field=$property}
    {selector_category
        editLink=0
        category=$category
        name="postcalendar_events[__CATEGORIES__][$property]"
        field="id"
        selectedValue=$selectedValue
        defaultValue="0"
        all=1
        allText=$allText
        allValue=0}
    <a href='#' id='postcalendar_events___CATEGORIES____{$property}__open' title='{gt text="Select multiple categories"}'>
        {img modname="core" src="edit_add.png" set="icons/extrasmall" __alt="Select Multiple" __title="Select Multiple"}
    </a>
    <script type="text/javascript">
        var postcalendar_events___CATEGORIES____{{$property}}_ = new Zikula.UI.SelectMultiple(
            'postcalendar_events___CATEGORIES____{{$property}}_',
            {opener: 'postcalendar_events___CATEGORIES____{{$property}}__open',
            okLabel: Zikula.__('Done!','module_PostCalendar'),
            value: '{{pc_implode value=$selectedValue}}',
            excludeValues: ['0']}
        );
    </script>
{/foreach}
{/nocache}