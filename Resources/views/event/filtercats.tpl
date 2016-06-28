{nocache}
{gt text="All These Categories" assign="allText"}
{foreach from=$catregistry key='property' item='category'}
    {selector_category
        editLink=0
        category=$category
        name="filtercats[$property]"
        field="id"
        selectedValue=$selectedcategories
        defaultValue="0"
        all=1
        allText=$allText
        allValue=0}
    <a href='#' id='filtercats_{$property}__open' title='{gt text="Select multiple categories"}'>{img modname="core" src="edit_add.png" set="icons/extrasmall" __alt="Select Multiple" __title="Select Multiple"}</a>
    <script type="text/javascript">
        var filtercats_{{$property}}_ = new Zikula.UI.SelectMultiple(
            'filtercats_{{$property}}_',
            {opener: 'filtercats_{{$property}}__open',
            okLabel: '{{gt text='Done'}}',
            value: '{{pc_implode value=$selectedcategories}}',
            excludeValues: ['0']}
        );
    </script>
{/foreach}
{/nocache}
