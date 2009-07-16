<?php
require_once 'includes/HtmlUtil.class.php';
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

// extend basic htmlutil class to redifine the date month selector function to provide long month names
class pcHtmlUtil extends HtmlUtil
{
    /**
     * Return the HTML for the date month selector
     *
     * @param selected       The value which should be selected (default=0) (optional)
     * @param name           The name of the generated selector (default='month') (optional)
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     * @param text           Whether or not to use text values (1) or numeric values (0) for option display
     *
     * @return The generated HTML for the selector
     */
    function getSelector_DatetimeMonth ($selected=0, $name='month', $submit=false, $disabled=false, $multipleSize=1, $text=0)
    {
        if (!$name) {
            $name='month';
        }
        if ($text) $mnames=explode(" ", _MONTH_LONG); array_unshift($mnames, "noval");

        $id = strtr ($name, '[]', '__');
        $disabled     = $disabled ? 'disabled="disabled"' : '';
        $multiple     = $multipleSize > 1 ? 'multiple="multiple"' : '';
        $multipleSize = $multipleSize > 1 ? "size=\"$multipleSize\"" : '';
        $submit       = $submit ? 'onchange="this.form.submit();"' : '';

        $html = "<select name=\"$name\" id=\"$id\" $multipleSize $multiple $submit $disabled>";

        for ($i=1; $i<13; $i++) {
            $val = sprintf ("%02d", $i);
            $opt = $text ? $mnames[$i]:$val;
            $sel = ($i==$selected ? 'selected="selected"' : '');
            $html = $html . "<option value=\"$val\" $sel>$opt</option>";
        }

        $html = $html . '</select>';

        return $html;
    }
}
