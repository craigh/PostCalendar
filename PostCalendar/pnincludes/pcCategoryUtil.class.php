<?php
if (!class_exists('CategoryUtil')) {
    Loader::requireOnce('CategoryUtil.class.php');
}
/**
 * class pcCategoryUtil extends CategoryUtil in order to enhance multiple select option with LivePipe
 *
 * @package     Zikula_Core
 * @subpackage  PostCalendar
 * @author      original author undocumented, Craig Heydenburg
 */
class pcCategoryUtil extends CategoryUtil
{
    /**
     * Return *enhanced* HTML selector code for the given category hierarchy
     *   LivePipe reference: http://livepipe.net/control/selectmultiple
     *   LivePipe builds a standard select (not multiple) and then augments with with JS to return a comma-separated list of values.
     *   Hence, this funciton will not provide an option for 'multiple' but will provide to a functional equivelant
     *   code adapted from CategoryUtil::getSelector_Categories and example code at website referenced above
     *
     * @param cats              The category hierarchy to generate a HTML selector for
     * @param field             The field value to return (optional) (default='id')
     * @param selected          The selected category (optional) (default=0)
     * @param name              The name of the selector field to generate (optional) (default='category[parent_id]')
     * @param defaultValue      The default value to present to the user (optional) (default=0)
     * @param defaultText       The default text to present to the user (optional) (default='')
     * @param allValue          The value to assign to the "all" option (optional) (default=0)
     * @param allText           The text to assign to the "all" option (optional) (default='')
     * @param submit            whether or not to submit the form upon change (optional) (default=false)
     * @param displayPath       If false, the path is simulated, if true, the full path is shown (optional) (default=false)
     * @param doReplaceRootCat  Whether or not to replace the root category with a localized string (optional) (default=true)
     * @param multipleSize      If > 1, a multiple selector box is built, otherwise a normal/single selector box is build (optional) (default=1)
     *
     * @return The HTML selector code for the given category hierarchy
     */
    public static function getSelector_LivePipeMultCats($cats, $field = 'id', $selectedValue = '0', $name = 'category[parent_id]', $defaultValue = 0, $defaultText = '', $allValue = 0, $allText = '', $submit = false, $displayPath = false, $doReplaceRootCat = true, $fieldIsAttribute = false)
    {
        $line = '---------------------------------------------------------------------';

        if (!is_array($selectedValue)) {
            $selectedValue = array(
                (string) $selectedValue);
        }

        $id = strtr($name, '[]', '__');
        $submit = $submit ? ' onchange="this.form.submit();"' : '';
        $lang = ZLanguage::getLanguageCode();

        $html = "<select name='$name' id='$id' class='zLP_width200'{$submit}>";
        $options = array();

        if (!empty($defaultText)) {
            $sel = (in_array((string) $defaultValue, $selectedValue) ? ' selected="selected"' : '');
            $html .= "<option value=\"$defaultValue\"$sel>$defaultText</option>";
            $options[$defaultValue] = $defaultText;
        }

        if ($allText) {
            $sel = (in_array((string) $allValue, $selectedValue) ? ' selected="selected"' : '');
            $html .= "<option value=\"$allValue\"$sel>$allText</option>";
            //$options[$allValue] = $allText;
        }

        Loader::loadClass('StringUtil');
        $count = 0;
        if (!isset($cats) || empty($cats)) {
            $cats = array();
        }

        foreach ($cats as $cat) {
            if ($fieldIsAttribute) {
                $sel = (in_array((string) $cat['__ATTRIBUTES__'][$field], $selectedValue) ? ' selected="selected"' : '');
            } else {
                $sel = (in_array((string) $cat[$field], $selectedValue) ? ' selected="selected"' : '');
            }
            if ($displayPath) {
                if ($fieldIsAttribute) {
                    $v = $cat['__ATTRIBUTES__'][$field];
                    $html .= "<option value=\"$v\"$sel>$cat[path]</option>"; // is that right? path can't be a constant!
                    $options[$v] = $cat['path'];
                } else {
                    $html .= "<option value=\"$cat[$field]\"$sel>$cat[path]</option>";
                    $options[$cat[$field]] = $cat['path'];
                }
            } else {
                $cslash = StringUtil::countInstances(isset($cat['ipath_relative']) ? $cat['ipath_relative'] : $cat['ipath'], '/');
                $indent = '';
                if ($cslash > 0)
                    $indent = substr($line, 0, $cslash * 2);

                $indent = '|' . $indent;
                //if ($count) {
                //    $indent = '|' . $indent;
                //} else {
                //    $indent = '&nbsp;' . $indent;
                //}


                if (isset($cat['display_name'][$lang]) && !empty($cat['display_name'][$lang])) {
                    $catName = $cat['display_name'][$lang];
                } else {
                    $catName = $cat['name'];
                }

                if ($fieldIsAttribute) {
                    $v = $cat['__ATTRIBUTES__'][$field];
                    $html .= "<option value=\"$v\"$sel>$indent " . DataUtil::formatForDisplayHtml($catName) . "</option>";
                    $options[$v] = $indent . DataUtil::formatForDisplayHtml($catName);
                } else {
                    $html .= "<option value=\"$cat[$field]\"$sel>$indent " . DataUtil::formatForDisplayHtml($catName) . "</option>";
                    $options[$cat[$field]] = $indent . DataUtil::formatForDisplayHtml($catName);
                }
            }
            $count++;
        }

        $html .= '</select>';

        if ($doReplaceRootCat) {
            $html = str_replace('__SYSTEM__', __('Root category'), $html);
            $options = str_replace('__SYSTEM__', __('Root category'), $options);
        }

        // add 'plus' icon to display multi-selector
        $html .= "<a href='' id='{$id}_open'><img src='images/icons/extrasmall/edit_add.gif' alt='" . __("Select Multiple") . "' title='" . __("Select Multiple") . "' /></a>";

        //build multi-selector div
        $html .= "<div style='display:none;' id='{$id}_options' class='zLP_select_multiple_container'>  
            <div class='zLP_select_multiple_header'>" . __("Select Multiple Categories") . "</div>  
            <table cellspacing='0' cellpadding='0' class='zLP_select_multiple_table' width='100%'>";
        $cl = "zLP_odd";
        foreach ($options as $value => $display) {
            $html .= "<tr class='$cl'>
                <td class='zLP_select_multiple_name'>$display</td>  
                <td class='zLP_select_multiple_checkbox'><input type='checkbox' value='$value'/></td>  
                </tr> ";
            $cl = ($cl == "zLP_odd") ? "zLP_even" : "zLP_odd";
        }
        $html .= "</table>  
            <div class='zLP_select_multiple_submit'><input type='button' value='" . __("Done") . "' id='{$id}_close'/></div>  
            </div>";

        $zLP_javascript = "
            <!--//
            var {$id} = new Control.SelectMultiple('{$id}','{$id}_options',{
                checkboxSelector: 'table.zLP_select_multiple_table tr td input[type=checkbox]',
                nameSelector: 'table.zLP_select_multiple_table tr td.zLP_select_multiple_name',
                afterChange: function(){
                    if({$id} && {$id}.setSelectedRows)
                        {$id}.setSelectedRows();
                }
            });
            {$id}.setSelectedRows = function(){
                this.checkboxes.each(function(checkbox){
                    var tr = $(checkbox.parentNode.parentNode);
                    tr.removeClassName('selected');
                    if(checkbox.checked)
                        tr.addClassName('selected');
                });
            }.bind({$id});
            {$id}.checkboxes.each(function(checkbox){
                $(checkbox).observe('click',{$id}.setSelectedRows);
            });
            {$id}.setSelectedRows();
            $('{$id}_open').observe('click',function(event){
                $(this.select).style.visibility = 'hidden';
                new Effect.BlindDown(this.container,{
                    duration: 0.3
                });
                Event.stop(event);
                return false;
            }.bindAsEventListener({$id}));
            $('{$id}_close').observe('click',function(event){
                $(this.select).style.visibility = 'visible';
                new Effect.BlindUp(this.container,{
                    duration: 0.3
                });
                Event.stop(event);
                return false;
            }.bindAsEventListener({$id}));
            //-->";

        PageUtil::addVar("stylesheet", "modules/PostCalendar/pnstyle/zLP_selectmultiple.css");
        PageUtil::addVar("javascript", "javascript/ajax/prototype.js");
        PageUtil::addVar("javascript", "javascript/livepipe/livepipe.js");
        PageUtil::addVar("javascript", "javascript/livepipe/selectmultiple.js");
        PageUtil::addVar("rawtext",    "<script type='text/javascript'>$zLP_javascript</script>");

        return "<div id='zLP_select_multiple_container'>$html</div>";
    } // end getSelector_LivePipeMultCats

} // end class