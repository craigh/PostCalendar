{include file="admin/menu.tpl"}
<div class="z-admincontainer">
<div class="z-adminpageicon">{img modname='HelloWorld' src='admin.png'}</div>
<h2>{gt text="Hello World settings"}&nbsp;({gt text="version"}&nbsp;{$version})</h2>
<form class="z-form" action="{modurl modname="HelloWorld" type="admin" func="updateconfig"}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
	<input type="hidden" name="authid" value="{secgenauthkey module="HelloWorld"}" />
    <fieldset>
        <legend>{gt text='General settings'}</legend>
        <div class="z-formrow">
			<label for="pcAllowDirectSubmit">{gt text='Allow submitted events to be activated without review'}</label>
			{modgetvar module="PostCalendar" name="pcAllowDirectSubmit" assign="pcADS"}
			<input type="checkbox" value="1" id="pcAllowDirectSubmit" name="pcAllowDirectSubmit"{if $pcADS eq true} checked="checked"{/if}/>
        </div>
    </fieldset>
    <div class="z-buttons z-formbuttons">
        {button src="button_ok.gif" set="icons/extrasmall" __alt="Save" __title="Save" __text="Save"}
        <a href="{modurl modname="HelloWorld" type="admin"}" title="{gt text="Cancel"}">{img modname=core src="button_cancel.gif" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
    </div>
    </div>
</form>
</div><!-- /z-admincontainer -->