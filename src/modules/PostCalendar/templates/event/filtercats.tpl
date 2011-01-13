{ajaxheader module="PostCalendar" ui=true}
{gt text="All These Categories" assign="allText"}
{nocache}
{foreach from=$catregistry key=property item=category}
    {array_field_isset assign="selectedValue" array=$selectedcategories field=$property returnValue=1}
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
    <a href='' id='postcalendar_events___CATEGORIES____{$property}__open'>
        {img modname="core" src="edit_add.gif" set="icons/extrasmall" __alt="Select Multiple" __title="Select Multiple"}
    </a>
    <script type="text/javascript">
        var postcalendar_events___CATEGORIES____{{$property}}_ = new Zikula.UI.SelectMultiple(
            'postcalendar_events___CATEGORIES____{{$property}}_',
            {opener: 'postcalendar_events___CATEGORIES____{{$property}}__open',
            title: Zikula.__('Select multiple categories','module_PostCalendar'),
            value: '{{pc_implode value=$selectedValue}}',
            excludeValues: ['0']}
        );
    </script>
{/foreach}
{/nocache}