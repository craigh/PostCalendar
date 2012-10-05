{nocache}
{gt text="All These Categories" assign="allText"}
{foreach from=$catregistry key='property' item='category'}
    {selector_category
        editLink=0
        category=$category
        name="pc_categories[$property]"
        field="id"
        selectedValue=$selectedcategories
        defaultValue="0"
        all=1
        allText=$allText
        allValue=0}
    <a href='#' id='pc_categories_{$property}__open' title='{gt text="Select multiple categories"}'>{img modname="core" src="edit_add.png" set="icons/extrasmall" __alt="Select Multiple" __title="Select Multiple"}</a>
    <script type="text/javascript">
        var pc_categories_{{$property}}_ = new Zikula.UI.SelectMultiple(
            'pc_categories_{{$property}}_',
            {opener: 'pc_categories_{{$property}}__open',
            okLabel: Zikula.__('Done!','module_PostCalendar'),
            value: '{{pc_implode value=$selectedcategories}}',
            excludeValues: ['0']}
        );
    </script>
{/foreach}
{/nocache}